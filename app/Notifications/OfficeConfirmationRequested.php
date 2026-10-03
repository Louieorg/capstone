<?php

namespace App\Notifications;

use App\Models\OfficeConfirmationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OfficeConfirmationRequested extends Notification
{
    use Queueable;

    /**
     * Marks this notification so the existing redirect and the notification
     * surfaces know where it should take the recipient.
     */
    public const TYPE = 'office_confirmation_requested';

    public function __construct(
        protected string $requesterName,
        protected string $opportunityTitle,
        protected int $ideaEvaluationId,
        protected int $confirmationRequestId
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => self::TYPE,
            'message' => "{$this->requesterName} requested office confirmation for {$this->opportunityTitle}.",
            // Identifiers only. They route the recipient; they are never shown.
            'idea_evaluation_id' => $this->ideaEvaluationId,
            'confirmation_request_id' => $this->confirmationRequestId,
        ];
    }

    public static function forRequest(OfficeConfirmationRequest $confirmationRequest): self
    {
        return new self(
            (string) ($confirmationRequest->requester?->name ?? 'A student'),
            (string) ($confirmationRequest->ideaEvaluation?->idea_title ?? 'a capstone opportunity'),
            (int) $confirmationRequest->idea_evaluation_id,
            (int) $confirmationRequest->id,
        );
    }
}
