<?php

namespace App\Services\TechnologyNews;

use Carbon\CarbonImmutable;
use RuntimeException;

class Normalizer
{
    public function text($value, int $max = 2000): string
    {
        if (!is_scalar($value)) { return ''; }
        $value = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', (string) $value);
        return mb_substr(trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'))) ?? ''), 0, $max);
    }

    public function url(string $url): string
    {
        $p = parse_url($url);
        if (!filter_var($url, FILTER_VALIDATE_URL) || !is_array($p) || !in_array($p['scheme'] ?? '', ['http', 'https'], true)
            || isset($p['user']) || isset($p['pass']) || isset($p['port']) && !in_array($p['port'], [80, 443], true)
            || !preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/i', $p['host'] ?? '') || str_ends_with(strtolower($p['host']), '.local')
            || str_ends_with(strtolower($p['host']), '.internal')) { throw new RuntimeException('Invalid article URL.'); }
        $query = [];
        $signed = preg_match('/(?:^|&)(?:signature|sig|token|x-amz-signature)=/i', $p['query'] ?? '');
        foreach (explode('&', $p['query'] ?? '') as $part) {
            if ($part === '') { continue; }
            $key = strtolower(rawurldecode(explode('=', $part, 2)[0]));
            if (!$signed && (str_starts_with($key, 'utm_') || in_array($key, ['fbclid', 'gclid', 'mc_cid', 'mc_eid'], true))) { continue; }
            $query[] = $part;
        }
        // Preserve ordering, duplicate keys, and meaningful query values.
        return strtolower($p['scheme']).'://'.strtolower($p['host']).($p['path'] ?? '/').($query ? '?'.implode('&', $query) : '');
    }

    public function article(array $row): array
    {
        $title = $this->text($row['title'] ?? null, 1000);
        if (!$title || !isset($row['id']) || !is_scalar($row['id']) || (string) $row['id'] === ''
            || !is_string($row['published_at'] ?? null) || !preg_match('/\d{4}/', $row['published_at'])) { throw new RuntimeException('Missing title, provider ID, or publication date.'); }
        $parsed = date_parse($row['published_at']);
        if ($parsed['error_count'] || $parsed['warning_count']) { throw new RuntimeException('Invalid publication date.'); }
        try { $date = CarbonImmutable::parse($row['published_at'], 'UTC')->utc(); }
        catch (\Throwable $e) { throw new RuntimeException('Invalid publication date.'); }
        if ($date->isFuture() || $date->year < 1970) { throw new RuntimeException('Invalid publication date.'); }
        $url = $this->url(is_string($row['url'] ?? null) ? $row['url'] : '');
        $tags = array_slice(array_values(array_filter(array_map(fn ($v) => $this->text($v, 80), is_array($row['tags'] ?? null) ? $row['tags'] : []))), 0, 20);
        return ['source_article_id' => hash('sha256', (string) $row['id']), 'title' => $title,
            'original_url' => $url, 'canonical_url' => $url, 'url_hash' => hash('sha256', $url),
            'description' => $this->text($row['description'] ?? null, 400) ?: null,
            'author_name' => $this->text($row['author'] ?? null, 255) ?: null,
            'published_at' => $date, 'tags' => $tags,
            'title_hash' => hash('sha256', mb_strtolower(preg_replace('/[^\pL\pN]+/u', ' ', $title))),
            'content_hash' => hash('sha256', $title.'|'.$url.'|'.$date->toIso8601String()),
            'source_score' => $row['source_score'] ?? null];
    }

    public function category(array $article): string
    {
        $text = mb_strtolower($article['title'].' '.implode(' ', $article['tags']).' '.($article['description'] ?? ''));
        $scores = [];
        foreach (config('technology_news.rules') as $category => $words) {
            foreach ($words as $word) {
                if (preg_match('/(?<![\pL\pN])'.preg_quote($word, '/').'(?![\pL\pN])/iu', $text)) { $scores[$category] = ($scores[$category] ?? 0) + 1; }
            }
        }
        arsort($scores);
        return array_key_first($scores) ?: 'general-technology';
    }
}
