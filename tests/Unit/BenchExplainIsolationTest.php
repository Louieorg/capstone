<?php

use App\Console\Commands\BenchExplainCommand;
use App\Models\CategoryAssignment;
use App\Models\ClusterExplanation;
use App\Models\Feedback;
use App\Models\FeedbackComment;
use App\Models\FeedbackVote;
use App\Models\IdeaEvaluation;
use App\Models\SavedIdea;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

/**
 * This file deliberately lives outside tests/Feature.
 *
 * tests/Pest.php applies RefreshDatabase to every Feature test, and
 * RefreshDatabase wraps each test in its own transaction. The benchmark opens
 * a nested transaction, so a broken rollback would still be hidden by that
 * outer transaction at the end of the test. The schema is built here with a
 * plain migrate:fresh and no wrapping transaction, so the benchmark's own
 * transaction is the outermost one, exactly as it is when a developer runs the
 * command by hand. If the rollback ever regressed, the rows would survive and
 * these tests would fail.
 */
uses(TestCase::class);

beforeEach(function (): void {
    // The test connection is sqlite/:memory:, so a fresh migration per test
    // gives each test its own clean schema and nothing survives the process.
    Artisan::call('migrate:fresh', ['--force' => true]);
});

/**
 * @return array<string, int>
 */
function isolationFingerprint(): array
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
 * @return array<string, mixed>
 */
function isolationSynthesis(): array
{
    return [
        'summary' => 'Students repeatedly follow up in person because they cannot see whether a request was received or resolved.',
        'patterns' => ['Status updates are shared verbally instead of being published.'],
        'experiences' => [[
            'title' => 'Queuing for an answer',
            'body' => 'Students wait during class hours to ask staff whether their request was received or already resolved.',
        ]],
    ];
}

/**
 * @param  array<string, mixed>  $parameters
 * @return array<string, mixed>
 */
function runIsolationBenchmark(array $parameters): array
{
    Http::fake([
        '*/api/tags' => Http::response(['models' => [['name' => 'llama3.2:latest']]], 200),
        '*/api/generate' => Http::response(['response' => json_encode(isolationSynthesis())], 200),
    ]);

    $buffer = new BufferedOutput;
    Artisan::call('likha:bench-explain', $parameters + ['--json' => true], $buffer);

    $output = $buffer->fetch();

    return json_decode(substr($output, (int) strpos($output, '{')), true);
}

it('leaves every checked row count unchanged when its transaction is the outermost one', function (): void {
    Queue::fake();
    (new DemoDataSeeder)->run();

    $before = isolationFingerprint();
    $ideaIds = IdeaEvaluation::query()->pluck('id')->all();
    $notificationIds = DB::table('notifications')->pluck('id')->all();

    $result = runIsolationBenchmark(['model' => 'llama3.2:latest', '--iterations' => 1]);

    $after = isolationFingerprint();

    foreach ($before as $table => $count) {
        expect($after[$table])->toBe($count, "row count moved for {$table}");
    }

    // No row created during the run survives it.
    expect(array_diff(IdeaEvaluation::query()->pluck('id')->all(), $ideaIds))->toBe([])
        ->and(array_diff(DB::table('notifications')->pluck('id')->all(), $notificationIds))->toBe([]);

    expect($result['read_only']['unchanged'])->toBeTrue()
        ->and($result['read_only']['deltas'])->toBe([])
        ->and($result['overall']['valid_attempts'])->toBeGreaterThan(0);

    // The command must not leave a transaction open for the rest of the process.
    expect(DB::transactionLevel())->toBe(0);

    Queue::assertNothingPushed();
});

it('really exercises writes that only the rollback prevents', function (): void {
    (new DemoDataSeeder)->run();

    $before = isolationFingerprint();

    // The production generation path genuinely writes. Scheduling has no
    // existing idea, so it inserts one and notifies its contributing users.
    // Without the benchmark's rollback these rows would persist, which is what
    // makes the read-only test above meaningful rather than vacuous.
    $generated = app(App\Services\CategoryIdeaGenerationService::class)->generate('Scheduling');

    expect($generated['ideas'])->not->toBeEmpty()
        ->and(isolationFingerprint()['idea_evaluations'])->toBeGreaterThan($before['idea_evaluations'])
        ->and(isolationFingerprint()['notifications'])->toBeGreaterThan($before['notifications']);
});

