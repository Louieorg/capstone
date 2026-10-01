<?php

use App\Services\IdeaGeneratorService;
use Illuminate\Support\Collection;

test('idea generation keeps the department out of the title but in the description', function () {
    $service = new IdeaGeneratorService;

    $feedbacks = new Collection([
        (object) [
            'title' => 'Network downtime in labs',
            'description' => 'Internet connection is slow during classes and lab work.',
            'impact' => 'Students cannot complete online activities on time.',
            'department' => 'CICS',
            'affected_group' => ['Students'],
            'current_process' => 'Manual or paper-based process',
        ],
        (object) [
            'title' => 'Weak wifi signal',
            'description' => 'The network is unstable and often disconnects users.',
            'impact' => 'Faculty and students lose access to online resources.',
            'department' => 'CICS',
            'affected_group' => ['Students'],
            'current_process' => 'Manual or paper-based process',
        ],
    ]);

    $idea = $service->generate('network monitoring', 'Facilities', $feedbacks, 2, 8, 3, 3);

    expect($idea['title'])->not->toContain('CICS')
        ->and($idea['title'])->not->toContain('for CICS');

    expect($idea['description'])->toContain('in the CICS');
    expect($idea['general_objective'])->toContain('in the CICS');
});

test('idea generation never appends a multiple departments phrase to the title', function () {
    $service = new IdeaGeneratorService;

    $feedbacks = new Collection([
        (object) [
            'title' => 'Record request delays',
            'description' => 'Students wait too long for requests to be processed.',
            'impact' => 'Transactions are delayed for users.',
            'department' => 'Registrar',
            'affected_group' => ['Students'],
            'current_process' => 'Send an email or message',
        ],
        (object) [
            'title' => 'Guidance request delays',
            'description' => 'Appointments and updates take too long to process.',
            'impact' => 'Students do not know the status of their requests.',
            'department' => 'Guidance Office',
            'affected_group' => ['Students'],
            'current_process' => 'Send an email or message',
        ],
    ]);

    $idea = $service->generate('request tracking', 'Academic Process', $feedbacks, 2, 5, 2, 2);

    expect($idea['title'])->not->toContain('across Multiple Departments')
        ->and($idea['title'])->not->toContain('across multiple departments');

    expect($idea['description'])->toContain('across multiple departments');
    expect($idea['general_objective'])->toContain('across multiple departments');
});

test('idea generation formats internal cluster keys for people', function (): void {
    $feedbacks = new Collection([
        (object) [
            'title' => 'Laboratory computer availability',
            'description' => 'Personnel manually check workstation and equipment availability.',
            'impact' => 'Students cannot identify available computers.',
            'department' => 'CICS',
            'affected_group' => ['Students'],
            'current_process' => 'Manual or paper-based process',
        ],
    ]);

    $idea = (new IdeaGeneratorService)->generate(
        'laboratory_equipment_monitoring',
        'Facilities',
        $feedbacks,
        1,
        10,
        3,
        3
    );

    expect($idea['title'])->not->toContain('_')
        ->and($idea['title'])->not->toContain('unclassified')
        ->and($idea['title'])->not->toBe('')
        ->and($idea['cluster_key'])->toBe('laboratory_equipment_monitoring');
});

test('community opportunity descriptions retain community evidence wording', function (): void {
    $feedbacks = new Collection([
        (object) [
            'title' => 'Community request tracking delays',
            'description' => 'Students wait too long for request status updates.',
            'impact' => 'Students cannot plan around delayed requests.',
            'department' => 'Registrar',
            'affected_group' => ['Students'],
            'current_process' => 'Manual or paper-based process',
            'office_id' => null,
        ],
    ]);

    $idea = (new IdeaGeneratorService)->generate('request tracking', 'Academic Process', $feedbacks, 1, 41, 3, 3);

    expect($idea['description'])->toContain('Backed by 1 community reports and 41 upvotes');
});

test('office opportunity descriptions identify office-associated evidence', function (): void {
    $feedbacks = new Collection([
        (object) [
            'title' => 'Office request tracking delays',
            'description' => 'Staff manually track request status updates for students.',
            'impact' => 'Students cannot plan around delayed requests.',
            'department' => 'Registrar',
            'affected_group' => ['Students'],
            'current_process' => 'Manual or paper-based process',
            'office_id' => 7,
        ],
    ]);

    $idea = (new IdeaGeneratorService)->generate('request tracking', 'Academic Process', $feedbacks, 1, 41, 3, 3);

    expect($idea['description'])
        ->toContain('Backed by 1 reports associated with this office and 41 upvotes')
        ->not->toContain('community reports');
});
