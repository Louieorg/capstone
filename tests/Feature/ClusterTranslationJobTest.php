<?php

use App\Jobs\TranslateClusterExplanationToFilipino;
use App\Models\ClusterExplanation;
use App\Services\ClusterTranslationService;
use App\Services\OllamaService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

const AI5JOB_CATEGORY = 'Filipino Job Category';

/**
 * @return array<string, mixed>
 */
function ai5JobEnglish(): array
{
    return [
        'summary' => 'Students follow up in person because they cannot see whether a request was received or resolved.',
        'patterns' => [
            'Status updates are shared verbally instead of being published.',
            'The same concern is raised again because nothing is tracked.',
        ],
        'experiences' => [
            [
                'title' => 'Queuing for an answer',
                'body' => 'Students wait during office hours to ask staff whether their request was received or already resolved.',
            ],
        ],
    ];
}

/**
 * @return array<string, mixed>
 */
function ai5JobFilipino(): array
{
    return [
        'summary' => 'Paulit-ulit na personal na nagfo-follow up ang mga estudyante dahil hindi nila nakikita kung natanggap o naresolba na ang kanilang kahilingan.',
        'patterns' => [
            'Ang mga update sa status ay sinasabi nang pasalita sa halip na ilathala para sa lahat.',
            'Ang parehong alalahanin ay muling ibinabangon dahil walang naitatalang talaan.',
        ],
        'experiences' => [
            [
                'title' => 'Pumipila para sa kasagutan',
                'body' => 'Naghihintay ang mga estudyante sa oras ng klase upang tanungin ang mga kawani kung natanggap o naresolba na ang kanilang kahilingan.',
            ],
        ],
    ];
}

function ai5JobHash(ClusterExplanation $english): string
{
    $translation = app(ClusterTranslationService::class);
    $source = $translation->canonicalSource($english);
    $profile = app(OllamaService::class)->translationProfile();

    return $translation->translationKey($source, $profile['prompt_version'], $profile['model'], $profile['language']);
}

function ai5JobEnglishRow(array $overrides = []): ClusterExplanation
{
    return ClusterExplanation::query()->create(array_merge([
        'evidence_hash' => str_repeat('e', 64),
        'category' => AI5JOB_CATEGORY,
        'cluster_label' => 'Request Follow-Up',
        'language' => 'en',
        'prompt_version' => '1',
        'model' => (string) config('services.ollama.synthesis.model'),
        'summary' => ai5JobEnglish()['summary'],
        'patterns' => ai5JobEnglish()['patterns'],
        'experiences' => ai5JobEnglish()['experiences'],
        'status' => 'complete',
        'attempts' => 1,
        'generated_at' => now(),
    ], $overrides));
}

test('the job is unique and addressed by the translation hash', function (): void {
    $job = new TranslateClusterExplanationToFilipino(AI5JOB_CATEGORY, 7, str_repeat('f', 64));
    $job->onQueue((string) config('services.ollama.translation.queue'));

    expect($job)->toBeInstanceOf(ShouldBeUnique::class)
        ->and($job->uniqueId())->toBe(str_repeat('f', 64))
        ->and($job->uniqueFor)->toBe(300)
        ->and($job->tries)->toBe(2)
        ->and($job->timeout)->toBe(180)
        ->and($job->queue)->toBe('ai-filipino');
});

test('a successful translation stores a filipino row and leaves english untouched', function (): void {
    $english = ai5JobEnglishRow();
    $before = $english->fresh()->toArray();

    Http::fake(['*' => Http::response(['response' => json_encode(ai5JobFilipino())], 200)]);

    (new TranslateClusterExplanationToFilipino(AI5JOB_CATEGORY, $english->id, ai5JobHash($english)))->handle(
        app(ClusterTranslationService::class),
        app(OllamaService::class)
    );

    $hash = ai5JobHash($english->fresh());
    $filipino = ClusterExplanation::query()->where('evidence_hash', $hash)->first();

    expect($filipino)->not->toBeNull()
        ->and($filipino->language)->toBe('fil')
        ->and($filipino->status)->toBe('complete')
        ->and($filipino->category)->toBe(AI5JOB_CATEGORY)
        ->and($filipino->cluster_label)->toBe('Request Follow-Up')
        ->and($filipino->summary)->toBe(ai5JobFilipino()['summary'])
        ->and($filipino->prompt_version)->toBe('2')
        ->and((int) $filipino->attempts)->toBe(1)
        ->and($filipino->generated_at)->not->toBeNull()
        ->and($english->fresh()->toArray())->toBe($before)
        ->and(ClusterExplanation::query()->count())->toBe(2);

    Http::assertSentCount(1);
});

