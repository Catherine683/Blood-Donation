<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\DriveStatus;
use App\Models\Drive;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController
{
    /**
     * Plain cache key for sitemap without cache tags (compatible with database cache store).
     */
    public const CACHE_KEY = 'donorlink:sitemap:xml';

    /**
     * Generate dynamic sitemap.xml for public pages and published blood drives.
     */
    public function __invoke(): Response
    {
        $content = Cache::remember(self::CACHE_KEY, 3600, function (): string {
            $publishedDrives = Drive::query()
                ->where('status', DriveStatus::Published)
                ->orderByDesc('updated_at')
                ->get();

            return (string) view('sitemap', [
                'publishedDrives' => $publishedDrives,
            ])->render();
        });

        return response($content, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
        ]);
    }

    /**
     * Invalidate the cached sitemap using a plain key (database cache driver friendly).
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
