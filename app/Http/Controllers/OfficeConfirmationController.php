<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\IdeaEvaluation;
use App\Models\Office;
use App\Models\OfficeConfirmationRequest;
use App\Notifications\OfficeConfirmationRequested;
use App\Services\OfficeConfirmationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OfficeConfirmationController extends Controller
{
    public function __construct(private OfficeConfirmationService $confirmationService) {}

    public function index(Request $request): View
    {
        $representative = $request->user();

        // The queue is the office's own worklist, so only someone who currently
        // represents an active office may open it. Representation is resolved
        // from Office.representative_user_id right now — not from a role, a
        // category assignment, or from having reported to an office — so a
        // former representative, a student, a reviewer, or an admin is refused,
        // while a representative of several active offices is allowed.
        abort_unless(
            $representative->isOfficeRepresentative(),
            403,
            'Only the current representative of an active office can view office confirmation requests.'
        );

        $confirmationRequests = $this->confirmationService->pendingForRepresentative($representative);

        foreach ($confirmationRequests as $confirmationRequest) {
            $feedbacks = Feedback::query()
                ->whereIn('id', $confirmationRequest->source_feedback_ids)
                ->where('office_id', $confirmationRequest->office_id)
                ->get(['id', 'title', 'category']);

            $confirmationRequest->setRelation('sourceFeedbacks', $feedbacks);
        }

        return view('office.confirmations.index', compact('confirmationRequests'));
    }

    public function store(Request $request, IdeaEvaluation $evaluation): RedirectResponse
    {
        $result = $this->confirmationService->request($request->user(), $evaluation);

        // Only a request that was actually created notifies anyone. The service
        // returns after its transaction has committed, and 'pending' means an
        // existing request of this student's was reused, so neither case notifies.
        if ($result['result'] === 'requested' && $result['request'] !== null) {
            $this->notifyCurrentRepresentative($result['request']);
        }

        return match ($result['result']) {
            'requested' => back()->with('success', 'Confirmation request sent. Consultation happens outside LIKHA; this request does not reserve the opportunity.'),
            'pending' => back()->with('info', 'Your confirmation request is still pending. The opportunity remains available until the office confirms.'),
            'taken' => back()->withErrors(['confirmation' => 'This opportunity is no longer available to request.']),
            'community' => back()->withErrors(['confirmation' => 'Community opportunities do not use office confirmation.']),
            default => back()->withErrors(['confirmation' => 'This office opportunity is no longer current or available.']),
        };
    }

    /**
     * Notify the office's representative as they are right now.
     *
     * The Office row is read again here, so a representative who changed between
     * the request being created and this call is the one who is told. No other
     * role is ever notified, and this grants nobody any authority: only this
     * same representative_user_id can still confirm or decline.
     */
    private function notifyCurrentRepresentative(OfficeConfirmationRequest $confirmationRequest): void
    {
        $representative = Office::query()
            ->whereKey($confirmationRequest->office_id)
            ->first()
            ?->representative;

        $representative?->notify(OfficeConfirmationRequested::forRequest($confirmationRequest));
    }

    public function confirm(Request $request, OfficeConfirmationRequest $confirmationRequest): RedirectResponse
    {
        $result = $this->confirmationService->decide($request->user(), $confirmationRequest->id, true);

        return $this->decisionResponse($result, true);
    }

    public function decline(Request $request, OfficeConfirmationRequest $confirmationRequest): RedirectResponse
    {
        $result = $this->confirmationService->decide($request->user(), $confirmationRequest->id, false);

        return $this->decisionResponse($result, false);
    }

    private function decisionResponse(string $result, bool $confirm): RedirectResponse
    {
        return match ($result) {
            'confirmed' => back()->with('success', 'The office opportunity is now confirmed for this group leader.'),
            'declined' => back()->with('success', 'The request was declined. The opportunity remains available if it is still current.'),
            'taken' => back()->withErrors(['confirmation' => 'Another group has already been confirmed for this opportunity.']),
            'stale' => back()->withErrors(['confirmation' => 'This opportunity is stale or inactive and cannot be confirmed.']),
            default => back()->with('info', $confirm ? 'This request is no longer pending.' : 'This request has already been decided.'),
        };
    }
}
