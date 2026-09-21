<?php

use App\Jobs\EnhanceIdeaWithAi;
use App\Models\IdeaEvaluation;
use App\Services\OllamaService;

test('authenticated users can enhance the original DSS recommendation without changing its evaluation', function (): void {
    $user = \App\Models\User::factory()->create();
    $evaluation = IdeaEvaluation::query()->create([
        'idea_title' => 'Campus Request Tracker',
        'category' => 'Academic Process',
        'overall_score' => 4.25,
        'recommendation' => 'Highly Recommended',
    ]);
    $original = [
        'title' => 'Campus Request Tracker',
        'description' => 'A system for tracking campus requests.',
        'general_objective' => 'Improve request visibility.',
        'specific_objectives' => ['Record requests.', 'Track request progress.'],
    ];

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
