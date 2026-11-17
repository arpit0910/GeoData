<?php

namespace App\Services\TechnologyNews;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class NewsCache
{
    public static function invalidate(): void { Cache::forever('technology-news-version', (string) Str::uuid()); }
    public static function remember(string $key, int $seconds, callable $callback)
    {
        $version = Cache::rememberForever('technology-news-version', fn () => (string) Str::uuid());
        return Cache::remember('technology-news:'.$version.':'.hash('sha256', $key), $seconds, $callback);
    }
}
