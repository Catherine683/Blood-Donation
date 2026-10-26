<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request and apply security and caching headers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Core security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        // Strict-Transport-Security in production only (6 months = 15,552,000s, no preload)
        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=15552000');
        }

        // Content-Security-Policy-Report-Only (advisory, compatible with Livewire and Alpine)
        $csp = "default-src 'self'; "
            ."script-src 'self' 'unsafe-inline' 'unsafe-eval'; "
            ."style-src 'self' 'unsafe-inline'; "
            ."font-src 'self' data:; "
            ."img-src 'self' data:; "
            ."connect-src 'self'; "
            ."frame-ancestors 'none'; "
            ."form-action 'self'; "
            ."base-uri 'self';";
        $response->headers->set('Content-Security-Policy-Report-Only', $csp);

        // Cache-Control: no-store on all authenticated pages and every export
        $isAuthenticated = $request->user() !== null;
        $isExport = $request->routeIs('*export*') || str_contains($request->path(), 'export');

        if ($isAuthenticated || $isExport) {
            $response->headers->set('Cache-Control', 'no-store, private');
            $response->headers->set('Pragma', 'no-cache');
        }

        // X-Robots-Tag: noindex on authenticated, signed, and unsubscribe routes
        $isSignedOrSensitive = $request->hasValidSignature(false)
            || $request->routeIs('unsubscribe.*', 'staff.invitation.*', 'donor.email.verify-change', 'verification.verify')
            || $request->is('unsubscribe/*', 'staff/invitation/*', 'donor/email/verify-change/*', 'email/verify/*');

        if ($isAuthenticated || $isSignedOrSensitive) {
            $response->headers->set('X-Robots-Tag', 'noindex');
        }

        return $response;
    }
}
