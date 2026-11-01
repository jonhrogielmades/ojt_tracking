@extends('layouts.admin')

@section('title', 'Database backups')
@section('eyebrow', 'Recovery readiness')
@section('page-title', 'Database backups')

@section('content')
    @php
        $formatBytes = static function (int $bytes): string {
            if ($bytes >= 1_073_741_824) {
                return number_format($bytes / 1_073_741_824, 2).' GB';
            }
            if ($bytes >= 1_048_576) {
                return number_format($bytes / 1_048_576, 2).' MB';
            }
            if ($bytes >= 1024) {
                return number_format($bytes / 1024, 1).' KB';
            }
            return $bytes.' B';
        };
    @endphp

    <section class="card card-body sm:flex sm:items-center sm:justify-between sm:gap-6">
        <div>
            <h2 class="section-title">Create a recovery point</h2>
            <p class="section-copy">
                {{ $backupsEnabled
                    ? 'Backups are stored privately, checksummed, and retained according to server configuration.'
                    : 'Database backup creation is disabled until durable backup storage is configured.' }}
            </p>
        </div>
        @if ($backupsEnabled)
            <form method="POST" action="{{ route('admin.backups.store') }}" class="mt-4 shrink-0 sm:mt-0">@csrf<button type="submit" class="btn-primary">Create database backup</button></form>
        @else
            <span class="status-pill mt-4 bg-slate-100 text-slate-700 sm:mt-0">Disabled</span>
        @endif
    </section>

    <section class="mt-6 card overflow-hidden">
        <div class="border-b border-slate-200 px-5 py-4 sm:px-6"><h2 class="section-title">Backup history</h2><p class="section-copy">{{ number_format($backups->total()) }} metadata records</p></div>
        @if ($backups->isEmpty())
            <div class="p-6"><x-empty-state title="No backups yet" message="Create the first protected database recovery point." /></div>
        @else
            <div class="overflow-x-auto"><table class="data-table">
                <thead><tr><th>Created</th><th>Database</th><th>Size</th><th>Status</th><th>Verification</th><th>Created by</th><th><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                    @foreach ($backups as $backup)
                        <tr>
                            <td class="whitespace-nowrap">{{ $backup->created_at?->format('M j, Y g:i A') }}</td>
                            <td class="uppercase">{{ $backup->database_driver }}</td>
                            <td>{{ $formatBytes((int) $backup->size_bytes) }}</td>
                            <td><span @class(['status-pill', 'bg-emerald-100 text-emerald-800' => $backup->status === 'completed', 'bg-rose-100 text-rose-800' => $backup->status !== 'completed'])>{{ ucfirst($backup->status) }}</span></td>
                            <td>{{ $backup->verified_at?->format('M j, Y g:i A') ?? 'Not verified' }}</td>
                            <td>{{ $backup->creator?->name ?? 'Scheduled task' }}</td>
                            <td><div class="flex justify-end gap-2">
                                <form method="POST" action="{{ route('admin.backups.verify', $backup) }}">@csrf<button type="submit" class="text-sm font-bold text-teal-700 hover:text-teal-800">Verify</button></form>
                                <form method="POST" action="{{ route('admin.backups.destroy', $backup) }}" data-confirm="Delete this backup file and metadata record?">@csrf @method('DELETE')<button type="submit" class="text-sm font-bold text-rose-700 hover:text-rose-800">Delete</button></form>
                            </div></td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
            <div class="border-t border-slate-200 px-5 py-4 sm:px-6">{{ $backups->links() }}</div>
        @endif
    </section>
@endsection
