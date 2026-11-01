<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): View
    {
        Gate::authorize('viewAny', AuditLog::class);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'event' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);
        $logs = AuditLog::query()
            ->with('actor:id,name,email,deleted_at')
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('event', 'like', "%{$search}%")
                        ->orWhere('subject_type', 'like', "%{$search}%")
                        ->orWhere('subject_id', 'like', "%{$search}%")
                        ->orWhereHas('actor', function (Builder $query) use ($search): void {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when(
                $filters['event'] ?? null,
                fn (Builder $query, string $event): Builder => $query->where('event', $event),
            )
            ->when(
                $filters['date_from'] ?? null,
                fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date),
            )
            ->when(
                $filters['date_to'] ?? null,
                fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date),
            )
            ->latest('id')
            ->paginate(25)
            ->withQueryString();
        $events = AuditLog::query()->select('event')->distinct()->orderBy('event')->pluck('event');

        return view('admin.audit.index', compact('logs', 'events', 'filters'));
    }
}
