<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatabaseBackup;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class SystemHealthController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): View
    {
        Gate::authorize('view-system-health');
        $databaseHealthy = true;

        try {
            DB::connection()->getPdo();
        } catch (Throwable) {
            $databaseHealthy = false;
        }

        $failedJobs = null;
        $latestBackup = null;

        if ($databaseHealthy) {
            try {
                $failedJobs = Schema::hasTable('failed_jobs')
                    ? DB::table('failed_jobs')->count()
                    : null;
                $latestBackup = DatabaseBackup::query()->latest('id')->first();
            } catch (Throwable) {
                $databaseHealthy = false;
            }
        }

        $storagePath = storage_path('app');
        $freeStorageBytes = disk_free_space($storagePath);
        $connectionName = (string) config('database.default');

        return view('admin.system-health', [
            'databaseHealthy' => $databaseHealthy,
            'databaseDriver' => (string) config("database.connections.{$connectionName}.driver", 'unknown'),
            'failedJobs' => $failedJobs,
            'storageWritable' => is_writable($storagePath),
            'freeStorageBytes' => $freeStorageBytes === false ? null : $freeStorageBytes,
            'latestBackup' => $latestBackup,
            'queueConnection' => (string) config('queue.default'),
            'cacheStore' => (string) config('cache.default'),
            'appEnvironment' => (string) config('app.env'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
        ]);
    }
}
