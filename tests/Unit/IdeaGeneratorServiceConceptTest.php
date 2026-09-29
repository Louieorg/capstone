<?php

use App\Services\IdeaGeneratorService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Minimal DB-free IdeaGeneratorService with a controlled title registry.
 */
function conceptService(): IdeaGeneratorService
{
    return new class extends IdeaGeneratorService
    {
        protected function existingTitles(): Collection
        {
            return new Collection;
        }
    };
}

/**
 * @param  array<int, string>  $titles
 */
function conceptServiceWithTitles(array $titles): IdeaGeneratorService
{
    return new class($titles) extends IdeaGeneratorService
    {
        public function __construct(public array $titles) {}

        protected function existingTitles(): Collection
        {
            return new Collection($this->titles);
        }
    };
}

function makeConceptFeedback(array $attributes = []): object
{
    return (object) array_merge([
        'translated_title' => 'Reported campus problem',
        'translated_description' => 'People wait too long for this service.',
        'translated_impact' => 'Daily work is delayed for everyone involved.',
        'department' => 'CICS',
        'affected_group' => ['Students'],
        'current_process' => 'Manual or paper-based process',
        'frequency' => 'Often',
        'affected_users' => '200-500',
    ], $attributes);
}

/**
 * @param  array<int, object>  $feedbacks
 * @param  array<string, mixed>  $options
 * @return array<string, mixed>
 */
function generateConceptIdea(string $clusterKey, array $feedbacks = [], string $category = 'Facilities', array $options = []): array
{
    $service = $options['service'] ?? conceptService();

    return $service->generate(
        $clusterKey,
        $category,
        new Collection($feedbacks === [] ? [makeConceptFeedback()] : $feedbacks),
        $options['reports'] ?? 3,
        $options['votes'] ?? 12,
        $options['frequency'] ?? 3.0,
        $options['impact'] ?? 3.0,
        $clusterKey
    );
}

/**
 * @return array<int, string>
 */
function knownConceptClusters(): array
{
    return [
        'facility_inspection_records',
        'facility_request_tracking',
        'laboratory_equipment_monitoring',
        'campus_network_connectivity',
        'request_tracking',
        'records_management',
        'scheduling_coordination',
    ];
}

test('solution concept generation is deterministic for the same cluster and evidence', function (): void {
    $first = generateConceptIdea('records_management', [makeConceptFeedback()], 'Academic Process');
    $second = generateConceptIdea('records_management', [makeConceptFeedback()], 'Academic Process');

    expect($first['title'])->toBe($second['title'])
        ->and($first['project_name'])->toBe($second['project_name'])
        ->and($first['concept'])->toBe($second['concept']);
});

test('the laboratory cluster produces equipment related concepts', function (): void {
    $idea = generateConceptIdea('laboratory_equipment_monitoring', [
        makeConceptFeedback([
            'translated_title' => 'Laboratory equipment status is unknown',
            'translated_description' => 'Laboratory personnel check equipment status and workstation availability by hand.',
            'translated_impact' => 'Students cannot tell which computers are under maintenance.',
        ]),
    ]);

    expect($idea['concept']['primary_key'])->toBeIn([
        'equipment_monitoring',
        'equipment_availability',
        'laboratory_usage_monitoring',
    ])
        ->and($idea['title'])->toMatch('/Equipment|Laboratory/');
});

test('the scheduling cluster produces scheduling related concepts', function (): void {
    $idea = generateConceptIdea('scheduling_coordination', [
        makeConceptFeedback([
            'translated_title' => 'Class schedule conflicts',
            'translated_description' => 'Schedules overlap and time slots clash during enrollment.',
            'translated_impact' => 'Students and faculty lose time resolving double bookings.',
        ]),
    ], 'Scheduling');

    expect($idea['concept']['primary_key'])->toBeIn([
        'scheduling_system',
        'appointment_system',
        'coordination_platform',
    ])
        ->and($idea['title'])->toMatch('/Scheduling|Appointment|Coordination/');
});

test('the request tracking cluster produces tracking or service concepts', function (): void {
    $idea = generateConceptIdea('request_tracking', [
        makeConceptFeedback([
            'translated_title' => 'Students cannot track request status',
            'translated_description' => 'There is no update or follow up once a request is filed.',
            'translated_impact' => 'Students keep asking staff for updates.',
            'department' => 'Registrar',
        ]),
    ], 'Academic Process');

    expect($idea['concept']['primary_key'])->toBeIn([
        'request_tracking',
        'service_request_portal',
        'workflow_management',
    ])
        ->and($idea['title'])->toMatch('/Request|Tracking|Portal|Workflow|Service/');
});

