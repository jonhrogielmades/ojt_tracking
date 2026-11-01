<?php

namespace App\Notifications;

use App\Models\CorrectionRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CorrectionRequestReviewedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $correctionRequestId;

    public string $status;

    public string $statusLabel;

    public string $message;

    public function __construct(CorrectionRequest $correctionRequest)
    {
        $this->correctionRequestId = $correctionRequest->id;
        $this->status = $correctionRequest->status->value;
        $this->statusLabel = $correctionRequest->status->label();
        $this->message = $correctionRequest->reviewer_response ?: 'Your correction request has been reviewed.';
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Correction request '.$this->statusLabel,
            'message' => $this->message,
            'status' => $this->status,
            'correction_request_id' => $this->correctionRequestId,
            'action_url' => '/corrections',
        ];
    }
}
