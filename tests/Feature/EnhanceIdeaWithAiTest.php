<?php

use App\Jobs\EnhanceIdeaWithAi;
use App\Models\IdeaEvaluation;
use App\Models\User;
use App\Services\CategoryIdeaGenerationService;
use App\Services\OllamaService;

function configureEnhanceFlagCategoryPage(): void
{
    app()->instance(CategoryIdeaGenerationService::class, new class
    {
        public function generate(string $category): array
        {
            return [
                'qualifying' => true,
                'feedbacks' => collect(),
                'ideas' => [[
                    'title' => 'Flag Test DSS Idea',
                    'description' => 'The DSS generated description.',
                    'general_objective' => 'To improve the documented campus process.',
                    'specific_objectives' => ['To improve the documented process.'],
                    'project_name' => 'FlagTest',
                    'concept' => ['primary' => 'Process improvement system'],
                    'explanation' => [
                        'factors' => [
                            'top_affected_group' => 'Students',
                        ],
                    ],
                    'evaluation' => [
                        'overall_score' => 4.0,
                        'recommendation' => 'Recommended',
                        'feasibility' => 4,
                        'impact' => 4,
                        'complexity' => 4,
                        'innovation' => 4,
                    ],
                ]],
                'thresholds' => ['reports' => 1, 'votes' => 1],
            ];
        }
    });
}

function enhanceFlagPayload(): array
{
    return [
        'title' => 'Campus Request Tracker',
        'description' => 'A system for tracking campus requests.',
        'general_objective' => 'Improve request visibility.',
        'specific_objectives' => ['Record requests.', 'Track request progress.'],
    ];
}

test('authenticated users can enhance the original DSS recommendation without changing its evaluation', function (): void {
    config(['services.ollama.enhance_enabled' => true]);

    $user = \App\Models\User::factory()->create();
    $evaluation = IdeaEvaluation::query()->create([
        'idea_title' => 'Campus Request Tracker',
        'category' => 'Academic Process',
        'overall_score' => 4.25,
        'recommendation' => 'Highly Recommended',
    ]);
    $original = enhanceFlagPayload();

    $this->mock(OllamaService::class, function ($mock) use ($original): void {
        $mock->shouldReceive('enhance')
            ->once()
            ->with($original)
            ->andReturn([
                'title' => 'Campus Request Management System',
                'description' => 'A centralized system for managing campus requests.',
                'general_objective' => 'Improve the visibility and resolution of campus requests.',
                'specific_objectives' => ['Record requests.', 'Monitor request progress.'],
            ]);
    });

    $this->actingAs($user)
        ->post(route('idea.enhance', ['category' => $evaluation->category]), $original)
        ->assertRedirect();

    $evaluation->refresh();

    expect($evaluation)
        ->idea_title->toBe('Campus Request Tracker')
        ->overall_score->toBe(4.25)
        ->recommendation->toBe('Highly Recommended')
        ->ai_title->toBe('Campus Request Management System')
        ->ai_description->toBe('A centralized system for managing campus requests.')
        ->ai_general_objective->toBe('Improve the visibility and resolution of campus requests.')
        ->ai_specific_objectives->toBe(['Record requests.', 'Monitor request progress.'])
        ->ai_enhanced_at->not->toBeNull();
});

test('failed ai enhancement stores the original recommendation as fallback', function (): void {
    IdeaEvaluation::query()->create([
        'idea_title' => 'Campus Request Tracker',
        'category' => 'Academic Process',
        'overall_score' => 90,
    ]);

    $this->mock(OllamaService::class, function ($mock): void {
        $mock->shouldReceive('enhance')
            ->once()
            ->andReturn([
                'title' => 'Campus Request Tracker',
                'description' => 'Original generated description.',
                'general_objective' => 'Original generated objective.',
                'specific_objectives' => ['Original generated specific objective.'],
            ]);
    });

    app(EnhanceIdeaWithAi::class, [
        'ideaTitle' => 'Campus Request Tracker',
        'category' => 'Academic Process',
        'description' => 'Original generated description.',
        'generalObjective' => 'Original generated objective.',
        'specificObjectives' => ['Original generated specific objective.'],
    ])->handle(app(OllamaService::class));

    $evaluation = IdeaEvaluation::query()
        ->where('idea_title', 'Campus Request Tracker')
        ->where('category', 'Academic Process')
        ->first();

    expect($evaluation)
        ->ai_title->toBe('Campus Request Tracker')
        ->ai_description->toBe('Original generated description.')
        ->ai_general_objective->toBe('Original generated objective.')
        ->ai_specific_objectives->toBe(['Original generated specific objective.'])
        ->ai_enhanced_at->not->toBeNull();
});