test('the cluster key prevents unrelated text noise from changing the concept', function (): void {
    $noisy = [
        makeConceptFeedback([
            'translated_title' => 'Wifi and network problems everywhere',
            'translated_description' => 'The internet connection is unstable and the network keeps dropping for students.',
            'translated_impact' => 'Nobody can connect to the wifi signal.',
        ]),
    ];

    $records = generateConceptIdea('records_management', $noisy, 'Academic Process');

    expect($records['concept']['primary_key'])->toBeIn([
        'records_management',
        'document_retrieval',
        'digital_archive',
    ])
        ->and($records['title'])->not->toMatch('/Network|Connectivity|Wifi|Wi-Fi/');

    $library = generateConceptIdea('library_unclassified_3a0f8534aaef', $noisy, 'Library');

    expect($library['concept']['fallback_used'])->toBeTrue()
        ->and($library['title'])->toMatch('/Library/')
        ->and($library['title'])->not->toMatch('/Network|Connectivity|Wifi|Wi-Fi/');
});

test('titles never gain gratuitous technology prefixes', function (): void {
    foreach (knownConceptClusters() as $clusterKey) {
        $idea = generateConceptIdea($clusterKey, [
            makeConceptFeedback([
                'translated_title' => 'Everything about this service is slow and manual',
                'translated_description' => 'Students queue for hours because records, schedules, equipment and requests are all handled on paper.',
                'translated_impact' => 'This affects more than 500 students daily.',
                'affected_group' => ['Students'],
            ]),
        ]);

        expect($idea['title'])->not->toMatch('/Web-Based|Mobile-First|Smart|Digital|Automated/');
    }
});

test('titles never append the dominant department automatically', function (): void {
    $idea = generateConceptIdea('laboratory_equipment_monitoring', [
        makeConceptFeedback(['department' => 'CICS']),
        makeConceptFeedback(['department' => 'CICS']),
    ]);

    expect($idea['title'])->not->toContain('for CICS')
        ->and($idea['title'])->not->toContain('across Multiple Departments')
        ->and($idea['title'])->not->toContain('across multiple departments');
});

test('titles stay within a medium length for every known cluster', function (): void {
    foreach (knownConceptClusters() as $clusterKey) {
        $idea = generateConceptIdea($clusterKey, [
            makeConceptFeedback([
                'translated_title' => 'Students cannot track request status or book a schedule',
                'translated_description' => 'Records, files, equipment status, occupancy, availability, maintenance requests, inspection checklists and appointments are handled manually.',
                'translated_impact' => 'Wifi signal drops, queues are long and files get lost.',
                'affected_group' => ['Students', 'Staff'],
            ]),
        ]);

        $words = count(array_filter(preg_split('/\s+/', (string) $idea['title']) ?: []));

        expect($words)->toBeGreaterThanOrEqual(6)
            ->and($words)->toBeLessThanOrEqual(12);
    }
});

test('the current process contributes to candidate scoring', function (): void {
    $strong = generateConceptIdea('laboratory_equipment_monitoring', [
        makeConceptFeedback(['current_process' => 'Manual or paper-based process']),
    ]);

    $weak = generateConceptIdea('laboratory_equipment_monitoring', [
        makeConceptFeedback(['current_process' => 'Send an email or message']),
    ]);

    expect($strong['concept']['evidence'])->toContain('Current process: Manual or paper-based process')
        ->and($strong['concept']['score'])->toBeGreaterThan($weak['concept']['score']);
});

test('the current process can promote another declared candidate', function (): void {
    $idea = generateConceptIdea('laboratory_equipment_monitoring', [
        makeConceptFeedback([
            'translated_title' => 'Laboratory computer availability',
            'translated_description' => 'Students cannot tell which computers are available or in use.',
            'translated_impact' => 'Time is wasted looking for an available computer.',
            'affected_group' => ['Students'],
            'current_process' => 'Report verbally to staff',
        ]),
    ]);

    expect($idea['concept']['primary_key'])->toBe('equipment_availability');
});

test('the affected group contributes to a relevant candidate', function (): void {
    $fixture = [
        'translated_title' => 'Appointment booking is disorganised',
        'translated_description' => 'Students cannot reserve an appointment slot because there is no timetable for booking.',
        'translated_impact' => 'Students queue to reserve an appointment slot.',
    ];

    $students = generateConceptIdea('scheduling_coordination', [
        makeConceptFeedback($fixture + ['affected_group' => ['Students']]),
    ], 'Scheduling');

    $missing = generateConceptIdea('scheduling_coordination', [
        makeConceptFeedback($fixture + ['affected_group' => ['Administration']]),
    ], 'Scheduling');

    expect($students['concept']['evidence'])->toContain('Affected group: Students')
        ->and($missing['concept']['evidence'])->not->toContain('Affected group: Administration');
});