it('names the table and the exact movement when a count does change', function (): void {
    $command = new BenchExplainCommand;
    $deltas = new ReflectionMethod($command, 'deltas');
    $deltas->setAccessible(true);

    $moved = $deltas->invoke($command, [
        'feedback' => 25,
        'idea_evaluations' => 6,
        'notifications' => 12,
    ], [
        'feedback' => 25,
        'idea_evaluations' => 7,
        'notifications' => 15,
    ]);

    expect($moved)->toBe([
        'idea_evaluations' => ['before' => 6, 'after' => 7, 'delta' => 1],
        'notifications' => ['before' => 12, 'after' => 15, 'delta' => 3],
    ]);
});

it('reports no movement when nothing changed', function (): void {
    $command = new BenchExplainCommand;
    $deltas = new ReflectionMethod($command, 'deltas');
    $deltas->setAccessible(true);

    expect($deltas->invoke($command, ['feedback' => 25], ['feedback' => 25]))->toBe([]);
});

it('reports moved tables and guidance only when the read-only check fails', function (): void {
    // render() is private and writes through the output the console runner
    // would normally inject. option() is never reached because these payloads
    // carry no clusters.
    $render = function (array $readOnly): string {
        $command = new BenchExplainCommand;
        $command->setLaravel(app());

        $stream = new ReflectionProperty(BenchExplainCommand::class, 'output');
        $stream->setAccessible(true);

        $output = new BufferedOutput;
        $stream->setValue($command, $output);

        $method = new ReflectionMethod($command, 'render');
        $method->setAccessible(true);
        $method->invoke($command, [
            'model' => 'llama3.2:latest',
            'prompt_version' => '1',
            'language' => 'en',
            'iterations' => 1,
            'cluster_count' => 0,
            'clusters' => [],
            'overall' => [
                'note' => 'rendering the read-only branch only',
                'total_attempts' => 3,
                'valid_attempts' => 3,
                'pass_rate' => 1.0,
                'gate' => 0.9,
                'result' => 'PASS',
                'timing_ms' => ['min' => 1.0, 'median' => 1.0, 'max' => 1.0],
                'validation_rules_observed' => [],
                'transport_failures' => [],
            ],
            'read_only' => $readOnly,
        ]);

        return $output->fetch();
    };

    $changed = $render([
        'unchanged' => false,
        'before' => ['idea_evaluations' => 6, 'notifications' => 12],
        'after' => ['idea_evaluations' => 7, 'notifications' => 15],
        'deltas' => [
            'idea_evaluations' => ['before' => 6, 'after' => 7, 'delta' => 1],
            'notifications' => ['before' => 12, 'after' => 15, 'delta' => 3],
        ],
    ]);

    // The gate verdict is still reported: a moved count does not silently void
    // the measurement, it is reported alongside it.
    expect($changed)->toMatch('/result:\s+PASS/')
        ->and($changed)->toContain('READ-ONLY CHECK: ROW COUNTS CHANGED')
        ->and($changed)->toMatch('/idea_evaluations\s+6 -> 7\s+\(\+1\)/')
        ->and($changed)->toMatch('/notifications\s+12 -> 15\s+\(\+3\)/')
        ->and($changed)->toContain('rolled back')
        ->and($changed)->toContain('another process using the app')
        ->and($changed)->toContain('Re-run while nothing else is using the app.');

    $steady = $render([
        'unchanged' => true,
        'before' => ['idea_evaluations' => 6, 'notifications' => 12],
        'after' => ['idea_evaluations' => 6, 'notifications' => 12],
        'deltas' => [],
    ]);

    expect($steady)->toContain('READ-ONLY CHECK: all application row counts unchanged')
        ->and($steady)->not->toContain('ROW COUNTS CHANGED')
        ->and($steady)->not->toContain('Changed tables')
        ->and($steady)->not->toContain('Re-run while nothing else is using the app.');
});
