@extends('layouts.app')
@section('title', 'Latest Index Quotes')

@section('content')
<div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 mb-2">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            LIVE EXCHANGE FEEDS
        </div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Latest Index Quotes</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 font-medium">Real-time quotes, valuation metrics, and intraday changes for NSE &amp; BSE market indices. All timestamps in IST.</p>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        <form method="POST" action="{{ route('admin.indices.quotes.sync') }}" onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').innerHTML='<i class=&quot;fas fa-spinner fa-spin mr-2&quot;></i>Syncing…';">
            @csrf
            <button type="submit" class="inline-flex items-center px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white text-sm font-bold shadow-lg shadow-emerald-600/20 transition-all hover:scale-[1.02] active:scale-[0.98]">
                <i class="fas fa-cloud-arrow-down mr-2"></i> Sync Live Quotes
            </button>
        </form>
        <a href="{{ request()->fullUrl() }}" class="inline-flex items-center px-4 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-sm font-bold shadow-lg shadow-amber-600/20 transition-all">
            <i class="fas fa-rotate mr-2"></i> Refresh
        </a>
        <a href="{{ route('admin.indices.prices') }}" class="inline-flex items-center px-4 py-2.5 rounded-xl border border-gray-200 dark:border-white/10 text-gray-700 dark:text-gray-300 bg-white dark:bg-white/5 hover:bg-gray-50 dark:hover:bg-white/10 text-sm font-semibold transition-all">
            <i class="fas fa-chart-line mr-2 text-amber-500"></i> Historical Prices
        </a>
        <a href="{{ route('admin.indices.index') }}" class="inline-flex items-center px-4 py-2.5 rounded-xl border border-gray-200 dark:border-white/10 text-gray-700 dark:text-gray-300 bg-white dark:bg-white/5 hover:bg-gray-50 dark:hover:bg-white/10 text-sm font-semibold transition-all">
            <i class="fas fa-list-ol mr-2 text-indigo-400"></i> Index Master
        </a>
    </div>
</div>

@if (session('success'))
<div class="mb-6 rounded-2xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 px-5 py-4 text-sm font-medium text-emerald-800 dark:text-emerald-300 flex items-center justify-between">
    <div class="flex items-center gap-3">
        <i class="fas fa-check-circle text-emerald-500 text-base"></i>
        <span>{{ session('success') }}</span>
    </div>
</div>
@endif

@if (session('error'))
<div class="mb-6 rounded-2xl bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/20 px-5 py-4 text-sm font-medium text-rose-800 dark:text-rose-300 flex items-center justify-between">
    <div class="flex items-center gap-3">
        <i class="fas fa-exclamation-circle text-rose-500 text-base"></i>
        <span>{{ session('error') }}</span>
    </div>
</div>
@endif

