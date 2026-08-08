<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(): View
    {
        $categoryData = Feedback::query()
            ->where('status', 'approved')
            ->notFlagged()
            ->select('category')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $affectedGroupData = Feedback::query()
            ->where('status', 'approved')
            ->notFlagged()
            ->select('affected_group')
            ->get()
            ->flatMap(fn (Feedback $feedback): array => is_array($feedback->affected_group) ? $feedback->affected_group : [])
            ->filter(fn ($group): bool => is_string($group) && ! empty(trim($group)))
            ->countBy()
            ->map(fn (int $count, string $group): object => (object) [
                'affected_group' => $group,
                'total' => $count,
            ])
            ->values();

        $monthlyReports = Feedback::query()
            ->selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, COUNT(*) as total')
            ->notFlagged()
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->map(function ($row) {
                $row->label = \Carbon\Carbon::createFromDate($row->year, $row->month, 1)->format('M Y');

                return $row;
            });

        $impactLevels = Feedback::query()
            ->select('affected_users')
            ->notFlagged()
            ->selectRaw('COUNT(*) as total')
            ->groupBy('affected_users')
            ->get();

        $statusBreakdown = [
            'approved' => Feedback::where('status', 'approved')->count(),
            'pending' => Feedback::where('status', 'pending')->count(),
            'rejected' => Feedback::where('status', 'rejected')->count(),
            'flagged' => Feedback::where('is_flagged', true)->count(),
        ];

        $frequencyBreakdown = Feedback::query()
            ->select('frequency')
            ->notFlagged()
            ->selectRaw('COUNT(*) as total')
            ->groupBy('frequency')
            ->get();

        return view('admin.analytics.index', compact(
            'categoryData',
            'affectedGroupData',
            'monthlyReports',
            'impactLevels',
            'statusBreakdown',
            'frequencyBreakdown'
        ));
    }
}
