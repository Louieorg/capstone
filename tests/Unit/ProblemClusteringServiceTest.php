<?php

use App\Services\ClusteringService;
use Illuminate\Support\Collection;

test('it separates distinct facilities problem types into explainable clusters', function (): void {
    $feedbacks = new Collection([
        (object) [
            'category' => 'Facilities',
            'title' => 'Manual facility inspection records',
            'description' => 'Facility inspection results and historical maintenance records are difficult to organize.',
            'impact' => 'Recurring facility problems cannot be identified reliably.',
        ],
        (object) [
            'category' => 'Facilities',
            'title' => 'Facility request status is unclear',
            'description' => 'Users cannot track whether a facility concern was received, assigned, or resolved.',
            'impact' => 'Request status updates depend on manual communication.',
        ],
        (object) [
            'category' => 'Facilities',
            'title' => 'CICS laboratory equipment monitoring',
            'description' => 'Laboratory personnel manually check computer availability, workstation status, and occupancy.',
            'impact' => 'Equipment under maintenance is not visible to laboratory users.',
        ],
    ]);

    $clusters = app(ClusteringService::class)->group($feedbacks);

    expect($clusters->keys()->all())->toEqualCanonicalizing([
        'facility_inspection_records',
        'facility_request_tracking',
        'laboratory_equipment_monitoring',
    ]);
});

test('it groups related network connectivity reports together', function (): void {
    $feedbacks = new Collection([
        (object) [
            'category' => 'Network & Connectivity',
            'title' => 'Internet is slow',
            'description' => 'Internet connectivity is slow during class hours.',
            'impact' => 'Students cannot access online learning resources.',
        ],
        (object) [
            'category' => 'Network & Connectivity',
            'title' => 'No internet in school',
            'description' => 'The campus network is unavailable in several buildings.',
            'impact' => 'Classes cannot use online systems.',
        ],
    ]);

    $clusters = app(ClusteringService::class)->group($feedbacks);

    expect($clusters)->toHaveCount(1)
        ->and($clusters->keys()->first())->toBe('campus_network_connectivity')
        ->and($clusters->first())->toHaveCount(2);
});
