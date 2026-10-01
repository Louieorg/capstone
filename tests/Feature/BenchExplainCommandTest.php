<?php

use App\Console\Commands\BenchExplainCommand;
use App\Models\Feedback;
use App\Models\FeedbackComment;
use App\Models\FeedbackVote;
use App\Models\IdeaEvaluation;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

const BENCH_MODEL = 'bench-model:latest';

/**
 * A synthesis response that satisfies every production validation rule.
 *
 * @return array<string, mixed>
 */
function benchValidSynthesis(): array
{
    return [
        'summary' => 'Students repeatedly follow up in person because they cannot see whether a request was received or resolved.',
        'patterns' => [
            'Status updates are shared verbally instead of being published.',
            'The same concern is raised again because nothing is tracked.',
        ],
        'experiences' => [
            [
                'title' => 'Queuing for an answer',
                'body' => 'Students wait during class hours to ask staff whether their request was received or already resolved.',
            ],
        ],
    ];
}

function seedBenchData(): void
{
    (new DemoDataSeeder)->run();
}

/**
 * Fake Ollama: a model list plus a synthesis reply the test controls.
 */
function fakeOllama(array $synthesis, string $model = BENCH_MODEL, int $status = 200): void
{
    Http::fake([
        '*/api/tags' => Http::response([
            'models' => [['name' => $model, 'size' => 2019393189]],
        ], 200),
        '*/api/generate' => Http::response(['response' => json_encode($synthesis)], $status),
    ]);
}

/**
 * Run the command through the real kernel so its output can be captured.
 *
 * Artisan uses NullOutput unless a buffer is supplied, so one is passed
 * explicitly.
 *
 * @param  array<string, mixed>  $parameters
 */
function runBench(array $parameters): string
{
    $buffer = new Symfony\Component\Console\Output\BufferedOutput;

    Artisan::call('likha:bench-explain', $parameters, $buffer);

    return $buffer->fetch();
}

/**
 * @return array<string, mixed>
 */
function decodeBenchmarkJson(string $output): array
{
    $start = strpos($output, '{');

    return json_decode(substr($output, (int) $start), true);
}

it('is registered and accepts the model and option arguments', function (): void {
    expect(class_exists(BenchExplainCommand::class))->toBeTrue();

    $definition = Artisan::all()['likha:bench-explain'] ?? null;

    expect($definition)->not->toBeNull()
        ->and($definition->getName())->toBe('likha:bench-explain')
        ->and($definition->getDefinition()->getArgument('model')->isRequired())->toBeTrue();

    $options = $definition->getDefinition()->getOptions();

    expect($options)->toHaveKeys(['category', 'iterations', 'json', 'show']);
});

it('refuses a model that is not installed locally', function (): void {
    Cache::flush();
    seedBenchData();
    fakeOllama(benchValidSynthesis());

    $this->artisan('likha:bench-explain', ['model' => 'not-installed:latest'])
        ->expectsOutputToContain('is not installed in the local Ollama instance')
        ->assertExitCode(1);

    Http::assertNotSent(fn ($request) => str_contains($request->url(), '/api/generate'));
});

it('rejects a non positive iteration count before doing any work', function (): void {
    Cache::flush();
    seedBenchData();
    fakeOllama(benchValidSynthesis());

    $this->artisan('likha:bench-explain', ['model' => BENCH_MODEL, '--iterations' => 0])
        ->expectsOutputToContain('must be a positive integer')
        ->assertExitCode(1);

    Http::assertNotSent(fn ($request) => str_contains($request->url(), '/api/generate'));
});

