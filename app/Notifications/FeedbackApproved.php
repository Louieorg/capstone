<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class FeedbackApproved extends Notification
{
    use Queueable;

    protected $feedback;

    public function __construct($feedback)
    {
        $this->feedback = $feedback;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => 'Your submitted problem has been approved.',
            'feedback_id' => $this->feedback->id
        ];
    }
}