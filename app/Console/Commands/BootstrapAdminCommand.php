<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

#[Signature('admin:bootstrap')]
#[Description('Create the first Super Administrator from deployment configuration')]
class BootstrapAdminCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = trim((string) config('ojt.bootstrap_admin.name'));
        $email = Str::lower(trim((string) config('ojt.bootstrap_admin.email')));
        $password = (string) config('ojt.bootstrap_admin.password');

        if ($name === '' && $email === '' && $password === '') {
            $this->components->info('Initial administrator configuration is not set; nothing to bootstrap.');

            return SymfonyCommand::SUCCESS;
        }

        if (User::query()->withTrashed()->administrators()->exists()) {
            $this->components->info('An administrator already exists; bootstrap was skipped.');

            return SymfonyCommand::SUCCESS;
        }

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => trim($password) === '' ? '' : $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'max:255', Password::defaults()],
        ]);

        $validator->after(function ($validator) use ($password): void {
            if (Str::contains(Str::upper($password), ['REPLACE_WITH', 'CHANGE_ME', 'CHANGEME'])) {
                $validator->errors()->add('password', 'The initial administrator password is still a placeholder.');
            }
        });

        if ($validator->fails()) {
            $this->components->warn('The initial administrator configuration is invalid; administrator creation was skipped.');

            foreach ($validator->errors()->all() as $message) {
                $this->line(" - {$message}");
            }

            $this->components->warn('Fix INITIAL_ADMIN_NAME, INITIAL_ADMIN_EMAIL, and INITIAL_ADMIN_PASSWORD, then redeploy to create the first administrator.');

            return SymfonyCommand::SUCCESS;
        }

        User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => UserRole::SuperAdmin,
            'is_active' => true,
        ]);

        $this->components->info("Initial Super Administrator created for {$email}.");
        $this->components->warn('Remove INITIAL_ADMIN_PASSWORD from the deployment environment.');

        return SymfonyCommand::SUCCESS;
    }
}