test('the job translates the stored english row, not raw reports', function (): void {
    $english = ai5JobEnglishRow();

    Http::fake(['*' => Http::response(['response' => json_encode(ai5JobFilipino())], 200)]);

    (new TranslateClusterExplanationToFilipino(AI5JOB_CATEGORY, $english->id, ai5JobHash($english)))->handle(
        app(ClusterTranslationService::class),
        app(OllamaService::class)
    );

    $sent = Http::recorded()[0][0] ?? null;

    expect($sent)->not->toBeNull();

    $payload = $sent->data();

    // The prompt carries the stored English synthesis, fenced as translation
    // source. No raw report text leaves the application boundary.
    expect($payload['prompt'])->toContain('<english>')
        ->and($payload['prompt'])->toContain('Students follow up in person')
        ->and($payload['prompt'])->not->toContain('<evidence>')
        ->and($payload['prompt'])->not->toContain('You explain');
});

test('a stale english source is never persisted', function (): void {
    $english = ai5JobEnglishRow();

    Http::fake(['*' => Http::response(['response' => json_encode(ai5JobFilipino())], 200)]);

    $staleHash = str_repeat('0', 64);

    (new TranslateClusterExplanationToFilipino(AI5JOB_CATEGORY, $english->id, $staleHash))->handle(
        app(ClusterTranslationService::class),
        app(OllamaService::class)
    );

    expect(ClusterExplanation::query()->count())->toBe(1)
        ->and(ClusterExplanation::query()->where('evidence_hash', $staleHash)->count())->toBe(0);

    Http::assertNothingSent();
});

test('a missing or rejected english source stores nothing and calls nothing', function (): void {
    Http::fake(['*' => Http::response(['response' => json_encode(ai5JobFilipino())], 200)]);

    (new TranslateClusterExplanationToFilipino(AI5JOB_CATEGORY, 999999, str_repeat('1', 64)))->handle(
        app(ClusterTranslationService::class),
        app(OllamaService::class)
    );

    $rejected = ai5JobEnglishRow(['evidence_hash' => str_repeat('d', 64), 'summary' => null, 'status' => 'rejected']);
    $hash = app(ClusterTranslationService::class)->translationKey(
        ['summary' => 'x', 'patterns' => [], 'experiences' => []],
        '1',
        (string) config('services.ollama.translation.model'),
        'fil'
    );

    (new TranslateClusterExplanationToFilipino(AI5JOB_CATEGORY, $rejected->id, $hash))->handle(
        app(ClusterTranslationService::class),
        app(OllamaService::class)
    );

    expect(ClusterExplanation::query()->count())->toBe(1);

    Http::assertNothingSent();
});

test('an already complete translation is never generated again', function (): void {
    $english = ai5JobEnglishRow();

    Http::fake(['*' => Http::response(['response' => json_encode(ai5JobFilipino())], 200)]);

    $job = new TranslateClusterExplanationToFilipino(AI5JOB_CATEGORY, $english->id, ai5JobHash($english));
    $job->handle(app(ClusterTranslationService::class), app(OllamaService::class));
    $job->handle(app(ClusterTranslationService::class), app(OllamaService::class));

    expect(ClusterExplanation::query()->count())->toBe(2)
        ->and((int) ClusterExplanation::query()->where('language', 'fil')->value('attempts'))->toBe(1);

    Http::assertSentCount(1);
});

test('a failed translation stores no completed row but keeps english', function (): void {
    $english = ai5JobEnglishRow();

    Http::fake(['*' => Http::response(['response' => json_encode(['summary' => 'too short'])], 200)]);

    (new TranslateClusterExplanationToFilipino(AI5JOB_CATEGORY, $english->id, ai5JobHash($english)))->handle(
        app(ClusterTranslationService::class),
        app(OllamaService::class)
    );

    $filipino = ClusterExplanation::query()->where('language', 'fil')->first();

    expect($filipino)->not->toBeNull()
        ->and($filipino->status)->toBe('rejected')
        ->and($filipino->summary)->toBeNull()
        ->and($english->fresh()->status)->toBe('complete');
});

test('the model is configured through translation keys, not synthesis keys', function (): void {
    Config::set('services.ollama.translation.model', 'translation-model:latest');

    $english = ai5JobEnglishRow();

    Http::fake(['*' => Http::response(['response' => json_encode(ai5JobFilipino())], 200)]);

    (new TranslateClusterExplanationToFilipino(AI5JOB_CATEGORY, $english->id, ai5JobHash($english)))->handle(
        app(ClusterTranslationService::class),
        app(OllamaService::class)
    );

    $sent = Http::recorded()[0][0] ?? null;

    expect($sent->data()['model'])->toBe('translation-model:latest')
        ->and(ClusterExplanation::query()->where('language', 'fil')->value('model'))->toBe('translation-model:latest');
});