test('department context can strengthen but never invent a concept', function (): void {
    $declared = generateConceptIdea('laboratory_equipment_monitoring', [
        makeConceptFeedback(['department' => 'CICS']),
    ]);

    $undeclared = generateConceptIdea('laboratory_equipment_monitoring', [
        makeConceptFeedback(['department' => 'Registrar']),
    ]);

    expect($declared['concept']['primary_key'])->toBe($undeclared['concept']['primary_key'])
        ->and($declared['concept']['score'])->toBeGreaterThan($undeclared['concept']['score'])
        ->and($undeclared['concept']['evidence'])->not->toContain('Department context: Registrar')
        ->and($declared['title'])->not->toContain('Registrar');
});

test('frequency and impact may only decide a close race', function (): void {
    $neutral = makeConceptFeedback();

    $high = generateConceptIdea('laboratory_equipment_monitoring', [$neutral], 'Facilities', [
        'frequency' => 4.0,
        'impact' => 4.0,
    ]);

    $low = generateConceptIdea('laboratory_equipment_monitoring', [$neutral], 'Facilities', [
        'frequency' => 1.0,
        'impact' => 1.0,
    ]);

    expect(collect($high['concept']['evidence'])->contains(
        fn (string $entry): bool => str_contains($entry, 'Close race')
    ))->toBeTrue()
        ->and(collect($low['concept']['evidence'])->contains(
            fn (string $entry): bool => str_contains($entry, 'Close race')
        ))->toBeFalse();
});

test('frequency and impact never override strong problem evidence', function (): void {
    $fixture = [
        makeConceptFeedback([
            'translated_title' => 'Equipment status is unknown',
            'translated_description' => 'Computers under maintenance are not marked and equipment status is checked by hand.',
            'translated_impact' => 'Students use malfunctioning computers all day.',
        ]),
    ];

    $high = generateConceptIdea('laboratory_equipment_monitoring', $fixture, 'Facilities', [
        'frequency' => 4.0,
        'impact' => 4.0,
    ]);

    $low = generateConceptIdea('laboratory_equipment_monitoring', $fixture, 'Facilities', [
        'frequency' => 1.0,
        'impact' => 1.0,
    ]);

    expect($high['concept']['primary_key'])->toBe($low['concept']['primary_key'])
        ->and($high['concept']['primary_key'])->toBe('equipment_monitoring')
        ->and(collect($high['concept']['evidence'])->contains(
            fn (string $entry): bool => str_contains($entry, 'Close race')
        ))->toBeFalse();
});

test('every idea exposes an alternative concept direction', function (): void {
    $idea = generateConceptIdea('laboratory_equipment_monitoring', [makeConceptFeedback()]);

    expect($idea['concept']['alternatives'])->not->toBeEmpty();

    $alternative = $idea['concept']['alternatives'][0];

    expect($alternative)->toHaveKeys(['concept', 'concept_key', 'project_name', 'title', 'pattern'])
        ->and($alternative['concept_key'])->not->toBe($idea['concept']['primary_key'])
        ->and($alternative['title'])->not->toBe('');
});

test('a combined concept never contains more than two concepts', function (): void {
    $idea = generateConceptIdea('scheduling_coordination', [
        makeConceptFeedback([
            'translated_title' => 'Class schedule conflict and appointment booking',
            'translated_description' => 'Students cannot book an appointment because schedules overlap and clash.',
            'translated_impact' => 'Double booking wastes time.',
            'affected_group' => ['Students'],
            'department' => 'Registrar',
        ]),
    ], 'Scheduling');

    expect($idea['concept']['combined'])->toHaveCount(2)
        ->and($idea['concept']['combined'][0])->toBe('scheduling_system')
        ->and($idea['concept']['combined'][1])->toBe('appointment_system')
        ->and($idea['concept']['primary'])->toBe('Scheduling and Appointment System')
        ->and($idea['title'])->toContain('Scheduling and Appointment System');
});

test('project names come from the declared naming vocabulary', function (): void {
    $expected = [
        'laboratory_equipment_monitoring' => ['LabWatch', 'EquipGuard', 'LabMonitor'],
        'records_management' => ['DocuTrack', 'DocuFlow', 'RecordHub'],
        'scheduling_coordination' => ['TimeSync', 'SlotEase', 'ScheduleHub'],
        'request_tracking' => ['TrackPoint', 'TrackDesk', 'StatusHub'],
    ];

    foreach ($expected as $clusterKey => $names) {
        $idea = generateConceptIdea($clusterKey, [makeConceptFeedback()]);

        expect($names)->toContain($idea['project_name'])
            ->and($idea['title'])->toStartWith((string) $idea['project_name']);
    }
});

