<?php

namespace App\Services;

class IdeaGeneratorService
{
    public function generate($groupName, $category, $groupFeedbacks, $reports, $votes, $frequencyScore, $impactScore)
    {
        // Build a rich text corpus from all feedback in this group
        $allTitles = $groupFeedbacks->pluck('translated_title')->implode(' ');
        $allDescriptions = $groupFeedbacks->pluck('translated_description')->implode(' ');
        $allImpacts = $groupFeedbacks->pluck('translated_impact')->filter()->implode(' ');

        $text = strtolower($groupName.' '.$allTitles.' '.$allDescriptions.' '.$allImpacts);

        // Dominant affected group
        $topGroup = $this->resolveTopGroup($groupFeedbacks);

        // Dominant current process (what's failing right now)
        $currentProcess = $this->resolveCurrentProcess($groupFeedbacks);

        // Dominant department where the issue occurs
        $dominantDepartment = $this->resolveDominantDepartment($groupFeedbacks);

        // Detect problem signals from text
        $signals = $this->detectSignals($text);

        // Build all parts
        $title = $this->generateTitle($groupName, $category, $signals, $frequencyScore, $impactScore, $groupFeedbacks, $dominantDepartment);
        $description = $this->generateDescription($groupName, $category, $signals, $reports, $votes, $topGroup, $currentProcess, $frequencyScore, $impactScore, $dominantDepartment);
        $objectives = $this->generateObjectives($signals, $category, $groupName, $topGroup, $groupFeedbacks, $dominantDepartment);
        $explanation = $this->generateExplanation($groupName, $topGroup, $reports, $votes, $frequencyScore, $impactScore);
        $impact = $this->simulateImpact($reports, $frequencyScore, $impactScore, $signals);

        return [
            'title' => $title,
            'description' => $description,
            'general_objective' => $objectives['general'],
            'specific_objectives' => $objectives['specific'],
            'explanation' => $explanation,
            'top_group' => $topGroup,
            'impact_simulation' => $impact,
        ];
    }

    // ══════════════════════════════════════════════
    // SIGNAL DETECTION
    // Reads the corpus and flags what kind of problem this is
    // ══════════════════════════════════════════════
    private function detectSignals(string $text): array
    {
        return [
            'is_slow' => $this->has($text, ['slow', 'delay', 'wait', 'queue', 'long line', 'hour', 'hours']),
            'is_manual' => $this->has($text, ['manual', 'paper', 'physical', 'form', 'handwritten', 'walk-in']),
            'is_no_system' => $this->has($text, ['no system', 'no platform', 'no portal', 'no online', 'no way to track']),
            'is_access' => $this->has($text, ['access', 'login', 'password', 'account', 'locked', 'unavailable']),
            'is_tracking' => $this->has($text, ['track', 'status', 'update', 'progress', 'monitor', 'check']),
            'is_scheduling' => $this->has($text, ['schedule', 'conflict', 'clash', 'overlap', 'slot', 'booking']),
            'is_record' => $this->has($text, ['record', 'file', 'document', 'grade', 'transcript', 'lost']),
            'is_booking' => $this->has($text, ['book', 'reserve', 'reservation', 'appointment', 'slot']),
            'is_complaint' => $this->has($text, ['complaint', 'report', 'issue', 'concern', 'feedback']),
            'is_crowded' => $this->has($text, ['crowd', 'congested', 'full', 'packed', 'overloaded']),
            'is_wifi' => $this->has($text, ['wifi', 'internet', 'connectivity', 'network', 'signal', 'connection']),
            'is_notification' => $this->has($text, ['notify', 'notification', 'alert', 'inform', 'update']),
        ];
    }

    private function has(string $text, array $keywords): bool
    {
        foreach ($keywords as $kw) {
            if (str_contains($text, $kw)) {
                return true;
            }
        }

        return false;
    }

