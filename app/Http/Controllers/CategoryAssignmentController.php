<?php

namespace App\Http\Controllers;

use App\Models\CategoryAssignment;
use App\Models\Feedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryAssignmentController extends Controller
{
    public function index(): View
    {
        $allCategories = Feedback::query()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $assignments = CategoryAssignment::query()
            ->pluck('office', 'category');

        return view('admin.category-assignments.index', compact('allCategories', 'assignments'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'string'],
            'office' => ['nullable', 'in:office_academic,office_chief'],
        ]);

        if (empty($validated['office'])) {
            CategoryAssignment::where('category', $validated['category'])->delete();

            return back()->with('success', "'{$validated['category']}' is now unassigned — visible to RDE only.");
        }

        CategoryAssignment::updateOrCreate(
            ['category' => $validated['category']],
            ['office' => $validated['office']]
        );

        return back()->with('success', "'{$validated['category']}' assigned.");
    }
}