test('enhancement writes are disabled by default without changing existing ai fields', function (): void {
    config(['services.ollama.enhance_enabled' => false]);

    $evaluation = IdeaEvaluation::query()->create([
        'idea_title' => 'Campus Request Tracker',
        'category' => 'Academic Process',
        'overall_score' => 4.25,
        'ai_title' => 'Existing AI title',
        'ai_description' => 'Existing AI description',
        'ai_general_objective' => 'Existing AI objective',
        'ai_specific_objectives' => ['Existing AI specific objective'],
        'ai_enhanced_at' => now()->subMinute(),
    ]);
    $before = $evaluation->only([
        'ai_title',
        'ai_description',
        'ai_general_objective',
        'ai_specific_objectives',
        'ai_enhanced_at',
    ]);

    $this->actingAs(User::factory()->create())
        ->post(route('idea.enhance', ['category' => $evaluation->category]), enhanceFlagPayload())
        ->assertNotFound();

    expect($evaluation->refresh()->only([
        'ai_title',
        'ai_description',
        'ai_general_objective',
        'ai_specific_objectives',
        'ai_enhanced_at',
    ]))->toEqual($before);
});

test('a guest is still redirected to login when enhancement is disabled', function (): void {
    config(['services.ollama.enhance_enabled' => false]);

    $this->post(route('idea.enhance', ['category' => 'Academic Process']), enhanceFlagPayload())
        ->assertRedirect(route('login'));
});

test('the category page hides the enhancement form when the flag is disabled', function (): void {
    config(['services.ollama.enhance_enabled' => false]);
    configureEnhanceFlagCategoryPage();

    IdeaEvaluation::query()->create([
        'idea_title' => 'Flag Test DSS Idea',
        'category' => 'Enhance Flag Category',
        'overall_score' => 4.0,
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('feedback.category', ['category' => 'Enhance Flag Category']))
        ->assertOk()
        ->assertDontSeeText('Improve with AI')
        ->assertDontSee(route('idea.enhance', 'Enhance Flag Category'), false);
});

test('the category page shows the enhancement form when the flag is enabled', function (): void {
    config(['services.ollama.enhance_enabled' => true]);
    configureEnhanceFlagCategoryPage();

    IdeaEvaluation::query()->create([
        'idea_title' => 'Flag Test DSS Idea',
        'category' => 'Enhance Flag Category On',
        'overall_score' => 4.0,
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('feedback.category', ['category' => 'Enhance Flag Category On']))
        ->assertOk()
        ->assertSeeText('Improve with AI')
        ->assertSee(route('idea.enhance', 'Enhance Flag Category On'), false);
});

test('stored ai wording remains visible when new enhancement writes are disabled', function (): void {
    config(['services.ollama.enhance_enabled' => false]);
    configureEnhanceFlagCategoryPage();

    IdeaEvaluation::query()->create([
        'idea_title' => 'Flag Test DSS Idea',
        'category' => 'Enhance Flag Category Existing AI',
        'overall_score' => 4.0,
        'ai_title' => 'Previously Enhanced Flag Idea',
        'ai_description' => 'Previously stored AI wording remains available.',
        'ai_general_objective' => 'To preserve the existing wording.',
        'ai_specific_objectives' => ['To keep old AI content readable.'],
        'ai_enhanced_at' => now()->subMinute(),
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('feedback.category', ['category' => 'Enhance Flag Category Existing AI']))
        ->assertOk()
        ->assertSeeText('AI-ENHANCED')
        ->assertSeeText('Previously Enhanced Flag Idea')
        ->assertDontSeeText('Improve with AI');
});
