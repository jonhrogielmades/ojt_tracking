<?php

namespace App\Jobs;

use App\Models\Announcement;
use App\Models\User;
use App\Notifications\AnnouncementPublished;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class DispatchAnnouncement implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public int $announcementId) {}

    public function handle(): void
    {
        $announcement = Announcement::query()->find($this->announcementId);
        if (
            $announcement === null
            || $announcement->sent_at !== null
            || $announcement->published_at === null
            || $announcement->expires_at?->isPast()
        ) {
            return;
        }

        $completed = User::query()
            ->students()
            ->where('is_active', true)
            ->when(
                $announcement->audience === 'program',
                fn (Builder $query): Builder => $query->where('program', $announcement->audience_value),
            )
            ->when(
                $announcement->audience === 'section',
                fn (Builder $query): Builder => $query->where('section', $announcement->audience_value),
            )
            ->orderBy('id')
            ->chunkById(200, function ($students) use ($announcement): bool {
                if ($announcement->expires_at?->isPast()) {
                    return false;
                }

                foreach ($students as $student) {
                    DB::transaction(function () use ($announcement, $student): void {
                        $timestamp = now();
                        $claimed = DB::table('announcement_deliveries')->insertOrIgnore([
                            'announcement_id' => $announcement->id,
                            'user_id' => $student->id,
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ]);

                        if ($claimed === 0) {
                            return;
                        }

                        $student->notify(new AnnouncementPublished($announcement));
                        DB::table('announcement_deliveries')
                            ->where('announcement_id', $announcement->id)
                            ->where('user_id', $student->id)
                            ->update([
                                'delivered_at' => now(),
                                'updated_at' => now(),
                            ]);
                    });
                }

                return true;
            });

        if ($completed !== false && ! $announcement->expires_at?->isPast()) {
            $announcement->update(['sent_at' => now()]);
        }
    }

    public function uniqueId(): string
    {
        return (string) $this->announcementId;
    }
}