    // ══════════════════════════════════════════════
    // TITLE GENERATION
    // Produces varied, natural-sounding project titles
    // ══════════════════════════════════════════════
    private function generateTitle($groupName, $category, array $signals, $frequencyScore, $impactScore, $groupFeedbacks, ?string $dominantDepartment): string
    {
        $name = ucwords($groupName);

        // Pick the primary solution type based on dominant signals
        if ($signals['is_wifi']) {
            $core = 'Campus Network Monitoring and Management System';
        } elseif ($signals['is_scheduling']) {
            $core = "{$name} Scheduling and Conflict Resolution System";
        } elseif ($signals['is_booking']) {
            $core = "Smart {$name} Reservation and Booking Platform";
        } elseif ($signals['is_manual'] && $signals['is_slow']) {
            $core = "Automated {$name} Processing System";
        } elseif ($signals['is_manual']) {
            $core = "Digital {$name} Management System";
        } elseif ($signals['is_no_system']) {
            $core = "Centralized {$name} Information Portal";
        } elseif ($signals['is_tracking']) {
            $core = "{$name} Tracking and Status Monitoring System";
        } elseif ($signals['is_record']) {
            $core = "{$name} Records Management and Retrieval System";
        } elseif ($signals['is_slow']) {
            $core = "{$name} Queue Management and Optimization System";
        } elseif ($signals['is_access']) {
            $core = "{$name} Access and Account Management System";
        } elseif ($signals['is_complaint']) {
            $core = "{$name} Complaint Reporting and Resolution System";
        } else {
            $core = "{$name} Service Improvement System";
        }

        // Platform — derived from dominant affected group + frequency
        $topGroup = $this->resolveTopGroup($groupFeedbacks);
        $isMobile = str_contains(strtolower($topGroup ?? ''), 'student');

        if ($signals['is_wifi'] || $signals['is_access']) {
            $platform = 'Web-Based';
        } elseif ($isMobile && $frequencyScore >= 3) {
            $platform = 'Mobile-First';
        } elseif ($impactScore >= 3) {
            $platform = 'Web-Based';
        } else {
            $platform = 'Integrated';
        }

        $title = "{$platform} {$core}";

        if ($dominantDepartment === null) {
            return $title;
        }

        if ($dominantDepartment === 'across multiple departments') {
            return "{$title} across Multiple Departments";
        }

        return "{$title} for {$dominantDepartment}";
    }

    // ══════════════════════════════════════════════
    // DESCRIPTION GENERATION
    // Rich, specific, reads like a project abstract
    // ══════════════════════════════════════════════
    private function generateDescription($groupName, $category, array $signals, $reports, $votes, $topGroup, $currentProcess, $frequencyScore, $impactScore, ?string $dominantDepartment): string
    {
        $name = ucwords($groupName);
        $group = $topGroup ?? 'campus users';
        $process = $currentProcess ? "Currently, the process relies on {$currentProcess}, " : '';
        $departmentContext = $this->departmentContextPhrase($dominantDepartment);

        // Problem framing sentence
        $problem = $this->describeProblem($name, $signals, $group, $departmentContext);

        // Current gap sentence
        $gap = $process
            ? "{$process}which contributes to inefficiencies and delays that affect {$group} on a recurring basis."
            : "The absence of a dedicated system forces {$group} to rely on fragmented or manual workarounds.";

        // Evidence sentence
        $freq = $frequencyScore >= 3 ? 'frequently occurring' : 'reported';
        $support = $votes >= 10
            ? "Backed by {$reports} community reports and {$votes} upvotes"
            : "Based on {$reports} submitted reports";

        $evidence = "{$support}, this is identified as a {$freq} concern with measurable impact on campus operations.";

        // Solution framing
        $solution = $this->describeSolution($name, $signals, $impactScore, $departmentContext);

        return "{$problem} {$gap} {$evidence} {$solution}";
    }

    private function describeProblem($name, array $signals, $group, string $departmentContext): string
    {
        if ($signals['is_slow'] && $signals['is_manual']) {
            return "Campus {$group}{$departmentContext} consistently experience delays caused by manual {$name} processes that are slow, prone to error, and difficult to scale.";
        } elseif ($signals['is_slow']) {
            return "Long waiting times in {$name}-related services have become a persistent source of frustration for {$group}{$departmentContext}.";
        } elseif ($signals['is_manual']) {
            return "The reliance on paper-based and manual procedures for {$name} creates bottlenecks that slow down service delivery for {$group}{$departmentContext}.";
        } elseif ($signals['is_no_system']) {
            return "The lack of a centralized system for {$name} leaves {$group}{$departmentContext} without a reliable way to access, track, or manage their requests.";
        } elseif ($signals['is_wifi']) {
            return "Unreliable internet connectivity disrupts the academic and administrative activities of {$group}{$departmentContext} daily.";
        } elseif ($signals['is_scheduling']) {
            return "Scheduling conflicts and the absence of a coordinated system for {$name} cause recurring disruptions for {$group}{$departmentContext}.";
        } elseif ($signals['is_tracking']) {
            return "{$group}{$departmentContext} have no reliable way to monitor the status or progress of their {$name}-related requests and transactions.";
        } else {
            return "Recurring issues in {$name} services have been reported by {$group}{$departmentContext}, pointing to systemic gaps in how these processes are currently managed.";
        }
    }

