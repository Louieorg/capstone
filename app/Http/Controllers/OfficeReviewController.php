<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
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

        $feedback->update([
            'status' => 'approved',
            'is_flagged' => false,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Report approved.');
    }

    public function reject(int $id): RedirectResponse
    {
        $feedback = Feedback::findOrFail($id);
        $this->authorizeCategory($feedback->category);

        $feedback->update([
            'status' => 'rejected',
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
}
