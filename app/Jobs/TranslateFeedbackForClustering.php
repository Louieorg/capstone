<?php

namespace App\Jobs;

use App\Models\Feedback;
use App\Services\OllamaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TranslateFeedbackForClustering implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 200;

    public function __construct(public int $feedbackId) {}

    public function handle(OllamaService $ollama): void
    {
        $feedback = Feedback::find($this->feedbackId);

        if (! $feedback) {
            return;
        }

        $translated = $ollama->translate([
            'title' => $feedback->title,
            'description' => $feedback->description,
            'impact' => $feedback->impact ?? '',
        ]);

        if ($translated === null) {
            // Ollama unreachable or returned invalid output. Leave
            // translated_at null so a future re-cluster attempt retries.
            Log::warning('Feedback translation failed — will retry on next attempt.', [
                'feedback_id' => $this->feedbackId,
            ]);

            return;
        }

        $feedback->update([
            'title_en' => $translated['title'],
            'description_en' => $translated['description'],
            'impact_en' => $translated['impact'],
            'translated_at' => now(),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('TranslateFeedbackForClustering job failed', [
            'feedback_id' => $this->feedbackId,
            'error' => $exception->getMessage(),
        ]);
    }
}
