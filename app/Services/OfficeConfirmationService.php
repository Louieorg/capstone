<?php

namespace App\Services;

use App\Models\IdeaEvaluation;
use App\Models\Office;
use App\Models\OfficeConfirmationRequest;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OfficeConfirmationService
{
    public function __construct(private CategoryIdeaGenerationService $generationService) {}

    /**
     * @return array{result: string, request: OfficeConfirmationRequest|null}
     */
    public function request(User $requester, IdeaEvaluation $evaluation): array
    {
        abort_unless($requester->role === 'user', 403, 'Only students may request office confirmation for their capstone group.');

        return DB::transaction(function () use ($requester, $evaluation): array {
            $lockedEvaluation = IdeaEvaluation::query()->whereKey($evaluation->id)->lockForUpdate()->firstOrFail();

            if ($lockedEvaluation->office_id === null) {
                return ['result' => 'community', 'request' => null];
            }

            $office = Office::query()->whereKey($lockedEvaluation->office_id)->lockForUpdate()->firstOrFail();
            $snapshot = $this->reconcile($lockedEvaluation, $office);

            if (! $office->is_active || $snapshot === null) {
                return ['result' => 'stale', 'request' => null];
            }

            if (OfficeConfirmationRequest::query()
                ->where('active_opportunity_id', $lockedEvaluation->id)
                ->exists()) {
                return ['result' => 'taken', 'request' => null];
            }

            $existingRequest = OfficeConfirmationRequest::query()
                ->where('requester_user_id', $requester->id)
                ->where('idea_evaluation_id', $lockedEvaluation->id)
                ->where('status', OfficeConfirmationRequest::STATUS_PENDING)
                ->first();

            if ($existingRequest !== null) {
                return ['result' => 'pending', 'request' => $existingRequest];
            }

            $confirmationRequest = OfficeConfirmationRequest::query()->create([
                'requester_user_id' => $requester->id,
                'idea_evaluation_id' => $lockedEvaluation->id,
                'office_id' => $office->id,
                'status' => OfficeConfirmationRequest::STATUS_PENDING,
                'office_cluster_key' => $snapshot['office_cluster_key'],
                'source_feedback_ids' => $snapshot['office_source_feedback_ids'],
                'provenance_fingerprint' => $snapshot['office_provenance_fingerprint'],
            ]);

            return ['result' => 'requested', 'request' => $confirmationRequest];
        });
    }

    public function decide(User $representative, int $requestId, bool $confirm): string
    {
        try {
            return DB::transaction(function () use ($representative, $requestId, $confirm): string {
                $initialRequest = OfficeConfirmationRequest::query()->findOrFail($requestId);
                $evaluation = IdeaEvaluation::query()
                    ->whereKey($initialRequest->idea_evaluation_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $confirmationRequest = OfficeConfirmationRequest::query()
                    ->whereKey($requestId)
                    ->lockForUpdate()
                    ->firstOrFail();
                $office = Office::query()->whereKey($confirmationRequest->office_id)->lockForUpdate()->firstOrFail();

                abort_unless(
                    (int) $office->representative_user_id === (int) $representative->id,
                    403,
                    'Only this office’s current representative can decide the request.'
                );

                if ($confirmationRequest->status !== OfficeConfirmationRequest::STATUS_PENDING) {
                    return $confirmationRequest->status;
                }

                if ((int) $evaluation->office_id !== (int) $office->id) {
                    $this->markStale($confirmationRequest);

                    return 'stale';
                }

                $snapshot = $this->reconcile($evaluation, $office);

                if (! $office->is_active || ! $this->requestMatchesSnapshot($confirmationRequest, $snapshot)) {
                    $this->markStale($confirmationRequest);

                    return 'stale';
                }

                if ($confirm && OfficeConfirmationRequest::query()
                    ->where('active_opportunity_id', $evaluation->id)
                    ->whereKeyNot($confirmationRequest->id)
                    ->exists()) {
                    $this->markStale($confirmationRequest);

                    return 'taken';
                }

                $confirmationRequest->update([
                    'status' => $confirm
                        ? OfficeConfirmationRequest::STATUS_CONFIRMED
                        : OfficeConfirmationRequest::STATUS_DECLINED,
                    'decision_outcome' => $confirm
                        ? OfficeConfirmationRequest::STATUS_CONFIRMED
                        : OfficeConfirmationRequest::STATUS_DECLINED,
                    'decided_by_user_id' => $representative->id,
                    'decision_at' => now(),
                    'active_opportunity_id' => $confirm ? $evaluation->id : null,
                ]);

                if ($confirm) {
                    $competingRequests = OfficeConfirmationRequest::query()
                        ->where('idea_evaluation_id', $evaluation->id)
                        ->where('status', OfficeConfirmationRequest::STATUS_PENDING)
                        ->whereKeyNot($confirmationRequest->id)
                        ->get();

                    foreach ($competingRequests as $competingRequest) {
                        $this->markStale($competingRequest);
                    }
                }

                return $confirm ? 'confirmed' : 'declined';
            });
        } catch (QueryException $exception) {
            if (str_contains($exception->getMessage(), 'active_opportunity_id')) {
                return 'taken';
            }

            throw $exception;
        }
    }

    public function stateFor(IdeaEvaluation $evaluation, ?int $viewerId): string
    {
        if ($evaluation->office_id === null) {
            return 'community';
        }

        return DB::transaction(function () use ($evaluation, $viewerId): string {
            $lockedEvaluation = IdeaEvaluation::query()->whereKey($evaluation->id)->lockForUpdate()->firstOrFail();
            $office = Office::query()->whereKey($lockedEvaluation->office_id)->lockForUpdate()->firstOrFail();
            $snapshot = $this->reconcile($lockedEvaluation, $office);

            if (! $office->is_active || $snapshot === null) {
                return 'unavailable';
            }

            $claim = OfficeConfirmationRequest::query()
                ->where('active_opportunity_id', $lockedEvaluation->id)
                ->where('status', OfficeConfirmationRequest::STATUS_CONFIRMED)
                ->first();

            if ($claim === null) {
                return 'available';
            }

            return (int) $claim->requester_user_id === (int) $viewerId
                ? 'office-confirmed'
                : 'taken';
        });
    }

    /**
     * @return Collection<int, OfficeConfirmationRequest>
     */
    public function pendingForRepresentative(User $representative): Collection
    {
        $officeIds = $representative->representedOffices()
            ->where('is_active', true)
            ->pluck('offices.id');

        $evaluationIds = OfficeConfirmationRequest::query()
            ->whereIn('office_id', $officeIds)
            ->where('status', OfficeConfirmationRequest::STATUS_PENDING)
            ->pluck('idea_evaluation_id')
            ->unique();

        foreach ($evaluationIds as $evaluationId) {
            $evaluation = IdeaEvaluation::query()->find($evaluationId);

            if ($evaluation !== null) {
                $this->stateFor($evaluation, null);
            }
        }

        return OfficeConfirmationRequest::query()
            ->whereIn('office_id', $officeIds)
            ->where('status', OfficeConfirmationRequest::STATUS_PENDING)
            ->with(['requester:id,name', 'office:id,name', 'ideaEvaluation:id,idea_title,category,office_id'])
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Return the current snapshot and stale out-of-date pending/confirmed rows.
     *
     * @return array{office_cluster_key: string, office_source_feedback_ids: array<int, int>, office_provenance_fingerprint: string}|null
     */
    private function reconcile(IdeaEvaluation $evaluation, Office $office): ?array
    {
        $snapshot = $office->is_active
            ? $this->generationService->currentOfficeProvenance($evaluation)
            : null;

        $requests = OfficeConfirmationRequest::query()
            ->where('idea_evaluation_id', $evaluation->id)
            ->whereIn('status', [OfficeConfirmationRequest::STATUS_PENDING, OfficeConfirmationRequest::STATUS_CONFIRMED])
            ->get();

        foreach ($requests as $confirmationRequest) {
            if (! $this->requestMatchesSnapshot($confirmationRequest, $snapshot)) {
                $this->markStale($confirmationRequest);
            }
        }

        return $snapshot;
    }

    /**
     * @param  array{office_cluster_key: string, office_source_feedback_ids: array<int, int>, office_provenance_fingerprint: string}|null  $snapshot
     */
    private function requestMatchesSnapshot(OfficeConfirmationRequest $confirmationRequest, ?array $snapshot): bool
    {
        if ($snapshot === null) {
            return false;
        }

        $requestIds = array_map('intval', $confirmationRequest->source_feedback_ids ?? []);
        sort($requestIds);

        return $confirmationRequest->office_cluster_key === $snapshot['office_cluster_key']
            && $requestIds === $snapshot['office_source_feedback_ids']
            && hash_equals($confirmationRequest->provenance_fingerprint, $snapshot['office_provenance_fingerprint']);
    }

    private function markStale(OfficeConfirmationRequest $confirmationRequest): void
    {
        $confirmationRequest->update([
            'status' => OfficeConfirmationRequest::STATUS_STALE,
            'decision_outcome' => $confirmationRequest->decision_outcome
                ?? ($confirmationRequest->status === OfficeConfirmationRequest::STATUS_CONFIRMED ? OfficeConfirmationRequest::STATUS_CONFIRMED : null),
            'stale_at' => now(),
            'active_opportunity_id' => null,
        ]);
    }
}
