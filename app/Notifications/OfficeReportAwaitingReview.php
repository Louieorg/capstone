<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OfficeReportAwaitingReview extends Notification
{
    use Queueable;

    /**
     * Marks this notification so the redirect and the notification surfaces know
     * where it should take the recipient.
     */
    public const TYPE = 'office_report_awaiting_review';

    public function __construct(
        protected string $reportTitle,
        protected string $category,
        protected ?string $officeName,
        protected int $feedbackId
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => self::TYPE,
            'message' => $this->officeName === null
                ? "New office report awaiting review: {$this->reportTitle}."
                : "New office report from {$this->officeName} awaiting review: {$this->reportTitle}.",
            'category' => $this->category,
            // Identifier only; it routes the recipient and is never shown.
            'feedback_id' => $this->feedbackId,
        ];
    }
}
