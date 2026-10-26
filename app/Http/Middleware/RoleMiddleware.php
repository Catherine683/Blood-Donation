<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            abort(Response::HTTP_FORBIDDEN, 'Your account is inactive.');
        }

        $authorized = match ($role) {
            'donor' => $user->isDonor(),
            'staff' => $user->isStaff(),
            'admin' => $user->isAdmin(),
            default => false,
        };

        if (! $authorized) {
            abort(Response::HTTP_FORBIDDEN, 'You are not authorized to access this area.');
        }

        return $next($request);
    }
}
