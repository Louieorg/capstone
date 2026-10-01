<?php

namespace App\Console\Commands;

use App\Models\CategoryAssignment;
use App\Models\ClusterExplanation;
use App\Models\Feedback;
use App\Models\FeedbackComment;
use App\Models\FeedbackVote;
use App\Models\IdeaEvaluation;
use App\Models\SavedIdea;
use App\Models\Setting;
use App\Models\User;
use App\Services\CategoryIdeaGenerationService;
use App\Services\ClusterEvidenceService;
use App\Services\OllamaService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Development-only benchmark for the AI evidence-synthesis pipeline.
 *
 * It measures how reliably a locally installed Ollama model produces output
 * that the existing ClusterExplanationValidator accepts. It is a measurement
 * tool, not a product feature, and it never stores a result.
 *
 * The evidence is never invented. Every cluster is obtained through the real
 * pipeline: CategoryIdeaGenerationService::generate() -> the idea's additive
 * 'evidence' key -> ClusterEvidenceService::buildPackage() -> evidenceKey(),
 * and every response is judged by the same ClusterExplanationValidator that
 * production synthesis uses.
 *
 * Read-only guarantees, because the real pipeline is not read-only on its own:
 *   - CategoryIdeaGenerationService persists IdeaEvaluation rows and sends
 *     IdeaGenerated notifications, so the whole run happens inside a database
 *     transaction that is always rolled back.
 *   - It also reads and writes the shared cache, so the default cache driver is
 *     swapped to the in-memory array store for the duration of the run.
 *   - Model events are suppressed.
 *   - Row counts for every application table are captured before and after, and
 *     the difference is reported.
 *
 * First-token latency and token counts are NOT reported: the production
 * synthesis path is non-streaming and returns only the validated result, so
 * neither metric is observable through it. Neither is estimated or invented.
 */
class BenchExplainCommand extends Command
{
    protected $signature = 'likha:bench-explain
                            {model : Model already installed in the local Ollama instance}
                            {--category= : Restrict the benchmark to a single category}
                            {--iterations=3 : Attempts per cluster; must be a positive integer}
                            {--json : Emit machine-readable JSON instead of a readable report}
                            {--show : Also print the generated synthesis text (may contain generated wording)}';

    protected $description = 'Benchmark a local Ollama model against the real LIKHA evidence and validation contract (read-only)';

    /**
     * Deterministic category order for the default selection. Facilities yields
     * the two laboratory/inspection clusters first, then the next qualifying
     * category supplies the third. No clustering logic lives here.
     */
    private const BENCHMARK_CATEGORIES = ['Facilities', 'Scheduling', 'Enrollment', 'Library'];

    private const MAX_CLUSTERS = 3;

    private const PASS_RATE_GATE = 0.90;

    /**
     * @var array<string, int>
     */
    private array $fingerprintBefore = [];

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('This benchmark is development-only and refuses to run outside local/testing.');

