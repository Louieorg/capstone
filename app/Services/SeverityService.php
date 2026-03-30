<?php

namespace App\Services;

class SeverityService
{
    public function compute($reports, $votes, $frequencyScore, $impactScore)
    {
        $normalizedReports = min(5, $reports);
        $normalizedVotes = min(5, $votes / 10);

        $score = round(
            ($normalizedReports * 0.35) +
            ($normalizedVotes * 0.20) +
            ($frequencyScore * 0.25) +
            ($impactScore * 0.20),
            2
        );

        if ($score >= 3.5) {
            $level = 'High';
        } elseif ($score >= 2.5) {
            $level = 'Medium';
        } else {
            $level = 'Low';
        }

        return [
            'score' => $score,
            'level' => $level
        ];
    }
}