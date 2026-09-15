<?php

use App\Jobs\EnhanceIdeaWithAi;
use App\Models\IdeaEvaluation;
use App\Services\OllamaService;

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
