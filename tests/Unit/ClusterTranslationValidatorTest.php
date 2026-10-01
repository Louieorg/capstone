<?php

use App\Services\ClusterTranslationValidator;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

uses(TestCase::class);

afterEach(function (): void {
    Model::unsetConnectionResolver();
});

function translationValidator(): ClusterTranslationValidator
{
    return app(ClusterTranslationValidator::class);
}

/**
 * @return array<string, mixed>
 */
function englishSource(array $overrides = []): array
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

/**
 * @return array<string, mixed>
 */
function filipinoResult(array $overrides = []): array
{
    return array_replace([
        'summary' => 'Paulit-ulit na personal na nagfo-follow up ang mga estudyante dahil hindi nila nakikita kung natanggap o naresolba na ang kanilang kahilingan.',
        'patterns' => [
            'Ang mga update sa status ay sinasabi nang pasalita sa halip na ilathala para sa lahat.',
            'Ang parehong alalahanin ay muling ibinabangon dahil walang naitatalang talaan.',
        ],
        'experiences' => [
            [
                'title' => 'Pumipila para sa kasagutan',
                'body' => 'Naghihintay ang mga estudyante sa oras ng klase upang tanungin ang mga kawani kung natanggap o naresolba na ang kanilang kahilingan.',
            ],
        ],
    ], $overrides);
}

test('a faithful Filipino rendering is accepted', function (): void {
    $result = translationValidator()->validate(filipinoResult(), englishSource());

    expect($result['ok'])->toBeTrue()
        ->and($result['rule'])->toBeNull()
        ->and(array_keys($result['result']))->toBe(['summary', 'patterns', 'experiences'])
        ->and($result['result']['experiences'][0])->toHaveKeys(['title', 'body']);
});

test('invalid json shape is rejected', function (): void {
    $missing = filipinoResult();
    unset($missing['patterns']);

    $extra = filipinoResult(['recommendation' => 'Highly Recommended']);

    $badExperience = filipinoResult(['experiences' => [['title' => 'Pumipila para sa kasagutan']]]);

    foreach ([$missing, $extra, $badExperience] as $result) {
        $outcome = translationValidator()->validate($result, englishSource());

        expect($outcome['ok'])->toBeFalse()
            ->and($outcome['rule'])->toBe(ClusterTranslationValidator::RULE_SHAPE)
            ->and($outcome['result'])->toBeNull();
    }
});

test('wrong item counts are rejected', function (): void {
    $dropped = filipinoResult(['patterns' => [
        'Ang mga update sa status ay sinasabi nang pasalita sa halip na ilathala para sa lahat.',
    ]]);

    $outcome = translationValidator()->validate($dropped, englishSource());

    expect($outcome['ok'])->toBeFalse()
        ->and($outcome['rule'])->toBe(ClusterTranslationValidator::RULE_SHAPE);
});

test('changed numbers are rejected', function (): void {
    $source = englishSource(['summary' => 'Students filed 3 reports and waited 35% longer for an answer that never arrived here.']);

    $changed = filipinoResult(['summary' => 'Ang mga estudyante ay nagsumite ng 4 na ulat at naghintay nang 53% nang mas matagal para sa kasagutang hindi dumating dito.']);

    $outcome = translationValidator()->validate($changed, $source);

    expect($outcome['ok'])->toBeFalse()
        ->and($outcome['rule'])->toBe(ClusterTranslationValidator::RULE_NUMBERS);
});

test('preserved numbers are accepted', function (): void {
    $source = englishSource(['summary' => 'Students filed 3 reports and waited 35% longer for an answer that never arrived here.']);

    $kept = filipinoResult(['summary' => 'Ang mga estudyante ay nagsumite ng 3 na ulat at naghintay nang 35% nang mas matagal para sa kasagutang hindi dumating dito.']);

    $outcome = translationValidator()->validate($kept, $source);

    expect($outcome['ok'])->toBeTrue();
});

test('unfamiliar capitalized names and offices are hard personal data failures', function (): void {
    $introduced = filipinoResult(['patterns' => [
        'Ang mga update sa status ay sinabi ni Juan Dela Cruz nang pasalita sa halip na ilathala.',
        'Ang parehong alalahanin ay muling ibinabangon dahil walang naitatalang talaan.',
    ]]);

    $office = filipinoResult([
        'summary' => 'Ang mga estudyante ay pumupunta sa Student Services Office para sa tulong sa proseso.',
    ]);

    foreach ([$introduced, $office] as $result) {
        $outcome = translationValidator()->validate($result, englishSource());

        expect($outcome['ok'])->toBeFalse()
            ->and($outcome['rule'])->toBe(ClusterTranslationValidator::RULE_PERSONAL_DATA);
    }
});

