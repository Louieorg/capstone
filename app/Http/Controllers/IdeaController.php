<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSavedIdeaRequest;
use App\Models\IdeaEvaluation;
use App\Models\SavedIdea;
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
     * record. No clustering, idea generation, evaluation writes, or AI calls
     * happen here.
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