test('project name selection is stable and never random', function (): void {
    $titles = collect(range(1, 20))->map(
        fn (): ?string => generateConceptIdea('campus_network_connectivity', [makeConceptFeedback()])['title']
    )->unique();

    expect($titles)->toHaveCount(1);
});

test('a taken project name reuses the existing title for the same concept instead of rotating', function (): void {
    $original = generateConceptIdea('records_management', [makeConceptFeedback()], 'Academic Process');

    $service = conceptServiceWithTitles([$original['title']]);
    $regenerated = generateConceptIdea('records_management', [makeConceptFeedback()], 'Academic Process', [
        'service' => $service,
    ]);

    expect($regenerated['concept']['primary'])->toBe($original['concept']['primary'])
        ->and($regenerated['concept']['primary_key'])->toBe($original['concept']['primary_key'])
        ->and($regenerated['project_name'])->toBe($original['project_name'])
        ->and($regenerated['title'])->toBe($original['title']);
});

test('a descriptive title is returned when every declared name is taken', function (): void {
    $service = conceptServiceWithTitles([
        'InspectPro: Existing Facility Idea',
        'FacilCheck: Existing Facility Check Idea',
        'SiteLog: Existing Site Log Idea',
    ]);

    $idea = generateConceptIdea('facility_inspection_records', [makeConceptFeedback()], 'Facilities', [
        'service' => $service,
    ]);

    expect($idea['project_name'])->toBeNull()
        ->and($idea['title'])->not->toBe('')
        ->and($idea['title'])->toContain($idea['concept']['primary']);
});

test('an unknown cluster key falls back to the category concept', function (): void {
    $idea = generateConceptIdea('some_unknown_cluster', [makeConceptFeedback()], 'Academic Process');

    expect($idea['concept']['fallback_used'])->toBeTrue()
        ->and($idea['concept']['primary_key'])->toBe('academic_process_service')
        ->and($idea['title'])->not->toBe('');

    $general = generateConceptIdea('some_unknown_cluster', [makeConceptFeedback()], 'Threshold Category');

    expect($general['concept']['fallback_used'])->toBeTrue()
        ->and($general['concept']['primary_key'])->toBe('campus_service_improvement')
        ->and($general['title'])->not->toBe('');
});

test('unclassified cluster keys never leak into the generated title', function (): void {
    $idea = generateConceptIdea('facility_request_tracking_unclassified_9d8f7c6b5a41', [makeConceptFeedback()], 'Facilities');

    expect($idea['concept']['fallback_used'])->toBeTrue()
        ->and($idea['title'])->not->toContain('_')
        ->and($idea['title'])->not->toContain('unclassified')
        ->and($idea['title'])->not->toContain('9d8f7c6b5a41');
});

test('the existing return contract is preserved and additive keys are added', function (): void {
    $idea = generateConceptIdea('request_tracking', [makeConceptFeedback()], 'Academic Process');

    expect($idea)->toHaveKeys([
        'title',
        'description',
        'general_objective',
        'specific_objectives',
        'explanation',
        'top_group',
        'concept',
        'project_name',
        'cluster_key',
    ])
        ->and($idea['explanation']['factors'])->toHaveKeys([
            'reports',
            'votes',
            'frequency_score',
            'impact_score',
            'top_affected_group',
        ])
        ->and($idea['concept'])->toHaveKeys([
            'primary',
            'primary_key',
            'score',
            'evidence',
            'combined',
            'alternatives',
            'fallback_used',
        ])
        ->and($idea['cluster_key'])->toBe('request_tracking');
});

// ══════════════════════════════════════════════
// STAGE 2.5 — NAME/CONCEPT ALIGNMENT, FALLBACK DIVERSITY, EVIDENCE SCOPE
// ══════════════════════════════════════════════

/**
 * @return array<string, mixed>
 */
function appointmentEvidence(): array
{
    return [
        'translated_title' => 'Students queue to reserve an appointment at the guidance office',
        'translated_description' => 'Bookings are only written on paper, so students queue for hours and repeat the same information.',
        'translated_impact' => 'Students lose study time waiting for their turn.',
        'department' => 'Guidance Office',
        'affected_group' => ['Students'],
        'current_process' => 'Report verbally to staff',
    ];
}

/**
 * @return array<string, mixed>
 */
function roomEvidence(): array
{
    return [
        'translated_title' => 'Room and venue availability is unknown to faculty',
        'translated_description' => 'Faculty coordinate room assignments through messages and the venue list is out of date.',
        'translated_impact' => 'Classes are moved at the last minute.',
        'department' => 'Registrar',
        'affected_group' => ['Faculty'],
        'current_process' => 'Send an email or message',
    ];
}

