<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

#[Signature('backup:prune')]
#[Description('Remove database backups older than the configured retention period')]
class PruneDatabaseBackupsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(DatabaseBackupService $backupService): int
    {
        $deleted = $backupService->prune();

        $this->info("{$deleted} expired backup(s) removed.");

        return SymfonyCommand::SUCCESS;
    }
}
