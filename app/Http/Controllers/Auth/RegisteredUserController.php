<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = User::query()->create([
            'name' => $request->string('name')->trim()->toString(),
            'email' => $request->string('email')->toString(),
            'student_id' => $request->string('student_id')->toString(),
            'program' => $request->string('program')->trim()->toString(),
            'section' => $request->string('section')->trim()->toString(),
            'offline_storage_key' => (string) Str::uuid(),
            'password' => Hash::make($request->string('password')->toString()),
            'role' => UserRole::Student,
            'is_active' => true,
        ]);

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('status', 'Welcome to OJT Tracker. Complete your placement profile to get started.');
    }
}
