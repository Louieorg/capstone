<?php

use App\Services\ClusterExplanationValidator;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

// The suite's shared test case is bound to the Feature directory only, so this
// file binds it for itself. Neither of these tests touches the database.
//
// Booting the application leaves Eloquent's static connection resolver pointing
// at a container that this file tears down again. DB-free unit tests such as
// IdeaGeneratorServiceTest rely on that resolver being null, so it is restored
// here rather than leaking into whatever runs next.
uses(TestCase::class);

afterEach(function (): void {
    Model::unsetConnectionResolver();
});

function explanationValidator(): ClusterExplanationValidator
{
    return app(ClusterExplanationValidator::class);
}

/**
 * A small, in-memory evidence package. No database, no DSS service.
 *
 * @return array<string, mixed>
 */
function validatorPackage(array $overrides = []): array
{
    $package = [
        'schema_version' => '1',
        'category' => 'Facilities',
        'cluster_label' => 'Request Tracking',
        'report_count' => 3,
        'evidence_shown' => 2,
        'evidence_total' => 3,
        'evidence' => [
            [
                'title' => 'Request status is unclear after submission',
                'description' => 'Students follow up in person because status updates are never published.',
                'impact' => 'Students lose class time queuing for an answer.',
                'frequency' => 'Often',
                'affected_users' => '50-200',
                'affected_group' => ['Students'],
                'current_process' => 'Report verbally to staff',
            ],
            [
                'title' => 'Facility concerns are handled without a record',
                'description' => 'Concerns reach the office by talking to staff in person.',
                'impact' => 'Nothing is tracked, so the same concern is raised again.',
                'frequency' => 'Often',
                'affected_users' => '50-200',
                'affected_group' => ['Students', 'Staff'],
                'current_process' => 'Report verbally to staff',
            ],
        ],
    ];

    return array_replace($package, $overrides);
}

