<?php

namespace App\Console\Commands;

use App\Models\DatabaseBackup;
use App\Services\DatabaseBackupService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

#[Signature('backup:verify {backup : Database backup record ID}')]
#[Description('Verify a stored database backup checksum and integrity')]
class VerifyDatabaseBackupCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(DatabaseBackupService $backupService): int
    {
        $backup = DatabaseBackup::query()->find($this->argument('backup'));

        if ($backup === null) {
            $this->error('The requested backup record does not exist.');

            return SymfonyCommand::FAILURE;
        }

        if (! $backupService->verify($backup)) {
            $this->error("Database backup #{$backup->id} failed verification.");

            return SymfonyCommand::FAILURE;
        }

        $this->info("Database backup #{$backup->id} passed verification.");

        return SymfonyCommand::SUCCESS;
    }
}
