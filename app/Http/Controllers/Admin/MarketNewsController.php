<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketNews;
use App\Services\GeminiNewsRewriter;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class MarketNewsController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $status = trim((string) $request->input('status'));
        $validStatuses = [
            MarketNews::STATUS_PENDING,
            MarketNews::STATUS_PROCESSING,
            MarketNews::STATUS_READY,
            MarketNews::STATUS_PUBLISHED,
            MarketNews::STATUS_FAILED,
        ];

        $news = MarketNews::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('original_title', 'like', "%{$search}%")
                        ->orWhere('symbol', 'like', "%{$search}%")
                        ->orWhere('isin', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, $validStatuses, true), fn ($query) => $query->where('editorial_status', $status))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $counts = MarketNews::query()
            ->selectRaw('editorial_status, COUNT(*) as total')
            ->groupBy('editorial_status')
            ->pluck('total', 'editorial_status');
        $summary = [
            'total' => MarketNews::count(),
            'published' => (int) ($counts[MarketNews::STATUS_PUBLISHED] ?? 0),
            'pending' => (int) ($counts[MarketNews::STATUS_PENDING] ?? 0),
            'ready' => (int) ($counts[MarketNews::STATUS_READY] ?? 0),
            'failed' => (int) ($counts[MarketNews::STATUS_FAILED] ?? 0),
        ];

        return view('admin.market-news.index', compact('news', 'summary'));
    }

    public function regenerate(MarketNews $marketNews, GeminiNewsRewriter $rewriter): RedirectResponse
    {
        set_time_limit(180);

        try {
            $rewriter->rewrite($marketNews);

            return back()->with('success', "News #{$marketNews->id} was regenerated and is ready for approval.");
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', "News #{$marketNews->id} could not be regenerated: {$exception->getMessage()}");
        }
    }

    public function approve(Request $request, MarketNews $marketNews): RedirectResponse
    {
        if ($marketNews->editorial_status !== MarketNews::STATUS_READY
            || ! $marketNews->rewritten_at
            || trim((string) $marketNews->title) === ''
            || trim((string) $marketNews->summary) === '') {
            return back()->with('error', 'Only a successfully regenerated news draft can be approved.');
        }

        $marketNews->forceFill([
            'editorial_status' => MarketNews::STATUS_PUBLISHED,
            'is_published' => true,
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
        ])->save();

        return back()->with('success', "News #{$marketNews->id} was approved and published.");
    }

    public function unpublish(MarketNews $marketNews): RedirectResponse
    {
        $marketNews->forceFill([
            'editorial_status' => $marketNews->rewritten_at
                ? MarketNews::STATUS_READY
                : MarketNews::STATUS_PENDING,
            'is_published' => false,
            'reviewed_at' => null,
            'reviewed_by' => null,
        ])->save();

        return back()->with('success', "News #{$marketNews->id} was removed from the public website.");
    }
}
