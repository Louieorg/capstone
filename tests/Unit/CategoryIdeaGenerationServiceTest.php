<?php

use App\Models\Feedback;
use App\Services\CategoryIdeaGenerationService;

test('the extracted evaluation scoring matches the previous dss inputs', function (): void {
    $service = new CategoryIdeaGenerationService;

    $evaluation = $service->evaluateIdea(1, 0, 3.0, 2.0, 'Manual or paper-based process');

    expect($evaluation)->toMatchArray([
        'feasibility' => 3,
        'impact' => 2,
        'complexity' => 2,
        'innovation' => 4,
        'overall_score' => 2.65,
        'recommendation' => 'Needs Improvement',
    ]);
});

test('the extracted scoring helpers keep the existing report and vote behaviour', function (): void {
    $service = new CategoryIdeaGenerationService;

    $feedback = new Feedback([
        'frequency' => 'Often',
        'affected_users' => '200-500',
        'affected_group' => ['Students', 'Staff'],
    ]);

    expect($service->averageFrequencyScore(collect([$feedback])))->toBe(3.0)
        ->and($service->averageImpactScore(collect([$feedback])))->toBe(3.5);
});

test('the extracted text helpers keep the existing deduplication behaviour', function (): void {
    $service = new CategoryIdeaGenerationService;

    expect($service->normalizedText('  Queue   Management  '))->toBe('queue management')
        ->and($service->similarityScore('', 'anything'))->toBe(0.0)
        ->and($service->similarityScore('queue management', 'queue management'))->toBe(100.0);
});

test('only approved unflagged capstone-worthy problems are institutionally validated', function (): void {
    $service = new CategoryIdeaGenerationService;

    $approved = new Feedback([
        'status' => 'approved',
        'is_flagged' => false,
        'is_capstone_worthy' => true,
    ]);

    $pending = new Feedback([
        'status' => 'pending',
        'is_flagged' => false,
        'is_capstone_worthy' => true,
    ]);

    $rejected = new Feedback([
        'status' => 'rejected',
        'is_flagged' => false,
        'is_capstone_worthy' => true,
    ]);

    $flagged = new Feedback([
        'status' => 'approved',
        'is_flagged' => true,
        'is_capstone_worthy' => true,
    ]);

    $community = new Feedback([
        'status' => 'approved',
        'is_flagged' => false,
        'is_capstone_worthy' => false,
    ]);

    expect($service->isInstitutionallyValidated($approved))->toBeTrue()
        ->and($service->isInstitutionallyValidated($pending))->toBeFalse()
        ->and($service->isInstitutionallyValidated($rejected))->toBeFalse()
        ->and($service->isInstitutionallyValidated($flagged))->toBeFalse()
        ->and($service->isInstitutionallyValidated($community))->toBeFalse();
});

test('institutional validation never runs for pending or rejected records', function (): void {
    $service = new CategoryIdeaGenerationService;

    $pending = new Feedback([
        'category' => 'Facilities',
        'status' => 'pending',
        'is_flagged' => false,
        'is_capstone_worthy' => true,
    ]);

    $rejected = new Feedback([
        'category' => 'Facilities',
        'status' => 'rejected',
        'is_flagged' => false,
        'is_capstone_worthy' => true,
    ]);

    expect($service->generateForInstitutionalValidation($pending))->toBeNull()
        ->and($service->generateForInstitutionalValidation($rejected))->toBeNull();
});
