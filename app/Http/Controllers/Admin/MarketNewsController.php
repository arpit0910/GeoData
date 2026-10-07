<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketNews;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketNewsController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $status = trim((string) $request->input('status'));
        $validStatuses = [
            MarketNews::STATUS_PENDING,
            MarketNews::STATUS_PROCESSING,
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
            'failed' => (int) ($counts[MarketNews::STATUS_FAILED] ?? 0),
        ];

        return view('admin.market-news.index', compact('news', 'summary'));
    }
}
