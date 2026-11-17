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
        return ['items' => $this->parseFeed($response->body(), $source->rewritePermitted() && $source->allow_feed_content), 'etag' => $response->header('ETag'), 'last_modified' => $response->header('Last-Modified')];
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

    public function parseFeed(string $xml, bool $includeContent = false): array
    {
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
                    'published_at' => $get('pubDate') ?: $get('published') ?: $get('date'),
                    'description' => $get('description') ?: $get('summary'), 'author' => $author ?: null, 'tags' => $tags,
                    'content' => $includeContent ? ($get('encoded') ?: $get('content')) : null];
            }
            return $result;
        } finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
    }
}