/**
 * @return array<string, mixed>
 */
function timetableEvidence(): array
{
    return [
        'translated_title' => 'Timetable conflicts and overlapping time slots',
        'translated_description' => 'The timetable is rebuilt by hand every term and time slots overlap.',
        'translated_impact' => 'Students are unsure which class they attend.',
        'department' => 'Registrar',
        'affected_group' => ['Students', 'Faculty'],
        'current_process' => 'Manual or paper-based process',
    ];
}

/**
 * @return array<string, mixed>
 */
function studentRequestEvidence(): array
{
    return [
        'translated_title' => 'Students walk in at the counter to submit a request form',
        'translated_description' => 'There is no online request portal, so every submission is written on paper.',
        'translated_impact' => 'Students queue at the counter during office hours.',
        'department' => 'Registrar',
        'affected_group' => ['Students'],
        'current_process' => 'No solution exists at all',
    ];
}

/**
 * Declared naming families. A project name may only come from the family
 * that matches the selected solution concept.
 *
 * @return array<string, array<int, string>>
 */
function namingFamilies(): array
{
    return [
        'laboratory' => ['Lab', 'Equip', 'LENS'],
        'queue' => ['Queue', 'Appoint', 'Care'],
        'room' => ['Room', 'Space', 'Coord'],
        'time' => ['Time', 'Slot', 'Schedule'],
    ];
}

function belongsToFamily(?string $name, string $family): bool
{
    foreach (namingFamilies()[$family] as $stem) {
        if (str_starts_with((string) $name, $stem)) {
            return true;
        }
    }

    return false;
}

/**
 * @return array<int, string>
 */
function libraryClusterKeys(): array
{
    return [
        'library_unclassified_aaaa1111bbbb',
        'library_unclassified_cccc2222dddd',
        'library_unclassified_eeee3333ffff',
        'library_unclassified_1234abcd5678',
        'library_unclassified_90ab12cd34ef',
        'library_unclassified_55fe66aa77bb',
    ];
}

test('queue and appointment evidence never receives a room or space project name', function (): void {
    $idea = generateConceptIdea('scheduling_coordination', [makeConceptFeedback(appointmentEvidence())], 'Scheduling');

    expect($idea['concept']['primary_key'])->toBe('appointment_system')
        ->and(belongsToFamily($idea['project_name'], 'queue'))->toBeTrue()
        ->and(belongsToFamily($idea['project_name'], 'room'))->toBeFalse()
        ->and(belongsToFamily($idea['project_name'], 'time'))->toBeFalse()
        ->and($idea['title'])->toMatch('/Appointment|Queue/')
        ->and($idea['title'])->not->toMatch('/Room|Space/');
});

test('room and venue evidence receives a room or space project name', function (): void {
    $idea = generateConceptIdea('scheduling_coordination', [makeConceptFeedback(roomEvidence())], 'Scheduling');

    expect($idea['concept']['primary_key'])->toBe('coordination_platform')
        ->and(belongsToFamily($idea['project_name'], 'room'))->toBeTrue()
        ->and(belongsToFamily($idea['project_name'], 'queue'))->toBeFalse()
        ->and($idea['title'])->toMatch('/Room|Venue/');
});

test('laboratory evidence always receives an equipment or laboratory project name', function (): void {
    $fixtures = [
        [
            'translated_title' => 'Laboratory equipment status is unknown',
            'translated_description' => 'Laboratory personnel check equipment status and workstation availability by hand.',
            'translated_impact' => 'Students cannot tell which computers are under maintenance.',
            'department' => 'CICS',
            'affected_group' => ['Staff', 'Faculty'],
            'current_process' => 'No solution exists at all',
        ],
        [
            'translated_title' => 'Laboratory computer availability',
            'translated_description' => 'Students cannot tell which computers are available or in use.',
            'translated_impact' => 'Time is wasted looking for an available computer.',
            'department' => 'CICS',
            'affected_group' => ['Students'],
            'current_process' => 'Report verbally to staff',
        ],
        [
            'translated_title' => 'Computer usage in the laboratory is not recorded',
            'translated_description' => 'Personnel keep a log sheet at the workstation and count utilization by hand.',
            'translated_impact' => 'The laboratory is overused during peak hours.',
            'department' => 'CICS',
            'affected_group' => ['Staff', 'Administration'],
            'current_process' => 'Manual or paper-based process',
        ],
    ];

    foreach ($fixtures as $fixture) {
        $idea = generateConceptIdea('laboratory_equipment_monitoring', [makeConceptFeedback($fixture)]);

        expect(belongsToFamily($idea['project_name'], 'laboratory'))->toBeTrue(
            (string) $idea['project_name'].' does not belong to the laboratory naming family for '.$idea['concept']['primary']
        );
    }
});

