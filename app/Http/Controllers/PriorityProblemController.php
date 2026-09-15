<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PriorityProblemController extends Controller
{
    private const VALID_STATUSES = ['pending', 'taken', 'resolved'];

    public function index(Request $request): View
    {
        $query = Feedback::query()
            ->where('is_priority', true)
            ->with(['user', 'takenBy'])
            ->withCount('votes');

        $status = (string) $request->get('status', 'all');

        if (in_array($status, self::VALID_STATUSES, true)) {
            $query->where('priority_status', $status);
        }

        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        $sort = (string) $request->get('sort', 'newest');

        match ($sort) {
            'oldest' => $query->oldest(),
            default => $query->latest(),
        };

        $problems = $query->paginate(15)->withQueryString();

        $departments = Feedback::query()
            ->where('is_priority', true)
            ->whereNotNull('department')
            ->select('department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        $counts = [
            'all' => Feedback::where('is_priority', true)->count(),
            'pending' => Feedback::where('is_priority', true)->where('priority_status', 'pending')->count(),
            'taken' => Feedback::where('is_priority', true)->where('priority_status', 'taken')->count(),
            'resolved' => Feedback::where('is_priority', true)->where('priority_status', 'resolved')->count(),
        ];

        return view('admin.priority.index', compact('problems', 'departments', 'counts', 'status', 'sort'));
    }

    public function take(int $id): RedirectResponse
    {
        Feedback::findOrFail($id)->update([
            'priority_status' => 'taken',
            'priority_taken_by' => auth()->id(),
            'priority_taken_at' => now(),
        ]);

        return back()->with('success', 'Priority problem claimed.');
    }

    public function resolve(int $id): RedirectResponse
    {
        Feedback::findOrFail($id)->update([
            'priority_status' => 'resolved',
            'priority_resolved_at' => now(),
        ]);

        return back()->with('success', 'Priority problem marked as resolved.');
    }

    public function reopen(int $id): RedirectResponse
    {
        Feedback::findOrFail($id)->update([
            'priority_status' => 'pending',
            'priority_taken_by' => null,
            'priority_taken_at' => null,
            'priority_resolved_at' => null,
        ]);

        return back()->with('success', 'Priority problem reopened.');
    }
}
