<?php

namespace App\Services;

class ClusteringService
{
    // ══════════════════════════════════════════════
    // Keyword map — aligned to your actual categories
    // Each group has primary keywords (weight 2) and
    // secondary/contextual keywords (weight 1)
    // ══════════════════════════════════════════════
    private array $groups = [

        'enrollment' => [
            'primary'   => ['enroll', 'enrollment', 'registration', 'register', 'subject', 'subjects', 'grade', 'grades', 'transcript', 'clearance', 'credential', 'pre-enrollment', 'pre enrollment', 'sectioning', 'slot', 'units'],
            'secondary' => ['form', 'submit', 'requirement', 'deadline', 'online', 'portal', 'queue', 'long line', 'waiting'],
        ],

        'academic process' => [
            'primary'   => ['grade', 'grading', 'thesis', 'research', 'capstone', 'defense', 'adviser', 'curriculum', 'syllabus', 'academic', 'professor', 'faculty', 'exam', 'examination', 'cheating', 'plagiarism', 'evaluation', 'feedback', 'submission'],
            'secondary' => ['class', 'student', 'lecture', 'attendance', 'requirement', 'assignment', 'project', 'late'],
        ],

        'facilities' => [
            'primary'   => ['facility', 'facilities', 'toilet', 'comfort room', 'cr', 'bathroom', 'restroom', 'building', 'room', 'classroom', 'laboratory', 'lab', 'equipment', 'air conditioning', 'aircon', 'electricity', 'lighting', 'chair', 'table', 'maintenance', 'repair', 'broken', 'leaking', 'flooding'],
            'secondary' => ['dirty', 'unsafe', 'damaged', 'old', 'crowded', 'space', 'area', 'parking'],
        ],

        'library' => [
            'primary'   => ['library', 'librarian', 'book', 'books', 'reference', 'borrow', 'return', 'fine', 'fines', 'reading', 'resource', 'e-library', 'digital library', 'catalog', 'archive'],
            'secondary' => ['quiet', 'seat', 'available', 'access', 'wifi', 'internet', 'study', 'materials'],
        ],

        'scheduling' => [
            'primary'   => ['schedule', 'scheduling', 'timetable', 'conflict', 'clash', 'overlap', 'time slot', 'booking', 'reservation', 'event', 'calendar', 'class schedule', 'room assignment'],
            'secondary' => ['double booking', 'unavailable', 'cancel', 'reschedule', 'venue', 'available'],
        ],

        'network and connectivity' => [
            'primary'   => ['wifi', 'wi-fi', 'internet', 'network', 'connectivity', 'connection', 'signal', 'bandwidth', 'slow internet', 'no internet', 'disconnected'],
            'secondary' => ['access point', 'router', 'online', 'streaming', 'upload', 'download', 'lag'],
        ],

        'student services' => [
            'primary'   => ['scholarship', 'allowance', 'financial aid', 'lost and found', 'id', 'student id', 'school id', 'guidance', 'clinic', 'health', 'cafeteria', 'canteen', 'food', 'shuttle', 'transport', 'dormitory', 'dorm', 'organization', 'org'],
            'secondary' => ['student', 'service', 'support', 'benefit', 'complaint', 'concern'],
        ],

        'administration' => [
            'primary'   => ['registrar', 'cashier', 'payment', 'billing', 'tuition', 'fee', 'account', 'login', 'password', 'portal', 'system access', 'document', 'request', 'certificate', 'records', 'office'],
            'secondary' => ['process', 'manual', 'paper', 'form', 'slow', 'queue', 'staff', 'personnel'],
        ],
    ];

    // ══════════════════════════════════════════════
    // PUBLIC: group feedbacks by detected topic
    // ══════════════════════════════════════════════
    public function group($feedbacks)
    {
        return $feedbacks->groupBy(function ($feedback) {
            return $this->classify($feedback);
        });
    }

    // ══════════════════════════════════════════════
    // CLASSIFY a single feedback item
    // ══════════════════════════════════════════════
    private function classify($feedback): string
    {
        // Build a weighted text corpus — title matters more than description
        $text = strtolower(
            $feedback->title . ' ' . $feedback->title . ' ' . // title counted twice
            $feedback->description . ' ' .
            ($feedback->impact ?? '')
        );

        $scores = [];

        foreach ($this->groups as $groupName => $keywords) {
            $score = 0;

            // Primary keywords — worth 2 points each
            foreach ($keywords['primary'] as $word) {
                if (str_contains($text, $word)) {
                    $score += 2;
                }
            }

            // Secondary keywords — worth 1 point each
            foreach ($keywords['secondary'] as $word) {
                if (str_contains($text, $word)) {
                    $score += 1;
                }
            }

            $scores[$groupName] = $score;
        }

        // Category hint — if the feedback's category column matches a group,
        // give that group a bonus to break ties meaningfully
        $categoryHint = strtolower($feedback->category ?? '');
        foreach (array_keys($this->groups) as $groupName) {
            if (str_contains($categoryHint, $groupName) || str_contains($groupName, $categoryHint)) {
                $scores[$groupName] = ($scores[$groupName] ?? 0) + 3;
            }
        }

        $maxScore = max($scores);

        // Nothing matched — fall back to the feedback's own category
        // so it at least groups with similar category problems
        if ($maxScore === 0) {
            return $this->fallback($feedback);
        }

        // Handle ties — pick the group whose primary keywords scored highest
        $topGroups = array_keys(array_filter($scores, fn($s) => $s === $maxScore));

        if (count($topGroups) === 1) {
            return $topGroups[0];
        }

        return $this->resolveTie($topGroups, $text);
    }

    // ══════════════════════════════════════════════
    // RESOLVE TIES by primary-keyword count only
    // ══════════════════════════════════════════════
    private function resolveTie(array $tiedGroups, string $text): string
    {
        $primaryScores = [];

        foreach ($tiedGroups as $groupName) {
            $score = 0;
            foreach ($this->groups[$groupName]['primary'] as $word) {
                if (str_contains($text, $word)) $score++;
            }
            $primaryScores[$groupName] = $score;
        }

        arsort($primaryScores);
        return array_key_first($primaryScores);
    }

    // ══════════════════════════════════════════════
    // FALLBACK — use the feedback's own category field
    // so unrecognized problems still group sensibly
    // ══════════════════════════════════════════════
    private function fallback($feedback): string
    {
        $cat = strtolower($feedback->category ?? '');

        if (!$cat || $cat === 'other') {
            return 'general';
        }

        // Try to match category to a known group
        foreach (array_keys($this->groups) as $groupName) {
            if (str_contains($cat, $groupName) || str_contains($groupName, $cat)) {
                return $groupName;
            }
        }

        // Return the raw category so at least same-category problems cluster
        return $cat;
    }

    // ══════════════════════════════════════════════
    // PUBLIC: expose group definitions so other
    // services can reference them if needed
    // ══════════════════════════════════════════════
    public function getGroups(): array
    {
        return array_keys($this->groups);
    }
}