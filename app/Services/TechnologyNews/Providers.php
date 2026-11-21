<?php

namespace App\Services\TechnologyNews;

use App\Models\NewsSource;
use RuntimeException;

class Providers
{
    private $http;
    public function __construct(SafeHttp $http) { $this->http = $http; }

    public function fetch(NewsSource $source): array
    {
        if ($source->source_type === 'newsapi') { return $this->newsApi($source); }
        if ($source->source_type === 'hackernews') { return $this->hackerNews($source); }
        if ($source->source_type === 'dev') {
            $rows = $this->http->get($source->api_endpoint)->json();
            if (!is_array($rows) || !array_is_list($rows)) { throw new RuntimeException('Malformed DEV article list.'); }
            return ['items' => array_map(fn ($r) => !is_array($r) ? [] : [
                'id' => $r['id'] ?? null, 'title' => $r['title'] ?? null,
                'url' => $r['canonical_url'] ?? $r['url'] ?? null,
                'published_at' => $r['published_at'] ?? null, 'description' => $r['description'] ?? null,
                'author' => $r['user']['name'] ?? null, 'tags' => $r['tag_list'] ?? [],
            ], array_slice($rows, 0, config('technology_news.limit')))];
        }
        if ($source->source_type !== 'rss') { throw new RuntimeException('Unsupported news provider.'); }
        $headers = array_filter(['If-None-Match' => $source->etag, 'If-Modified-Since' => $source->last_modified]);
        $response = $this->http->get($source->feed_url, $headers);
        if ($response->status() === 304) { return ['items' => []]; }
        return ['items' => $this->parseFeed($response->body(), $source->rewritePermitted() && $source->allow_feed_content, $source->feed_content_field ?: 'content'), 'etag' => $response->header('ETag'), 'last_modified' => $response->header('Last-Modified')];
    }

    private function newsApi(NewsSource $source): array
    {
        $key = trim((string) config('technology_news.newsapi.key'));
        if ($key === '') {
            throw new RuntimeException('TECH_NEWS_API_KEY is missing.');
        }

        $end = now('UTC');
        $start = $source->last_success_at?->copy()->utc()->subMinutes(30) ?? $end->copy()->subMinutes(90);
        $start = $start->max($end->copy()->subDay());
        $maxPages = max(1, min(50, (int) config('technology_news.newsapi.max_pages')));
        $query = [
            'q' => config('technology_news.newsapi.query'),
            'from' => $start->toIso8601String(),
            'to' => $end->toIso8601String(),
            'sortBy' => 'publishedAt',
            'pageSize' => 100,
        ];
        $items = [];
        $total = 0;

        for ($page = 1; $page <= $maxPages; $page++) {
            // The credential is sent only to the provider's fixed HTTPS endpoint.
            $url = 'https://newsapi.org/v2/everything?'.http_build_query($query + ['page' => $page], '', '&', PHP_QUERY_RFC3986);
            $body = $this->http->get($url, ['X-Api-Key' => $key])->json();
            if (($body['status'] ?? null) !== 'ok' || !is_array($body['articles'] ?? null)
                || !array_is_list($body['articles']) || !is_int($body['totalResults'] ?? null)) {
                throw new RuntimeException('Malformed NewsAPI article list.');
            }
            $total = $body['totalResults'];
            foreach ($body['articles'] as $article) {
                if (!is_array($article) || ($article['title'] ?? null) === '[Removed]') {
                    continue;
                }
                $items[] = [
                    'id' => $article['url'] ?? null,
                    'url' => $article['url'] ?? null,
                    'title' => $article['title'] ?? null,
                    'published_at' => $article['publishedAt'] ?? null,
                    'publisher_name' => $article['source']['name'] ?? null,
                    'author' => $article['author'] ?? null,
                ];
            }
            if (count($body['articles']) < 100 || $page * 100 >= $total) {
                return ['items' => $items];
            }
        }

        return ['items' => $items, 'warning' => sprintf('NewsAPI found %d matches and reached the %d-page limit.', $total, $maxPages)];
    }

    private function hackerNews(NewsSource $source): array
    {
        $base = rtrim($source->api_endpoint, '/');
        $top = $this->http->get($base.'/topstories.json')->json();
        $recent = $this->http->get($base.'/newstories.json')->json();
        if (!is_array($top) || !is_array($recent)) { throw new RuntimeException('Malformed Hacker News story list.'); }
        $limit = config('technology_news.limit');
        $ids = array_unique(array_merge(array_slice($top, 0, (int) ceil($limit / 2)), array_slice($recent, 0, (int) floor($limit / 2))));
        $items = [];
        foreach ($ids as $id) {
            if (!is_int($id) || $id < 1) { continue; }
            $r = $this->http->get($base.'/item/'.$id.'.json')->json();
            if (!is_array($r) || ($r['type'] ?? '') !== 'story' || !empty($r['dead']) || !empty($r['deleted'])) { continue; }
            $items[] = ['id' => $r['id'] ?? null, 'title' => $r['title'] ?? null, 'url' => $r['url'] ?? null,
                'published_at' => isset($r['time']) && is_int($r['time']) ? gmdate(DATE_ATOM, $r['time']) : null,
                'source_score' => isset($r['score']) && is_numeric($r['score']) ? max(0, (int) $r['score']) : null];
            // The submitter is not the article author; discussion text is never imported.
        }
        return ['items' => $items];
    }

    public function parseFeed(string $xml, bool $includeContent = false, string $contentField = 'content'): array
    {
        if (!in_array($contentField, ['content', 'description'], true)) { throw new RuntimeException('Unsupported full-text feed field.'); }
        if (preg_match('/<!DOCTYPE|<!ENTITY/i', $xml)) { throw new RuntimeException('XML declarations and entities are forbidden.'); }
        $previous = libxml_use_internal_errors(true);
        try {
            $feed = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
            if (!$feed || !in_array($feed->getName(), ['rss', 'feed', 'RDF'], true)) { throw new RuntimeException('Invalid RSS/Atom document.'); }
            $items = $feed->xpath('//*[local-name()="channel"]/*[local-name()="item"] | /*[local-name()="feed"]/*[local-name()="entry"] | /*[local-name()="RDF"]/*[local-name()="item"]');
            $result = [];
            foreach (array_slice($items ?: [], 0, config('technology_news.limit')) as $node) {
                $get = fn ($name) => (string) (($node->xpath('./*[local-name()="'.$name.'"]')[0] ?? ''));
                $url = $get('link');
                foreach ($node->xpath('./*[local-name()="link"]') ?: [] as $link) {
                    if (isset($link['href']) && (!isset($link['rel']) || (string) $link['rel'] === 'alternate')) { $url = (string) $link['href']; break; }
                }
                $author = $get('creator');
                $names = $node->xpath('./*[local-name()="author"]/*[local-name()="name"]');
                if (!$author && $names) { $author = (string) $names[0]; }
                $tags = [];
                foreach ($node->xpath('./*[local-name()="category"]') ?: [] as $category) { $tags[] = (string) ($category['term'] ?? $category); }
                $result[] = ['id' => $get('guid') ?: $get('id') ?: $url, 'title' => $get('title'), 'url' => $url,
                    'published_at' => $get('pubDate') ?: $get('published') ?: $get('date') ?: $get('updated'),
                    'description' => $get('description') ?: $get('summary'), 'author' => $author ?: null, 'tags' => $tags,
                    'content' => $includeContent ? ($contentField === 'description' ? $get('description') : ($get('encoded') ?: $get('content'))) : null];
            }
            return $result;
        } finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
    }
}
