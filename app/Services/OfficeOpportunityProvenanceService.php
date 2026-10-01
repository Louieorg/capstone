<?php

namespace App\Services;

use App\Models\Feedback;
use Illuminate\Support\Collection;

class OfficeOpportunityProvenanceService
{
    /**
     * @param  array{votes: int, reports: int}  $thresholds
     * @param  array<string, int|float|string>  $evaluation
     * @return array{office_cluster_key: string, office_source_feedback_ids: array<int, int>, office_provenance_fingerprint: string}
     */
    public function snapshot(
        string $ideaTitle,
        string $category,
        int $officeId,
        string $clusterKey,
        Collection $feedbacks,
        array $evaluation,
        array $thresholds
    ): array {
        $sourceFeedbacks = $this->sourceRecords($feedbacks);
        $sourceIds = array_column($sourceFeedbacks, 'id');

        return [
            'office_cluster_key' => $clusterKey,
            'office_source_feedback_ids' => $sourceIds,
            'office_provenance_fingerprint' => $this->hash([
                'identity' => [
                    'office_id' => $officeId,
                    'category' => $category,
                    'idea_title' => $ideaTitle,
                ],
                'cluster_key' => $clusterKey,
                'thresholds' => $thresholds,
                'source_feedbacks' => $sourceFeedbacks,
                'evaluation' => $evaluation,
            ]),
        ];
    }

    /**
     * @return array<int, int>
     */
    public function sourceIds(Collection $feedbacks): array
    {
        return array_column($this->sourceRecords($feedbacks), 'id');
    }

    public function sourceFingerprint(Collection $feedbacks): string
    {
        return $this->hash($this->sourceRecords($feedbacks));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function sourceRecords(Collection $feedbacks): array
    {
        return $feedbacks
            ->sortBy('id')
            ->map(fn (Feedback $feedback): array => [
                'id' => (int) $feedback->id,
                'office_id' => $feedback->office_id === null ? null : (int) $feedback->office_id,
                'category' => (string) $feedback->category,
                'status' => (string) $feedback->status,
                'is_flagged' => (bool) $feedback->is_flagged,
                'is_capstone_worthy' => (bool) $feedback->is_capstone_worthy,
                'title' => (string) $feedback->translated_title,
                'description' => (string) $feedback->translated_description,
                'impact' => (string) ($feedback->translated_impact ?? ''),
                'frequency' => (string) ($feedback->frequency ?? ''),
                'current_process' => (string) ($feedback->current_process ?? ''),
                'affected_users' => (string) ($feedback->affected_users ?? ''),
                'affected_group' => is_array($feedback->affected_group) ? $feedback->affected_group : [],
                'department' => (string) ($feedback->department ?? ''),
                'votes_count' => (int) ($feedback->votes_count ?? 0),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function hash(array $payload): string
    {
        return hash('sha256', json_encode(
            $payload,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));
    }
}
