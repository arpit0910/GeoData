<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockNudge;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockNudgeController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'date' => 'nullable|date_format:Y-m-d',
            'category' => 'nullable|in:growth,fall,volume,comparison',
            'search' => 'nullable|string|max:100',
        ]);
        $date = $filters['date'] ?? now('Asia/Kolkata')->toDateString();
        $category = $filters['category'] ?? null;
        $search = trim((string) ($filters['search'] ?? ''));

        $baseQuery = StockNudge::query()->whereDate('trading_date', $date);
        $nudges = (clone $baseQuery)
            ->with([
                'equity:id,company_name,nse_symbol,bse_symbol,sector,market_cap_category',
                'index:index_code,index_name,exchange,category',
            ])
            ->when($category, fn ($query) => $query->whereIn('rule', StockNudge::rulesForCategory($category)))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%")
                        ->orWhere('isin', 'like', "%{$search}%")
                        ->orWhere('index_code', 'like', "%{$search}%")
                        ->orWhereHas('equity', fn ($equity) => $equity->where('company_name', 'like', "%{$search}%"));
                });
            })
            ->latest('observed_at')
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        $summary = [
            'total' => (clone $baseQuery)->count(),
            'stocks' => (clone $baseQuery)->whereNotNull('equity_id')->count(),
            'indices' => (clone $baseQuery)->whereNotNull('index_code')->count(),
            'significant' => (clone $baseQuery)->where('severity', 'significant')->count(),
        ];

        return view('admin.nudges.index', compact('nudges', 'summary', 'date', 'category'));
    }
}
