<?php

namespace App\Notifications;

use App\Models\WeeklySubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class WeeklySubmissionReviewedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $submissionId;

    public string $status;

    public string $statusLabel;

    public string $message;

    public function __construct(WeeklySubmission $submission)
    {
        $this->submissionId = $submission->id;
        $this->status = $submission->status->value;
        $this->statusLabel = $submission->status->label();
        $this->message = $submission->reviewer_comments
            ?: 'Your week of '.$submission->week_start->format('M j, Y').' has been reviewed.';
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
            'title' => 'Weekly submission '.$this->statusLabel,
            'message' => $this->message,
            'status' => $this->status,
            'submission_id' => $this->submissionId,
            'action_url' => '/submissions/'.$this->submissionId,
        ];
    }
}
