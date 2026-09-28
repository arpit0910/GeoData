<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $pages = [
            ['route' => 'home', 'changefreq' => 'weekly', 'priority' => '1.0'],
            ['route' => 'about', 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['route' => 'docs', 'changefreq' => 'weekly', 'priority' => '0.9'],
            ['route' => 'faq', 'changefreq' => 'monthly', 'priority' => '0.7'],
            ['route' => 'contact', 'changefreq' => 'monthly', 'priority' => '0.7'],
            ['route' => 'market.index', 'changefreq' => 'daily', 'priority' => '0.9'],
            ['route' => 'market.stocks', 'changefreq' => 'daily', 'priority' => '0.8'],
            ['route' => 'market.mutual-funds', 'changefreq' => 'daily', 'priority' => '0.8'],
            ['route' => 'market.news', 'changefreq' => 'hourly', 'priority' => '0.8'],
            ['route' => 'market.fundamentals', 'changefreq' => 'daily', 'priority' => '0.8'],
            ['route' => 'market.corporate-actions', 'changefreq' => 'daily', 'priority' => '0.8'],
            ['route' => 'privacy', 'changefreq' => 'yearly', 'priority' => '0.3'],
            ['route' => 'terms', 'changefreq' => 'yearly', 'priority' => '0.3'],
        ];

        $lastModified = collect([
            base_path('routes/web.php'),
            resource_path('views/layouts/public.blade.php'),
            resource_path('views/website/home.blade.php'),
        ])->filter(fn (string $path) => is_file($path))
            ->map(fn (string $path) => filemtime($path))
            ->max();

        return response()
            ->view('seo.sitemap', [
                'pages' => $pages,
                'lastModified' => date('Y-m-d', $lastModified ?: time()),
            ])
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
