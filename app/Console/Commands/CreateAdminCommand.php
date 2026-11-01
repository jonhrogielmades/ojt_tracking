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

#[Signature('admin:create {--name=} {--email=}')]
#[Description('Create an administrator account using a securely prompted password')]
class CreateAdminCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = trim((string) ($this->option('name') ?: $this->ask('Administrator name')));
        $email = Str::lower(trim((string) ($this->option('email') ?: $this->ask('Administrator email'))));
        $password = (string) $this->secret('Password');
        $passwordConfirmation = (string) $this->secret('Confirm password');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'max:255', 'confirmed', Password::defaults()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return SymfonyCommand::FAILURE;
        }

        User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => UserRole::SuperAdmin,
            'is_active' => true,
        ]);

        $this->info("Administrator account created for {$email}.");

        return SymfonyCommand::SUCCESS;
    }
}
