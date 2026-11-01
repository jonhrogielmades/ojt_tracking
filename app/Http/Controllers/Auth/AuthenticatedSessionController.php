<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * @throws ValidationException
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        if (! Auth::attempt(
            [
                ...$request->safe()->only(['student_id', 'password']),
                'role' => UserRole::Student->value,
                'is_active' => true,
            ],
            $request->boolean('remember'),
        )) {
            throw ValidationException::withMessages([
                'student_id' => 'The provided Student ID or password is incorrect.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $redirectRoute = $request->user()?->isAdmin() ? 'admin.login' : 'login';

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($redirectRoute)->with('status', 'You have been logged out safely.');
    }
}