test('library fallbacks offer several distinct directions and never repeat the category name', function (): void {
    $titles = [];
    $concepts = [];

    foreach (libraryClusterKeys() as $clusterKey) {
        $idea = generateConceptIdea($clusterKey, [makeConceptFeedback()], 'Library');

        expect($idea['concept']['fallback_used'])->toBeTrue()
            ->and($idea['concept']['primary_key'])->toBe('library_service');

        $titles[] = (string) $idea['title'];
        $concepts[] = (string) $idea['concept']['primary'];

        expect($idea['title'])->toContain('Library')
            ->and(substr_count((string) $idea['title'], 'Library'))->toBe(1)
            ->and($idea['title'])->not->toContain('for Library Services')
            ->and($idea['title'])->not->toContain('Library Service System');
    }

    expect(count(array_unique($concepts)))->toBeGreaterThanOrEqual(2)
        ->and(count(array_unique($titles)))->toBeGreaterThanOrEqual(2);
});

test('a project name never crosses into another concept naming family', function (): void {
    $scenarios = [
        ['scheduling_coordination', appointmentEvidence(), 'queue'],
        ['scheduling_coordination', roomEvidence(), 'room'],
        ['scheduling_coordination', timetableEvidence(), 'time'],
    ];

    foreach ($scenarios as [$clusterKey, $evidence, $family]) {
        $idea = generateConceptIdea($clusterKey, [makeConceptFeedback($evidence)], 'Scheduling');

        expect(belongsToFamily($idea['project_name'], $family))->toBeTrue(
            (string) $idea['project_name'].' does not belong to the '.$family.' naming family for '.$idea['concept']['primary']
        );
    }
});

test('fallback direction rotation stays deterministic for the same evidence', function (): void {
    foreach (libraryClusterKeys() as $clusterKey) {
        $first = generateConceptIdea($clusterKey, [makeConceptFeedback()], 'Library');
        $second = generateConceptIdea($clusterKey, [makeConceptFeedback()], 'Library');

        expect($first['title'])->toBe($second['title'])
            ->and($first['project_name'])->toBe($second['project_name'])
            ->and($first['concept']['primary'])->toBe($second['concept']['primary']);
    }
});

test('a taken name inside a fallback direction keeps the concept and drops the brand', function (): void {
    $clusterKey = libraryClusterKeys()[0];
    $original = generateConceptIdea($clusterKey, [makeConceptFeedback()], 'Library');
    $service = conceptServiceWithTitles([$original['project_name'].': An Older Library Idea']);
    $renamed = generateConceptIdea($clusterKey, [makeConceptFeedback()], 'Library', [
        'service' => $service,
    ]);

    expect($renamed['concept']['primary'])->toBe($original['concept']['primary'])
        ->and($renamed['project_name'])->not->toBe($original['project_name'])
        ->and($renamed['title'])->not->toBe($original['title'])
        ->and($renamed['title'])->toContain($renamed['concept']['primary']);
});

test('title scope comes from the evidence instead of a generic campus scope', function (): void {
    $scenarios = [
        ['scheduling_coordination', 'Scheduling', appointmentEvidence(), 'Student Booking and Reservations'],
        ['scheduling_coordination', 'Scheduling', roomEvidence(), 'Room and Venue Availability'],
        ['scheduling_coordination', 'Scheduling', timetableEvidence(), 'Timetable and Time Slot Coordination'],
        ['request_tracking', 'Academic Process', studentRequestEvidence(), 'Student Service Submissions'],
    ];

    $scoped = 0;

    foreach ($scenarios as [$clusterKey, $category, $evidence, $scope]) {
        $title = (string) generateConceptIdea($clusterKey, [makeConceptFeedback($evidence)], $category)['title'];

        expect($title)->not->toContain('Campus Operations')
            ->and($title)->not->toContain('Campus Services')
            ->and($title)->not->toMatch('/for (CICS|Registrar|Guidance Office)$/');

        $usesEvidenceScope = ! str_contains($title, ' for ') || str_contains($title, ' for '.$scope);

        expect($usesEvidenceScope)->toBeTrue($title.' does not use the evidence scope "'.$scope.'"');

        $scoped += str_contains($title, ' for ') ? 1 : 0;
    }

    expect($scoped)->toBeGreaterThan(0);
});

