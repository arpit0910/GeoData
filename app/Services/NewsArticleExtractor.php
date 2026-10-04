<?php

namespace App\Services;

use App\Models\MarketNews;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class NewsArticleExtractor
{
    public function extract(MarketNews $news): string
    {
        if ($news->original_content && mb_strlen($news->original_content) >= 200) {
            return $news->original_content;
        }

        $url = trim((string) $news->article_url);
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if (parse_url($url, PHP_URL_SCHEME) !== 'https'
            || ($host !== 'upstox.com' && ! str_ends_with($host, '.upstox.com'))) {
            throw new RuntimeException('A trusted Upstox article URL is required for a detailed rewrite.');
        }

        $request = Http::accept('text/html')
            ->withUserAgent('SetuGeo News Editor/1.0')
            ->connectTimeout(10)
            ->timeout(30);
        $caBundle = trim((string) (config('market_data.ca_bundle') ?: config('services.gemini.ca_bundle')));
        if ($caBundle !== '') {
            $request = $request->withOptions(['verify' => $caBundle]);
        }
        $response = $request->get($url);
        if (! $response->successful()) {
            throw new RuntimeException('The source article could not be fetched (HTTP '.$response->status().').');
        }

        $content = $this->articleBody($response->body());
        if (mb_strlen($content) < 200) {
            throw new RuntimeException('The source article did not contain enough extractable content.');
        }

        $news->forceFill([
            'original_content' => $content,
            'source_fetched_at' => now(),
        ])->save();

        return $content;
    }

    private function articleBody(string $html): string
    {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (! $loaded) {
            return '';
        }

        $xpath = new DOMXPath($document);
        foreach ($xpath->query('//script[@type="application/ld+json"]') ?: [] as $script) {
            $decoded = json_decode($script->textContent, true);
            $body = $this->findArticleBody($decoded);
            if (is_string($body) && trim($body) !== '') {
                return $this->clean($body);
            }
        }

        return '';
    }

    private function findArticleBody(mixed $value): ?string
    {
        if (! is_array($value)) {
            return null;
        }
        if (isset($value['articleBody']) && is_string($value['articleBody'])) {
            return $value['articleBody'];
        }
        foreach ($value as $child) {
            if (is_array($child) && ($body = $this->findArticleBody($child))) {
                return $body;
            }
        }

        return null;
    }

    private function clean(string $body): string
    {
        $body = html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $body = preg_replace('/\{\{[^}]+\}\}/u', '', $body) ?? $body;
        $body = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $body) ?? $body;
        $paragraphs = preg_split('/\R{2,}/u', str_replace(["\r\n", "\r"], "\n", $body)) ?: [];
        $paragraphs = array_values(array_filter(array_map(
            fn ($paragraph) => trim(preg_replace('/[ \t]+/u', ' ', $paragraph) ?? $paragraph),
            $paragraphs
        )));

        return mb_substr(implode("\n\n", $paragraphs), 0, 30000);
    }
}
