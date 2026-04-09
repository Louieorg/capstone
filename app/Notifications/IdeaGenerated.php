<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class IdeaGenerated extends Notification
{
    use Queueable;

    public function __construct(
        protected string $ideaTitle,
        protected string $category
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => "A new capstone idea has been generated from problems in {$this->category}!",
            'idea_title' => $this->ideaTitle,
            'category' => $this->category,
        ];
    }
}
