<?php

use App\Http\Controllers\AdviserController;
use App\Http\Controllers\AdviserReviewController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\CategoryAssignmentController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\IdeaController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OfficeReviewController;
use App\Http\Controllers\PriorityProblemController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:isOfficeReviewer'])->group(function () {

    Route::get('/office/review', [OfficeReviewController::class, 'index'])
        ->name('office.review.index');

    Route::patch('/office/review/{id}/approve', [OfficeReviewController::class, 'approve'])
        ->name('office.review.approve');

    Route::patch('/office/review/{id}/reject', [OfficeReviewController::class, 'reject'])
        ->name('office.review.reject');

    Route::patch('/office/review/{id}/mark-capstone', [OfficeReviewController::class, 'markCapstoneWorthy'])
        ->name('office.review.mark-capstone'); // inside your can:isOfficeReviewer group
});

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES (No authentication required)
|--------------------------------------------------------------------------
*/

// Landing page (FIRST PAGE)
Route::get('/', [FeedbackController::class, 'landing'])->name('landing');

// Home/dashboard page
Route::get('/home', [FeedbackController::class, 'home'])->name('home');

Route::get('/discover', [FeedbackController::class, 'index'])
    ->name('discover');

Route::get('/capstone-opportunities', [FeedbackController::class, 'capstoneOpportunities'])
    ->name('capstone.opportunities');

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

Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
    ->name('google.callback');

/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES (Authenticated Users)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

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

    Route::get('/priority-problems', [FeedbackController::class, 'priorityIndex'])
        ->name('priority.index');

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

    Route::post('/category/{category}/enhance-idea', [FeedbackController::class, 'enhanceIdea'])
        ->name('idea.enhance');

    Route::patch('/my-ideas/{id}/status', [IdeaController::class, 'updateStatus'])
        ->name('idea.updateStatus')
        ->middleware('auth');

    // View saved ideas
    Route::get('/my-ideas', function () {
        $ideas = \App\Models\SavedIdea::where('user_id', auth()->id())
            ->with('ideaEvaluation')
            ->latest()
            ->get();

        $responsibleOffices = \App\Models\CategoryAssignment::query()
            ->pluck('office', 'category')
            ->map(fn (string $office): ?string => \App\Models\CategoryAssignment::labelFor($office));

        return view('user.ideas', compact('ideas', 'responsibleOffices'));
    })->name('user.ideas');

    // Notifications
    Route::get('/notifications', function () {
        $user = auth()->user();

        $user->unreadNotifications->markAsRead();
        $user->unsetRelation('unreadNotifications');

        $notifications = $user->notifications()->latest()->get();

        return view('notifications.index', compact('notifications'));
    })->name('notifications');

    Route::get('/notifications/dropdown', [NotificationController::class, 'dropdown'])
        ->name('notifications.dropdown');

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

        Route::get('/adviser/dashboard', [AdviserController::class, 'dashboard'])
            ->name('adviser.dashboard');

        Route::get('/adviser/evaluations', [AdviserController::class, 'evaluations'])
            ->name('adviser.evaluations.index');

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

        // Manage feedback
        Route::get('/admin/feedback', [FeedbackController::class, 'manageFeedback'])
            ->name('admin.feedback.index');

        Route::patch('/admin/feedback/{id}/approve', [FeedbackController::class, 'approve'])
            ->name('feedback.approve');

        Route::patch('/admin/feedback/{id}/reject', [FeedbackController::class, 'reject'])
            ->name('feedback.reject');

        // Manage users
        Route::get('/admin/users', [UserController::class, 'index'])
            ->name('admin.users.index');

        Route::patch('/admin/users/{user}/role', [UserController::class, 'updateRole'])
            ->name('admin.users.role');

        Route::delete('/admin/users/{user}', [UserController::class, 'destroy'])
            ->name('admin.users.destroy');
        Route::get('/admin/recommendations', [RecommendationController::class, 'index'])
            ->name('admin.recommendations.index');
        Route::get('/admin/adviser-reviews', [AdviserReviewController::class, 'index'])
            ->name('admin.adviser-reviews.index');
        Route::get('/admin/analytics', [AnalyticsController::class, 'index'])
            ->name('admin.analytics.index');
        Route::get('/admin/reports', [ReportController::class, 'index'])
            ->name('admin.reports.index');

        Route::get('/admin/reports/export', [ReportController::class, 'export'])
            ->name('admin.reports.export');
        Route::get('/admin/priority', [PriorityProblemController::class, 'index'])
            ->name('admin.priority.index');

        Route::patch('/admin/priority/{id}/take', [PriorityProblemController::class, 'take'])
            ->name('admin.priority.take');

        Route::patch('/admin/priority/{id}/resolve', [PriorityProblemController::class, 'resolve'])
            ->name('admin.priority.resolve');

        Route::patch('/admin/priority/{id}/reopen', [PriorityProblemController::class, 'reopen'])
            ->name('admin.priority.reopen');

        Route::patch('/admin/users/{user}/office-head', [UserController::class, 'updateOfficeHead'])
            ->name('admin.users.office-head');

        Route::get('/admin/settings', [SettingController::class, 'index'])
            ->name('admin.settings');

        Route::patch('/admin/settings', [SettingController::class, 'update'])
            ->name('admin.settings.update');
        Route::get('/admin/category-assignments', [CategoryAssignmentController::class, 'index'])
            ->name('admin.category-assignments.index');

        Route::post('/admin/category-assignments', [CategoryAssignmentController::class, 'update'])
            ->name('admin.category-assignments.update');
        Route::patch('/admin/priority/{id}/mark-capstone', [FeedbackController::class, 'markCapstoneWorthy'])
            ->name('admin.priority.mark-capstone'); // inside your can:isAdmin group

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
