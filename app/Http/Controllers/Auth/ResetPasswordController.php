<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ResetPasswordController extends Controller
{
    public function create(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->string('email')->toString(),
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function store(ResetPasswordRequest $request): RedirectResponse
    {
        $role = UserRole::Student;
        $status = Password::reset(
            $request->safe()->only(['email', 'password', 'password_confirmation', 'token']),
            function (User $user, string $password) use (&$role): void {
                $role = $user->role;
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => __($status),
            ]);
        }

        return to_route($role->isAdministrator() ? 'admin.login' : 'login')
            ->with('status', 'Your password has been reset. You can now sign in.');
    }
}
