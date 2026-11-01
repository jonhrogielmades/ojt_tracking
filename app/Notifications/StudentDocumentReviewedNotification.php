<?php

namespace App\Notifications;

use App\Models\StudentDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class StudentDocumentReviewedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $documentId;

    public string $title;

    public string $reviewStatus;

    public ?string $reviewNotes;

    public string $actionUrl;

    public function __construct(StudentDocument $document)
    {
        $this->documentId = $document->id;
        $this->title = $document->title;
        $this->reviewStatus = $document->review_status;
        $this->reviewNotes = $document->review_notes;
        $this->actionUrl = route('documents.index');
        $this->afterCommit();
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        $status = ucfirst($this->reviewStatus);
        $notes = trim((string) $this->reviewNotes);

        return [
            'title' => "Document {$status}",
            'message' => $notes !== ''
                ? $this->title.': '.$notes
                : $this->title.' review status was updated.',
            'document_id' => $this->documentId,
            'review_status' => $this->reviewStatus,
            'action_url' => $this->actionUrl,
        ];
    }
}
