<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Throwable;

#[Signature('backup:create {--prune : Remove backups older than the configured retention period}')]
#[Description('Create a private database backup and metadata record')]
class CreateDatabaseBackupCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(DatabaseBackupService $backupService): int
    {
        try {
            $backup = $backupService->create();

            $this->info("Database backup #{$backup->id} created ({$backup->size_bytes} bytes).");

            if ($this->option('prune')) {
                $this->info($backupService->prune().' expired backup(s) removed.');
            }

            return SymfonyCommand::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Database backup failed. Review the application log for details.');

            return SymfonyCommand::FAILURE;
        }
    }
}
