<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSavedIdeaRequest;
use App\Models\IdeaEvaluation;
use App\Models\SavedIdea;
use App\Services\CategoryIdeaGenerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class IdeaController extends Controller
{
    /**
     * Save an existing DSS-generated idea for the authenticated student.
     *
     * The submitted title and category must match a persisted idea_evaluations
     * record, and that record's category must still be a current DSS-qualified
     * category. No clustering, idea generation, evaluation writes, or AI calls
     * happen here.
     *
     * This guard is applied only when a new saved idea would be created. An
     * existing saved idea is a historical personal record and is never
     * re-validated, so a retained evaluation that later goes stale cannot
     * invalidate a student's saved idea.
     */
    public function save(StoreSavedIdeaRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $evaluation = IdeaEvaluation::query()
            ->where('idea_title', $validated['title'])
            ->where('category', $validated['category'])
            ->first();

        if ($evaluation === null) {
            throw ValidationException::withMessages([
                'title' => 'That capstone opportunity could not be found. Open it from its category page and save it again.',
            ]);
        }

        // The DSS decides whether an opportunity is still current. Stored
        // evaluations are retained, but only a currently qualifying category may
        // back a brand-new saved idea. This reuses the same read-only
        // qualification helper the Home, Discover and Capstone Opportunities
        // surfaces use, so a save can never introduce an opportunity that those
        // pages deliberately hide.
        $qualifyingCategories = app(CategoryIdeaGenerationService::class)->qualifyingCategories();

        // Strict comparison: both sides are raw category strings, and identity
        // is compared in PHP so the result never depends on the database
        // collation (MySQL's utf8mb4_unicode_ci is case-insensitive, SQLite is
        // not). Identical behaviour in every environment.
        if (! in_array($evaluation->category, $qualifyingCategories->all(), true)) {
            throw ValidationException::withMessages([
                'title' => 'That capstone opportunity is no longer a current capstone opportunity. Open it from its category page and save it again.',
            ]);
        }

        $idea = SavedIdea::query()->firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'idea_evaluation_id' => $evaluation->id,
            ],
            [
                'title' => $evaluation->idea_title,
                'description' => $validated['description'],
                'category' => $evaluation->category,
            ]
        );

        $redirectUrl = route('feedback.category', [
            'category' => $evaluation->category,
            'idea' => $evaluation->idea_title,
        ]).'#idea-'.Str::slug($evaluation->idea_title);

        if (! $idea->wasRecentlyCreated) {
            return redirect()->to($redirectUrl)->with('info', 'This idea is already in your saved ideas.');
        }

        return redirect()->to($redirectUrl)->with('success', 'Idea saved successfully.');
    }

    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:Exploring,Adopted,In Progress,Completed',
        ]);

        $idea = SavedIdea::query()
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $idea->update(['status' => $validated['status']]);

        return back()->with('success', 'Idea status updated.');
    }
}
