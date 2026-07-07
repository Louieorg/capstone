<?php

namespace App\Http\Controllers;

use App\Models\FeedbackEvidence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;

class FeedbackEvidenceController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->is_admin ?? false, 403);

        $evidence = FeedbackEvidence::query()->with('feedback', 'user')->latest()->paginate(30);

        return view('admin.evidence.index', compact('evidence'));
    }

    public function download(FeedbackEvidence $evidence)
    {
        abort_unless(auth()->user()->is_admin ?? false, 403);

        return Storage::disk('public')->download($evidence->file_path, $evidence->file_name);
    }

    public function destroy(FeedbackEvidence $evidence)
    {
        abort_unless(auth()->user()->is_admin ?? false, 403);

        Storage::disk('public')->delete($evidence->file_path);
        $evidence->delete();

        return Redirect::back()->with('success', 'Evidence deleted.');
    }
}
