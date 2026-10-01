<?php

use App\Models\CategoryAssignment;
use App\Models\Feedback;
use App\Models\FeedbackComment;
use App\Models\FeedbackVote;
use App\Models\Setting;
use App\Models\User;
use App\Services\CategoryIdeaGenerationService;
use App\Services\ClusterEvidenceService;
use App\Services\ClusteringService;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

function seedDemo(): void
{
    (new DemoDataSeeder)->run();
}

/**
 * @return array<string, int> Cluster key => report count.
 */
function clusterKeysFor(ClusteringService $clustering, string $category): array
{
    $feedbacks = Feedback::query()
        ->where('category', $category)
        ->where('status', 'approved')
        ->notFlagged()
        ->get();

    return $clustering->group($feedbacks)
        ->map(fn ($group): int => $group->count())
        ->all();
}

function clusterSizeFor(array $clusters, string $key): int
{
    return $clusters[$key] ?? 0;
}

it('creates the planned demo users, reports, votes and comments', function (): void {
    Cache::flush();
    seedDemo();

    expect(User::query()->where('email', 'like', '%@likha-demo.test')->count())->toBe(12)
        ->and(User::query()->where('email', 'like', '%@likha-demo.test')
            ->whereNotNull('email_verified_at')->count())->toBe(12)
        ->and(User::query()->where('email', 'like', '%@likha-demo.test')->where('role', 'user')->count())->toBe(12)
        ->and(User::query()->where('name', 'like', 'Demo Student %')->count())->toBe(12);

    // 4 (A) + 3 (B) + 3 (C) + 3 (D) + 3 (E) + 2 (F) + 4 (G)
    expect(Feedback::query()->count())->toBe(22)
        ->and(Feedback::query()->where('status', 'approved')->count())->toBe(19)
        ->and(Feedback::query()->where('status', 'pending')->count())->toBe(2)
        ->and(Feedback::query()->where('status', 'rejected')->count())->toBe(1)
        ->and(Feedback::query()->where('is_flagged', true)->count())->toBe(1)
        ->and(Feedback::query()->where('is_anonymous', true)->count())->toBe(0)
        ->and(Feedback::query()->whereNotNull('user_id')->count())->toBe(22)
        ->and(Feedback::query()->where('is_capstone_worthy', true)->count())->toBe(0);

    // 18 qualifying reports x 10 votes; the four non-qualifying ones cast none.
    expect(FeedbackVote::query()->count())->toBe(180)
        ->and(FeedbackComment::query()->count())->toBe(8);

    $selfVotes = FeedbackVote::query()
        ->join('feedback', 'feedback.id', '=', 'feedback_votes.feedback_id')
        ->whereColumn('feedback_votes.user_id', 'feedback.user_id')
        ->count();

    expect($selfVotes)->toBe(0);

    // Every approved, unflagged report clears the vote threshold on its own.
    $qualifying = Feedback::query()
        ->where('status', 'approved')
        ->where('is_flagged', false)
        ->withCount('votes')
        ->get();

    expect($qualifying)->toHaveCount(18);

    foreach ($qualifying as $report) {
        expect($report->votes_count)->toBeGreaterThanOrEqual(10);
    }
});

it('is idempotent: running twice creates nothing new', function (): void {
    Cache::flush();
    seedDemo();

    $users = User::query()->count();
    $feedback = Feedback::query()->count();
    $votes = FeedbackVote::query()->count();
    $comments = FeedbackComment::query()->count();

    seedDemo();

    expect(User::query()->count())->toBe($users)
        ->and(Feedback::query()->count())->toBe($feedback)
        ->and(FeedbackVote::query()->count())->toBe($votes)
        ->and(FeedbackComment::query()->count())->toBe($comments);
});

it('produces qualifying dss results for the intended categories only', function (): void {
    Cache::flush();
    seedDemo();

    $service = app(CategoryIdeaGenerationService::class);

    $facilities = $service->generate('Facilities');
    $scheduling = $service->generate('Scheduling');
    $enrollment = $service->generate('Enrollment');
    $library = $service->generate('Library');
    $academic = $service->generate('Academic Process');

    expect($facilities['qualifying'])->toBeTrue()
        ->and($facilities['ideas'])->toHaveCount(2)
        ->and($scheduling['qualifying'])->toBeTrue()
        ->and($scheduling['ideas'])->toHaveCount(1)
        ->and($enrollment['qualifying'])->toBeTrue()
        ->and($enrollment['ideas'])->toHaveCount(1)
        ->and($library['qualifying'])->toBeTrue()
        ->and($library['ideas'])->toHaveCount(1)
        ->and($academic['qualifying'])->toBeFalse()
        ->and($academic['ideas'])->toBe([]);
});

