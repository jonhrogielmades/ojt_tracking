<?php

namespace App\Services;

use App\Models\DatabaseBackup;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PDO;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class DatabaseBackupService
{
    public function create(?User $creator = null): DatabaseBackup
    {
        $driver = (string) DB::connection()->getDriverName();
        $extension = $driver === 'sqlite' ? 'sqlite' : 'sql';
        $directory = $this->directory();
        $path = $directory.'/database-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(8)).'.'.$extension;
        $disk = (string) config('ojt.backups.disk', 'local');
        $temporaryPath = $this->temporaryPath();

        try {
            match ($driver) {
                'sqlite' => $this->createSqliteBackup($temporaryPath),
                'mysql', 'mariadb' => $this->createMysqlBackup($temporaryPath),
                default => throw new RuntimeException("Database backups are not supported for the {$driver} driver."),
            };

            $size = filesize($temporaryPath);
            $checksum = hash_file('sha256', $temporaryPath);

            if ($size === false || $size < 1 || $checksum === false) {
                throw new RuntimeException('The database backup file could not be validated.');
            }

            $stream = fopen($temporaryPath, 'rb');

            if ($stream === false) {
                throw new RuntimeException('The database backup file could not be stored.');
            }

            try {
                $stored = Storage::disk($disk)->put($path, $stream);
            } finally {
                fclose($stream);
            }

            if (! $stored) {
                throw new RuntimeException('The database backup file could not be stored.');
            }

            try {
                return DatabaseBackup::query()->create([
                    'created_by' => $creator?->getKey(),
                    'disk' => $disk,
                    'path' => $path,
                    'database_driver' => $driver,
                    'status' => 'completed',
                    'size_bytes' => $size,
                    'checksum' => $checksum,
                    'metadata' => ['retention_days' => $this->retentionDays()],
                ]);
            } catch (Throwable $exception) {
                if (! Storage::disk($disk)->delete($path)) {
                    report(new RuntimeException(
                        'Backup metadata failed and the stored database dump could not be removed.',
                        previous: $exception,
                    ));
                }

                throw $exception;
            }
        } finally {
            if (is_file($temporaryPath) && ! @unlink($temporaryPath)) {
                report(new RuntimeException('A temporary database backup file could not be removed.'));
            }
        }
    }

    public function verify(DatabaseBackup $backup): bool
    {
        if (! Storage::disk($backup->disk)->exists($backup->path)) {
            $backup->update([
                'status' => 'missing',
                'verified_at' => null,
                'failure_reason' => 'Backup file is missing.',
            ]);

            return false;
        }

        $temporaryPath = $this->temporaryPath();

        try {
            $failureReason = 'Checksum, size, or database integrity verification failed.';

            try {
                $this->copyBackupToTemporaryPath($backup, $temporaryPath);

                $checksum = hash_file('sha256', $temporaryPath);
                $size = filesize($temporaryPath);
                $isValid = $checksum !== false
                    && hash_equals((string) $backup->checksum, $checksum)
                    && $size === $backup->size_bytes;

                if ($isValid && $backup->database_driver === 'sqlite') {
                    $isValid = $this->isSqliteBackupValid($temporaryPath);
                }

                if ($isValid && in_array($backup->database_driver, ['mysql', 'mariadb'], true)) {
                    $isValid = $this->isMysqlDumpStructurallyValid($temporaryPath);
                }
            } catch (Throwable $exception) {
                report($exception);
                $isValid = false;
                $failureReason = 'The backup file could not be read or failed database integrity verification.';
            }

            $backup->update([
                'status' => $isValid ? 'completed' : 'invalid',
                'verified_at' => $isValid ? now() : null,
                'failure_reason' => $isValid ? null : $failureReason,
            ]);

            return $isValid;
        } finally {
            if (is_file($temporaryPath) && ! @unlink($temporaryPath)) {
                report(new RuntimeException('A temporary backup verification file could not be removed.'));
            }
        }
    }

    private function copyBackupToTemporaryPath(DatabaseBackup $backup, string $temporaryPath): void
    {
        $source = null;
        $destination = null;

        try {
            $source = Storage::disk($backup->disk)->readStream($backup->path);
            $destination = fopen($temporaryPath, 'wb');

            if ($source === false || $destination === false) {
                throw new RuntimeException('The database backup file could not be opened for verification.');
            }

            if (stream_copy_to_stream($source, $destination) === false) {
                throw new RuntimeException('The database backup file could not be copied for verification.');
            }
        } finally {
            if (is_resource($source)) {
                fclose($source);
            }

            if (is_resource($destination)) {
                fclose($destination);
            }
        }
    }

    private function isSqliteBackupValid(string $path): bool
    {
        $pdo = null;
        $statement = null;

        try {
            $pdo = new PDO('sqlite:'.$path);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $statement = $pdo->query('PRAGMA integrity_check');

            return $statement !== false && $statement->fetchColumn() === 'ok';
        } finally {
            $statement = null;
            $pdo = null;
        }
    }

    public function prune(): int
    {
        $deleted = 0;
        $directoryPrefix = $this->directory().'/';

        DatabaseBackup::query()
            ->where('created_at', '<', now()->subDays($this->retentionDays()))
            ->orderBy('id')
            ->eachById(function (DatabaseBackup $backup) use (&$deleted, $directoryPrefix): void {
                if (! Str::startsWith($backup->path, $directoryPrefix)) {
                    return;
                }

                $filesystem = Storage::disk($backup->disk);
                $removed = ! $filesystem->exists($backup->path) || $filesystem->delete($backup->path);

                if (! $removed) {
                    $backup->update([
                        'status' => 'delete_failed',
                        'failure_reason' => 'The expired backup file could not be removed from storage.',
                    ]);
                    report(new RuntimeException("Expired database backup #{$backup->id} could not be removed."));

                    return;
                }

                $backup->delete();
                $deleted++;
            });

        return $deleted;
    }

    public function delete(DatabaseBackup $backup): void
    {
        $directoryPrefix = $this->directory().'/';

        if (! Str::startsWith($backup->path, $directoryPrefix)) {
            throw new RuntimeException('The backup path is outside the configured backup directory.');
        }

        $filesystem = Storage::disk($backup->disk);

        if ($filesystem->exists($backup->path) && ! $filesystem->delete($backup->path)) {
            throw new RuntimeException('The database backup file could not be deleted from storage.');
        }

        $backup->delete();
    }

    private function createSqliteBackup(string $temporaryPath): void
    {
        $configuredPath = (string) config('database.connections.sqlite.database');
        $databasePath = $this->absoluteDatabasePath($configuredPath);

        if ($configuredPath === ':memory:' || ! is_file($databasePath)) {
            throw new RuntimeException('The configured SQLite database file is unavailable.');
        }

        if (is_file($temporaryPath) && ! unlink($temporaryPath)) {
            throw new RuntimeException('The temporary SQLite backup file could not be prepared.');
        }

        $pdo = new PDO('sqlite:'.$databasePath);
        $statement = $pdo->prepare('VACUUM INTO :destination');
        $statement->execute(['destination' => $temporaryPath]);
        $statement = null;
        $pdo = null;
    }

    private function createMysqlBackup(string $temporaryPath): void
    {
        $connectionName = DB::getDefaultConnection();
        $connection = (array) config("database.connections.{$connectionName}");
        $command = [
            (string) config('ojt.backups.mysql_dump_binary', 'mysqldump'),
            '--single-transaction',
            '--quick',
            '--skip-lock-tables',
            '--host='.(string) ($connection['host'] ?? '127.0.0.1'),
            '--port='.(string) ($connection['port'] ?? '3306'),
            '--user='.(string) ($connection['username'] ?? ''),
            (string) ($connection['database'] ?? ''),
        ];
        $process = new Process($command, null, [
            'MYSQL_PWD' => (string) ($connection['password'] ?? ''),
        ]);
        $process->setTimeout((int) config('ojt.backups.process_timeout_seconds', 300));
        $handle = fopen($temporaryPath, 'wb');

        if ($handle === false) {
            throw new RuntimeException('A temporary backup file could not be created.');
        }

        try {
            $process->run(function (string $type, string $buffer) use ($handle): void {
                if ($type === Process::OUT) {
                    $this->writeAll($handle, $buffer);
                }
            });
        } finally {
            fclose($handle);
        }

        if (! $process->isSuccessful()) {
            throw new RuntimeException('The MySQL backup process failed.');
        }

        if (! $this->isMysqlDumpStructurallyValid($temporaryPath)) {
            throw new RuntimeException('The MySQL backup output appears incomplete.');
        }
    }

    /** @param  resource  $handle */
    private function writeAll($handle, string $buffer): void
    {
        $offset = 0;
        $length = strlen($buffer);

        while ($offset < $length) {
            $written = fwrite($handle, substr($buffer, $offset));

            if ($written === false || $written === 0) {
                throw new RuntimeException('The database backup could not be written to temporary storage.');
            }

            $offset += $written;
        }
    }

    private function isMysqlDumpStructurallyValid(string $path): bool
    {
        $size = filesize($path);

        if ($size === false || $size < 100) {
            return false;
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return false;
        }

        try {
            $head = fread($handle, 4096);
            fseek($handle, max(0, $size - 8192));
            $tail = fread($handle, 8192);
        } finally {
            fclose($handle);
        }

        return is_string($head)
            && is_string($tail)
            && (str_contains($head, 'MySQL dump') || str_contains($head, 'MariaDB dump'))
            && str_contains($tail, 'Dump completed on');
    }

    private function temporaryPath(): string
    {
        $path = tempnam(storage_path('app'), 'ojt-backup-');

        if ($path === false) {
            throw new RuntimeException('A temporary backup file could not be created.');
        }

        return $path;
    }

    private function absoluteDatabasePath(string $path): string
    {
        if (preg_match('/^(?:[A-Za-z]:[\\\\\/]|[\\\\\/])/', $path) === 1) {
            return $path;
        }

        return database_path($path);
    }

    private function directory(): string
    {
        $directory = trim((string) config('ojt.backups.directory', 'backups/database'), '/\\');

        if ($directory === '') {
            throw new RuntimeException('The backup directory must not be empty.');
        }

        return $directory;
    }

    private function retentionDays(): int
    {
        return max(1, (int) config('ojt.backups.retention_days', 30));
    }
}