test('personal identifiers introduced by the translation are rejected as personal data', function (): void {
    foreach ([
        'Ang tanggapan ay matatagpuan sa help.desk@campus.edu para sa update sa proseso.',
        'Ang tanggapan ay matatagpuan @ sa gusali para sa update sa proseso.',
        'Tumawag sa +63 9123456789 para sa update sa proseso.',
        'Tumawag sa 09123456789 para sa update sa proseso.',
        'Si Dr. Santos ay nag-ulat ng paulit-ulit na problema sa proseso.',
        'Si Engr. Reyes ay nag-ulat ng paulit-ulit na problema sa proseso.',
    ] as $summary) {
        $outcome = translationValidator()->validate(
            filipinoResult(['summary' => $summary]),
            englishSource()
        );

        expect($outcome['ok'])->toBeFalse()
            ->and($outcome['rule'])->toBe(ClusterTranslationValidator::RULE_PERSONAL_DATA);
    }
});

test('personal data already present in the English source is allowed exactly', function (): void {
    $cases = [
        [
            'source' => 'Students can contact help.desk@campus.edu for request status updates today.',
            'translation' => 'Maaaring makipag-ugnayan sa help.desk@campus.edu para sa update sa kahilingan.',
        ],
        [
            'source' => 'Students can find the office @ the main counter for request status updates.',
            'translation' => 'Matatagpuan ang tanggapan @ sa pangunahing counter para sa update.',
        ],
        [
            'source' => 'Students can call +63 9123456789 for request status updates today.',
            'translation' => 'Maaaring tumawag sa +63 9123456789 para sa update sa kahilingan.',
        ],
        [
            'source' => 'Students can call 09123456789 for request status updates today.',
            'translation' => 'Maaaring tumawag sa 09123456789 para sa update sa kahilingan.',
        ],
        [
            'source' => 'Dr. Santos reported recurring delays in the request process today.',
            'translation' => 'Iniulat ni Dr. Santos ang paulit-ulit na problema sa proseso.',
        ],
    ];

    foreach ($cases as $case) {
        $outcome = translationValidator()->validate(
            filipinoResult(['summary' => $case['translation']]),
            englishSource(['summary' => $case['source']])
        );

        expect($outcome['ok'])->toBeTrue();
    }
});

test('ordinary English and Filipino words ending in ms are accepted', function (): void {
    foreach (['problems.', 'systems.', 'items.'] as $word) {
        $english = englishSource(['summary' => "Students report recurring delays in {$word}."]);
        $filipino = filipinoResult(['summary' => "Ang mga estudyante ay nag-uulat ng mga {$word}"]);

        expect(translationValidator()->validate($filipino, $english)['ok'])->toBeTrue();
    }
});

test('a capitalized name already in the English source is not warned', function (): void {
    $source = englishSource(['summary' => 'Juan Dela Cruz reported recurring service delays in the request process.']);
    $result = filipinoResult(['summary' => 'Ang mga estudyante ay nag-uulat tungkol kay Juan Dela Cruz sa proseso.']);

    $outcome = translationValidator()->validate($result, $source);

    expect($outcome['ok'])->toBeTrue()
        ->and($outcome['rule'])->toBeNull();
});

test('sentence-initial capitals and ordinary Filipino loanwords are accepted', function (): void {
    $result = filipinoResult([
        'summary' => 'Ang mga estudyante ay gumagamit ng online status form para sa proseso.',
    ]);

    $outcome = translationValidator()->validate($result, englishSource());

    expect($outcome['ok'])->toBeTrue()
        ->and($outcome['rule'])->toBeNull();
});

test('the English source copied back unchanged is rejected', function (): void {
    $outcome = translationValidator()->validate(englishSource(), englishSource());

    expect($outcome['ok'])->toBeFalse()
        ->and($outcome['rule'])->toBe(ClusterTranslationValidator::RULE_NOT_FILIPINO);
});

test('internals leaked into the translation are rejected', function (): void {
    $leaked = filipinoResult(['summary' => 'Paulit-ulit na nagfo-follow up ang mga estudyante dahil sa cluster key abc123def456 na hindi nakikita ng lahat.']);

    $outcome = translationValidator()->validate($leaked, englishSource());

    expect($outcome['ok'])->toBeFalse()
        ->and($outcome['rule'])->toBe(ClusterTranslationValidator::RULE_INTERNALS);
});
