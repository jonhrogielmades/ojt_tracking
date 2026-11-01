<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isStudent(), 403);

        $announcements = Announcement::query()
            ->published()
            ->where(function (Builder $query) use ($user): void {
                $query->where('audience', 'all')
                    ->orWhere(fn (Builder $query): Builder => $query->where('audience', 'program')->where('audience_value', $user->program))
                    ->orWhere(fn (Builder $query): Builder => $query->where('audience', 'section')->where('audience_value', $user->section));
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(15);

        return view('announcements.index', compact('announcements'));
    }
}
