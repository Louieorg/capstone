<?php

namespace App\Services;

class ConfidenceService
{
    public function compute($reports, $votes, $frequencyScore, $impactScore)
    {
        $score = round(
            (min(5, $reports) * 0.30) +
            (min(5, $votes / 10) * 0.25) +
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