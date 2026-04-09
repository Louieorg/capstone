<?php

use App\Http\Controllers\AdviserController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\IdeaController;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES (No authentication required)
|--------------------------------------------------------------------------
*/

// Landing page (FIRST PAGE)
Route::get('/', function () {
    return view('welcome');
})->name('landing');

// Home/dashboard page
Route::get('/home', [FeedbackController::class, 'home'])->name('home');

Route::get('/submit', [FeedbackController::class, 'create'])
    ->name('feedback.create');

Route::get('/problems', [FeedbackController::class, 'index'])
    ->name('feedback.index');

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

});

require __DIR__.'/auth.php';
