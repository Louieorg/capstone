<?php

namespace App\Services;

class ConfidenceService
{
    /**
     * Confidence measures HOW MUCH WE CAN TRUST the recommendation —
     * not how bad the problem is (that's Severity's job).
     *
     * A recommendation is highly confident when:
     *   - Multiple DIFFERENT people reported it (not one person many times)
     *   - Reporters AGREE on how frequent it is (consistency)
     *   - There is enough data volume to generalize from (sample size)
     *   - The community validated it through votes (social proof)
     *
     * It can be LOW confidence even on a HIGH severity problem —
     * e.g. 1 person reported a critical issue with no votes = we believe
     * them, but we can't be confident yet it's systemic.
     */
    public function compute($reports, $votes, $frequencyScore, $impactScore, $groupFeedbacks = null)
    {
        // ── FACTOR 1: Source diversity (0–5) ─────────────────────────
        // How many UNIQUE people submitted this? One person submitting
        // 5 times is far less convincing than 5 different people.
        if ($groupFeedbacks !== null) {
            $uniqueSubmitters = $groupFeedbacks
                ->pluck('user_id')
                ->filter() // remove anonymous (null) — we can't verify uniqueness
                ->unique()
                ->count();

            // Anonymous reports count as partial evidence (0.5 each, max 2)
            $anonymousCount = $groupFeedbacks->whereNull('user_id')->count();
            $anonCredit     = min(2, $anonymousCount * 0.5);

            $diversityScore = min(5, $uniqueSubmitters + $anonCredit);
        } else {
            // Fallback if feedbacks not passed — estimate from report count
            $diversityScore = min(5, $reports * 0.7);
        }

        // ── FACTOR 2: Frequency consistency (0–5) ────────────────────
        // Do reporters AGREE on how often this happens?
        // High agreement = high confidence the frequency data is real.
        if ($groupFeedbacks !== null) {
            $frequencyValues = $groupFeedbacks->pluck('frequency')->filter();

            if ($frequencyValues->count() > 1) {
                $dominantFreq  = $frequencyValues->groupBy(fn($f) => $f)->map->count()->max();
                $totalFreq     = $frequencyValues->count();
                $agreementRate = $dominantFreq / $totalFreq; // 1.0 = perfect agreement
                $consistencyScore = round($agreementRate * 5, 2);
            } else {
                $consistencyScore = 2.0; // single report = neutral
            }
        } else {
            // If frequency is high, assume consistency is moderate
            $consistencyScore = min(5, $frequencyScore * 0.8 + 1);
        }

        // ── FACTOR 3: Sample size (0–5) ──────────────────────────────
        // Raw volume of reports — more data = more reliable.
        // Diminishing returns: 3 reports is much better than 1,
        // but 10 vs 12 is not a big difference.
        $sampleScore = match(true) {
            $reports >= 10 => 5.0,
            $reports >= 7  => 4.0,
            $reports >= 5  => 3.5,
            $reports >= 3  => 2.5,
            $reports >= 2  => 1.5,
            default        => 0.8,
        };

        // ── FACTOR 4: Community validation (0–5) ─────────────────────
        // Votes show that OTHER people (beyond reporters) recognize
        // this as a real problem. Strong social proof = higher confidence.
        $validationScore = min(5, $votes / 8);

        // ── WEIGHTED FORMULA ─────────────────────────────────────────
        // Diversity and consistency are the most important —
        // they directly measure data quality, not just quantity.
        $score = round(
            ($diversityScore   * 0.35) +  // who reported it
            ($consistencyScore * 0.30) +  // do they agree
            ($sampleScore      * 0.20) +  // how many reports
            ($validationScore  * 0.15),   // community agreement
            2
        );

        $score = min(5, $score);

        $level = match(true) {
            $score >= 3.8 => 'High',
            $score >= 2.5 => 'Medium',
            default       => 'Low',
        };

        // ── EXPLANATION ──────────────────────────────────────────────
        $explanation = $this->buildExplanation(
            $level, $diversityScore, $consistencyScore, $sampleScore, $validationScore, $reports, $votes
        );

        return [
            'score'            => $score,
            'level'            => $level,
            'explanation'      => $explanation,
            'diversity_score'  => round($diversityScore, 2),
            'consistency_score'=> round($consistencyScore, 2),
            'sample_score'     => $sampleScore,
            'validation_score' => round($validationScore, 2),
        ];
    }

    private function buildExplanation($level, $diversity, $consistency, $sample, $validation, $reports, $votes): string
    {
        $reasons = [];

        if ($diversity >= 3.5) {
            $reasons[] = 'reported by multiple independent sources';
        } elseif ($diversity < 2) {
            $reasons[] = 'limited source diversity — few unique reporters';
        }

        if ($consistency >= 4.0) {
            $reasons[] = 'reporters strongly agree on frequency';
        } elseif ($consistency < 2.5) {
            $reasons[] = 'inconsistent frequency reports across submissions';
        }

        if ($sample >= 3.5) {
            $reasons[] = "{$reports} reports provide a reliable data sample";
        } elseif ($sample < 1.5) {
            $reasons[] = 'small sample size — more reports would strengthen confidence';
        }

        if ($validation >= 3.0) {
            $reasons[] = "{$votes} community votes confirm broad awareness";
        }

        if (empty($reasons)) {
            return $level === 'High'
                ? 'Strong data quality across all confidence factors.'
                : 'Limited data available — confidence will improve with more submissions.';
        }

        return match($level) {
            'High'   => 'High confidence: ' . implode(', ', $reasons) . '.',
            'Medium' => 'Moderate confidence: ' . implode(', ', $reasons) . '.',
            default  => 'Low confidence: ' . implode(', ', $reasons) . '.',
        };
    }
}