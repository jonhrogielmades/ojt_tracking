<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if ($role === 'admin') {
            abort_unless($request->user()?->isAdmin(), 403);

            return $next($request);
        }

        $expectedRole = UserRole::tryFrom($role);

        abort_if($expectedRole === null || $request->user()?->role !== $expectedRole, 403);

        return $next($request);
    }
}
