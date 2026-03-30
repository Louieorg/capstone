<?php

namespace App\Services;

class IdeaGeneratorService
{
    public function generate($groupName, $category, $groupFeedbacks, $reports, $votes, $frequencyScore, $impactScore)
    {
        $text = strtolower(
            $groupName . ' ' .
            $groupFeedbacks->pluck('title')->implode(' ') . ' ' .
            $groupFeedbacks->pluck('description')->implode(' ')
        );

        // TITLE
        $title = $this->generateTitle($groupName, $frequencyScore, $text);

        // DESCRIPTION
        $description = "This system focuses on {$groupName} related issues. "
            . "It is based on {$reports} reports and {$votes} votes.";

        // OBJECTIVES
        $objectives = $this->generateObjectives($text, $category, $groupFeedbacks, $groupName);

        // TOP GROUP
        $topGroup = $groupFeedbacks
            ->groupBy('affected_group')
            ->map->count()
            ->sortDesc()
            ->keys()
            ->first();

        // EXPLANATION
        $explanation = $this->generateExplanation(
            $groupName,
            $topGroup,
            $reports,
            $votes,
            $frequencyScore,
            $impactScore
        );

        $impactSimulation = $this->simulateImpact($reports, $frequencyScore, $impactScore);

        return [
            'title' => $title,
            'description' => $description,
            'general_objective' => $objectives['general'],
            'specific_objectives' => $objectives['specific'],
            'explanation' => $explanation,
            'top_group' => $topGroup,
            'impact_simulation' => $impactSimulation,
        ];
    }

    private function simulateImpact($reports, $frequencyScore, $impactScore)
{
    $base = ($frequencyScore * 10) + ($impactScore * 10);

    $reportFactor = min(20, $reports * 2);

    $improvement = min(90, round($base + $reportFactor));

    return [
        'percentage' => $improvement,
        'message' => "This system may reduce the problem impact by approximately {$improvement}%."
    ];
}

    private function generateTitle($groupName, $frequencyScore, $text)
    {
        $type = 'Management System';

        if (str_contains($text, 'slow') || str_contains($text, 'delay')) {
            $type = 'Optimization System';
        } elseif (str_contains($text, 'error')) {
            $type = 'Detection and Resolution System';
        } elseif (str_contains($text, 'manual')) {
            $type = 'Automation System';
        }

        $feature = $frequencyScore >= 3
            ? 'with Real-Time Monitoring'
            : 'with Centralized Platform';

        $techOptions = ['Web-Based', 'Mobile-Based', 'Cloud-Based'];

        $techIndex = crc32($groupName) % count($techOptions);
        $tech = $techOptions[$techIndex];

        return $tech . ' ' .
            ucfirst($groupName) . ' ' . $type . ' ' .
            $feature . ' for Campus Users';
    }

    private function generateObjectives($text, $category, $groupFeedbacks, $groupName)
    {
        $actions = [];

        if (str_contains($text, 'wait') || str_contains($text, 'delay')) {
            $actions[] = 'reduce waiting time';
        }

        if (str_contains($text, 'queue') || str_contains($text, 'line')) {
            $actions[] = 'manage queue efficiently';
        }

        if (str_contains($text, 'record')) {
            $actions[] = 'automate record management';
        }

        if (str_contains($text, 'request')) {
            $actions[] = 'track user requests';
        }

        if (str_contains($text, 'complaint')) {
            $actions[] = 'monitor complaints';
        }

        $categoryText = strtolower($category);

        if (str_contains($categoryText, 'system') || str_contains($categoryText, 'it')) {
            $mainAction = 'develop a system to improve';
        } elseif (str_contains($categoryText, 'administrative')) {
            $mainAction = 'streamline';
        } else {
            $mainAction = 'improve';
        }

        $general = "To {$mainAction} {$groupName} related processes in the campus.";

        $specific = [];

        foreach ($actions as $action) {
            $specific[] = "To {$action}";
        }

        if (count($specific) < 2) {
            $specific[] = "To improve service efficiency";
            $specific[] = "To enhance process management";
        }

        $topGroup = $groupFeedbacks
            ->groupBy('affected_group')
            ->map->count()
            ->sortDesc()
            ->keys()
            ->first();

        if ($topGroup) {
            $specific[] = "To improve experience for {$topGroup}";
        }

        return [
            'general' => $general,
            'specific' => $specific
        ];
    }

    private function generateExplanation($groupName, $topGroup, $reports, $votes, $frequencyScore, $impactScore)
    {
        return [
            'summary' => "This idea is recommended based on recurring {$groupName} issues affecting {$topGroup}.",

            'factors' => [
                'reports' => $reports,
                'votes' => $votes,
                'frequency_score' => round($frequencyScore, 2),
                'impact_score' => round($impactScore, 2),
                'top_affected_group' => $topGroup
            ],

            'reasoning' => [
                'impact' => $impactScore >= 3
                    ? 'High impact because many users are affected'
                    : 'Lower impact based on data',

                'frequency' => $frequencyScore >= 3
                    ? 'Occurs frequently in campus operations'
                    : 'Occurs occasionally',

                'reports' => $reports >= 5
                    ? 'Multiple reports indicate recurring issue'
                    : 'Limited reports available',

                'votes' => $votes >= 20
                    ? 'Strong user support through votes'
                    : 'Limited user validation'
            ]
        ];
    }
}