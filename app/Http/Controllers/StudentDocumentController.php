<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentDocumentRequest;
use App\Models\DocumentRequirement;
use App\Models\StudentDocument;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class StudentDocumentController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', StudentDocument::class);
        /** @var User $user */
        $user = $request->user();
        $documents = StudentDocument::query()
            ->whereBelongsTo($user)
            ->with('requirement:id,name,due_date')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15);
        $requirements = DocumentRequirement::query()
            ->active()
            ->forProgram($user->program)
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->orderBy('name')
            ->orderBy('id')
            ->get();
        $latestDocumentsByRequirement = StudentDocument::query()
            ->whereBelongsTo($user)
            ->whereNotNull('document_requirement_id')
            ->orderByRaw("case when review_status = 'approved' then 0 else 1 end")
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->unique('document_requirement_id')
            ->keyBy('document_requirement_id');

        return view('documents.index', compact('documents', 'requirements', 'latestDocumentsByRequirement'));
    }

    public function store(StoreStudentDocumentRequest $request): RedirectResponse
    {
        Gate::authorize('create', StudentDocument::class);
        /** @var User $user */
        $user = $request->user();
        $requirementId = $request->validated('document_requirement_id');

        if ($requirementId !== null && StudentDocument::query()
            ->whereBelongsTo($user)
            ->where('document_requirement_id', $requirementId)
            ->where('review_status', 'approved')
            ->exists()) {
            return back()->withErrors(['document_requirement_id' => 'This requirement already has an approved document.']);
        }

        $file = $request->file('document');
        $documentDisk = $this->documentDisk();
        $path = $file->store("ojt-documents/{$user->id}", $documentDisk);

        try {
            $user->studentDocuments()->create([
                'document_requirement_id' => $requirementId,
                'title' => $request->string('title')->trim()->toString(),
                'category' => $request->filled('category') ? $request->string('category')->trim()->toString() : null,
                'storage_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        } catch (Throwable $exception) {
            Storage::disk($documentDisk)->delete($path);

            throw $exception;
        }

        return to_route('documents.index')->with('success', 'Document uploaded privately.');
    }

    public function download(Request $request, StudentDocument $document): StreamedResponse
    {
        $this->ownedDocument($request, $document);
        Gate::authorize('view', $document);
        $documentDisk = Storage::disk($this->documentDisk());
        abort_unless($documentDisk->exists($document->storage_path), 404);

        return $documentDisk->download(
            $document->storage_path,
            $document->original_name,
            ['Content-Type' => $document->mime_type],
        );
    }

    public function destroy(Request $request, StudentDocument $document): RedirectResponse
    {
        $user = $this->ownedDocument($request, $document);
        $storagePath = DB::transaction(function () use ($user, $document): string {
            User::query()->whereKey($user->getKey())->lockForUpdate()->value('id');
            $lockedDocument = StudentDocument::query()
                ->whereBelongsTo($user)
                ->whereKey($document->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            Gate::authorize('delete', $lockedDocument);
            $storagePath = $lockedDocument->storage_path;
            $lockedDocument->delete();

            return $storagePath;
        });
        Storage::disk($this->documentDisk())->delete($storagePath);

        return to_route('documents.index')->with('success', 'Document deleted.');
    }

    private function ownedDocument(Request $request, StudentDocument $document): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && $document->user_id === $user->id, 404);

        return $user;
    }

    private function documentDisk(): string
    {
        return (string) config('ojt.documents.disk', 'local');
    }
}