test('every category fallback reads cleanly for an unclassified cluster', function (): void {
    $categories = [
        'Enrollment' => 'enrollment_unclassified_1a2b3c4d5e6f',
        'Academic Process' => 'academic_process_unclassified_2b3c4d5e6f7a',
        'Facilities' => 'facilities_unclassified_3c4d5e6f7a8b',
        'Library' => 'library_unclassified_aaaa1111bbbb',
        'Scheduling' => 'scheduling_unclassified_4d5e6f7a8b9c',
        'Other' => 'other_unclassified_5e6f7a8b9c0d',
        'Threshold Category' => 'threshold_category_unclassified_6f7a8b9c0d1e',
    ];

    $genericWords = ['system', 'platform'];

    foreach ($categories as $category => $clusterKey) {
        $idea = generateConceptIdea($clusterKey, [makeConceptFeedback()], $category);
        $title = (string) $idea['title'];
        $words = array_values(array_filter(preg_split('/\s+/', $title) ?: []));
        $scope = str_contains($title, ' for ') ? Str::afterLast($title, ' for ') : '';

        // The scope must add information, never restate the concept.
        $conceptWords = collect(preg_split('/\s+/', Str::lower((string) $idea['concept']['primary'])))
            ->diff($genericWords)
            ->unique()
            ->sort()
            ->values()
            ->all();
        $scopeWords = collect(preg_split('/\s+/', Str::lower($scope)))
            ->diff($genericWords)
            ->unique()
            ->sort()
            ->values()
            ->all();

        expect($idea['concept']['fallback_used'])->toBeTrue()
            ->and($scopeWords)->not->toBe($conceptWords)
            ->and($title)->not->toContain('for Library Services')
            ->and(count($words))->toBeGreaterThanOrEqual(5)
            ->and(count($words))->toBeLessThanOrEqual(12);
    }
});

test('generated titles stay within the idea title column length', function (): void {
    foreach (knownConceptClusters() as $clusterKey) {
        $idea = generateConceptIdea($clusterKey, [makeConceptFeedback()]);

        expect(strlen((string) $idea['title']))->toBeLessThanOrEqual(255)
            ->and($idea['title'])->not->toBe('');
    }
});

// ══════════════════════════════════════════════
// CONCEPT-LEVEL IDEMPOTENCY (duplicate-generation fix)
// ══════════════════════════════════════════════

test('the same cluster and concept always resolve to the same title across repeated runs', function (): void {
    $first = generateConceptIdea('records_management', [makeConceptFeedback()], 'Academic Process');

    $persisted = [$first['title']];

    for ($run = 0; $run < 3; $run++) {
        $again = generateConceptIdea('records_management', [makeConceptFeedback()], 'Academic Process', [
            'service' => conceptServiceWithTitles($persisted),
        ]);

        expect($again['title'])->toBe($first['title'])
            ->and($again['project_name'])->toBe($first['project_name'])
            ->and($again['concept']['primary_key'])->toBe($first['concept']['primary_key']);

        $persisted[] = $again['title'];
    }

    expect(array_unique($persisted))->toHaveCount(1);
});

test('reusing an existing title never manufactures a second title for the same concept', function (): void {
    $laboratoryEvidence = [
        makeConceptFeedback([
            'translated_title' => 'Laboratory equipment status is unknown',
            'translated_description' => 'Laboratory personnel check equipment status and workstation availability by hand.',
            'translated_impact' => 'Students cannot tell which computers are under maintenance.',
        ]),
    ];

    $original = generateConceptIdea('laboratory_equipment_monitoring', $laboratoryEvidence, 'Facilities');

    $again = generateConceptIdea('laboratory_equipment_monitoring', $laboratoryEvidence, 'Facilities', [
        'service' => conceptServiceWithTitles([$original['title']]),
    ]);

    expect($again['title'])->toBe($original['title'])
        ->and($again['project_name'])->toBe($original['project_name'])
        ->and($again['concept']['primary'])->toBe($original['concept']['primary']);
});

test('a descriptive title for an existing concept is reused rather than regenerated', function (): void {
    // These brands belong to other concepts, so this concept has no
    // presentation of its own yet and must still take a declared name.
    $otherConcepts = [
        'InspectPro: Existing Facility Idea',
        'FacilCheck: Existing Facility Check Idea',
        'SiteLog: Existing Site Log Idea',
    ];

    $idea = generateConceptIdea('facility_inspection_records', [makeConceptFeedback()], 'Facilities', [
        'service' => conceptServiceWithTitles($otherConcepts),
    ]);

    // Persist the brand-less presentation, then prove it is reused verbatim.
    $withoutBrand = generateConceptIdea('facility_inspection_records', [makeConceptFeedback()], 'Facilities', [
        'service' => conceptServiceWithTitles([...$otherConcepts, $idea['title']]),
    ]);

    expect($withoutBrand['title'])->toBe($idea['title'])
        ->and($withoutBrand['project_name'])->toBeNull();
});