    private function describeSolution($name, array $signals, $impactScore, string $departmentContext): string
    {
        $impact = $impactScore >= 3 ? 'a significant portion of the campus community' : 'the directly affected users';
        $scope = $departmentContext === '' ? $impact : "{$impact}{$departmentContext}";

        if ($signals['is_manual'] || $signals['is_no_system']) {
            return "A digital solution targeting {$name} processes would automate key workflows, reduce manual effort, and provide {$scope} with a more reliable and accessible service experience.";
        } elseif ($signals['is_tracking'] || $signals['is_notification']) {
            return "A system with real-time tracking and notification capabilities would give {$scope} visibility into their requests, reducing uncertainty and improving satisfaction.";
        } elseif ($signals['is_slow']) {
            return "Implementing an optimized, queue-aware system for {$name} would measurably reduce wait times and improve throughput for {$scope}.";
        } else {
            return "Addressing these issues through a structured software solution would improve the overall {$name} experience for {$scope} and reduce recurring operational friction.";
        }
    }

    // ══════════════════════════════════════════════
    // OBJECTIVES GENERATION
    // Specific, measurable, tied to actual problem signals
    // ══════════════════════════════════════════════
    private function generateObjectives(array $signals, $category, $groupName, $topGroup, $groupFeedbacks, ?string $dominantDepartment): array
    {
        $name = ucwords($groupName);
        $group = $topGroup ?? 'campus users';

        // General objective — action-verb framing
        $verb = 'develop and implement';
        if ($signals['is_slow']) {
            $verb = 'design and deploy';
        }
        if ($signals['is_manual']) {
            $verb = 'develop and automate';
        }

        $general = "To {$verb} a {$name} system that addresses recurring campus issues and improves service delivery for {$group}.";

        if ($dominantDepartment === 'across multiple departments') {
            $general = "To {$verb} a {$name} system that improves service delivery across multiple departments.";
        } elseif ($dominantDepartment !== null) {
            $general = "To {$verb} a {$name} system for improving services in the {$dominantDepartment}.";
        }

        // Specific objectives — drawn from real signals, no generic fallbacks
        $specific = [];

        if ($signals['is_slow'] || $signals['is_crowded']) {
            $specific[] = "To reduce average waiting and processing time for {$name} transactions by at least 40%.";
        }
        if ($signals['is_manual']) {
            $specific[] = "To digitize and automate manual {$name} workflows, eliminating paper-based processes.";
        }
        if ($signals['is_tracking'] || $signals['is_notification']) {
            $specific[] = "To provide {$group} with real-time tracking and status updates for their {$name} requests.";
        }
        if ($signals['is_record']) {
            $specific[] = "To establish a centralized, searchable digital repository for {$name}-related records and documents.";
        }
        if ($signals['is_scheduling']) {
            $specific[] = "To implement an automated scheduling engine that detects and prevents conflicts for {$group}.";
        }
        if ($signals['is_booking']) {
            $specific[] = "To enable {$group} to view availability and book {$name} resources online without manual coordination.";
        }
        if ($signals['is_wifi'] || $signals['is_access']) {
            $specific[] = 'To provide administrators with a dashboard for monitoring connectivity and access issues in real time.';
        }
        if ($signals['is_complaint']) {
            $specific[] = "To create a structured complaint submission and resolution workflow with status tracking for {$group}.";
        }

        // Always include: user experience + evaluation objectives
        $specific[] = "To improve overall satisfaction of {$group} with {$name} services through accessible and responsive system design.";
        $specific[] = 'To evaluate system effectiveness through user acceptance testing and post-deployment feedback.';

        // Cap at 5 specific objectives — most capstone panels expect 3–5
        $specific = array_slice($specific, 0, 5);

        return ['general' => $general, 'specific' => $specific];
    }

