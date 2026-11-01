<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentNotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $this->authenticatedUser($request);
        $notifications = $user->notifications()->latest()->paginate(15);

        return view('student.notifications', compact('notifications'));
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        $user = $this->authenticatedUser($request);
        $user->notifications()->findOrFail($notification)->markAsRead();

        return back()->with('success', 'Notification marked as read.');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $this->authenticatedUser($request)->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }

    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
