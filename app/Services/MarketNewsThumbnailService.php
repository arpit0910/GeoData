<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MarketNewsThumbnailService
{
    private const MAX_BYTES = 5 * 1024 * 1024;

    /** Store a provider thumbnail locally and return its public-disk path. */
    public function store(?string $url): ?string
    {
        $url = trim((string) $url);
        if (! filter_var($url, FILTER_VALIDATE_URL)
            || ! in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            return null;
        }

        $hash = hash('sha256', $url);
        foreach (['jpg', 'png', 'gif', 'webp'] as $extension) {
            $path = "news-thumbnails/{$hash}.{$extension}";
            if (Storage::disk('public')->exists($path)) {
                return $path;
            }
        }

        try {
            $response = Http::accept('image/*')->connectTimeout(5)->timeout(15)->get($url);
            $contents = $response->body();
            if (! $response->successful() || $contents === '' || strlen($contents) > self::MAX_BYTES) {
                return null;
            }

            $image = @getimagesizefromstring($contents);
            $extension = match ($image['mime'] ?? null) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                default => null,
            };
            if (! $extension) {
                return null;
            }

            $path = "news-thumbnails/{$hash}.{$extension}";
            if (! Storage::disk('public')->put($path, $contents)) {
                return null;
            }

            return $path;
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }
}
