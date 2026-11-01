<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Notifications\Notification;

class AnnouncementPublished extends Notification
{
    public int $announcementId;

    public string $title;

    public string $message;

    public string $audience;

    public ?string $publishedAt;

    public string $actionUrl;

    public function __construct(Announcement $announcement)
    {
        $this->announcementId = $announcement->id;
        $this->title = $announcement->title;
        $this->message = $announcement->body;
        $this->audience = $announcement->audience;
        $this->publishedAt = $announcement->published_at?->toIso8601String();
        $this->actionUrl = route('announcements.index');
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'announcement_id' => $this->announcementId,
            'title' => $this->title,
            'message' => $this->message,
            'audience' => $this->audience,
            'published_at' => $this->publishedAt,
            'action_url' => $this->actionUrl,
        ];
    }
}
