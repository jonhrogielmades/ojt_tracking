<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatabaseBackup;
use App\Services\AuditLogger;
use App\Services\DatabaseBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Throwable;

class BackupController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', DatabaseBackup::class);
        $backups = DatabaseBackup::query()
            ->with('creator:id,name,email,deleted_at')
            ->latest('id')
            ->paginate(20);
        $backupsEnabled = (bool) config('ojt.backups.enabled', true);

        return view('admin.backups.index', compact('backups', 'backupsEnabled'));
    }

    public function store(
        DatabaseBackupService $backupService,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        Gate::authorize('create', DatabaseBackup::class);

        if (! config('ojt.backups.enabled', true)) {
            return back()->withErrors([
                'backup' => 'Database backups are disabled for this deployment. Configure durable backup storage before enabling them.',
            ]);
        }

        try {
            $backup = $backupService->create(request()->user());
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['backup' => 'The database backup could not be created. Check the application log.']);
        }

        $auditLogger->record('backup.created', request()->user(), $backup, metadata: [
            'driver' => $backup->database_driver,
            'size_bytes' => $backup->size_bytes,
        ]);

        return back()->with('status', 'Database backup created successfully.');
    }

    public function verify(
        DatabaseBackup $backup,
        DatabaseBackupService $backupService,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        Gate::authorize('view', $backup);
        $valid = $backupService->verify($backup);
        $auditLogger->record('backup.verified', request()->user(), $backup, metadata: [
            'valid' => $valid,
        ]);

        if (! $valid) {
            return back()->withErrors(['backup' => 'Database backup failed integrity verification.']);
        }

        return back()->with('status', 'Database backup passed integrity verification.');
    }

    public function destroy(
        DatabaseBackup $backup,
        DatabaseBackupService $backupService,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        Gate::authorize('delete', $backup);
        $metadata = ['created_at' => $backup->created_at?->toIso8601String()];
        $backupService->delete($backup);
        $auditLogger->record('backup.deleted', request()->user(), $backup, metadata: $metadata);

        return back()->with('status', 'Database backup deleted.');
    }
}