it('groups each cluster under one key with the real clustering service', function (): void {
    Cache::flush();
    seedDemo();

    $clustering = app(ClusteringService::class);

    $facilities = clusterKeysFor($clustering, 'Facilities');
    $scheduling = clusterKeysFor($clustering, 'Scheduling');
    $enrollment = clusterKeysFor($clustering, 'Enrollment');
    $library = clusterKeysFor($clustering, 'Library');

    expect($facilities)->toHaveCount(2)
        ->and($facilities)->toHaveKey('laboratory_equipment_monitoring')
        ->and($facilities)->toHaveKey('facility_inspection_records')
        ->and(clusterSizeFor($facilities, 'laboratory_equipment_monitoring'))->toBe(4)
        ->and(clusterSizeFor($facilities, 'facility_inspection_records'))->toBe(3)
        ->and($scheduling)->toHaveCount(1)
        ->and(clusterSizeFor($scheduling, 'scheduling_coordination'))->toBe(3)
        ->and($enrollment)->toHaveCount(1)
        ->and(clusterSizeFor($enrollment, 'request_tracking'))->toBe(3)
        ->and($library)->toHaveCount(1)
        ->and(clusterSizeFor($library, 'records_management'))->toBe(3);
});

it('keeps pending, rejected and flagged reports out of the dss inputs', function (): void {
    Cache::flush();
    seedDemo();

    $generated = app(CategoryIdeaGenerationService::class)->generate('Facilities');

    $ids = collect($generated['feedbacks'])->pluck('id')->all();

    $excluded = Feedback::query()
        ->where('status', '!=', 'approved')
        ->orWhere('is_flagged', true)
        ->pluck('id')
        ->all();

    expect($excluded)->not->toBeEmpty();

    foreach ($excluded as $id) {
        expect($ids)->not->toContain($id);
    }

    foreach (collect($generated['feedbacks']) as $feedback) {
        expect($feedback->status)->toBe('approved')
            ->and((bool) $feedback->is_flagged)->toBeFalse();
    }
});

it('changes no setting, assignment, non-demo user or non-demo report', function (): void {
    Cache::flush();

    $existingSetting = Setting::query()->create([
        'key' => 'minimum_votes_for_idea_generation',
        'value' => '7',
    ]);

    $existingAssignment = CategoryAssignment::query()->create([
        'category' => 'Existing Category',
        'office' => 'office_academic',
    ]);

    $existingUser = User::query()->create([
        'name' => 'Existing Person',
        'email' => 'existing.person@example.com',
        'password' => 'secret-hash',
        'role' => 'adviser',
    ]);

    $existingFeedback = Feedback::query()->create([
        'user_id' => $existingUser->id,
        'title' => 'A pre-existing approved report',
        'description' => 'This report existed before the demo data was seeded and must not change.',
        'impact' => 'It must keep its original values after seeding.',
        'category' => 'Facilities',
        'frequency' => 'Often',
        'current_process' => 'Manual or paper-based process',
        'affected_users' => '50-200',
        'affected_group' => ['Students'],
        'status' => 'approved',
    ]);

    seedDemo();

    expect(Setting::query()->count())->toBe(1)
        ->and($existingSetting->fresh()->value)->toBe('7')
        ->and(CategoryAssignment::query()->count())->toBe(1)
        ->and($existingAssignment->fresh()->category)->toBe('Existing Category')
        ->and(User::query()->count())->toBe(13)
        ->and($existingUser->fresh()->name)->toBe('Existing Person')
        ->and($existingUser->fresh()->role)->toBe('adviser')
        ->and(Feedback::query()->count())->toBe(23)
        ->and($existingFeedback->fresh()->title)->toBe('A pre-existing approved report')
        ->and($existingFeedback->fresh()->description)->toBe(
            'This report existed before the demo data was seeded and must not change.'
        );
});

it('aborts and writes nothing outside local and testing', function (): void {
    Cache::flush();
    app()->detectEnvironment(fn () => 'production');

    expect(fn () => seedDemo())->toThrow(RuntimeException::class);

    expect(User::query()->count())->toBe(0)
        ->and(Feedback::query()->count())->toBe(0)
        ->and(FeedbackVote::query()->count())->toBe(0)
        ->and(FeedbackComment::query()->count())->toBe(0);
});

it('builds a valid evidence package for every generated idea', function (): void {
    Cache::flush();
    seedDemo();

    $service = app(CategoryIdeaGenerationService::class);
    $evidence = app(ClusterEvidenceService::class);

    $laboratory = collect($service->generate('Facilities')['ideas'])
        ->firstWhere('cluster_label', 'Laboratory Equipment Monitoring');

    expect($laboratory)->not->toBeNull();

    $package = $evidence->buildPackage($laboratory, 'Facilities');

    expect($package)->not->toBeNull()
        ->and($package['report_count'])->toBe(4)
        ->and($package['evidence_total'])->toBe(4)
        ->and($evidence->isSynthesizable($package))->toBeTrue();

    foreach (['Facilities', 'Scheduling', 'Enrollment', 'Library'] as $category) {
        foreach ($service->generate($category)['ideas'] as $idea) {
            $package = $evidence->buildPackage($idea, $category);

            expect($package)->not->toBeNull()
                ->and($package['report_count'])->toBeGreaterThanOrEqual(3)
                ->and($evidence->isSynthesizable($package))->toBeTrue();
        }
    }
});

it('dispatches no queue job', function (): void {
    Cache::flush();
    Queue::fake();

    seedDemo();

    Queue::assertNothingPushed();
});
