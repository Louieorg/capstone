<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\IdeaEvaluation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $submittedProblems = $user->feedbacks()->count();
        $supportedProblems = $user->votes()->count();
        $evidenceContributions = $user->comments()->count();
        $contributedCategories = $user->feedbacks()
            ->where('status', 'approved')
            ->pluck('category')
            ->filter()
            ->unique();
        $generatedIdeasContributedTo = IdeaEvaluation::query()
            ->whereIn('category', $contributedCategories)
            ->count();
        $communityContributionScore = ($submittedProblems * 3)
            + ($supportedProblems * 2)
            + ($evidenceContributions * 2)
            + ($generatedIdeasContributedTo * 4);

        return view('profile.edit', [
            'user' => $user,
            'submittedProblems' => $submittedProblems,
            'supportedProblems' => $supportedProblems,
            'evidenceContributions' => $evidenceContributions,
            'generatedIdeasContributedTo' => $generatedIdeasContributedTo,
            'communityContributionScore' => $communityContributionScore,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
