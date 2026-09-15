<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $minimumVotes = (int) Setting::get('minimum_votes_for_idea_generation', 10);
        $minimumReports = (int) Setting::get('minimum_reports_for_idea_generation', 3);

        return view('admin.settings.index', compact('minimumVotes', 'minimumReports'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'minimum_votes_for_idea_generation' => 'required|integer|min:1|max:1000',
            'minimum_reports_for_idea_generation' => 'required|integer|min:1|max:1000',
        ]);

        Setting::set('minimum_votes_for_idea_generation', $validated['minimum_votes_for_idea_generation']);
        Setting::set('minimum_reports_for_idea_generation', $validated['minimum_reports_for_idea_generation']);

        return back()->with('success', 'System thresholds updated.');
    }
}