/**
 * A response that satisfies every hard rule.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function passingResult(array $overrides = []): array
{
    return array_replace([
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
    ], $overrides);
}

test('a grounded response is accepted and normalized', function (): void {
    $result = explanationValidator()->validate(passingResult(), validatorPackage());

    expect($result['ok'])->toBeTrue()
        ->and($result['rule'])->toBeNull()
        ->and($result['warnings'])->toBe([])
        ->and(array_keys($result['result']))->toBe(['summary', 'patterns', 'experiences'])
        ->and($result['result']['experiences'][0])->toHaveKeys(['title', 'body']);
});

test('strings are trimmed and whitespace normalized', function (): void {
    $result = explanationValidator()->validate(passingResult([
        'summary' => "  Students repeatedly   follow up in person\n because they cannot see request status.  ",
    ]), validatorPackage());

    expect($result['ok'])->toBeTrue()
        ->and($result['result']['summary'])
        ->toBe('Students repeatedly follow up in person because they cannot see request status.');
});

test('rule one shape rejects missing, extra, or wrongly typed keys', function (): void {
    $missing = passingResult();
    unset($missing['patterns']);

    $extra = passingResult(['recommendation' => 'Highly Recommended']);
    $notAList = passingResult(['patterns' => ['a' => 'Status updates are shared verbally.']]);
    $notAString = passingResult(['patterns' => [['nested']]]);
    $badExperience = passingResult(['experiences' => [['title' => 'Only a title is present here']]]);
    $extraExperienceKey = passingResult(['experiences' => [[
        'title' => 'Queuing for an answer',
        'body' => 'Students wait for a status answer that never arrives.',
        'mood' => 'sad',
    ]]]);
    $summaryNotAString = passingResult(['summary' => 12345]);

    foreach ([$missing, $extra, $notAList, $notAString, $badExperience, $extraExperienceKey, $summaryNotAString] as $result) {
        $outcome = explanationValidator()->validate($result, validatorPackage());

        expect($outcome['ok'])->toBeFalse()
            ->and($outcome['rule'])->toBe(ClusterExplanationValidator::RULE_SHAPE)
            ->and($outcome['result'])->toBeNull();
    }
});

test('rule two length rejects out of range strings and item counts', function (): void {
    $cases = [
        passingResult(['summary' => 'Too short.']),
        passingResult(['summary' => str_repeat('Students report this. ', 60)]),
        passingResult(['patterns' => ['Short one.']]),
        passingResult(['patterns' => [str_repeat('pattern words ', 20)]]),
        passingResult(['patterns' => []]),
        passingResult(['patterns' => [
            'Status updates are shared verbally instead of published.',
            'The same concern is raised again because nothing is tracked.',
            'Concerns reach the office by talking to staff in person.',
            'Nothing is tracked, so the same concern is raised again.',
            'Students lose class time queuing for an answer.',
        ]]),
    ];

    foreach ($cases as $result) {
        $outcome = explanationValidator()->validate($result, validatorPackage());

        expect($outcome['ok'])->toBeFalse()
            ->and($outcome['rule'])->toBe(ClusterExplanationValidator::RULE_LENGTH);
    }
});

test('rule two accepts the maximum of four patterns and six experiences', function (): void {
    $fourPatterns = passingResult(['patterns' => [
        'Status updates are shared verbally instead of published.',
        'The same concern is raised again because nothing is tracked.',
        'Concerns reach the office by talking to staff in person.',
        'Students lose class time queuing for an answer.',
    ]]);

    $sixExperiences = passingResult(['experiences' => array_map(
        fn (int $index): array => [
            'title' => 'Queuing at the office counter',
            'body' => 'Students wait during class hours to ask staff whether their request was received or resolved.',
        ],
        range(1, 6)
    )]);

    expect(explanationValidator()->validate($fourPatterns, validatorPackage())['ok'])->toBeTrue()
        ->and(explanationValidator()->validate($sixExperiences, validatorPackage())['ok'])->toBeTrue();
});

test('rule two rejects seven experiences and an over long body', function (): void {
    $sevenExperiences = passingResult(['experiences' => array_map(
        fn (int $index): array => [
            'title' => "Experience number {$index}",
            'body' => 'Students wait during class hours to ask staff whether their request was received or resolved.',
        ],
        range(1, 7)
    )]);

    $longBody = passingResult(['experiences' => [[
        'title' => 'A heading for the experience',
        'body' => str_repeat('Students wait for a status answer that never arrives. ', 12),
    ]]]);

    foreach ([$sevenExperiences, $longBody] as $result) {
        $outcome = explanationValidator()->validate($result, validatorPackage());

        expect($outcome['ok'])->toBeFalse()
            ->and($outcome['rule'])->toBe(ClusterExplanationValidator::RULE_LENGTH);
    }
});

test('rule three numbers compares whole tokens and rejects invented figures', function (): void {
    // 3, 2, 3 and 50-200 are all present in the package.
    $grounded = passingResult([
        'summary' => 'Three reports describe 3 students queuing, and 50-200 students are affected overall.',
    ]);
    expect(explanationValidator()->validate($grounded, validatorPackage())['ok'])->toBeTrue();

    $invented = passingResult([
        'summary' => 'Nine reports describe students queuing, and 900 students are affected overall today.',
    ]);
    $outcome = explanationValidator()->validate($invented, validatorPackage());

    expect($outcome['ok'])->toBeFalse()
        ->and($outcome['rule'])->toBe(ClusterExplanationValidator::RULE_NUMBERS);
});

test('rule three rejects a digit that only appears inside a longer number', function (): void {
    // The package contains "50-200" but never a bare 5, so 5 must not pass.
    $substringOnly = passingResult([
        'summary' => 'Students report that 5 offices are affected by the same unclear request status problem.',
    ]);

    $outcome = explanationValidator()->validate($substringOnly, validatorPackage());

    expect($outcome['ok'])->toBeFalse()
        ->and($outcome['rule'])->toBe(ClusterExplanationValidator::RULE_NUMBERS);

    // 50 alone is a real token in the package, so it is accepted.
    expect(explanationValidator()->validate(passingResult([
        'summary' => 'Students report that 50 students are affected by the unclear request status problem.',
    ]), validatorPackage())['ok'])->toBeTrue();
});

test('rule four rejects personal identifiers absent from the evidence', function (): void {
    $email = passingResult([
        'summary' => 'Students report this, and the office can be reached at help.desk@campus.edu for updates.',
    ]);
    $atSign = passingResult([
        'summary' => 'Students ask the office directly @ the counter to find out whether a request was resolved.',
    ]);
    $honorific = passingResult([
        'summary' => 'Students wait at the counter while Mr. Santos looks up whether a request was resolved.',
    ]);

    foreach ([$email, $atSign, $honorific] as $result) {
        $outcome = explanationValidator()->validate($result, validatorPackage());

        expect($outcome['ok'])->toBeFalse()
            ->and($outcome['rule'])->toBe(ClusterExplanationValidator::RULE_PERSONAL_DATA);
    }
});

test('rule four rejects phone numbers, which the number rule may catch first', function (): void {
    $phone = passingResult([
        'summary' => 'Students call 09171234567 to find out whether their request was already received.',
    ]);
    $plus63 = passingResult([
        'summary' => 'Students can message the office on +639171234567 to ask whether a request was resolved.',
    ]);

    foreach ([$phone, $plus63] as $result) {
        $outcome = explanationValidator()->validate($result, validatorPackage());

        // Digits are checked before personal data, so a phone number may be
        // rejected as an ungrounded number. Either way it is never accepted.
        expect($outcome['ok'])->toBeFalse()
            ->and($outcome['rule'])->toBeIn([
                ClusterExplanationValidator::RULE_PERSONAL_DATA,
                ClusterExplanationValidator::RULE_NUMBERS,
            ]);
    }
});

test('rule four allows an honorific that already appears in the evidence', function (): void {
    $package = validatorPackage();
    $package['evidence'][0]['current_process'] = 'Report verbally to Ms. Cruz at the desk';

    $result = passingResult([
        'summary' => 'Students report being told to speak to Ms. Cruz at the desk, with no written record kept.',
    ]);

    expect(explanationValidator()->validate($result, $package)['ok'])->toBeTrue();
});

test('rule five rejects internal identifiers and scoring language', function (): void {
    $unclassified = passingResult([
        'summary' => 'Students report this, and the problem is currently marked unclassified by the office.',
    ]);
    $clusterKey = passingResult([
        'summary' => 'Students report this, and the internal cluster key request tracking was used for it.',
    ]);
    $hexRun = passingResult([
        'summary' => 'Students report this, and the office reference cafebabecafe was quoted to them.',
    ]);
    $score = passingResult([
        'summary' => 'Students report this, and the office told them the problem has a very high score overall.',
    ]);

    foreach ([$unclassified, $clusterKey, $hexRun, $score] as $result) {
        $outcome = explanationValidator()->validate($result, validatorPackage());

        expect($outcome['ok'])->toBeFalse()
            ->and($outcome['rule'])->toBe(ClusterExplanationValidator::RULE_INTERNALS);
    }
});

test('rule five allows a leak word that already appears in the evidence', function (): void {
    $package = validatorPackage();
    $package['evidence'][0]['description'] = 'Students are told to check the request tracking score board in person.';

    $result = passingResult([
        'summary' => 'Students are told to check the request tracking score board in person before asking again.',
    ]);

    expect(explanationValidator()->validate($result, $package)['ok'])->toBeTrue();
});

test('an unfamiliar capitalised phrase warns but never fails validation', function (): void {
    $result = passingResult([
        'summary' => 'Students mention the Registrar Office and Student Affairs Desk when they follow up in person.',
    ]);

    $outcome = explanationValidator()->validate($result, validatorPackage());

    expect($outcome['ok'])->toBeTrue()
        ->and($outcome['result'])->not->toBeNull()
        ->and($outcome['warnings'])->toBe([ClusterExplanationValidator::WARNING_UNFAMILIAR_NAMES]);
});

test('a fully grounded response produces no warnings', function (): void {
    expect(explanationValidator()->validate(passingResult(), validatorPackage())['warnings'])->toBe([]);
});
