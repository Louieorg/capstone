<?php

use App\Http\Controllers\AdviserController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\IdeaController;
use App\Http\Controllers\ProfileController;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES (No authentication required)
|--------------------------------------------------------------------------
*/

// Landing page (FIRST PAGE)
Route::get('/', [FeedbackController::class, 'home'])->name('landing');

// Home/dashboard page
Route::get('/home', [FeedbackController::class, 'home'])->name('home');

Route::get('/discover', [FeedbackController::class, 'index'])
    ->name('discover');

Route::get('/submit', [FeedbackController::class, 'create'])
    ->name('feedback.create');

Route::get('/problems', [FeedbackController::class, 'index'])
    ->name('feedback.index');

Route::get('/problems/{feedback}', [FeedbackController::class, 'show'])
    ->name('feedback.show');

Route::get('/summary', [FeedbackController::class, 'summary'])
    ->name('feedback.summary');

Route::get('/category/{category}', [FeedbackController::class, 'showCategory'])
    ->name('feedback.category');

Route::get('/feedback/submitted', function () {
    return view('feedback.submitted');
})->name('feedback.submitted');

/* Duplicate detection (rate-limited to prevent abuse) */
Route::get('/similar-problems', [FeedbackController::class, 'similarProblems'])
    ->middleware('throttle:20,1');

/*
|--------------------------------------------------------------------------
| AUTH ROUTES (Google Login)
|--------------------------------------------------------------------------
*/

Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])
    ->name('google.login');

Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);

/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES (Authenticated Users)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |--------------------------------------------------------------------------
    | USER FEATURES
    |--------------------------------------------------------------------------
    */

    // Submit feedback (protected + rate limited)
    Route::post('/submit', [FeedbackController::class, 'store'])
        ->name('feedback.store')
        ->middleware('throttle:5,1');

    // Vote (protected + rate limited)
    Route::post('/feedback/{id}/vote', [FeedbackController::class, 'vote'])
        ->name('feedback.vote')
        ->middleware('throttle:10,1');

    Route::post('/feedback/{feedback}/comments', [FeedbackController::class, 'storeComment'])
        ->name('feedback.comments.store')
        ->middleware('throttle:5,1');

    // Save idea
    Route::post('/idea/save', [IdeaController::class, 'save'])
        ->name('idea.save');

    Route::patch('/my-ideas/{id}/status', [IdeaController::class, 'updateStatus'])
        ->name('idea.updateStatus')
        ->middleware('auth');

    // View saved ideas
    Route::get('/my-ideas', function () {
        $ideas = \App\Models\SavedIdea::where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('user.ideas', compact('ideas'));
    })->name('user.ideas');

    // Notifications
    Route::get('/notifications', function () {
        $user = auth()->user();

        $user->unreadNotifications->markAsRead();
        $user->unsetRelation('unreadNotifications');

        $notifications = $user->notifications()->latest()->get();

        return view('notifications.index', compact('notifications'));
    })->name('notifications');

    Route::get('/notifications/{notification}', function (DatabaseNotification $notification) {
        abort_unless($notification->notifiable_id === auth()->id(), 404);

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        $category = $notification->data['category'] ?? null;
        $ideaTitle = $notification->data['idea_title'] ?? null;

        if ($category && $ideaTitle) {
            return redirect()->to(
                route('feedback.category', ['category' => $category, 'idea' => $ideaTitle]).'#idea-'.\Illuminate\Support\Str::slug($ideaTitle)
            );
        }

        return redirect()->route('notifications');
    })->name('notifications.redirect');

    /*
    |--------------------------------------------------------------------------
    | ADVISER FEATURES
    |--------------------------------------------------------------------------
    */

    Route::middleware('can:isAdviser')->group(function () {

        // Adviser dashboard
        Route::get('/adviser/dashboard', [AdviserController::class, 'dashboard'])
            ->name('adviser.dashboard');

        // Submit review (rate limited)
        Route::post('/adviser/dashboard', [FeedbackController::class, 'storeReview'])
            ->name('adviser.review')
            ->middleware('throttle:3,1');

    });

    /*
    |--------------------------------------------------------------------------
    | ADMIN FEATURES
    |--------------------------------------------------------------------------
    */

    Route::middleware('can:isAdmin')->group(function () {

        // Admin dashboard
        Route::get('/admin', [FeedbackController::class, 'admin'])
            ->name('admin.dashboard');

        // Approve feedback
        Route::patch('/admin/feedback/{id}/approve', [FeedbackController::class, 'approve'])
            ->name('feedback.approve');

        // Reject feedback
        Route::patch('/admin/feedback/{id}/reject', [FeedbackController::class, 'reject'])
            ->name('feedback.reject');

    });

    // Admin evidence management
    Route::middleware('can:isAdmin')->group(function () {
        Route::get('/admin/evidence', [\App\Http\Controllers\FeedbackEvidenceController::class, 'index'])
            ->name('admin.evidence');

        Route::get('/admin/evidence/{evidence}/download', [\App\Http\Controllers\FeedbackEvidenceController::class, 'download'])
            ->name('admin.evidence.download');

        Route::delete('/admin/evidence/{evidence}', [\App\Http\Controllers\FeedbackEvidenceController::class, 'destroy'])
            ->name('admin.evidence.destroy');
    });

});

require __DIR__.'/auth.php';
