<?php

namespace App\Services;

class SeverityService
{
    public function compute($reports, $votes, $frequencyScore, $impactScore, $currentProcess = null)
    {
        $normalizedReports = min(5, $reports);
        $normalizedVotes   = min(5, $votes / 10);

        $base = round(
            ($normalizedReports * 0.35) +
            ($normalizedVotes   * 0.20) +
            ($frequencyScore    * 0.25) +
            ($impactScore       * 0.20),
            2
        );

        // ── Current process bonus ──────────────────────────────────────
        // Problems with no solution at all are inherently more severe
        // than ones that have a workaround (even a bad one).
        // Bonus is capped at +0.5 so it never overrides the core score.
        $processBonus = match(true) {
            str_contains(strtolower($currentProcess ?? ''), 'no solution')     => 0.5,
            str_contains(strtolower($currentProcess ?? ''), 'manual')          => 0.3,
            str_contains(strtolower($currentProcess ?? ''), 'wait')            => 0.25,
            str_contains(strtolower($currentProcess ?? ''), 'verbally')        => 0.15,
            str_contains(strtolower($currentProcess ?? ''), 'broken')          => 0.2,
            str_contains(strtolower($currentProcess ?? ''), 'email')           => 0.1,
            default                                                             => 0.0,
        };

        $score = min(5, round($base + $processBonus, 2));

        $level = match(true) {
            $score >= 3.5 => 'High',
            $score >= 2.5 => 'Medium',
            default       => 'Low',
        };

        return [
            'score'         => $score,
            'level'         => $level,
            'process_bonus' => $processBonus, // exposed so controller can use it in explanation
        ];
    }
}