{{-- KPI Summary Stats --}}
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-8">
    <div class="bg-white dark:bg-[#0f172a]/80 backdrop-blur-xl border border-gray-200 dark:border-white/5 rounded-2xl p-4 shadow-sm">
        <div class="flex items-center justify-between text-xs font-bold text-gray-400 uppercase tracking-wider">
            <span>Total Indices</span>
            <i class="fas fa-layer-group text-amber-500"></i>
        </div>
        <div class="mt-2 text-2xl font-black text-gray-900 dark:text-white font-mono">{{ number_format($stats['total']) }}</div>
        <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Tracked index instruments</div>
    </div>

    <div class="bg-white dark:bg-[#0f172a]/80 backdrop-blur-xl border border-gray-200 dark:border-white/5 rounded-2xl p-4 shadow-sm">
        <div class="flex items-center justify-between text-xs font-bold text-gray-400 uppercase tracking-wider">
            <span>Live / Fresh (&le;15m)</span>
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
        </div>
        <div class="mt-2 text-2xl font-black text-emerald-500 font-mono">{{ number_format($stats['recent']) }}</div>
        <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Actively ticking quotes</div>
    </div>

    <div class="bg-white dark:bg-[#0f172a]/80 backdrop-blur-xl border border-gray-200 dark:border-white/5 rounded-2xl p-4 shadow-sm">
        <div class="flex items-center justify-between text-xs font-bold text-gray-400 uppercase tracking-wider">
            <span>Advances</span>
            <i class="fas fa-arrow-trend-up text-emerald-500"></i>
        </div>
        <div class="mt-2 text-2xl font-black text-emerald-400 font-mono">{{ number_format($stats['gainers']) }}</div>
        <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Positive intraday movement</div>
    </div>

    <div class="bg-white dark:bg-[#0f172a]/80 backdrop-blur-xl border border-gray-200 dark:border-white/5 rounded-2xl p-4 shadow-sm">
        <div class="flex items-center justify-between text-xs font-bold text-gray-400 uppercase tracking-wider">
            <span>Declines</span>
            <i class="fas fa-arrow-trend-down text-rose-500"></i>
        </div>
        <div class="mt-2 text-2xl font-black text-rose-400 font-mono">{{ number_format($stats['losers']) }}</div>
        <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Negative intraday movement</div>
    </div>

    <div class="bg-white dark:bg-[#0f172a]/80 backdrop-blur-xl border border-gray-200 dark:border-white/5 rounded-2xl p-4 shadow-sm col-span-2 sm:col-span-1">
        <div class="flex items-center justify-between text-xs font-bold text-gray-400 uppercase tracking-wider">
            <span>Last Sync Time</span>
            <i class="fas fa-clock text-cyan-400"></i>
        </div>
        <div class="mt-2 text-lg font-bold text-gray-900 dark:text-white font-mono">
            {{ $stats['last_fetched'] ? \Carbon\Carbon::parse($stats['last_fetched'], 'UTC')->timezone('Asia/Kolkata')->format('h:i:s A') : '--:--' }}
        </div>
        <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
            {{ $stats['last_fetched'] ? \Carbon\Carbon::parse($stats['last_fetched'], 'UTC')->timezone('Asia/Kolkata')->format('d M Y') : 'No sync recorded' }}
        </div>
    </div>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('admin.indices.quotes') }}" class="mb-6 p-6 rounded-2xl bg-white dark:bg-[#0f172a]/80 backdrop-blur-xl border border-gray-200 dark:border-white/5 shadow-sm">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
        <div>
            <label for="search" class="block text-xs font-bold text-gray-400 mb-2 uppercase tracking-wider">Search Index</label>
            <div class="relative">
                <i class="fas fa-search absolute left-3.5 top-3.5 text-xs text-gray-400"></i>
                <input id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="e.g. NIFTY 50, SENSEX..." class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl pl-9 pr-4 py-2.5 text-xs text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 transition-all">
            </div>
        </div>

        <div>
            <label for="exchange" class="block text-xs font-bold text-gray-400 mb-2 uppercase tracking-wider">Exchange</label>
            <select id="exchange" name="exchange" class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 transition-all">
                <option value="">All Exchanges</option>
                <option value="NSE" @selected(($filters['exchange'] ?? '') === 'NSE')>NSE</option>
                <option value="BSE" @selected(($filters['exchange'] ?? '') === 'BSE')>BSE</option>
            </select>
        </div>

        <div>
            <label for="category" class="block text-xs font-bold text-gray-400 mb-2 uppercase tracking-wider">Category</label>
            <select id="category" name="category" class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 transition-all">
                <option value="">All Categories</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat }}" @selected(($filters['category'] ?? '') === $cat)>{{ $cat }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="freshness" class="block text-xs font-bold text-gray-400 mb-2 uppercase tracking-wider">Freshness</label>
            <select id="freshness" name="freshness" class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 transition-all">
                <option value="">All Quote Ages</option>
                <option value="recent" @selected(($filters['freshness'] ?? '') === 'recent')>Live (&le; 15 mins)</option>
                <option value="stale" @selected(($filters['freshness'] ?? '') === 'stale')>Stale (&gt; 15 mins)</option>
                <option value="pending" @selected(($filters['freshness'] ?? '') === 'pending')>Pending Sync</option>
            </select>
        </div>

        <div class="flex items-center gap-2">
            <button type="submit" class="flex-1 bg-amber-600 hover:bg-amber-700 text-white font-bold py-2.5 px-4 rounded-xl text-xs transition-all shadow-md active:scale-95">
                <i class="fas fa-filter mr-1.5"></i> Filter
            </button>
            <a href="{{ route('admin.indices.quotes') }}" class="p-2.5 text-gray-400 hover:text-gray-700 dark:hover:text-white rounded-xl hover:bg-gray-100 dark:hover:bg-white/5 transition-all" title="Reset Filters">
                <i class="fas fa-undo text-xs"></i>
            </a>
        </div>
    </div>