test('a genuinely different concept still receives its own project name', function (): void {
    $laboratory = generateConceptIdea('laboratory_equipment_monitoring', [
        makeConceptFeedback([
            'translated_title' => 'Laboratory equipment status is unknown',
            'translated_description' => 'Laboratory personnel check equipment status and workstation availability by hand.',
            'translated_impact' => 'Students cannot tell which computers are under maintenance.',
        ]),
    ], 'Facilities');

    $records = generateConceptIdea('records_management', [makeConceptFeedback()], 'Academic Process', [
        'service' => conceptServiceWithTitles([$laboratory['title']]),
    ]);

    expect($records['title'])->not->toBe($laboratory['title'])
        ->and($records['project_name'])->not->toBe($laboratory['project_name'])
        ->and($records['project_name'])->toBeIn(['DocuTrack', 'DocuFlow', 'RecordHub'])
        ->and($records['concept']['primary_key'])->toBe('records_management');
});
test('at most one alternative concept direction is presented', function (): void {
    foreach (knownConceptClusters() as $clusterKey) {
        $idea = generateConceptIdea($clusterKey, [makeConceptFeedback()]);

        expect($idea['concept']['alternatives'])->toHaveCount(1);

        expect($idea['concept']['alternatives'][0]['concept_key'])
            ->not->toBe($idea['concept']['primary_key']);
    }
});

test('the single alternative is a lower ranked candidate than the primary', function (): void {
    $idea = generateConceptIdea('laboratory_equipment_monitoring', [
        makeConceptFeedback([
            'translated_title' => 'Equipment status is unknown',
            'translated_description' => 'Computers under maintenance are not marked and equipment status is checked by hand.',
            'translated_impact' => 'Students use malfunctioning computers all day.',
        ]),
    ]);

    $alternative = $idea['concept']['alternatives'][0];

    expect($idea['concept']['primary_key'])->toBe('equipment_monitoring')
        ->and($idea['concept']['alternatives'])->toHaveCount(1)
        ->and($alternative['concept_key'])->not->toBe($idea['concept']['primary_key'])
        ->and($alternative['score'])->toBeLessThan($idea['concept']['score'])
        ->and($alternative['title'])->not->toBe($idea['title']);
});

test('reusing a title never changes the selected concept or its score', function (): void {
    $evidence = [makeConceptFeedback([
        'translated_title' => 'Laboratory computer availability',
        'translated_description' => 'Students cannot tell which computers are available or in use.',
        'translated_impact' => 'Time is wasted looking for an available computer.',
        'affected_group' => ['Students'],
        'current_process' => 'Report verbally to staff',
    ])];

    $first = generateConceptIdea('laboratory_equipment_monitoring', $evidence);
    $again = generateConceptIdea('laboratory_equipment_monitoring', $evidence, 'Facilities', [
        'service' => conceptServiceWithTitles([$first['title']]),
    ]);

    expect($again['concept']['primary_key'])->toBe($first['concept']['primary_key'])
        ->and($again['concept']['score'])->toBe($first['concept']['score'])
        ->and($again['concept']['evidence'])->toBe($first['concept']['evidence'])
        ->and($again['concept']['alternatives'][0]['concept_key'])
        ->toBe($first['concept']['alternatives'][0]['concept_key']);
});
test('the presentation is rebuilt from the concept identity and not from title similarity', function (): void {
    $original = generateConceptIdea('campus_network_connectivity', [makeConceptFeedback()], 'Network and Connectivity');

    // A near-duplicate that is not the concept's own presentation must not be
    // adopted: only an exact match of the rebuilt title counts.
    $idea = generateConceptIdea('campus_network_connectivity', [makeConceptFeedback()], 'Network and Connectivity', [
        'service' => conceptServiceWithTitles([$original['title'].' Extra Words Appended Here']),
    ]);

    expect($idea['title'])->not->toBe($original['title'].' Extra Words Appended Here');
});

test('reusing a title does not let two clusters collide on one persisted title', function (): void {
    $laboratory = generateConceptIdea('laboratory_equipment_monitoring', [makeConceptFeedback()], 'Facilities');
    $facility = generateConceptIdea('facility_inspection_records', [makeConceptFeedback()], 'Facilities', [
        'service' => conceptServiceWithTitles([$laboratory['title']]),
    ]);

    expect($facility['title'])->not->toBe($laboratory['title'])
        ->and($facility['concept']['primary'])->not->toBe($laboratory['concept']['primary']);
});
