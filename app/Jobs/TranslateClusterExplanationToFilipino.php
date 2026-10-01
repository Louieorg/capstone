<?php

namespace App\Jobs;

use App\Models\ClusterExplanation;
use App\Services\ClusterTranslationService;
use App\Services\OllamaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Render one stored, completed English cluster explanation in Filipino.
 *
 * The English synthesis is the canonical AI interpretation; this job only
 * translates that stored wording into Filipino. It receives the identity of
 * the English row — never raw reports, clustering, scores or decisions — so
 * it cannot restate, re-score or revise a decision even if the model
 * misbehaves.
 *
 * The translation is addressed by a content hash of the stored English source
 * plus the translation configuration, recomputed here before anything is
 * written. When the English source has moved underneath the job, the result
 * is discarded instead of stored, so a translation can never be filed under
 * wording it did not render.
 *
 * Nothing is invented and nothing is guessed: a rejected translation leaves
 * the row in the "rejected" state with no model output, and a failure here
 * never reaches the page that queued it.
 */
class TranslateClusterExplanationToFilipino implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The model call is the only slow step and the same source is not worth
     * hammering, so one retry is enough to absorb a transient failure.
     */
    public int $tries = 2;

    /**
     * Sits above the Ollama translation request timeout, so a slow but
     * completed call is kept, and below the worker timeout so the worker, not
     * the job, is what reports a stuck process.
     */
    public int $timeout = 180;

    /**
     * How long one translation hash stays reserved. The same source content
     * cannot be translated twice inside this window, so repeated page views
     * during a single translation cannot stack duplicate work.
     */
    public int $uniqueFor = 300;

    /**
     * @param  string  $category  The category whose explanation is rendered.
     * @param  int  $englishId  The stored completed English explanation rendered.
     * @param  string  $translationHash  The content address the dispatcher computed.
     */
    public function __construct(
        public string $category,
        public int $englishId,
        public string $translationHash,
    ) {}

    /**
     * Address the queue reservation by content rather than by row, so the same
     * source is never translated twice at once.
     */
    public function uniqueId(): string
    {
        return $this->translationHash;
    }

    public function handle(ClusterTranslationService $translation, OllamaService $ollama): void
    {
        $english = ClusterExplanation::query()->find($this->englishId);

        $source = $translation->canonicalSource($english);

        // The English source is missing, rejected or incomplete. Nothing is
        // translated and no row is touched.
        if ($english === null || $source === null) {
            return;
        }

        $hash = $translation->translationKey(
            $source,
            $ollama->translationProfile()['prompt_version'],
            $ollama->translationProfile()['model'],
            $ollama->translationProfile()['language']
        );

        // The English source moved since dispatch: this result belongs to a
        // hash that is no longer current, so it is discarded.
        if (! hash_equals($hash, $this->translationHash)) {
            return;
        }

        $existing = ClusterExplanation::query()->where('evidence_hash', $hash)->first();

        // Already rendered. A re-queued or retried job must not spend a model
        // call reproducing text that is already stored and already complete.
        if ($existing !== null && $existing->status === 'complete') {
            return;
        }

        $result = $ollama->translateSynthesis($source, $hash);

        $this->record($english, $hash, $ollama->translationProfile(), $result);
    }

    /**
     * Record an attempt that threw after dequeue, so the dispatcher's retry
     * gate can stop queueing it again.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('TranslateClusterExplanationToFilipino job failed', [
            'translation_hash' => $this->translationHash,
            'category' => $this->category,
            'error' => $exception->getMessage(),
        ]);

        try {
            $existing = ClusterExplanation::query()->where('evidence_hash', $this->translationHash)->first();

            if ($existing === null || $existing->status === 'complete') {
                return;
            }

            $this->recordAttempt($existing, app(OllamaService::class)->translationProfile(), null);
        } catch (\Throwable $e) {
            Log::error('Cluster translation failure could not be recorded.', [
                'translation_hash' => $this->translationHash,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Store the attempt. A rejected attempt keeps every generated column
     * empty: this table records that a translation was attempted, never a
     * draft.
     *
     * @param  array{model: string, prompt_version: string, language: string}  $profile
     * @param  array<string, mixed>|null  $result
     */
    private function record(
        ClusterExplanation $english,
        string $hash,
        array $profile,
        ?array $result
    ): void {
        $this->recordAttempt(
            ClusterExplanation::query()->where('evidence_hash', $hash)->first(),
            $profile,
            $result,
            ['category' => $this->category, 'cluster_label' => (string) ($english->cluster_label ?? '')]
        );
    }

    /**
     * @param  array{model: string, prompt_version: string, language: string}  $profile
     * @param  array<string, mixed>|null  $result
     * @param  array<string, mixed>  $identity
     */
    private function recordAttempt(
        ?ClusterExplanation $existing,
        array $profile,
        ?array $result,
        array $identity = []
    ): void {
        $attempt = [
            'category' => (string) ($identity['category'] ?? $existing?->category ?? $this->category),
            'cluster_label' => (string) ($identity['cluster_label'] ?? $existing?->cluster_label ?? ''),
            'language' => $profile['language'],
            'prompt_version' => $profile['prompt_version'],
            'model' => $profile['model'],
            'attempts' => (int) ($existing?->attempts ?? 0) + 1,
            'last_attempted_at' => now(),
        ];

        if ($result === null) {
            ClusterExplanation::query()->updateOrCreate(
                ['evidence_hash' => $this->translationHash],
                $attempt + ['status' => 'rejected']
            );

            Log::warning('Cluster translation was rejected and not stored.', [
                'translation_hash' => $this->translationHash,
                'category' => $this->category,
            ]);

            return;
        }

        ClusterExplanation::query()->updateOrCreate(
            ['evidence_hash' => $this->translationHash],
            $attempt + [
                'status' => 'complete',
                'summary' => $result['summary'],
                'patterns' => $result['patterns'],
                'experiences' => $result['experiences'],
                'generated_at' => now(),
            ]
        );
    }
}