            return self::FAILURE;
        }

        $iterations = $this->option('iterations');

        if (! is_numeric($iterations) || (int) $iterations < 1) {
            $this->error('The --iterations option must be a positive integer.');

            return self::FAILURE;
        }

        $iterations = (int) $iterations;

        if ($this->queueWorkerLooksActive()) {
            $this->error('A queue worker appears to be running. Stop it first so the benchmark owns the Ollama instance.');

            return self::FAILURE;
        }

        $model = (string) $this->argument('model');
        $category = $this->option('category');
        $category = is_string($category) && $category !== '' ? $category : null;

        $available = $this->availableModels();

        if ($available === null) {
            return self::FAILURE;
        }

        if (! in_array($model, $available, true)) {
            $this->error("Model \"{$model}\" is not installed in the local Ollama instance.");
            $this->line('  Installed models: '.($available === [] ? '(none)' : implode(', ', $available)));
            $this->line('  This command never downloads or installs a model.');

            return self::FAILURE;
        }

        $this->fingerprintBefore = $this->fingerprint();

        $result = $this->runReadOnlyBenchmark($model, $category, $iterations);

        $after = $this->fingerprint();
        $unchanged = $after === $this->fingerprintBefore;

        $result['read_only'] = [
            'unchanged' => $unchanged,
            'before' => $this->fingerprintBefore,
            'after' => $after,
            'deltas' => $this->deltas($this->fingerprintBefore, $after),
        ];

        if ($this->option('json')) {
            $this->line((string) json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        } else {
            $this->render($result);
        }

        return $result['overall']['result'] === 'PASS' ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Run the whole benchmark inside a transaction that is always rolled back,
     * with an in-memory cache and suppressed model events.
     *
     * @return array<string, mixed>
     */
    private function runReadOnlyBenchmark(string $model, ?string $category, int $iterations): array
    {
        $originalModel = config('services.ollama.synthesis.model');
        $originalCache = config('cache.default');

        $lastRejection = null;

        Log::listen(function (MessageLogged $message) use (&$lastRejection): void {
            if ($message->message !== 'Cluster explanation synthesis rejected.') {
                return;
            }

            $reason = $message->context['reason'] ?? null;

            if ($reason !== null) {
                $lastRejection = (string) $reason;
            }
        });

        DB::beginTransaction();

        try {
            config(['services.ollama.synthesis.model' => $model, 'cache.default' => 'array']);
            Cache::setDefaultDriver('array');

            $ollama = app(OllamaService::class);
            $evidence = app(ClusterEvidenceService::class);
            $profile = $ollama->synthesisProfile();

            $clusters = $this->selectClusters($category, $evidence, $profile, $model);

            if ($clusters === []) {
                return [
                    'model' => $model,
                    'prompt_version' => $profile['prompt_version'],
                    'language' => $profile['language'],
                    'iterations' => $iterations,
                    'clusters' => [],
                    'overall' => [
                        'total_attempts' => 0,
                        'valid_attempts' => 0,
                        'pass_rate' => 0.0,
                        'gate' => self::PASS_RATE_GATE,
                        'result' => 'FAIL',
                        'note' => 'No qualifying cluster could be resolved from the available data.',
                        'validation_rules_observed' => [],
                        'transport_failures' => [],
                    ],
                ];
            }

            $results = [];

            foreach ($clusters as $cluster) {
                $results[] = $this->runCluster($cluster, $iterations, $profile, $model, $ollama, $lastRejection);
            }

            return $this->summarise($model, $profile, $iterations, $results);
        } catch (Throwable $e) {
            $this->error('The benchmark stopped unexpectedly: '.$e->getMessage());

            return [
                'model' => $model,
                'prompt_version' => 'unknown',
                'language' => 'unknown',
                'iterations' => $iterations,
                'clusters' => [],
                'overall' => [
                    'total_attempts' => 0,
                    'valid_attempts' => 0,
                    'pass_rate' => 0.0,
                    'gate' => self::PASS_RATE_GATE,
                    'result' => 'FAIL',
                    'note' => $e->getMessage(),
                    'validation_rules_observed' => [],
                    'transport_failures' => [],
                ],
            ];
        } finally {
            DB::rollBack();

            config(['services.ollama.synthesis.model' => $originalModel, 'cache.default' => $originalCache]);
            Cache::setDefaultDriver($originalCache ?: 'file');
        }
    }

    /**
     * Resolve real qualifying clusters through the production evidence path.
     *
     * @param  array{model: string, prompt_version: string, language: string}  $profile
     * @return array<int, array<string, mixed>>
     */
    private function selectClusters(?string $category, ClusterEvidenceService $evidence, array $profile, string $model): array
    {
        $categories = $category !== null ? [$category] : self::BENCHMARK_CATEGORIES;
        $selected = [];

        foreach ($categories as $name) {
            $generated = Model::withoutEvents(
                fn (): array => app(CategoryIdeaGenerationService::class)->generate($name)
            );

            if (! $generated['qualifying']) {
                continue;
            }

            foreach ($generated['ideas'] as $idea) {
                $package = $evidence->buildPackage($idea, $name);

                if ($package === null || ! $evidence->isSynthesizable($package)) {
                    continue;
                }

                $selected[] = [
                    'category' => $name,
                    'cluster_label' => (string) ($idea['cluster_label'] ?? 'Unknown'),
                    'package' => $package,
                    'evidence_hash' => $evidence->evidenceKey(
                        $package,
                        $profile['prompt_version'],
                        $profile['model'],
                        $profile['language']
                    ),
                ];

                if (count($selected) >= self::MAX_CLUSTERS) {
                    return $selected;
                }
            }
        }

        return $selected;
    }

    /**
     * Benchmark one cluster through OllamaService::synthesize().
     *
     * @param  array<string, mixed>  $cluster
     * @param  array{model: string, prompt_version: string, language: string}  $profile
     * @return array<string, mixed>
     */
    private function runCluster(
        array $cluster,
        int $iterations,
        array $profile,
        string $model,
        OllamaService $ollama,
        mixed &$lastRejection
    ): array {
        $package = $cluster['package'];
        $attempts = [];

        for ($iteration = 1; $iteration <= $iterations; $iteration++) {
            $lastRejection = null;

            $started = hrtime(true);
            $result = $ollama->synthesize($package, $cluster['evidence_hash']);
            $totalMs = (hrtime(true) - $started) / 1_000_000;

            $encoded = $result === null ? '' : (string) json_encode($result, JSON_UNESCAPED_UNICODE);

            $attempts[] = [
                'iteration' => $iteration,
                'model' => $model,
                'category' => $cluster['category'],
                'cluster_label' => $cluster['cluster_label'],
                'evidence_hash' => $cluster['evidence_hash'],
                'report_count' => (int) $package['report_count'],
                'evidence_item_count' => count($package['evidence']),
                'prompt_version' => $profile['prompt_version'],
                'language' => $profile['language'],
                'status' => $result === null ? 'failed' : 'valid',
                'reason' => $lastRejection,
                'first_token_ms' => null,
                'first_token_note' => 'Unavailable: production synthesis is non-streaming.',
                'total_ms' => round($totalMs, 2),
                'output_chars' => mb_strlen($encoded),
                'prompt_eval_tokens' => null,
                'eval_tokens' => null,
                'token_note' => 'Unavailable: the non-streaming production path returns only the validated result.',
                'output' => $this->option('show') ? $result : null,
            ];
        }

        $valid = count(array_filter($attempts, fn (array $a): bool => $a['status'] === 'valid'));
        $times = array_map(fn (array $a): float => (float) $a['total_ms'], $attempts);

        return [
            'category' => $cluster['category'],
            'cluster_label' => $cluster['cluster_label'],
            'evidence_hash' => $cluster['evidence_hash'],
            'report_count' => (int) $package['report_count'],
            'evidence_item_count' => count($package['evidence']),
            'attempts' => $iterations,
            'valid_results' => $valid,
            'pass_rate' => $iterations > 0 ? round($valid / $iterations, 4) : 0.0,
            'timing_ms' => [
                'min' => $times === [] ? null : min($times),
                'max' => $times === [] ? null : max($times),
                'mean' => $times === [] ? null : round(array_sum($times) / count($times), 2),
            ],
            'attempt_details' => $attempts,
        ];
    }

    /**
     * @param  array{model: string, prompt_version: string, language: string}  $profile
     * @param  array<int, array<string, mixed>>  $results
     * @return array<string, mixed>
     */
    private function summarise(string $model, array $profile, int $iterations, array $results): array
    {
        $attempts = array_merge(...array_column($results, 'attempt_details'));
        $total = count($attempts);
        $valid = count(array_filter($attempts, fn (array $a): bool => $a['status'] === 'valid'));

        $counts = [];
        $transport = ['connection_error', 'http_error', 'invalid_json'];

        foreach ($attempts as $attempt) {
            if ($attempt['reason'] !== null) {
                $counts[$attempt['reason']] = ($counts[$attempt['reason']] ?? 0) + 1;
            }
        }

        $times = array_map(fn (array $a): float => (float) $a['total_ms'], $attempts);
        sort($times);

        return [
            'model' => $model,
            'prompt_version' => $profile['prompt_version'],
            'language' => $profile['language'],
            'iterations' => $iterations,
            'cluster_count' => count($results),
            'clusters' => $results,
            'overall' => [
                'total_attempts' => $total,
                'valid_attempts' => $valid,
                'pass_rate' => $total > 0 ? round($valid / $total, 4) : 0.0,
                'gate' => self::PASS_RATE_GATE,
                'result' => ($total > 0 && ($valid / $total) >= self::PASS_RATE_GATE) ? 'PASS' : 'FAIL',
                'timing_ms' => [
                    'min' => $times === [] ? null : min($times),
                    'max' => $times === [] ? null : max($times),
                    'median' => $times === [] ? null : $times[intdiv(count($times), 2)],
                ],
                'failure_reason_counts' => $counts,
                'validation_rules_observed' => array_values(array_diff(array_keys($counts), $transport)),
                'transport_failures' => array_values(array_intersect(array_keys($counts), $transport)),
            ],
        ];
    }

    /**
     * Row counts for every application table the benchmark must not change.
     *
     * @return array<string, int>
     */
    private function fingerprint(): array
    {
        return [
            'feedback' => Feedback::query()->count(),
            'feedback_votes' => FeedbackVote::query()->count(),
            'feedback_comments' => FeedbackComment::query()->count(),
            'idea_evaluations' => IdeaEvaluation::query()->count(),
            'saved_ideas' => SavedIdea::query()->count(),
            'cluster_explanations' => ClusterExplanation::query()->count(),
            'settings' => Setting::query()->count(),
            'category_assignments' => CategoryAssignment::query()->count(),
            'users' => User::query()->count(),
            'notifications' => DB::table('notifications')->count(),
        ];
    }

    /**
     * Per-table row-count movement, so a failed read-only check names the table
     * that moved instead of only reporting that something moved.
     *
     * @param  array<string, int>  $before
     * @param  array<string, int>  $after
     * @return array<string, array{before: int, after: int, delta: int}>
     */
    private function deltas(array $before, array $after): array
    {
        $deltas = [];

        foreach ($after as $table => $count) {
            $previous = $before[$table] ?? $count;

            if ($previous !== $count) {
                $deltas[$table] = [
                    'before' => $previous,
                    'after' => $count,
                    'delta' => $count - $previous,
                ];
            }
        }

        return $deltas;
    }

    /**
     * Models already installed locally. Returns null when Ollama is unreachable.
     *
     * @return array<int, string>|null
     */
    private function availableModels(): ?array
    {
        $url = rtrim((string) config('services.ollama.url'), '/');

        try {
            $response = Http::connectTimeout(5)->timeout(10)->get($url.'/api/tags');
        } catch (Throwable $e) {
            $this->error('Cannot reach the local Ollama service at '.$url.'.');
            $this->line('  '.$e->getMessage());
            $this->line('  Start Ollama, or correct services.ollama.url. No fallback model is selected.');

            return null;
        }

        if ($response->failed()) {
            $this->error('Ollama at '.$url.' returned HTTP '.$response->status().'.');

            return null;
        }

        $models = $response->json('models');

        if (! is_array($models)) {
            $this->error('Ollama at '.$url.' returned an unexpected /api/tags payload.');

            return null;
        }

        return array_values(array_filter(array_map(
            fn ($model): string => is_array($model) ? (string) ($model['name'] ?? '') : '',
            $models
        )));
    }

    /**
     * A reserved job in the queue table means a worker is mid-flight.
     */
    private function queueWorkerLooksActive(): bool
    {
        try {
            return DB::table('jobs')->whereNotNull('reserved_at')->where('reserved_at', '>', 0)->exists();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function render(array $result): void
    {
        $line = str_repeat('=', 79);

        $this->line($line);
        $this->line('LIKHA AI SYNTHESIS BENCHMARK');
        $this->line($line);
        $this->line('');
        $this->line('Model:          '.($result['model'] ?? 'unknown'));
        $this->line('Prompt version: '.($result['prompt_version'] ?? 'unknown'));
        $this->line('Language:       '.($result['language'] ?? 'unknown'));
        $this->line('Iterations:     '.($result['iterations'] ?? 0));
        $this->line('Clusters:       '.($result['cluster_count'] ?? 0));
        $this->line('');

        if (($result['clusters'] ?? []) === []) {
            $this->warn('No qualifying cluster was resolved: '.($result['overall']['note'] ?? 'unknown reason'));
        }

        foreach ($result['clusters'] ?? [] as $index => $cluster) {
            $this->line('Cluster '.($index + 1).':');
            $this->line('  category:             '.$cluster['category']);
            $this->line('  label:                '.$cluster['cluster_label']);
            $this->line('  report count:         '.$cluster['report_count']);
            $this->line('  evidence item count:  '.$cluster['evidence_item_count']);
            $this->line('  evidence hash:        '.$cluster['evidence_hash']);
            $this->line('  attempts:             '.$cluster['attempts']);
            $this->line('  valid results:        '.$cluster['valid_results']);
            $this->line('  validation pass rate: '.$this->percent($cluster['pass_rate']));
            $this->line('  total time min/max:   '.$cluster['timing_ms']['min'].' / '.$cluster['timing_ms']['max'].' ms');
            $this->line('  first-token latency:  not measured (production synthesis is non-streaming)');
            $this->line('  token counts:         not exposed by the non-streaming production path');

            foreach ($cluster['attempt_details'] as $attempt) {
                $this->line(sprintf(
                    '    #%d %s  %s ms  %d chars  %s',
                    $attempt['iteration'],
                    $attempt['status'],
                    $attempt['total_ms'],
                    $attempt['output_chars'],
                    $attempt['reason'] === null ? 'no failure reason' : 'reason: '.$attempt['reason']
                ));
            }

            if ($this->option('show')) {
                $this->line('  --show: generated synthesis text follows (generated wording, printed only):');

                foreach ($cluster['attempt_details'] as $attempt) {
                    $this->line('    attempt '.$attempt['iteration'].':');
                    $this->line(json_encode($attempt['output'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '(no output)');
                }
            }

            $this->line('');
        }

        $overall = $result['overall'] ?? [];

        $this->line('OVERALL:');
        $this->line('  total attempts:        '.($overall['total_attempts'] ?? 0));
        $this->line('  valid attempts:        '.($overall['valid_attempts'] ?? 0));
        $this->line('  validation pass rate:  '.$this->percent($overall['pass_rate'] ?? 0));
        $this->line('  gate:                  ≥ '.($overall['gate'] ?? self::PASS_RATE_GATE));
        $this->line('  result:                '.($overall['result'] ?? 'FAIL'));
        $this->line('  timing min/median/max: '.($overall['timing_ms']['min'] ?? 'n/a').' / '.($overall['timing_ms']['median'] ?? 'n/a').' / '.($overall['timing_ms']['max'] ?? 'n/a').' ms');
        $this->line('  validation rules seen: '.($overall['validation_rules_observed'] === [] ? '(none)' : implode(', ', $overall['validation_rules_observed'])));
        $this->line('  transport failures:    '.($overall['transport_failures'] === [] ? '(none)' : implode(', ', $overall['transport_failures'])));
        $this->line('');
        $unchanged = (bool) ($result['read_only']['unchanged'] ?? false);

        $this->line('READ-ONLY CHECK: '.($unchanged
            ? 'all application row counts unchanged'
            : 'ROW COUNTS CHANGED - investigate before using this run'));

        if (! $unchanged) {
            $this->line('  Changed tables (before -> after, delta):');

            foreach ($result['read_only']['deltas'] as $table => $delta) {
                $this->line(sprintf(
                    '    %-22s %d -> %d  (%+d)',
                    $table,
                    $delta['before'],
                    $delta['after'],
                    $delta['delta']
                ));
            }

            $this->line('  The benchmark runs its own writes inside a transaction that is always');
            $this->line('  rolled back, and never commits. Movement in idea_evaluations or');
            $this->line('  notifications therefore came from another process using the app at the');
            $this->line('  same time - for example a category page view, which generates ideas');
            $this->line('  and saves them by design. Re-run while nothing else is using the app.');
        }

        $this->line('No model is ranked here. Compare the measured numbers yourself.');
    }

    private function percent(float $rate): string
    {
        return number_format($rate * 100, 1).'%';
    }
}
