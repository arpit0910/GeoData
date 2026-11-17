<?php

namespace App\Http\Controllers;

use App\Models\{NewsArticle, NewsCategory, NewsSource};
use App\Services\TechnologyNews\NewsCache;
use Illuminate\Http\Request;

class TechnologyNewsController extends Controller
{
    public static function filters(Request $request): array
    {
        return $request->validate(['q' => 'nullable|string|max:150', 'category' => 'nullable|integer|exists:news_categories,id',
            'source' => 'nullable|integer|exists:news_sources,id', 'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d|after_or_equal:from', 'page' => 'nullable|integer|min:1|max:10000', 'per_page' => 'nullable|integer|min:1|max:50']);
    }
    public static function query(array $f, bool $public = true)
    {
        $q = NewsArticle::with(['source:id,name,display_mode,permission_reference,permission_notes', 'category:id,name,slug']);
        if ($public) { $q->published(); }
        if (!empty($f['q'])) {
            $search = '%'.addcslashes($f['q'], '%_\\').'%';
            $q->where(fn ($q) => $q->where('title', 'like', $search)->orWhere('description', 'like', $search)->orWhere('ai_title', 'like', $search)->orWhere('ai_summary', 'like', $search));
        }
        foreach (['category' => 'category_id', 'source' => 'source_id'] as $key => $column) { if (!empty($f[$key])) { $q->where($column, $f[$key]); } }
        if (!empty($f['from'])) { $q->where('published_at', '>=', $f['from'].' 00:00:00'); }
        if (!empty($f['to'])) { $q->where('published_at', '<=', $f['to'].' 23:59:59'); }
        return $q->orderByDesc('published_at')->orderByDesc('id');
    }
    public static function sources()
    {
        return NewsSource::where('is_active', true)->whereNotNull('permissions_verified_at')->whereIn('display_mode', ['headline', 'excerpt'])->get(['id', 'name']);
    }
    public static function publicArticle(NewsArticle $article): array
    {
        return ['id' => $article->id, 'slug' => $article->slug, 'title' => $article->ai_title,
            'description' => $article->ai_summary, 'content' => $article->ai_content,
            'original_url' => $article->original_url, 'publisher_name' => $article->publisher_name,
            'author_name' => $article->author_name, 'published_at' => $article->published_at->utc()->toIso8601String(),
            'category' => $article->category, 'tags' => $article->ai_tags, 'source' => ['id' => $article->source_id, 'name' => $article->source->name],
            'editorial_note' => 'AI-assisted summary, fact-checked against source metadata.',
            'permission_reference' => $article->source->permission_reference,
            'source_score' => $article->source_score];
    }
    public function index(Request $request)
    {
        $filters = self::filters($request);
        $news = self::query($filters)->paginate(20)->withQueryString();
        return view('website.technology-news', [
            'news' => $news, 'categories' => NewsCache::remember('categories', 3600, fn () => NewsCategory::where('is_active', true)->orderBy('sort_order')->get()),
            'sources' => self::sources(),
            'featured' => NewsCache::remember('featured', 300, fn () => NewsArticle::published()->with(['source', 'category'])->where('is_featured', true)->latest('published_at')->first()),
            'trending' => $this->trendingArticles(),
        ]);
    }
    private function trendingArticles()
    {
        return NewsCache::remember('trending', 900, fn () => NewsArticle::published()->with(['source', 'category'])->whereHas('source', fn ($q) => $q->where('source_type', 'hackernews'))
            ->whereNotNull('source_score')->where('last_seen_at', '>=', now('UTC')->subDay())->reorder()->orderByDesc('source_score')->limit(5)->get());
    }
    public function api(Request $request)
    {
        $action = $request->route('action');
        if ($action === 'categories') { return response()->json(['success' => true, 'data' => NewsCache::remember('categories', 3600, fn () => NewsCategory::where('is_active', true)->orderBy('sort_order')->get())]); }
        if ($action === 'sources') { return response()->json(['success' => true, 'data' => self::sources()]); }
        if ($action === 'trending') { return response()->json(['success' => true, 'ranking' => 'Hacker News score', 'data' => $this->trendingArticles()->map(fn ($a) => self::publicArticle($a))]); }
        $filters = self::filters($request);
        $page = NewsCache::remember('list:'.json_encode($filters), 300, fn () => self::query($filters)->paginate($filters['per_page'] ?? 20, ['*'], 'page', $filters['page'] ?? 1));
        return response()->json(['success' => true, 'data' => $page->getCollection()->map(fn ($a) => self::publicArticle($a)),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage(), 'total' => $page->total()]]);
    }
    public function show(string $slug)
    {
        $article = NewsArticle::published()->with(['source', 'category'])->where('slug', $slug)->firstOrFail();
        // No thin local article pages: provide metadata in API and direct readers to the publisher.
        return request()->expectsJson() ? response()->json(['success' => true, 'data' => self::publicArticle($article)]) : redirect()->away($article->original_url);
    }
}
