<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveAnnouncementRequest;
use App\Jobs\DispatchAnnouncement;
use App\Models\Announcement;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Announcement::class);
        $announcements = Announcement::query()
            ->with('creator:id,name')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15);
        $editingAnnouncement = $request->integer('edit') > 0
            ? Announcement::query()->whereNull('published_at')->findOrFail($request->integer('edit'))
            : null;

        if ($editingAnnouncement !== null) {
            Gate::authorize('update', $editingAnnouncement);
        }

        $programs = User::query()->students()->whereNotNull('program')->distinct()->orderBy('program')->pluck('program');
        $sections = User::query()->students()->whereNotNull('section')->distinct()->orderBy('section')->pluck('section');

        return view('admin.announcements.index', compact('announcements', 'editingAnnouncement', 'programs', 'sections'));
    }

    public function store(SaveAnnouncementRequest $request): RedirectResponse
    {
        $announcement = Announcement::query()->create([
            ...$request->validated(),
            'creator_id' => $request->user()?->getAuthIdentifier(),
        ]);
        $this->auditLogger->record('announcement.created', $request->user(), $announcement, [], $announcement->attributesToArray());

        return to_route('admin.announcements.index')->with('status', 'Announcement saved as a draft.');
    }

    public function update(SaveAnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        DB::transaction(function () use ($request, $announcement): void {
            $lockedAnnouncement = Announcement::query()
                ->whereKey($announcement->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedAnnouncement->published_at !== null) {
                throw ValidationException::withMessages([
                    'announcement' => 'A published announcement cannot be edited.',
                ]);
            }

            $oldValues = $lockedAnnouncement->attributesToArray();
            $lockedAnnouncement->update($request->validated());
            $this->auditLogger->record(
                'announcement.updated',
                $request->user(),
                $lockedAnnouncement,
                $oldValues,
                $lockedAnnouncement->fresh()->attributesToArray(),
            );
        });

        return to_route('admin.announcements.index')->with('status', 'Announcement updated.');
    }

    public function publish(Request $request, Announcement $announcement): RedirectResponse
    {
        Gate::authorize('publish', $announcement);

        $queued = DB::transaction(function () use ($request, $announcement): bool {
            $lockedAnnouncement = Announcement::query()
                ->whereKey($announcement->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedAnnouncement->published_at !== null) {
                return false;
            }

            if ($lockedAnnouncement->expires_at?->isPast()) {
                throw ValidationException::withMessages([
                    'announcement' => 'An expired announcement cannot be published. Update its expiry date first.',
                ]);
            }

            $lockedAnnouncement->update(['published_at' => now()]);
            DispatchAnnouncement::dispatch($lockedAnnouncement->id)->afterCommit();
            $this->auditLogger->record('announcement.published', $request->user(), $lockedAnnouncement, [], [
                'audience' => $lockedAnnouncement->audience,
                'audience_value' => $lockedAnnouncement->audience_value,
                'published_at' => $lockedAnnouncement->published_at,
            ]);

            return true;
        });

        if (! $queued) {
            return back()->with('status', 'This announcement was already published and cannot be queued again.');
        }

        return to_route('admin.announcements.index')->with('status', 'Announcement queued for delivery.');
    }

    public function destroy(Request $request, Announcement $announcement): RedirectResponse
    {
        Gate::authorize('delete', $announcement);
        DB::transaction(function () use ($request, $announcement): void {
            $lockedAnnouncement = Announcement::query()
                ->whereKey($announcement->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedAnnouncement->published_at !== null) {
                throw ValidationException::withMessages([
                    'announcement' => 'A published announcement cannot be deleted.',
                ]);
            }

            $oldValues = $lockedAnnouncement->attributesToArray();
            $lockedAnnouncement->delete();
            $this->auditLogger->record('announcement.deleted', $request->user(), $lockedAnnouncement, $oldValues);
        });

        return to_route('admin.announcements.index')->with('status', 'Announcement deleted.');
    }

    public function retry(Request $request, Announcement $announcement): RedirectResponse
    {
        Gate::authorize('publish', $announcement);

        DB::transaction(function () use ($request, $announcement): void {
            $lockedAnnouncement = Announcement::query()
                ->whereKey($announcement->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedAnnouncement->published_at === null) {
                throw ValidationException::withMessages([
                    'announcement' => 'Publish this draft before retrying delivery.',
                ]);
            }

            if ($lockedAnnouncement->sent_at !== null) {
                throw ValidationException::withMessages([
                    'announcement' => 'This announcement has already been delivered.',
                ]);
            }

            if ($lockedAnnouncement->expires_at?->isPast()) {
                throw ValidationException::withMessages([
                    'announcement' => 'This announcement has expired and can no longer be delivered.',
                ]);
            }

            DispatchAnnouncement::dispatch($lockedAnnouncement->id)->afterCommit();
            $this->auditLogger->record('announcement.delivery-retried', $request->user(), $lockedAnnouncement, [], [
                'published_at' => $lockedAnnouncement->published_at,
                'retry_queued_at' => now(),
            ]);
        });

        return to_route('admin.announcements.index')->with('status', 'Announcement delivery queued again. Already delivered students will not receive a duplicate.');
    }
}
