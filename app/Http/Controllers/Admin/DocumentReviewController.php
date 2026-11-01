<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewStudentDocumentRequest;
use App\Models\StudentDocument;
use App\Models\User;
use App\Notifications\StudentDocumentReviewedNotification;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentReviewController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);
        $filters = $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'program' => ['nullable', 'string', 'max:255'],
        ]);
        $documents = StudentDocument::query()
            ->with(['user:id,name,student_id,program,section', 'requirement:id,name,due_date', 'reviewer:id,name'])
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('review_status', $status))
            ->when($filters['program'] ?? null, fn (Builder $query, string $program): Builder => $query->whereHas('user', fn (Builder $query): Builder => $query->where('program', $program)))
            ->orderByRaw("case review_status when 'pending' then 0 when 'rejected' then 1 else 2 end")
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
        $programs = User::query()->students()->whereNotNull('program')->distinct()->orderBy('program')->pluck('program');

        return view('admin.document-reviews.index', compact('documents', 'filters', 'programs'));
    }

    public function update(ReviewStudentDocumentRequest $request, StudentDocument $document): RedirectResponse
    {
        [$document, $oldValues] = DB::transaction(function () use ($request, $document): array {
            User::query()->whereKey($document->user_id)->lockForUpdate()->value('id');
            $lockedDocument = StudentDocument::query()->whereKey($document->getKey())->lockForUpdate()->firstOrFail();
            Gate::authorize('review', $lockedDocument);

            if (
                $request->validated('review_status') === 'approved'
                && $lockedDocument->document_requirement_id !== null
                && StudentDocument::query()
                    ->where('user_id', $lockedDocument->user_id)
                    ->where('document_requirement_id', $lockedDocument->document_requirement_id)
                    ->where('review_status', 'approved')
                    ->whereKeyNot($lockedDocument->getKey())
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'review_status' => 'This student already has an approved document for the requirement.',
                ]);
            }

            $oldValues = $lockedDocument->only(['review_status', 'reviewer_id', 'review_notes', 'reviewed_at']);
            $lockedDocument->update([
                ...$request->validated(),
                'reviewer_id' => $request->user()?->getAuthIdentifier(),
                'reviewed_at' => now(),
            ]);

            return [$lockedDocument->refresh(), $oldValues];
        });
        $this->auditLogger->record(
            'document.reviewed',
            $request->user(),
            $document,
            $oldValues,
            $document->only(['review_status', 'reviewer_id', 'review_notes', 'reviewed_at']),
        );
        $document->user()->first()?->notify(new StudentDocumentReviewedNotification($document));

        return back()->with('status', 'Document review saved.');
    }

    public function download(StudentDocument $document): StreamedResponse
    {
        Gate::authorize('review', $document);
        $documentDisk = Storage::disk((string) config('ojt.documents.disk', 'local'));
        abort_unless($documentDisk->exists($document->storage_path), 404);

        return $documentDisk->download(
            $document->storage_path,
            $document->original_name,
            ['Content-Type' => $document->mime_type],
        );
    }
}
