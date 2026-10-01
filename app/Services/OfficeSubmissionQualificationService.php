<?php

namespace App\Services;

use App\Models\Feedback;
use App\Models\Office;
use App\Models\User;

/**
 * Representative-based office DSS qualification.
 *
 * A report that an authenticated office representative submits on behalf of an
 * office they currently represent is institutionally qualified for that office.
 * It enters the existing DSS generation pipeline without the normal community
 * vote and report thresholds, and it is scoped to that office.
 *
 * This is DSS qualification only. It never marks an opportunity Office-Confirmed
 * or Taken: that remains the separate Phase 4 Office Confirmation step, and it
 * grants no review authority over any category, office, or report.
 */
class OfficeSubmissionQualificationService
{
    /**
     * Whether an office was selected by a user who currently represents it.
     */
    public function isRepresentativeSubmission(User|int|null $user, mixed $officeId): bool
    {
        if ($officeId === null || $officeId === '') {
            return false;
        }

        $office = Office::query()->find((int) $officeId);

        if (! $office || ! $office->is_active) {
            return false;
        }

        return (int) $office->representative_user_id === $this->userId($user);
    }

    /**
     * Whether an already-stored report satisfies the qualification rule.
     *
     * The report must be approved and non-flagged, it must be associated with
     * an office, and the account that submitted it must currently represent
     * that same office.
     */
    public function qualifies(Feedback $feedback): bool
    {
        if ($feedback->office_id === null || $feedback->user_id === null) {
            return false;
        }

        if ($feedback->status !== 'approved' || (bool) $feedback->is_flagged) {
            return false;
        }

        return $this->isRepresentativeSubmission((int) $feedback->user_id, $feedback->office_id);
    }

    /**
     * Mark a qualifying report as capstone-worthy.
     *
     * Idempotent: an already-qualified report is left exactly as it is, so no
     * marking, reviewer, or timestamp is rewritten on repeated runs.
     */
    public function qualify(Feedback $feedback): bool
    {
        if ((bool) $feedback->is_capstone_worthy || ! $this->qualifies($feedback)) {
            return false;
        }

        $feedback->update([
            'is_capstone_worthy' => true,
            'capstone_marked_by' => $feedback->user_id,
            'capstone_marked_at' => now(),
        ]);

        return true;
    }

    private function userId(User|int|null $user): int
    {
        if ($user instanceof User) {
            return (int) $user->getKey();
        }

        return (int) $user;
    }
}
