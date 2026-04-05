<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class IdeaGenerated extends Notification
{
    use Queueable;

    protected $ideaTitle;
    protected $category;

    public function __construct($ideaTitle, $category)
    {
        $this->ideaTitle = $ideaTitle;
        $this->category = $category;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => "A new capstone idea has been generated from problems in {$this->category}!",
            'idea_title' => $this->ideaTitle,
            'category' => $this->category,
        ];
    }
}
