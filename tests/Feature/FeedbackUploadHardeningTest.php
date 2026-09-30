<?php

use App\Models\Feedback;
use App\Models\FeedbackEvidence;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function uploadHardeningPngBytes(): string
{
    return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
}

function uploadHardeningPdfBytes(): string
{
    return "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n";
}

function uploadHardeningPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Upload hardening test submission',
        'category' => 'Facilities',
        'description' => 'Students need a reliable way to document recurring campus service problems.',
        'impact' => 'This delays student work and creates repeated follow ups for campus staff.',
        'frequency' => 'Often',
        'current_process' => 'Report verbally to staff',
        'affected_users' => '50-200',
        'affected_group' => ['Students'],
        'force_submit' => '1',
    ], $overrides);
}

function uploadHardeningUser(): User
{
    return User::factory()->create();
}

/**
 * Resolve the feedback row a single upload-hardening test created, so evidence
 * assertions stay scoped to that submission instead of the whole table.
 */
function uploadHardeningFeedback(string $title): Feedback
{
    return Feedback::query()->where('title', $title)->latest('id')->firstOrFail();
}

/**
 * @return \Illuminate\Database\Eloquent\Collection<int, FeedbackEvidence>
 */
function uploadHardeningEvidenceFor(Feedback $feedback): \Illuminate\Database\Eloquent\Collection
{
    return FeedbackEvidence::query()
        ->where('feedback_id', $feedback->id)
        ->get();
}

test('evidence uses the detected PDF type instead of a jpg client extension', function (): void {
    Storage::fake('public');

    $this->actingAs(uploadHardeningUser())->post(route('feedback.store'), uploadHardeningPayload([
        'title' => 'PDF evidence with image extension',
        'evidence' => [UploadedFile::fake()->createWithContent('photo.jpg', uploadHardeningPdfBytes())->mimeType('application/pdf')],
    ]))->assertRedirect(route('feedback.submitted', absolute: false));

    $feedback = uploadHardeningFeedback('PDF evidence with image extension');
    $evidence = uploadHardeningEvidenceFor($feedback)->firstOrFail();

    expect($evidence->file_path)->toEndWith('.pdf')
        ->and($evidence->file_type)->toBe('pdf');
});

test('evidence uses the detected PNG type instead of a pdf client extension', function (): void {
    Storage::fake('public');

    $this->actingAs(uploadHardeningUser())->post(route('feedback.store'), uploadHardeningPayload([
        'title' => 'PNG evidence with document extension',
        'evidence' => [UploadedFile::fake()->createWithContent('proof.pdf', uploadHardeningPngBytes())->mimeType('image/png')],
    ]))->assertRedirect(route('feedback.submitted', absolute: false));

    $feedback = uploadHardeningFeedback('PNG evidence with document extension');
    $evidence = uploadHardeningEvidenceFor($feedback)->firstOrFail();

    expect($evidence->file_path)->toEndWith('.png')
        ->and($evidence->file_type)->toBe('image');
});

test('evidence with an executable client extension is rejected before storage', function (): void {
    Storage::fake('public');

    $response = $this->actingAs(uploadHardeningUser())
        ->from(route('feedback.create'))
        ->post(route('feedback.store'), uploadHardeningPayload([
            'title' => 'PNG evidence with executable extension',
            'evidence' => [UploadedFile::fake()->createWithContent('payload.php', uploadHardeningPngBytes())->mimeType('image/png')],
        ]));

    $paths = FeedbackEvidence::query()->pluck('file_path');

    $response->assertSessionHasErrors('evidence.0');

    expect($paths)->toBeEmpty()
        ->and(Storage::disk('public')->allFiles())->toBeEmpty();
});

test('legacy attachments use detected content types instead of client extensions', function (): void {
    Storage::fake('public');
    $user = uploadHardeningUser();

    $this->actingAs($user)->post(route('feedback.store'), uploadHardeningPayload([
        'title' => 'PNG legacy attachment with document extension',
        'attachment' => UploadedFile::fake()->createWithContent('note.pdf', uploadHardeningPngBytes())->mimeType('image/png'),
    ]))->assertRedirect(route('feedback.submitted', absolute: false));

    $pngAttachment = Feedback::query()->where('title', 'PNG legacy attachment with document extension')->firstOrFail();

    $this->actingAs($user)->post(route('feedback.store'), uploadHardeningPayload([
        'title' => 'PDF legacy attachment with image extension',
        'attachment' => UploadedFile::fake()->createWithContent('note.png', uploadHardeningPdfBytes())->mimeType('application/pdf'),
    ]))->assertRedirect(route('feedback.submitted', absolute: false));

    $pdfAttachment = Feedback::query()->where('title', 'PDF legacy attachment with image extension')->firstOrFail();

    expect($pngAttachment->attachment_path)->toEndWith('.png')
        ->and($pngAttachment->attachment_type)->toBe('image')
        ->and($pdfAttachment->attachment_path)->toEndWith('.pdf')
        ->and($pdfAttachment->attachment_type)->toBe('pdf');
});

test('six evidence files are rejected before feedback or storage is created', function (): void {
    Storage::fake('public');

    $files = collect(range(1, 6))
        ->map(fn (int $index): UploadedFile => UploadedFile::fake()->createWithContent("evidence-{$index}.png", uploadHardeningPngBytes()))
        ->all();

    $this->actingAs(uploadHardeningUser())
        ->from(route('feedback.create'))
        ->post(route('feedback.store'), uploadHardeningPayload(['evidence' => $files]))
        ->assertSessionHasErrors('evidence');

    expect(Feedback::query()->count())->toBe(0)
        ->and(Storage::disk('public')->allFiles())->toBeEmpty();
});

test('five evidence files are accepted', function (): void {
    Storage::fake('public');

    $files = collect(range(1, 5))
        ->map(fn (int $index): UploadedFile => UploadedFile::fake()->createWithContent("evidence-{$index}.png", uploadHardeningPngBytes()))
        ->all();

    $this->actingAs(uploadHardeningUser())
        ->post(route('feedback.store'), uploadHardeningPayload([
            'title' => 'Five evidence files submission',
            'evidence' => $files,
        ]))
        ->assertRedirect(route('feedback.submitted', absolute: false));

    $feedback = uploadHardeningFeedback('Five evidence files submission');

    expect(uploadHardeningEvidenceFor($feedback))->toHaveCount(5)
        ->and(Storage::disk('public')->allFiles())->toHaveCount(5);
});

test('evidence preserves the original filename without using it as the stored filename', function (): void {
    Storage::fake('public');

    $this->actingAs(uploadHardeningUser())->post(route('feedback.store'), uploadHardeningPayload([
        'title' => 'Evidence filename preservation submission',
        'evidence' => [UploadedFile::fake()->createWithContent('photo.jpg', uploadHardeningPdfBytes())->mimeType('application/pdf')],
    ]))->assertRedirect(route('feedback.submitted', absolute: false));

    $feedback = uploadHardeningFeedback('Evidence filename preservation submission');
    $evidence = uploadHardeningEvidenceFor($feedback)->firstOrFail();
    $storedFilename = basename($evidence->file_path);

    expect($evidence->file_name)->toBe('photo.jpg')
        ->and($storedFilename)->not->toContain('/')
        ->and($storedFilename)->not->toContain('\\')
        ->and($storedFilename)->not->toContain('.jpg')
        ->and($storedFilename)->toEndWith('.pdf');
});