    // ══════════════════════════════════════════════
    // EXPLANATION
    // ══════════════════════════════════════════════
    private function generateExplanation($groupName, $topGroup, $reports, $votes, $frequencyScore, $impactScore): array
    {
        $freqLabel = match (true) {
            $frequencyScore >= 3.5 => 'Everyday', $frequencyScore >= 2.5 => 'Often', $frequencyScore >= 1.5 => 'Sometimes', default => 'Rarely'
        };
        $impactLabel = match (true) {
            $impactScore >= 3.5 => 'More than 500 users', $impactScore >= 2.5 => '200–500 users', $impactScore >= 1.5 => '50–200 users', default => 'Less than 50 users'
        };

        return [
            'summary' => "This idea surfaces from {$reports} reports of recurring {$groupName} issues. "
                       ."The problem occurs {$freqLabel} and affects an estimated {$impactLabel}, "
                       .'making it a strong candidate for a capstone project with measurable real-world impact.',

            'factors' => [
                'reports' => $reports,
                'votes' => $votes,
                'frequency_score' => round($frequencyScore, 2),
                'impact_score' => round($impactScore, 2),
                'top_affected_group' => $topGroup,
            ],

            'reasoning' => [
                'impact' => $impactScore >= 3
                    ? "Affects a large portion of campus — {$impactLabel} — giving a proposed solution wide reach and measurable benefit."
                    : 'Currently affects a smaller group, but the issue may expand if left unaddressed.',

                'frequency' => $frequencyScore >= 3
                    ? "Reported as occurring {$freqLabel}, indicating this is not an isolated incident but a systemic gap."
                    : 'Occurs occasionally, but the pattern across multiple submissions confirms it as a genuine recurring concern.',

                'reports' => $reports >= 5
                    ? "{$reports} independent reports validate that this is a shared experience, not an individual complaint."
                    : "Early-stage data with {$reports} reports — additional submissions would strengthen confidence further.",

                'votes' => $votes >= 10
                    ? "{$votes} community upvotes confirm broad awareness and shared frustration with this issue."
                    : "Currently {$votes} votes — as more students discover the problem, support is expected to grow.",
            ],
        ];
    }

    // ══════════════════════════════════════════════
    // IMPACT SIMULATION
    // ══════════════════════════════════════════════
    private function simulateImpact($reports, $frequencyScore, $impactScore, array $signals): array
    {
        $base = ($frequencyScore * 10) + ($impactScore * 10);
        $reportFactor = min(20, $reports * 2);

        // Boost if digital transformation signals are strong
        $digitalBoost = ($signals['is_manual'] || $signals['is_no_system']) ? 10 : 0;

        $improvement = min(90, round($base + $reportFactor + $digitalBoost));

        // Generate a message that sounds meaningful, not templated
        $qualifier = match (true) {
            $improvement >= 70 => 'significantly reduce',
            $improvement >= 50 => 'substantially reduce',
            $improvement >= 30 => 'measurably reduce',
            default => 'help reduce',
        };

        $scope = $impactScore >= 3 ? 'campus-wide impact' : 'impact on affected users';

        return [
            'percentage' => $improvement,
            'message' => "A targeted solution could {$qualifier} the {$scope} of this problem by an estimated {$improvement}%, based on report volume, frequency, and severity data.",
        ];
    }

    // ══════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════
    private function resolveTopGroup($groupFeedbacks): ?string
    {
        $groups = $groupFeedbacks->map(function ($f) {
            $g = $f->affected_group;
            // Handle both JSON array (new) and plain string (old)
            if (is_array($g)) {
                return $g;
            }
            if (is_string($g) && str_starts_with($g, '[')) {
                return json_decode($g, true) ?? [$g];
            }

            return [$g];
        })->flatten()->filter()->countBy()->sortDesc();

        return $groups->keys()->first();
    }

    private function resolveCurrentProcess($groupFeedbacks): ?string
    {
        return $groupFeedbacks
            ->pluck('current_process')
            ->filter(fn ($p) => $p && $p !== 'No system in place')
            ->groupBy(fn ($p) => $p)
            ->map->count()
            ->sortDesc()
            ->keys()
            ->first();
    }

    private function resolveDominantDepartment($groupFeedbacks): ?string
    {
        $departments = $groupFeedbacks
            ->pluck('department')
            ->filter(fn ($department) => filled($department))
            ->countBy()
            ->sortDesc();

        if ($departments->isEmpty()) {
            return null;
        }

        $topCount = $departments->first();
        $topDepartments = $departments
            ->filter(fn ($count) => $count === $topCount)
            ->keys();

        if ($topDepartments->count() > 1) {
            return 'across multiple departments';
        }

        return $topDepartments->first();
    }

    private function departmentContextPhrase(?string $dominantDepartment): string
    {
        if ($dominantDepartment === null) {
            return '';
        }

        if ($dominantDepartment === 'across multiple departments') {
            return ' across multiple departments';
        }

        return " in the {$dominantDepartment}";
    }
}
