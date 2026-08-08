<?php

namespace App\Http\Controllers;

use App\Models\AdviserReview;
use App\Models\Feedback;
use App\Models\IdeaEvaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private const VALID_TYPES = ['feedback', 'recommendations', 'adviser_reviews'];

    public function index(Request $request): View
    {
        $type = (string) $request->get('type', 'feedback');

        if (! in_array($type, self::VALID_TYPES, true)) {
            $type = 'feedback';
        }

        $categories = Feedback::query()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('admin.reports.index', compact('type', 'categories'));
    }

    public function export(Request $request): StreamedResponse
    {
        $type = (string) $request->get('type', 'feedback');

        if (! in_array($type, self::VALID_TYPES, true)) {
            abort(422, 'Invalid report type.');
        }

        [$filename, $headers, $rows] = match ($type) {
            'recommendations' => $this->buildRecommendationsExport($request),
            'adviser_reviews' => $this->buildAdviserReviewsExport($request),
            default => $this->buildFeedbackExport($request),
        };

        return Response::stream(function () use ($headers, $rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function buildFeedbackExport(Request $request): array
    {
        $query = Feedback::query()->with('user')->withCount('votes');

        $this->applyCommonFilters($query, $request);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $rows = $query->get()->map(fn (Feedback $item): array => [
            $item->id,
            $item->title,
            $item->category,
            $item->department,
            $item->status,
            $item->is_flagged ? 'Yes' : 'No',
            $item->votes_count,
            $item->frequency,
            $item->affected_users,
            $item->is_anonymous ? 'Anonymous' : ($item->user->name ?? 'Unknown'),
            $item->created_at->format('Y-m-d H:i'),
        ]);

        return [
            'likha-feedback-report-'.now()->format('Y-m-d').'.csv',
            ['ID', 'Title', 'Category', 'Department', 'Status', 'Flagged', 'Votes', 'Frequency', 'Affected Users', 'Submitted By', 'Submitted At'],
            $rows,
        ];
    }

    private function buildRecommendationsExport(Request $request): array
    {
        $query = IdeaEvaluation::query();

        $this->applyCommonFilters($query, $request);

        $rows = $query->get()->map(fn (IdeaEvaluation $item): array => [
            $item->id,
            $item->idea_title,
            $item->category,
            $item->feasibility,
            $item->impact,
            $item->complexity,
            $item->innovation,
            $item->overall_score,
            $item->recommendation,
            $item->adviser_feasibility,
            $item->adviser_impact,
            $item->adviser_complexity,
            $item->adviser_innovation,
            $item->final_score,
            $item->created_at->format('Y-m-d H:i'),
        ]);

        return [
            'likha-recommendations-report-'.now()->format('Y-m-d').'.csv',
            ['ID', 'Idea Title', 'Category', 'Feasibility', 'Impact', 'Complexity', 'Innovation', 'Overall Score', 'System Recommendation', 'Adviser Feasibility', 'Adviser Impact', 'Adviser Complexity', 'Adviser Innovation', 'Final Score', 'Generated At'],
            $rows,
        ];
    }

    private function buildAdviserReviewsExport(Request $request): array
    {
        $query = AdviserReview::query()->with('user');

        $this->applyCommonFilters($query, $request);

        if ($request->filled('recommendation')) {
            $query->where('recommendation', $request->recommendation);
        }

        $rows = $query->get()->map(fn (AdviserReview $item): array => [
            $item->id,
            $item->idea_title,
            $item->category,
            $item->user->name ?? 'Unknown adviser',
            $item->recommendation,
            $item->comment,
            $item->created_at->format('Y-m-d H:i'),
        ]);

        return [
            'likha-adviser-reviews-report-'.now()->format('Y-m-d').'.csv',
            ['ID', 'Idea Title', 'Category', 'Adviser', 'Recommendation', 'Comment', 'Reviewed At'],
            $rows,
        ];
    }

    private function applyCommonFilters($query, Request $request): void
    {
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
    }
}