it('measures the real evidence path and honours the iteration count', function (): void {
    Cache::flush();
    Queue::fake();
    seedBenchData();
    fakeOllama(benchValidSynthesis());

    $this->artisan('likha:bench-explain', [
        'model' => BENCH_MODEL,
        '--iterations' => 2,
        '--json' => true,
    ])->assertExitCode(0);

    // Three real qualifying clusters, two attempts each, plus one model list.
    Http::assertSentCount(7);

    $generate = Http::recorded(fn ($request) => str_contains($request->url(), '/api/generate'));

    expect($generate)->toHaveCount(6);

    foreach ($generate as [$request]) {
        $body = $request->data();

        expect($body['model'])->toBe(BENCH_MODEL)
            ->and($body['format'])->toBe('json')
            ->and($body['stream'])->toBeFalse()
            ->and($body['prompt'])->toContain('<evidence>')
            ->and($body['prompt'])->not->toContain('feedback_ids')
            ->and($body['prompt'])->not->toContain('user_id');
    }
});

it('records validation failures reported by the production validator', function (): void {
    Cache::flush();
    seedBenchData();

    // Wrong shape: rejected by ClusterExplanationValidator's shape rule.
    fakeOllama(['summary' => 'HACKED']);

    $output = runBench([
        'model' => BENCH_MODEL,
        '--iterations' => 1,
        '--category' => 'Scheduling',
        '--json' => true,
    ]);

    $result = decodeBenchmarkJson($output);

    expect($result['overall']['valid_attempts'])->toBe(0)
        ->and($result['overall']['pass_rate'])->toEqual(0.0)
        ->and($result['overall']['result'])->toBe('FAIL')
        ->and($result['overall']['validation_rules_observed'])->toContain('shape');
});

it('records a malformed response as a failed attempt', function (): void {
    Cache::flush();
    seedBenchData();

    // Not JSON at all, so the production parser cannot build a payload.
    Http::fake([
        '*/api/tags' => Http::response(['models' => [['name' => BENCH_MODEL]]], 200),
        '*/api/generate' => Http::response(['response' => 'Sure! Here is the summary you asked for.'], 200),
    ]);

    $output = runBench([
        'model' => BENCH_MODEL,
        '--iterations' => 1,
        '--category' => 'Scheduling',
        '--json' => true,
    ]);

    $result = decodeBenchmarkJson($output);

    expect($result['overall']['total_attempts'])->toBeGreaterThan(0)
        ->and($result['overall']['valid_attempts'])->toBe(0)
        ->and($result['overall']['transport_failures'])->toContain('invalid_json');
});

it('records an http failure without crashing and keeps benchmarking', function (): void {
    Cache::flush();
    seedBenchData();
    fakeOllama(benchValidSynthesis(), BENCH_MODEL, 500);

    $output = runBench([
        'model' => BENCH_MODEL,
        '--iterations' => 2,
        '--json' => true,
    ]);

    $result = decodeBenchmarkJson($output);

    expect($result['overall']['total_attempts'])->toBe(6)
        ->and($result['overall']['valid_attempts'])->toBe(0)
        ->and($result['overall']['transport_failures'])->toContain('http_error')
        ->and($result['overall']['result'])->toBe('FAIL');
});

it('reports a connection failure when ollama cannot be reached', function (): void {
    Cache::flush();
    seedBenchData();

    Http::fake(fn () => throw new Illuminate\Http\Client\ConnectionException('offline'));

    $this->artisan('likha:bench-explain', ['model' => BENCH_MODEL])
        ->expectsOutputToContain('Cannot reach the local Ollama service')
        ->assertExitCode(1);
});

it('performs no application writes and dispatches no job', function (): void {
    Cache::flush();
    Queue::fake();
    seedBenchData();
    fakeOllama(benchValidSynthesis());

    $count = fn (): array => [
        'feedback' => Feedback::query()->count(),
        'votes' => FeedbackVote::query()->count(),
        'comments' => FeedbackComment::query()->count(),
        'idea_evaluations' => IdeaEvaluation::query()->count(),
        'users' => User::query()->count(),
        'notifications' => DB::table('notifications')->count(),
    ];

    $before = $count();

    $output = runBench([
        'model' => BENCH_MODEL,
        '--iterations' => 1,
        '--json' => true,
    ]);

    expect($count())->toBe($before)
        ->and(decodeBenchmarkJson($output)['read_only']['unchanged'])->toBeTrue();

    Queue::assertNothingPushed();
});

