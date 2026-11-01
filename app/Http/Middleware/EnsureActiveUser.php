<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveUser
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->is_active !== false) {
            return $next($request);
        }

        $redirectRoute = $request->user()?->isAdmin() || $request->is('admin/*')
            ? 'admin.login'
            : 'login';

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($redirectRoute)
            ->with('status', 'This account has been deactivated. Contact an administrator.');

    }
}
