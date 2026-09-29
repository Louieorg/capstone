<?php

use App\Models\Feedback;
use App\Models\FeedbackVote;
use App\Models\IdeaEvaluation;
use App\Models\User;
use App\Services\ClusteringService;
use App\Services\IdeaGeneratorService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

function createAiWordingFeedback(array $attributes = []): Feedback
{
    return Feedback::query()->create(array_merge([
        'title' => 'Students cannot track their requests',
        'description' => 'Students repeatedly follow up in person because status updates are not published.',
        'impact' => 'Students spend extra time queueing just to ask for updates.',
        'category' => 'AI Wording Category',
        'frequency' => 'Often',
        'current_process' => 'Manual or paper-based process',
        'affected_users' => '50-200',
        'affected_group' => ['Students'],
        'is_anonymous' => false,
        'status' => 'approved',
    ], $attributes));
}

function addVotesForAiWording(Feedback $feedback, int $votes): void
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

test('the ai wording section is collapsible likha styling and leaves the dss result untouched', function (): void {
    Cache::flush();

    $this->app->instance(ClusteringService::class, new class
    {
        public function group($feedbacks): Collection
        {
            return collect(['request tracking gaps' => $feedbacks]);
        }

        public function label(string $clusterKey): string
        {
            return 'Request Tracking Gaps';
        }

        public function explanation(string $clusterKey): string
        {
            return 'Reports share the same request tracking problem.';
        }
    });

    $this->app->instance(IdeaGeneratorService::class, new class
    {
        public function generate($groupName, $category, $groupFeedbacks, $reports, $votes, $frequencyScore, $impactScore): array
        {
            return [
                'title' => 'DSS Request Tracking Idea',
                'description' => 'DSS generated description for request tracking.',
                'general_objective' => 'To improve request tracking across offices.',
                'specific_objectives' => ['To record every request.'],
                'explanation' => [
                    'summary' => 'Reports were grouped and scored by the DSS.',
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
                'project_name' => 'TrackHub',
                'concept' => [
                    'primary' => 'Request tracking',
                    'primary_key' => 'request_tracking',
                    'score' => 4.0,
                    'evidence' => [],
                ],
                'cluster_key' => 'request_tracking_gaps',
            ];
        }
    });

    collect(range(1, 3))->each(function (int $index): void {
        $feedback = createAiWordingFeedback(['title' => "Request tracking issue {$index}"]);
        addVotesForAiWording($feedback, 10);
    });

    IdeaEvaluation::query()->create([
        'idea_title' => 'DSS Request Tracking Idea',
        'category' => 'AI Wording Category',
        'overall_score' => 4.1,
        'recommendation' => 'Recommended',
        'ai_title' => 'AI-Enhanced Request Tracking System',
        'ai_description' => 'AI-enhanced wording for the request tracking idea.',
        'ai_general_objective' => 'To strengthen request tracking across offices.',
        'ai_specific_objectives' => ['To record and monitor every request.'],
        'ai_enhanced_at' => now(),
    ]);

    $response = $this->get(route('feedback.category', ['category' => 'AI Wording Category']))->assertOk();

    $response
        ->assertSeeText('DSS Request Tracking Idea')
        ->assertSeeText('DSS generated description for request tracking.')
        ->assertSeeText('Overall evaluation')
        ->assertSeeText('3.35')
        ->assertSeeText('Optional wording enhancement — the DSS recommendation and scores above remain unchanged.')
        ->assertSeeText('AI-Enhanced Request Tracking System')
        ->assertSee('AI-ENHANCED')
        ->assertSee('class="co-ai"', false)
        ->assertSee('class="co-ai-toggle"', false)
        ->assertSee('x-show="aiOpen"', false)
        ->assertSee('aiOpen:false', false)
        ->assertDontSee('aiOpen:true', false)
        ->assertSee("x-text=\"aiOpen ? 'Hide' : 'Show'\"", false)
        ->assertSee('aria-controls="ai-wording"', false)
        ->assertSeeText('To strengthen request tracking across offices.')
        ->assertSeeText('To record and monitor every request.');
});
