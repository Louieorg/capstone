<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ClusteringService
{
    /**
     * Second-level, rule-based problem profiles. Generic terms such as
     * "system" and "manual" are deliberately excluded: they do not identify
     * an institutional problem on their own.
     *
     * @var array<string, array<string, array{label: string, signals: array<int, string>}>>
     */
    private array $profiles = [
        'facilities' => [
            'facility_inspection_records' => [
                'label' => 'Facility Inspection & Records',
                'signals' => ['inspection', 'inspect', 'inspection result', 'checklist', 'maintenance record', 'maintenance history', 'historical record', 'facility record'],
            ],
            'facility_request_tracking' => [
                'label' => 'Facility Request Tracking',
                'signals' => ['facility request', 'maintenance request', 'service request', 'concern', 'request status', 'status update', 'request tracking', 'received', 'assigned', 'resolved'],
            ],
            'laboratory_equipment_monitoring' => [
                'label' => 'Laboratory Equipment Monitoring',
                'signals' => ['laboratory', 'lab', 'computer', 'workstation', 'equipment status', 'computer usage', 'occupancy', 'availability', 'under maintenance'],
            ],
        ],
        'network and connectivity' => [
            'campus_network_connectivity' => [
                'label' => 'Campus Network Connectivity',
                'signals' => ['internet', 'wifi', 'wi-fi', 'network', 'connectivity', 'no internet', 'slow internet', 'disconnected', 'signal'],
            ],
        ],
        'default' => [
            'request_tracking' => [
                'label' => 'Request Tracking',
                'signals' => ['request status', 'status update', 'request tracking', 'received', 'assigned', 'resolved', 'follow up'],
            ],
            'records_management' => [
                'label' => 'Records Management',
                'signals' => ['record', 'records', 'archive', 'historical', 'retrieval', 'document'],
            ],
            'scheduling_coordination' => [
                'label' => 'Scheduling & Coordination',
                'signals' => ['schedule', 'scheduling', 'timetable', 'conflict', 'overlap', 'time slot'],
            ],
        ],
    ];

    /**
     * @param  Collection<int, object>  $feedbacks
     * @return Collection<string, Collection<int, object>>
     */
    public function group(Collection $feedbacks): Collection
    {
        return $feedbacks->groupBy(fn (object $feedback): string => $this->clusterKeyFor($feedback));
    }

    public function label(string $clusterKey): string
    {
        foreach ($this->profiles as $categoryProfiles) {
            if (isset($categoryProfiles[$clusterKey])) {
                return $categoryProfiles[$clusterKey]['label'];
            }
        }

        if (str_contains($clusterKey, '_unclassified_')) {
            $category = Str::before($clusterKey, '_unclassified_');

            return Str::headline($category);
        }

        return Str::headline($clusterKey);
    }

    public function explanation(string $clusterKey): string
    {
        foreach ($this->profiles as $categoryProfiles) {
            if (isset($categoryProfiles[$clusterKey])) {
                return 'Reports share the problem-specific signals: '.implode(', ', $categoryProfiles[$clusterKey]['signals']).'.';
            }
        }

        if (str_contains($clusterKey, '_unclassified_')) {
            return 'The report has no sufficiently specific problem signals, so it is not merged with other generic reports.';
        }

        return 'Reports share the same institutional category and no more specific problem profile was detected.';
    }

    private function clusterKeyFor(object $feedback): string
    {
        $category = $this->normalizeCategory((string) ($feedback->category ?? ''));
        $categoryProfiles = $this->profiles[$category] ?? $this->profiles['default'];

        $text = $this->feedbackText($feedback);
        $scores = collect($categoryProfiles)
            ->map(fn (array $profile): int => $this->signalScore($text, $profile['signals']));

        $bestKey = $scores->sortDesc()->keys()->first();
        $bestScore = $scores->get($bestKey, 0);

        if ($category === 'network and connectivity' && $bestScore >= 1) {
            return $bestKey;
        }

        if ($bestScore >= 2) {
            return $bestKey;
        }

        return Str::slug($category, '_').'_unclassified_'.substr(md5($text), 0, 12);
    }

    /**
     * @param  array<int, string>  $signals
     */
    private function signalScore(string $text, array $signals): int
    {
        return collect($signals)
            ->filter(fn (string $signal): bool => str_contains($text, $signal))
            ->count();
    }

    private function feedbackText(object $feedback): string
    {
        return Str::lower(implode(' ', [
            (string) ($feedback->translated_title ?? $feedback->title ?? ''),
            (string) ($feedback->translated_title ?? $feedback->title ?? ''),
            (string) ($feedback->translated_description ?? $feedback->description ?? ''),
            (string) ($feedback->translated_impact ?? $feedback->impact ?? ''),
        ]));
    }

    private function normalizeCategory(string $category): string
    {
        return Str::of($category)->lower()->trim()->replace('&', 'and')->squish()->value();
    }
}
