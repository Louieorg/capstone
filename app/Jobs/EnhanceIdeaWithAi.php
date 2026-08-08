<?php

namespace App\Jobs;

use App\Models\IdeaEvaluation;
use App\Services\OllamaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EnhanceIdeaWithAi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Slightly above OllamaService's own 180s HTTP timeout, so this job's
    // timeout never fires first and masks the real error.
    public int $timeout = 200;

    public function __construct(
        public string $ideaTitle,
        public string $category,
        public string $description,
        public string $generalObjective,
        public array $specificObjectives,
    ) {}

    public function handle(OllamaService $ollama): void
    {
        $original = [
            'title' => $this->ideaTitle,
            'description' => $this->description,
            'general_objective' => $this->generalObjective,
            'specific_objectives' => $this->specificObjectives,
        ];

        $enhanced = $ollama->enhance($original);

        // OllamaService::enhance() returns the original array unchanged on any
        // failure (timeout, unreachable, invalid JSON). Detect that here so we
        // don't permanently mark this idea as "enhanced" with unenhanced text —
        // leaving ai_enhanced_at null means the next page view retries.
        $succeeded = $enhanced['title'] !== $original['title']
            || $enhanced['description'] !== $original['description'];

        if (! $succeeded) {
            Log::warning('AI enhancement produced no change — Ollama likely unreachable.', [
                'idea_title' => $this->ideaTitle,
                'category' => $this->category,
            ]);

            return;
        }

        IdeaEvaluation::query()
            ->where('idea_title', $this->ideaTitle)
            ->where('category', $this->category)
            ->update([
                'ai_title' => $enhanced['title'],
                'ai_description' => $enhanced['description'],
                'ai_general_objective' => $enhanced['general_objective'],
                'ai_specific_objectives' => $enhanced['specific_objectives'],
                'ai_enhanced_at' => now(),
            ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('EnhanceIdeaWithAi job failed', [
            'idea_title' => $this->ideaTitle,
            'category' => $this->category,
            'error' => $exception->getMessage(),
        ]);
    }
}
