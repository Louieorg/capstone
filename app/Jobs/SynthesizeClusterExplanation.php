<?php

namespace App\Jobs;

use App\Models\ClusterExplanation;
use App\Services\ClusterEvidenceService;
use App\Services\OllamaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Explain one already-decided DSS cluster without holding an HTTP request open.
 *
 * The DSS decides; this job only asks the model to describe the evidence the
 * DSS already scored, and stores that description under the content hash of the
 * evidence it describes. It receives the identity of a cluster — the report ids
 * the DSS attached to its idea — and nothing else: no score, no severity, no
 * confidence, no qualification result and no adviser or vote data, so it cannot
 * restate, re-score or revise a decision even if the model misbehaves.
 *
 * The cluster is never re-derived here. The package is rebuilt from the live
 * approved reports behind those same ids and re-validated by the production
 * synthesis path before anything is written, and the hash is recomputed from
 * that rebuild. When the rebuilt evidence no longer addresses the job, the
 * result is discarded instead of stored, so an explanation can never be filed
 * under evidence that has moved underneath it.
 *
 * Nothing is invented and nothing is guessed: a rejected result leaves the row
 * in the "rejected" state with no model output, and a failure here never
 * reaches the page that queued it.
 */
class SynthesizeClusterExplanation implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The model call is the only slow step and the same evidence is not worth
     * hammering, so one retry is enough to absorb a transient failure.
     */
    public int $tries = 2;

    /**
     * Sits above the Ollama request timeout (120s by default) so a slow but
     * completed call is kept, and below the worker timeout so the worker, not
     * the job, is what reports a stuck process.
     */
    public int $timeout = 180;

    /**
     * How long one evidence hash stays reserved. The same cluster content
     * cannot be queued twice inside this window, so repeated page views during
     * a single generation cannot stack duplicate work.
     */
    public int $uniqueFor = 300;

    /**
     * @param  string  $category  The category whose qualifying cluster is described.
     * @param  array<string, mixed>  $cluster  The cluster reference: its label plus the
     *                                         report ids and report count the DSS recorded.
     * @param  string  $evidenceHash  The content address the dispatcher computed for
     *                                this cluster, which the result must still match.
     */
    public function __construct(
        public string $category,
        public array $cluster,
        public string $evidenceHash,
    ) {}

    /**
     * Address the queue reservation by content rather than by cluster, so the
     * same evidence is never described twice at once.
     */
    public function uniqueId(): string
    {
        return $this->evidenceHash;
    }

    public function handle(ClusterEvidenceService $evidence, OllamaService $ollama): void
    {
        $package = $evidence->buildPackage($this->cluster, $this->category);

        // The reports behind this cluster were withdrawn, flagged or removed
        // since it was queued, or the cluster no longer carries enough reports
        // to describe. Either way there is nothing left to say about it.
        if ($package === null || ! $evidence->isSynthesizable($package)) {
            Log::info('Cluster explanation skipped: the cluster is no longer synthesizable.', [
                'evidence_hash' => $this->evidenceHash,
                'category' => $this->category,
            ]);

            return;
        }

        $profile = $ollama->synthesisProfile();
        $hash = $evidence->evidenceKey(
            $package,
            $profile['prompt_version'],
            $profile['model'],
            $profile['language']
        );

        // The evidence moved since it was queued. Describing it now would file
        // a description under an address it does not belong to, so it is
        // dropped: the page will queue the current evidence instead.
        if (! hash_equals($hash, $this->evidenceHash)) {
            Log::info('Cluster explanation discarded: the evidence changed since it was queued.', [
                'queued_hash' => $this->evidenceHash,
                'current_hash' => $hash,
                'category' => $this->category,
            ]);

            return;
        }

        $existing = ClusterExplanation::query()->where('evidence_hash', $hash)->first();

        // Already described. A re-queued or retried job must not spend a model
        // call reproducing text that is already stored and already complete.
        if ($existing !== null && $existing->status === 'complete') {
            return;
        }

        $result = $ollama->synthesize($package, $hash);

        if ($result === null) {
            $this->record($hash, $package, $profile, null);

            return;
        }

        $this->record($hash, $package, $profile, $result);
    }

    /**
     * Record an attempt that never produced a result, such as a timeout or an
     * unhandled error, so the caller's retry gate can stop queueing it.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SynthesizeClusterExplanation job failed', [
            'evidence_hash' => $this->evidenceHash,
            'category' => $this->category,
            'error' => $exception->getMessage(),
        ]);

        try {
            $existing = ClusterExplanation::query()->where('evidence_hash', $this->evidenceHash)->first();

            if ($existing === null || $existing->status === 'complete') {
                return;
            }

            $this->record($this->evidenceHash, [
                'cluster_label' => (string) ($this->cluster['cluster_label'] ?? ''),
            ], app(OllamaService::class)->synthesisProfile(), null);
        } catch (\Throwable $e) {
            Log::error('Cluster explanation failure could not be recorded.', [
                'evidence_hash' => $this->evidenceHash,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Store the attempt. A rejected attempt keeps every generated column empty:
     * this table records that an evidence hash was described, never a draft.
     *
     * @param  array<string, mixed>  $package
     * @param  array{model: string, prompt_version: string, language: string}  $profile
     * @param  array<string, mixed>|null  $result
     */
    private function record(string $hash, array $package, array $profile, ?array $result): void
    {
        $attempt = [
            'category' => $this->category,
            'cluster_label' => (string) ($package['cluster_label'] ?? ''),
            'language' => $profile['language'],
            'prompt_version' => $profile['prompt_version'],
            'model' => $profile['model'],
            'attempts' => (int) ClusterExplanation::query()->where('evidence_hash', $hash)->value('attempts') + 1,
            'last_attempted_at' => now(),
        ];

        if ($result === null) {
            ClusterExplanation::query()->updateOrCreate(
                ['evidence_hash' => $hash],
                $attempt + ['status' => 'rejected']
            );

            Log::warning('Cluster explanation was rejected and not stored.', [
                'evidence_hash' => $hash,
                'category' => $this->category,
            ]);

            return;
        }

        ClusterExplanation::query()->updateOrCreate(
            ['evidence_hash' => $hash],
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
