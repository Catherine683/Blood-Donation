<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController
{
    /**
     * Serve the public robots.txt content.
     */
    public function __invoke(): Response
    {
        $path = public_path('robots.txt');
        $content = file_exists($path) ? file_get_contents($path) : "User-agent: *\nDisallow:\n";
        $content = str_replace('Sitemap: /sitemap.xml', 'Sitemap: '.url('/sitemap.xml'), $content);

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
