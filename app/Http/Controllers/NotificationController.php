<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function dropdown(): JsonResponse
    {
        $notifications = auth()->user()
            ->notifications()
            ->latest()
            ->take(6)
            ->get();

        return response()->json([
            'html' => view('layouts.partials.notification-items', compact('notifications'))->render(),
            'has_notifications' => $notifications->isNotEmpty(),
        ]);
    }
}
