<?php

namespace App\Http\Controllers;

use App\Models\SavedIdea;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class IdeaController extends Controller
{
    public function save(Request $request)
{
    if (!auth()->check()) {
    return redirect()->back()->with('showLogin', true);
}
    SavedIdea::create([
        'user_id' => Auth::id(),
        'title' => $request->title,
        'description' => $request->description,
        'category' => $request->category,
    ]);

    return back()->with('success', 'Idea saved successfully.');
}

    public function updateStatus(Request $request, $id)
{
    $request->validate([
        'status' => 'required|in:Exploring,Adopted,In Progress,Completed'
    ]);
 
    $idea = SavedIdea::where('id', $id)
                     ->where('user_id', auth()->id())  // users can only update their own
                     ->firstOrFail();
 
    $idea->update(['status' => $request->status]);
 
    return back()->with('success', 'Idea status updated.');
}
}
