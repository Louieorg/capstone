<?php

use App\Models\Feedback;
use App\Models\FeedbackVote;
use App\Models\Setting;
use App\Models\User;
use App\Services\ClusteringService;
use App\Services\IdeaGeneratorService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

function createApprovedFeedbackForIdeaThreshold(array $attributes = []): Feedback
{
    return Feedback::query()->create(array_merge([
        'title' => 'Validated campus issue',
        'description' => 'Students experience recurring delays that need a coordinated solution.',
        'impact' => 'This affects student transactions and creates repeated follow ups.',
        'category' => 'Threshold Category',
        'frequency' => 'Often',
        'current_process' => 'Manual or paper-based process',
        'affected_users' => '50-200',
        'affected_group' => ['Students'],
        'is_anonymous' => false,
        'status' => 'approved',
    ], $attributes));
}

function addVotesForIdeaThreshold(Feedback $feedback, int $votes): void
{
    User::factory()
        ->count($votes)
        ->create()
        ->each(function (User $user) use ($feedback): void {
            FeedbackVote::query()->create([
                'feedback_id' => $feedback->id,
                'user_id' => $user->id,
            ]);
        });
}

test('category idea generation only processes problems with at least ten votes', function (): void {
    Cache::flush();

    $clusteringSpy = new class
    {
        public ?Collection $processedFeedbacks = null;

        public function group($feedbacks): Collection
        {
            $this->processedFeedbacks = $feedbacks;

            return collect(['validated campus issues' => $feedbacks]);
        }
    };

    $this->app->instance(ClusteringService::class, $clusteringSpy);
    $this->app->instance(IdeaGeneratorService::class, new class
    {
        public function generate($groupName, $category, $groupFeedbacks, $reports, $votes, $frequencyScore, $impactScore): array
        {
            return [
                'title' => 'Validated Vote Threshold Idea',
                'description' => 'Generated only from problems that have enough community support.',
                'general_objective' => 'To improve validated campus services.',
                'specific_objectives' => ['To process only validated problem reports.'],
                'explanation' => [
                    'summary' => 'Only sufficiently voted problems were processed.',
                    'factors' => [
                        'reports' => $reports,
                        'votes' => $votes,
                        'frequency_score' => $frequencyScore,
                        'impact_score' => $impactScore,
                        'top_affected_group' => 'Students',
                    ],
                    'reasoning' => [],
                ],
                'top_group' => 'Students',
                'impact_simulation' => ['message' => 'Expected impact for validated issues.'],
            ];
        }
    });

    $qualifiedFeedbacks = collect(range(1, 3))->map(function (int $index): Feedback {
        $feedback = createApprovedFeedbackForIdeaThreshold([
            'title' => "Qualified issue {$index}",
        ]);

        addVotesForIdeaThreshold($feedback, 10);

        return $feedback;
    });

    $underThresholdFeedback = createApprovedFeedbackForIdeaThreshold([
        'title' => 'Under threshold issue',
    ]);

    addVotesForIdeaThreshold($underThresholdFeedback, 9);

    $this->get(route('feedback.category', ['category' => 'Threshold Category']))
        ->assertOk()
        ->assertSeeText('Validated Vote Threshold Idea')
        ->assertSeeInOrder([
            'Top Recommended Idea',
            'Feasibility',
            'Impact',
            'Complexity',
            'Innovation',
            'Validated Vote Threshold Idea',
        ]);

    expect($clusteringSpy->processedFeedbacks)->not->toBeNull()
        ->and($clusteringSpy->processedFeedbacks)->toHaveCount(3)
        ->and($clusteringSpy->processedFeedbacks->pluck('id')->all())
        ->toEqualCanonicalizing($qualifiedFeedbacks->pluck('id')->all())
        ->and($clusteringSpy->processedFeedbacks->every(
            fn (Feedback $feedback): bool => $feedback->votes_count >= 10
        ))->toBeTrue()
        ->and($clusteringSpy->processedFeedbacks->pluck('id')->contains($underThresholdFeedback->id))
        ->toBeFalse();
});

test('home candidate count only includes categories with three reports that each have at least ten votes', function (): void {
    collect(range(1, 3))->each(function (int $index): void {
        $feedback = createApprovedFeedbackForIdeaThreshold([
            'category' => 'Under Voted Category',
            'title' => "Under voted issue {$index}",
        ]);

        addVotesForIdeaThreshold($feedback, 9);
    });

    collect(range(1, 3))->each(function (int $index): void {
        $feedback = createApprovedFeedbackForIdeaThreshold([
            'category' => 'Qualified Category',
            'title' => "Qualified candidate issue {$index}",
        ]);

        addVotesForIdeaThreshold($feedback, 10);
    });

    $this->get(route('home'))
        ->assertOk()
        ->assertSeeInOrder(['Capstone Candidates', '1', 'AI-scored & under review']);
});

test('category idea generation uses the thresholds configured by an administrator', function (): void {
    Cache::flush();
    Setting::set('minimum_votes_for_idea_generation', 1);
    Setting::set('minimum_reports_for_idea_generation', 1);

    $feedback = createApprovedFeedbackForIdeaThreshold();
    addVotesForIdeaThreshold($feedback, 1);

    $this->get(route('feedback.category', ['category' => 'Threshold Category']))
        ->assertOk()
        ->assertDontSeeText('Not Enough Data Yet');
});
