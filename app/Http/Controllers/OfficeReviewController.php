<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\User;
use App\Services\CategoryIdeaGenerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OfficeReviewController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $categories = $user->reviewableCategories();

        $query = Feedback::query()
            ->whereIn('category', $categories)
            ->with(['user', 'reviewedBy'])
            ->withCount('votes');

        $status = (string) $request->get('status', 'pending');

        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }

        $problems = $query->latest()->paginate(15)->withQueryString();

        $hasOtherReviewer = $this->hasOtherEligibleReviewer();

        foreach ($problems as $problem) {
            $problem->is_own_submission = $this->isOwnSubmission($problem);
            $problem->self_review_blocked = $problem->is_own_submission && $hasOtherReviewer;
        }

        $counts = [
            'pending' => Feedback::whereIn('category', $categories)->where('status', 'pending')->count(),
            'approved' => Feedback::whereIn('category', $categories)->where('status', 'approved')->count(),
            'rejected' => Feedback::whereIn('category', $categories)->where('status', 'rejected')->count(),
        ];

        return view('office.index', [
            'problems' => $problems,
            'counts' => $counts,
            'status' => $status,
            'officeLabel' => $user->officeLabel(),
            'categories' => $categories,
        ]);
    }

    public function approve(int $id): RedirectResponse
    {
        $feedback = Feedback::findOrFail($id);
        $this->authorizeCategory($feedback->category);
        $this->abortIfSelfReviewBlocked($feedback);

        abort_unless($feedback->status === 'pending', 422, 'Only pending reports can be approved.');

        $feedback->update([
            'status' => 'approved',
            'is_flagged' => false,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        app(CategoryIdeaGenerationService::class)->generateForInstitutionalValidation($feedback);

        return back()->with('success', 'Report approved.');
    }

    public function reject(int $id): RedirectResponse
    {
        $feedback = Feedback::findOrFail($id);
        $this->authorizeCategory($feedback->category);
        $this->abortIfSelfReviewBlocked($feedback);

        abort_unless($feedback->status === 'pending', 422, 'Only pending reports can be rejected.');

        $feedback->update([
            'status' => 'rejected',
            'is_capstone_worthy' => false,
            'capstone_marked_by' => null,
            'capstone_marked_at' => null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Report rejected.');
    }

    private function authorizeCategory(string $category): void
    {
        abort_unless(
            in_array($category, auth()->user()->reviewableCategories(), true),
            403,
            'This report does not belong to your office.'
        );
    }

    /**
     * Determine whether the report was submitted by the user who is reviewing it.
     *
     * Anonymous office-head submissions keep no user_id, so the id recorded in
     * capstone_marked_by identifies the submitter — but only while the report is
     * still pending. Marking a report capstone-worthy requires an approved status,
     * so on an approved row capstone_marked_by belongs to whoever marked it, which
     * may well be a different reviewer than the submitter.
     */
    private function isOwnSubmission(Feedback $feedback): bool
    {
        $userId = auth()->id();

        if ($userId === null) {
            return false;
        }

        if ($feedback->user_id !== null) {
            return (int) $feedback->user_id === $userId;
        }

        return $feedback->status === 'pending'
            && $feedback->capstone_marked_by !== null
            && (int) $feedback->capstone_marked_by === $userId;
    }

    /**
     * Determine whether another verified reviewer with the same office role exists.
     */
    private function hasOtherEligibleReviewer(): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        return User::query()
            ->where('role', $user->role)
            ->whereKeyNot($user->id)
            ->whereNotNull('email_verified_at')
            ->exists();
    }

    /**
     * Block reviewing your own submission while a second reviewer is available.
     */
    private function abortIfSelfReviewBlocked(Feedback $feedback): void
    {
        if ($this->isOwnSubmission($feedback) && $this->hasOtherEligibleReviewer()) {
            abort(403, 'You cannot review your own submission while another reviewer in your office is available.');
        }
    }

    public function markCapstoneWorthy(int $id): RedirectResponse
    {
        $feedback = Feedback::findOrFail($id);
        $this->authorizeCategory($feedback->category);
        $this->abortIfSelfReviewBlocked($feedback);

        abort_unless($feedback->status === 'approved', 422, 'Only approved reports can be marked as capstone ideas.');

        $feedback->update([
            'is_capstone_worthy' => true,
            'capstone_marked_by' => auth()->id(),
            'capstone_marked_at' => now(),
        ]);

        app(CategoryIdeaGenerationService::class)->generateForInstitutionalValidation($feedback);

        return back()->with('success', 'Marked as a capstone idea.');
    }
}