it('filters by category', function (): void {
    Cache::flush();
    seedBenchData();
    fakeOllama(benchValidSynthesis());

    $output = runBench([
        'model' => BENCH_MODEL,
        '--iterations' => 1,
        '--category' => 'Enrollment',
        '--json' => true,
    ]);

    $result = decodeBenchmarkJson($output);

    expect($result['clusters'])->toHaveCount(1)
        ->and($result['clusters'][0]['category'])->toBe('Enrollment')
        ->and($result['clusters'][0]['cluster_label'])->toBe('Request Tracking')
        ->and($result['clusters'][0]['report_count'])->toBe(3);
});

it('produces valid machine readable json and keeps output text out of it', function (): void {
    Cache::flush();
    seedBenchData();
    fakeOllama(benchValidSynthesis());

    $output = runBench([
        'model' => BENCH_MODEL,
        '--iterations' => 1,
        '--json' => true,
    ]);

    $result = decodeBenchmarkJson($output);

    expect($result)->toHaveKeys(['model', 'prompt_version', 'language', 'iterations', 'clusters', 'overall', 'read_only'])
        ->and($result['model'])->toBe(BENCH_MODEL)
        ->and($result['prompt_version'])->toBe('3')
        ->and($result['language'])->toBe('en')
        ->and($result['overall']['gate'])->toEqual(0.9)
        ->and($result['read_only']['unchanged'])->toBeTrue()
        // Generated wording must not leak into the default json output.
        ->and($output)->not->toContain('Students repeatedly follow up in person')
        ->and($result['clusters'][0]['attempt_details'][0]['output'])->toBeNull()
        ->and($result['clusters'][0]['attempt_details'][0]['first_token_ms'])->toBeNull();
});

it('only prints generated text when show is supplied', function (): void {
    Cache::flush();
    seedBenchData();
    fakeOllama(benchValidSynthesis());

    $without = runBench([
        'model' => BENCH_MODEL,
        '--iterations' => 1,
        '--category' => 'Scheduling',
    ]);

    $with = runBench([
        'model' => BENCH_MODEL,
        '--iterations' => 1,
        '--category' => 'Scheduling',
        '--show' => true,
    ]);

    expect($without)->toContain('LIKHA AI SYNTHESIS BENCHMARK')
        ->and($without)->toContain('READ-ONLY CHECK: all application row counts unchanged')
        ->and($without)->not->toContain('Students repeatedly follow up in person')
        ->and($with)->toContain('Students repeatedly follow up in person');
});

it('fails the ninety percent gate when one attempt in nine is invalid', function (): void {
    Cache::flush();
    seedBenchData();

    $index = 0;

    Http::fake([
        '*/api/tags' => Http::response(['models' => [['name' => BENCH_MODEL]]], 200),
        '*/api/generate' => function () use (&$index) {
            $index++;
            $valid = $index !== 9;

            return Http::response(['response' => json_encode(
                $valid ? benchValidSynthesis() : ['summary' => 'nope']
            )], 200);
        },
    ]);

    $output = runBench([
        'model' => BENCH_MODEL,
        '--iterations' => 3,
        '--json' => true,
    ]);

    $result = decodeBenchmarkJson($output);

    expect($result['overall']['total_attempts'])->toBe(9)
        ->and($result['overall']['valid_attempts'])->toBe(8)
        ->and($result['overall']['pass_rate'])->toEqual(0.8889)
        ->and($result['overall']['result'])->toBe('FAIL');
});

it('passes the gate when every attempt is valid', function (): void {
    Cache::flush();
    seedBenchData();
    fakeOllama(benchValidSynthesis());

    $output = runBench([
        'model' => BENCH_MODEL,
        '--iterations' => 1,
        '--json' => true,
    ]);

    $result = decodeBenchmarkJson($output);

    expect($result['overall']['valid_attempts'])->toBe(3)
        ->and($result['overall']['pass_rate'])->toEqual(1.0)
        ->and($result['overall']['result'])->toBe('PASS');
});
