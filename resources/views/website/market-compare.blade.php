@extends('layouts.public')
@section('title', 'Compare a Stock with an Index - SetuGeo Markets')
@section('meta_description', 'Compare an Indian stock against Nifty, Sensex and other benchmark indices over matching trading dates.')
@section('market_heading', 'Stock vs index')
@section('market_subheading', 'Compare normalized historical returns against Nifty, Sensex and other available benchmarks.')
@section('content')
<div class="market-shell">@include('website.partials.market-nav')
<div class="market-wrap py-5 sm:py-8">
    <form method="GET" class="market-panel grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4">
        <input class="market-input" name="stock" value="{{ request('stock') }}" required placeholder="NSE/BSE symbol or ISIN" aria-label="Stock symbol or ISIN">
        <select class="market-input" name="index_code" required aria-label="Benchmark index">
            <option value="">Select benchmark</option>
            @foreach($indices as $index)<option value="{{ $index->index_code }}" @selected(request('index_code') === $index->index_code)>{{ $index->index_name }} ({{ $index->exchange }})</option>@endforeach
        </select>
        <select class="market-input" name="period" aria-label="Comparison period">
            @foreach(['1m' => '1 month', '3m' => '3 months', '6m' => '6 months', '1y' => '1 year', '3y' => '3 years', '5y' => '5 years'] as $value => $label)<option value="{{ $value }}" @selected(request('period', '1y') === $value)>{{ $label }}</option>@endforeach
        </select>
        <button class="market-button" type="submit"><i class="fas fa-chart-line mr-2"></i>Compare</button>
    </form>

    @if($error)<div class="mt-5 rounded-xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm text-rose-200">{{ $error }}</div>@endif

    @if($result && !$error)
        @php($summary = $result['summary'])
        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="market-panel p-4"><div class="market-kicker">Stock</div><div class="mt-2 font-bold text-white">{{ $result['stock']['symbol'] }} · {{ $result['stock']['name'] }}</div></div>
            <div class="market-panel p-4"><div class="market-kicker">Benchmark</div><div class="mt-2 font-bold text-white">{{ $result['index']['name'] }}</div></div>
            <div class="market-panel p-4"><div class="market-kicker">Stock return</div><div class="mt-2 text-2xl font-extrabold {{ $summary['stock_return'] >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">{{ $summary['stock_return'] >= 0 ? '+' : '' }}{{ number_format($summary['stock_return'], 2) }}%</div></div>
            <div class="market-panel p-4"><div class="market-kicker">Outperformance</div><div class="mt-2 text-2xl font-extrabold {{ $summary['outperformance'] >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">{{ $summary['outperformance'] >= 0 ? '+' : '' }}{{ number_format($summary['outperformance'], 2) }} pp</div><div class="mt-1 text-xs market-muted">Index {{ number_format($summary['index_return'], 2) }}%</div></div>
        </div>

        @if($result['live']['stock'] || $result['live']['index'])
            <div class="market-panel mt-5 flex flex-wrap gap-x-8 gap-y-2 p-4 text-sm">
                <span class="market-muted">Latest Upstox snapshot</span>
                @if($result['live']['stock'])<span class="text-white">{{ $result['stock']['symbol'] }}: ₹{{ number_format($result['live']['stock']->price, 2) }}</span>@endif
                @if($result['live']['index'])<span class="text-white">{{ $result['index']['name'] }}: {{ number_format($result['live']['index']->price, 2) }}</span>@endif
            </div>
        @endif

        <div class="market-panel mt-5 overflow-x-auto">
            <table class="market-table">
                <thead><tr><th>Date</th><th class="text-right">Stock close</th><th class="text-right">Index close</th><th class="text-right">Stock return</th><th class="text-right">Index return</th><th class="text-right">Difference</th></tr></thead>
                <tbody>@foreach(array_reverse($result['data']) as $row)<tr>
                    <td>{{ \Carbon\Carbon::parse($row['date'])->format('d M Y') }}</td>
                    <td class="text-right font-mono">{{ number_format($row['stock_close'], 2) }}</td>
                    <td class="text-right font-mono">{{ number_format($row['index_close'], 2) }}</td>
                    <td class="text-right font-mono">{{ number_format($row['stock_return'], 2) }}%</td>
                    <td class="text-right font-mono">{{ number_format($row['index_return'], 2) }}%</td>
                    <td class="text-right font-mono {{ $row['relative_return'] >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">{{ $row['relative_return'] >= 0 ? '+' : '' }}{{ number_format($row['relative_return'], 2) }} pp</td>
                </tr>@endforeach</tbody>
            </table>
        </div>
        <p class="mt-3 text-xs market-muted">Returns are rebased to 0% on the first shared trading date ({{ $summary['start_date'] }}) and use {{ number_format($summary['aligned_points']) }} matching EOD observations through {{ $summary['end_date'] }}.</p>
    @endif
</div></div>
@endsection
