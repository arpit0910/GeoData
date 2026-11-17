<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TechnologyNewsController as PublicNews;
use App\Models\{NewsArticle, NewsCategory, NewsSource, NewsSyncLog};
use App\Jobs\SyncTechnologyNews;
use App\Services\TechnologyNews\{NewsCache, SafeHttp};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TechnologyNewsController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) { abort_unless($request->user() && $request->user()->is_admin, 403); return $next($request); });
    }
    public function index(Request $request)
    {
        $f = PublicNews::filters($request);
        $request->validate(['status' => 'nullable|in:published,hidden', 'editorial_status' => 'nullable|in:pending,processing,ready,published,failed']);
        $articles = PublicNews::query($f, false)->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->editorial_status, fn ($q) => $q->where('editorial_status', $request->editorial_status))->paginate(20)->withQueryString();
        $summary = ['Stored' => NewsArticle::count(), 'Published' => NewsArticle::published()->count(), 'Added in 24h' => NewsArticle::where('created_at', '>=', now('UTC')->subDay())->count(),
            'Active sources' => NewsSource::where('is_active', true)->count(), 'Failed syncs' => NewsSyncLog::whereIn('status', ['failed', 'partial'])->count(), 'Duplicates observed' => NewsSyncLog::sum('duplicate_count')];
        $summary['Awaiting AI'] = NewsArticle::whereIn('editorial_status', ['pending', 'processing'])->count();
        $summary['AI failures'] = NewsArticle::where('editorial_status', 'failed')->count();
        if ($request->expectsJson()) { return response()->json(['success' => true, 'data' => $articles, 'summary' => $summary]); }
        return view('admin.technology-news.index', ['articles' => $articles, 'sources' => NewsSource::all(), 'categories' => NewsCategory::orderBy('sort_order')->get(),
            'logs' => NewsSyncLog::with('source')->latest('id')->paginate(15, ['*'], 'logs_page'), 'summary' => $summary,
            'lastSuccess' => NewsSource::max('last_success_at'),
            'duplicates' => DB::table('news_duplicate_candidates as d')->join('news_articles as a', 'a.id', '=', 'd.article_id')->join('news_articles as b', 'b.id', '=', 'd.candidate_id')->where('d.status', 'pending')->limit(20)->get(['d.*', 'a.title', 'b.title as candidate_title'])]);
    }
    private function reply(Request $r, string $message)
    {
        return $r->expectsJson() ? response()->json(['success' => true, 'message' => $message]) : back()->with('success', $message);
    }
    public function sources() { return response()->json(['success' => true, 'data' => NewsSource::orderBy('id')->get()]); }
    public function logs() { return response()->json(['success' => true, 'data' => NewsSyncLog::with('source:id,name')->latest('id')->paginate(25)]); }
    public function sync(Request $request, ?int $id = null)
    {
        if ($id) { NewsSource::findOrFail($id); }
        $count = 0;
        foreach (NewsSource::where('is_active', true)->when($id, fn ($q) => $q->whereKey($id))->get() as $source) {
            if ($source->displayPermitted()) { SyncTechnologyNews::dispatch($source->id)->onConnection('technology-news')->onQueue('technology-news'); $count++; }
        }
        return $this->reply($request, $count.' source synchronization jobs queued. Provider retry windows remain in effect.');
    }
    public function storeSource(Request $r, SafeHttp $http) { return $this->saveSource($r, $http, new NewsSource(['source_type' => 'rss'])); }
    public function updateSource(Request $r, SafeHttp $http, int $id) { return $this->saveSource($r, $http, NewsSource::findOrFail($id)); }
    private function saveSource(Request $r, SafeHttp $http, NewsSource $source)
    {
        $data = $r->validate(['name' => 'required|string|max:255', 'feed_url' => 'nullable|url|max:2048', 'is_active' => 'required|boolean',
            'sync_interval_minutes' => 'required|integer|min:60|max:10080|multiple_of:60', 'display_mode' => 'required|in:disabled,headline,excerpt',
            'permission_reference' => 'nullable|url|max:2048', 'permission_notes' => 'nullable|string|max:4000', 'permissions_confirmed' => 'sometimes|accepted', 'allow_ai_rewrite' => 'sometimes|boolean']);
        if ($source->source_type === 'rss') {
            $r->validate(['feed_url' => 'required|url']);
            try { $http->validateUrl($data['feed_url']); } catch (\Throwable $e) { throw \Illuminate\Validation\ValidationException::withMessages(['feed_url' => $e->getMessage()]); }
        }
        if ($data['is_active'] || $data['display_mode'] !== 'disabled') {
            $r->validate(['permission_reference' => 'required|url', 'permission_notes' => 'required|string', 'permissions_confirmed' => 'required|accepted']);
            $data['permissions_verified_at'] = now('UTC');
        } else { $data['permissions_verified_at'] = null; }
        unset($data['permissions_confirmed']);
        $data['base_url'] = $source->base_url ?: 'https://'.parse_url($data['feed_url'], PHP_URL_HOST);
        if (($data['feed_url'] ?? null) !== $source->feed_url || ($data['allow_ai_rewrite'] ?? false) != $source->allow_ai_rewrite) { $data['etag'] = null; $data['last_modified'] = null; }
        $source->fill($data)->save(); NewsCache::invalidate();
        return $this->reply($r, 'News source saved.');
    }
    public function updateArticle(Request $r, int $id)
    {
        $data = $r->validate(['category_id' => 'sometimes|required|integer|exists:news_categories,id', 'status' => 'sometimes|required|in:published,hidden', 'is_featured' => 'sometimes|required|boolean']);
        if (isset($data['category_id'])) { $data['editorial_category'] = true; }
        NewsArticle::findOrFail($id)->update($data); NewsCache::invalidate();
        return $this->reply($r, 'Article updated.');
    }
    public function duplicate(Request $r, int $id)
    {
        $r->validate(['decision' => 'required|in:distinct,hide']);
        $candidate = DB::table('news_duplicate_candidates')->where('id', $id)->first(); abort_unless($candidate, 404);
        DB::transaction(function () use ($r, $id, $candidate) {
            if ($r->decision === 'hide') { NewsArticle::findOrFail($candidate->article_id)->update(['status' => 'hidden']); }
            DB::table('news_duplicate_candidates')->where('id', $id)->update(['status' => $r->decision, 'updated_at' => now('UTC')]);
        });
        NewsCache::invalidate(); return $this->reply($r, 'Duplicate candidate reviewed.');
    }
}
