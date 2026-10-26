<?php

declare(strict_types=1);

namespace App\Support;

class RedirectHelper
{
    /**
     * Sanitize a return_to / destination URL to guarantee it is strictly an internal relative path.
     *
     * Disallows:
     * - Absolute URLs with schemes (http://, https://, javascript:, data:, etc.)
     * - Protocol-relative URLs (//evil.test)
     * - Backslashes (/\evil.test, \evil.test)
     * - Control characters / newlines
     * - Non-root relative paths
     *
     * Returns sanitized path or null if invalid / hostile.
     */
    public static function sanitizeRelativePath(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        $decoded = rawurldecode($path);

        // Reject if raw or decoded contains control characters or null bytes
        if (preg_match('/[\x00-\x1F\x7F]/', $path) || preg_match('/[\x00-\x1F\x7F]/', $decoded)) {
            return null;
        }

        // Must start with a single '/' and not with '//' or '/\'
        if (! str_starts_with($path, '/') || str_starts_with($path, '//') || str_starts_with($path, '/\\')) {
            return null;
        }

        if (! str_starts_with($decoded, '/') || str_starts_with($decoded, '//') || str_starts_with($decoded, '/\\')) {
            return null;
        }

        // Must contain no backslash
        if (str_contains($path, '\\') || str_contains($decoded, '\\')) {
            return null;
        }

        // Must contain no scheme (e.g. javascript:, http:, etc.)
        if (preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]*:#', ltrim($path, '/')) || preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]*:#', ltrim($decoded, '/'))) {
            return null;
        }

        // Parse URL components to ensure no host or scheme is present
        $parsed = parse_url($path);
        if ($parsed === false || isset($parsed['host']) || isset($parsed['scheme'])) {
            return null;
        }

        $parsedDecoded = parse_url($decoded);
        if ($parsedDecoded === false || isset($parsedDecoded['host']) || isset($parsedDecoded['scheme'])) {
            return null;
        }

        return $path;
    }
}
