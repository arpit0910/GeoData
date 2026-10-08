<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\GroqRateLimitException;
use App\Http\Controllers\Controller;
use App\Models\MarketNews;
use App\Services\GroqNewsRewriter;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
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

    public function show(MarketNews $marketNews): View
    {
        return view('admin.market-news.show', compact('marketNews'));
    }

    public function regenerate(MarketNews $marketNews, GroqNewsRewriter $rewriter): RedirectResponse
    {
        set_time_limit(180);

        try {
            $this->regenerateDraft($marketNews, $rewriter);

            return back()->with('success', "News #{$marketNews->id} was regenerated and is ready for approval.");
        } catch (GroqRateLimitException $exception) {
            return back()->with('error', "News #{$marketNews->id} was deferred until Groq quota is available again. It will retry automatically.");
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', "News #{$marketNews->id} could not be regenerated: {$exception->getMessage()}");
        }
    }

    public function bulkRegenerate(Request $request, GroqNewsRewriter $rewriter): RedirectResponse
    {
        $validated = $request->validate([
            'news_ids' => ['required', 'array', 'min:1', 'max:25'],
            'news_ids.*' => ['integer', 'distinct', 'exists:market_news,id'],
        ]);
        $ids = array_values(array_unique(array_map('intval', $validated['news_ids'])));
        $stories = MarketNews::query()
            ->whereIn('id', $ids)
            ->where('editorial_status', '<>', MarketNews::STATUS_PROCESSING)
            ->orderBy('id')
            ->get();

        set_time_limit(max(180, count($ids) * 120));
        $generated = 0;
        $failures = [];
        $quotaDeferred = false;

        foreach ($stories as $story) {
            try {
                $this->regenerateDraft($story, $rewriter);
                $generated++;
            } catch (GroqRateLimitException) {
                $quotaDeferred = true;
                break;
            } catch (Throwable $exception) {
                report($exception);
                $failures[] = "#{$story->id}: ".$exception->getMessage();
            }
        }

        $skipped = count($ids) - $stories->count();
        if ($generated === 0) {
            if ($quotaDeferred) {
                return back()->with('error', 'Generation was deferred because the Groq model pool reached its rate limit. The pending story will retry automatically after the provider cooldown.');
            }
            $details = $failures !== [] ? ' '.implode(' | ', array_slice($failures, 0, 3)) : '';

            return back()->with('error', 'No selected stories could be generated.'.$details);
        }

        $message = "{$generated} selected news ".($generated === 1 ? 'story was' : 'stories were').' generated and sent for approval.';
        if ($failures !== []) {
            $message .= ' '.count($failures).' failed; open those rows to review the exact validation feedback.';
        }
        if ($skipped > 0) {
            $message .= " {$skipped} processing ".($skipped === 1 ? 'story was' : 'stories were').' skipped.';
        }
        if ($quotaDeferred) {
            $message .= ' Remaining stories were left pending because the Groq pool reached its rate limit; processing will resume automatically after cooldown.';
        }

        return back()->with('success', $message);
    }

    public function approve(Request $request, MarketNews $marketNews): RedirectResponse
    {
        if ($marketNews->editorial_status !== MarketNews::STATUS_READY
            || ! $marketNews->rewritten_at
            || trim((string) $marketNews->title) === ''
            || trim((string) $marketNews->summary) === '') {
            return back()->with('error', 'Only a successfully regenerated news draft can be approved.');
        }

        $this->approveDraft($marketNews, $request->user()->id);

        return back()->with('success', "News #{$marketNews->id} was approved and published.");
    }

    public function bulkApprove(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'news_ids' => ['required', 'array', 'min:1', 'max:100'],
            'news_ids.*' => ['integer', 'distinct', 'exists:market_news,id'],
        ]);
        $ids = array_values(array_unique(array_map('intval', $validated['news_ids'])));
        $approved = 0;

        DB::transaction(function () use ($ids, $request, &$approved) {
            $drafts = MarketNews::query()
                ->whereIn('id', $ids)
                ->where('editorial_status', MarketNews::STATUS_READY)
                ->whereNotNull('rewritten_at')
                ->whereNotNull('title')->where('title', '<>', '')
                ->whereNotNull('summary')->where('summary', '<>', '')
                ->lockForUpdate()
                ->get();

            foreach ($drafts as $draft) {
                $this->approveDraft($draft, $request->user()->id);
                $approved++;
            }
        });

        $skipped = count($ids) - $approved;
        if ($approved === 0) {
            return back()->with('error', 'None of the selected stories had a verified draft ready for approval.');
        }

        $message = "{$approved} news ".($approved === 1 ? 'story was' : 'stories were').' approved and published.';
        if ($skipped > 0) {
            $message .= " {$skipped} ineligible ".($skipped === 1 ? 'story was' : 'stories were').' skipped.';
        }

        return back()->with('success', $message);
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

    private function approveDraft(MarketNews $marketNews, int $userId): void
    {
        $marketNews->forceFill([
            'editorial_status' => MarketNews::STATUS_PUBLISHED,
            'is_published' => true,
            'reviewed_at' => now(),
            'reviewed_by' => $userId,
        ])->save();
    }

    private function regenerateDraft(MarketNews $marketNews, GroqNewsRewriter $rewriter): void
    {
        $cachedContent = $marketNews->original_content;
        $cachedAt = $marketNews->source_fetched_at;
        $marketNews->forceFill([
            'original_content' => null,
            'source_fetched_at' => null,
        ])->save();

        try {
            $rewriter->rewrite($marketNews);
        } catch (Throwable $exception) {
            $marketNews->refresh();
            if (! $marketNews->original_content && $cachedContent) {
                $marketNews->forceFill([
                    'original_content' => $cachedContent,
                    'source_fetched_at' => $cachedAt,
                ])->save();
            }

            throw $exception;
        }
    }
}