</form>

{{-- Results Header --}}
<div class="flex items-center justify-between mb-3 text-xs text-gray-500 dark:text-gray-400 font-medium">
    <div>
        Showing <span class="font-bold text-gray-900 dark:text-white font-mono">{{ number_format($quotes->total()) }}</span> live index quote records
    </div>
    <div>Page {{ $quotes->currentPage() }} of {{ $quotes->lastPage() }}</div>
</div>

{{-- Data Table --}}
<div class="rounded-2xl bg-white dark:bg-[#0f172a]/80 backdrop-blur-xl border border-gray-200 dark:border-white/5 shadow-sm overflow-hidden" x-data="{ modalOpen: false, activePayload: null, activeIndexName: '' }">
    <div class="overflow-x-auto">
        <table class="w-full text-xs text-left whitespace-nowrap">
            <thead class="bg-gray-50 dark:bg-white/5 text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider text-[11px]">
                <tr>
                    <th scope="col" class="px-5 py-3.5">Index Instrument</th>
                    <th scope="col" class="px-5 py-3.5 text-right">Live Price (LTP)</th>
                    <th scope="col" class="px-5 py-3.5 text-right">Net Change</th>
                    <th scope="col" class="px-5 py-3.5 text-right">Change %</th>
                    <th scope="col" class="px-5 py-3.5 text-right">Prev Close</th>
                    <th scope="col" class="px-5 py-3.5 text-center">Quoted Time (IST)</th>
                    <th scope="col" class="px-5 py-3.5 text-center">Status</th>
                    <th scope="col" class="px-5 py-3.5 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/5 text-gray-700 dark:text-gray-300">
                @forelse ($quotes as $quote)
                @php
                    $isGainer = $quote->change > 0;
                    $isLoser = $quote->change < 0;
                @endphp
                <tr class="hover:bg-gray-50/60 dark:hover:bg-white/[0.02] transition-colors">
                    {{-- Index Instrument --}}
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-sm text-gray-900 dark:text-white font-mono">{{ $quote->index_code }}</span>
                            @if ($quote->exchange)
                                <span class="rounded px-1.5 py-0.5 text-[10px] font-bold {{ $quote->exchange === 'NSE' ? 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20' }}">
                                    {{ $quote->exchange }}
                                </span>
                            @endif
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-medium">
                            {{ $quote->index_name ?: $quote->index_code }}
                        </div>
                        @if ($quote->category)
                            <div class="text-[10px] text-gray-400 mt-0.5">
                                <span class="inline-block bg-gray-100 dark:bg-white/5 px-1.5 py-0.5 rounded text-gray-500 dark:text-gray-400">{{ $quote->category }}</span>
                            </div>
                        @endif
                    </td>

                    {{-- Live Price --}}
                    <td class="px-5 py-3.5 text-right">
                        @if ($quote->price !== null)
                            <div class="text-sm font-black text-gray-900 dark:text-white font-mono">
                                {{ number_format($quote->price, 2) }}
                            </div>
                            @if ($quote->volume)
                                <div class="text-[10px] text-gray-400 mt-0.5 font-mono">Vol: {{ number_format($quote->volume) }}</div>
                            @endif
                        @else
                            <span class="text-gray-400 font-mono text-sm">--</span>
                        @endif
                    </td>

                    {{-- Net Change --}}
                    <td class="px-5 py-3.5 text-right font-mono font-bold">
                        @if ($quote->change !== null)
                            <span class="{{ $isGainer ? 'text-emerald-500' : ($isLoser ? 'text-rose-500' : 'text-gray-400') }}">
                                {{ $isGainer ? '+' : '' }}{{ number_format($quote->change, 2) }}
                            </span>
                        @else
                            <span class="text-gray-400">--</span>
                        @endif
                    </td>

                    {{-- Change % --}}
                    <td class="px-5 py-3.5 text-right font-mono">
                        @if ($quote->change_percent !== null)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold {{ $isGainer ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : ($isLoser ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20' : 'bg-gray-100 text-gray-500 dark:bg-white/5') }}">
                                <i class="fas {{ $isGainer ? 'fa-arrow-up text-[10px]' : ($isLoser ? 'fa-arrow-down text-[10px]' : 'fa-minus text-[10px]') }}"></i>
                                {{ $isGainer ? '+' : '' }}{{ number_format($quote->change_percent, 2) }}%
                            </span>
                        @else
                            <span class="text-gray-400">--</span>
                        @endif
                    </td>

                    {{-- Prev Close --}}
                    <td class="px-5 py-3.5 text-right font-mono text-gray-500 dark:text-gray-400">
                        {{ $quote->previous_close ? number_format($quote->previous_close, 2) : '--' }}
                    </td>

                    {{-- Quoted Time --}}
                    <td class="px-5 py-3.5 text-center font-mono">
                        @if ($quote->quoted_time)
                            <div class="text-gray-900 dark:text-gray-200 font-semibold">{{ $quote->quoted_time->format('h:i:s A') }}</div>
                            <div class="text-[10px] text-gray-400">{{ $quote->quoted_time->format('d M Y') }}</div>
                        @else
                            <span class="text-gray-400">--</span>
                        @endif
                    </td>

                    {{-- Status Badge --}}
                    <td class="px-5 py-3.5 text-center">
                        @if ($quote->price === null)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-gray-500/10 text-gray-400 border border-gray-500/20">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Pending Sync
                            </span>
                        @elseif ($quote->is_stale)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span> Stale (&gt;15m)
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> Live
                            </span>
                        @endif
                    </td>

                    {{-- Actions --}}
                    <td class="px-5 py-3.5 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            @if (!empty($quote->details))
                                <button type="button" @click="activePayload = {{ json_encode($quote->details) }}; activeIndexName = '{{ $quote->index_name ?: $quote->index_code }} ({{ $quote->index_code }})'; modalOpen = true;" class="p-1.5 text-gray-400 hover:text-amber-500 hover:bg-amber-500/10 rounded-lg transition-colors" title="View Payload JSON">
                                    <i class="fas fa-code text-xs"></i>
                                </button>
                            @else
                                <span class="p-1.5 text-gray-300 dark:text-gray-600 cursor-not-allowed" title="No payload available yet">
                                    <i class="fas fa-code text-xs"></i>
                                </span>
                            @endif
                            <a href="{{ route('admin.indices.show', $quote->index_code) }}" class="p-1.5 text-gray-400 hover:text-indigo-400 hover:bg-indigo-500/10 rounded-lg transition-colors" title="View Index History">
                                <i class="fas fa-arrow-up-right-from-square text-xs"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-5 py-16 text-center">
                        <div class="w-14 h-14 mx-auto rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-4 text-xl">
                            <i class="fas fa-arrow-trend-up"></i>
                        </div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">No Live Index Quotes Found</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                            No quotes have been stored yet. Click the <strong>Sync Live Quotes</strong> button at the top to fetch the latest index prices from the live exchange feed.
                        </p>
                        <form method="POST" action="{{ route('admin.indices.quotes.sync') }}" class="mt-5">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md transition-all">
                                <i class="fas fa-cloud-arrow-down mr-2"></i> Sync Now
                            </button>
                        </form>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($quotes->hasPages())
    <div class="p-4 border-t border-gray-100 dark:border-white/5 bg-gray-50/50 dark:bg-white/[0.01]">
        {{ $quotes->links() }}
    </div>
    @endif

    {{-- JSON Payload Modal --}}
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" @click="modalOpen = false"></div>
            <div class="relative bg-white dark:bg-[#0f172a] border border-gray-200 dark:border-white/10 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[calc(100vh-6rem)] overflow-hidden z-10 flex flex-col">
                <div class="p-6 border-b border-gray-200 dark:border-white/10 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white" x-text="activeIndexName"></h3>
                        <p class="text-xs text-gray-400">Live Quote Raw Payload</p>
                    </div>
                    <button type="button" @click="modalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-white p-2 rounded-lg">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-6 overflow-y-auto flex-1 font-mono text-xs bg-gray-50 dark:bg-[#070b13] text-gray-800 dark:text-gray-200">
                    <pre x-text="JSON.stringify(activePayload, null, 2)" class="whitespace-pre-wrap break-all"></pre>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

