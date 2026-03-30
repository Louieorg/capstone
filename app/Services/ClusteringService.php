<?php

namespace App\Services;

class ClusteringService
{
    public function group($feedbacks)
    {
        return $feedbacks->groupBy(function ($f) {

            $text = strtolower($f->title . ' ' . $f->description);

            $groupKeywords = [
                'internet' => ['wifi', 'internet', 'connection', 'network'],
                'account' => ['login', 'account', 'password', 'access'],
                'payment' => ['payment', 'payroll', 'billing', 'salary'],
                'enrollment' => ['enroll', 'registration', 'subjects'],
                'queue' => ['queue', 'line', 'waiting', 'delay'],
            ];

            $scores = [];

            foreach ($groupKeywords as $group => $keywords) {
                $score = 0;

                foreach ($keywords as $word) {
                    if (str_contains($text, $word)) {
                        $score++;
                    }
                }

                $scores[$group] = $score;
            }

            $bestGroup = array_keys($scores, max($scores))[0];

            return $scores[$bestGroup] > 0 ? $bestGroup : 'general';
        });
    }
}