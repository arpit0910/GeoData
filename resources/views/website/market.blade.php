@extends('layouts.public')

@section('title', 'Live Indian Stocks, Mutual Funds & Upstox Market Feed - SetuGeo')
@section('meta_description', 'Real-time feed of Indian Equities (NSE/BSE), Mutual Funds (AMFI), Corporate Actions (Splits, Bonus, Dividends), and News powered by Upstox APIs.')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<style>
    /* High-contrast styles for dropdowns and options so they are always visible */
    select.market-dropdown {
        background-color: #0b1120 !important;
        color: #ffffff !important;
        border: 1px solid rgba(255, 255, 255, 0.2) !important;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23f59e0b' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 8l4 4 4-4'/%3e%3c/svg%3e") !important;
        background-position: right 0.75rem center !important;
        background-repeat: no-repeat !important;
        background-size: 1.25em 1.25em !important;
        padding-right: 2.5rem !important;
        -webkit-appearance: none !important;
        -moz-appearance: none !important;
        appearance: none !important;
    }
    select.market-dropdown:focus {
        border-color: #f59e0b !important;
        box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.25) !important;
        outline: none !important;
    }
    select.market-dropdown option {
        background-color: #0b1120 !important;
        color: #ffffff !important;
        padding: 10px 14px !important;
    }
    select.market-dropdown option:checked {
        background-color: #d97706 !important;
        color: #ffffff !important;
    }
    .custom-scrollbar::-webkit-scrollbar {
        height: 6px;
        width: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.2);
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.15);
        border-radius: 9999px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(245, 158, 11, 0.4);
    }
</style>

<div class="relative min-h-screen bg-[#060913] text-slate-100 pb-24 overflow-hidden" x-data="marketLiveApp()" x-init="initApp()">
    {{-- Glowing background accents --}}
    <div class="absolute top-0 left-1/4 w-[650px] h-[450px] bg-amber-500/10 rounded-full blur-[150px] pointer-events-none"></div>
    <div class="absolute top-48 right-10 w-[550px] h-[400px] bg-cyan-500/10 rounded-full blur-[150px] pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
        {{-- Header & Real-time Market Status Bar --}}
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 pb-8 border-b border-white/10">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/10 border border-amber-500/30 text-amber-300">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    UPSTOX &amp; AMFI LIVE MARKET FEED
                </div>
                <h1 class="mt-3 text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-white">
                    Live Indian Stocks &amp; Mutual Funds <span class="text-gradient">Market</span>
                </h1>
                <p class="mt-2 text-slate-400 text-sm sm:text-base max-w-2xl">
                    Real-time market quotes for NSE/BSE equities, AMFI Mutual Fund NAVs, corporate actions (splits, bonus, dividends), and financial news automatically synchronized every minute.
                </p>
            </div>

            {{-- Market Status & Auto-Sync Widget --}}
            <div class="glass-card rounded-2xl p-5 border border-white/10 bg-slate-900/70 backdrop-blur-xl shadow-2xl flex flex-col sm:flex-row items-start sm:items-center gap-5">
                <div class="flex items-center gap-3">
                    <div class="w-3.5 h-3.5 rounded-full"
                        :class="marketStatus.is_open ? 'bg-emerald-400 shadow-lg shadow-emerald-500/50 animate-pulse' : 'bg-rose-500 shadow-lg shadow-rose-500/50'">
                    </div>
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wider"
                            :class="marketStatus.is_open ? 'text-emerald-400' : 'text-rose-400'"
                            x-text="marketStatus.status">
                        </div>
                        <div class="text-xs font-mono text-slate-300" x-text="liveIstTime"></div>
                    </div>
                </div>

                <div class="hidden sm:block h-8 w-[1px] bg-white/10"></div>

                <div class="flex items-center gap-3">
                    <div class="text-right">
                        <div class="text-[11px] uppercase tracking-wider text-slate-400">Auto Sync (1m)</div>
                        <div class="text-xs font-mono font-bold text-amber-400" x-text="'In ' + countdownText"></div>
                        <div class="text-[10px] font-mono text-slate-400" x-text="'Synced: ' + (lastSyncedAt || stats.last_sync_time || 'Just now')"></div>
                    </div>
                    <button @click="refreshAll()" :disabled="isLoading"
                        class="p-2.5 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/30 transition-all disabled:opacity-50"
                        title="Refresh &amp; Sync Live Quotes Now">
                        <i class="fas fa-sync-alt text-sm" :class="{ 'fa-spin': isLoading }"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- Top Summary Stats Bar --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 sm:gap-4 mt-6">
            <div class="glass-card rounded-xl p-4 border border-white/10 bg-slate-900/40">
                <div class="text-[11px] uppercase tracking-wider text-slate-400">Listed Stocks</div>
                <div class="mt-1 text-xl sm:text-2xl font-black text-white font-mono" x-text="formatNumberNoDec(instrumentCounts.stocks || stats.total_stocks)"></div>
                <div class="text-[10px] text-emerald-400 mt-1 flex items-center gap-1">
                    <i class="fas fa-check-circle text-[9px]"></i> True Equities (EQ/BE)
                </div>
            </div>

            <div class="glass-card rounded-xl p-4 border border-white/10 bg-slate-900/40">
                <div class="text-[11px] uppercase tracking-wider text-slate-400">Live Quotes Synced</div>
                <div class="mt-1 text-xl sm:text-2xl font-black text-emerald-400 font-mono" x-text="formatNumberNoDec(stats.live_quotes)"></div>
                <div class="text-[10px] text-slate-400 mt-1 truncate" x-text="'Updated: ' + (lastSyncedAt || stats.last_sync_time)"></div>
            </div>

            <div class="glass-card rounded-xl p-4 border border-white/10 bg-slate-900/40">
                <div class="text-[11px] uppercase tracking-wider text-slate-400">Mutual Fund Schemes</div>
                <div class="mt-1 text-xl sm:text-2xl font-black text-amber-400 font-mono" x-text="formatNumberNoDec(stats.total_mfs)"></div>
                <div class="text-[10px] text-slate-400 mt-1">Official AMFI Feed</div>
            </div>

            <div class="glass-card rounded-xl p-4 border border-white/10 bg-slate-900/40">
                <div class="text-[11px] uppercase tracking-wider text-slate-400">Corporate Actions</div>
                <div class="mt-1 text-xl sm:text-2xl font-black text-cyan-400 font-mono" x-text="formatNumberNoDec(stats.total_corporate_actions)"></div>
                <div class="text-[10px] text-slate-400 mt-1">Splits, Bonus &amp; Dividends</div>
            </div>

            <div class="col-span-2 md:col-span-1 glass-card rounded-xl p-4 border border-white/10 bg-slate-900/40">
                <div class="text-[11px] uppercase tracking-wider text-slate-400">Financial News</div>
                <div class="mt-1 text-xl sm:text-2xl font-black text-purple-400 font-mono" x-text="formatNumberNoDec(stats.total_news)"></div>
                <div class="text-[10px] text-slate-400 mt-1">Upstox News Stream</div>
            </div>
        </div>

        {{-- Main Navigation Tabs --}}
        <div class="mt-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-white/10 pb-4">
            <div class="flex items-center gap-2 overflow-x-auto custom-scrollbar pb-2 sm:pb-0">
                <button @click="switchTab('stocks')" class="px-5 py-2.5 rounded-xl text-sm font-semibold transition-all flex items-center gap-2 whitespace-nowrap"
                    :class="activeTab === 'stocks' ? 'bg-amber-500 text-black shadow-lg shadow-amber-500/20 font-bold' : 'bg-white/5 hover:bg-white/10 text-slate-300'">
                    <i class="fas fa-chart-line"></i>
                    <span>Stocks &amp; Equities</span>
                    <span class="text-xs px-2 py-0.5 rounded-md font-mono" :class="activeTab === 'stocks' ? 'bg-black/25 text-black' : 'bg-white/10 text-slate-300'">
                        {{ number_format($instrumentCounts['stocks']) }}
                    </span>
                </button>

                <button @click="switchTab('mf')" class="px-5 py-2.5 rounded-xl text-sm font-semibold transition-all flex items-center gap-2 whitespace-nowrap"
                    :class="activeTab === 'mf' ? 'bg-amber-500 text-black shadow-lg shadow-amber-500/20 font-bold' : 'bg-white/5 hover:bg-white/10 text-slate-300'">
                    <i class="fas fa-pie-chart"></i>
                    <span>Mutual Funds (AMFI)</span>
                    <span class="text-xs px-2 py-0.5 rounded-md font-mono" :class="activeTab === 'mf' ? 'bg-black/25 text-black' : 'bg-white/10 text-slate-300'">
                        {{ number_format($stats['total_mfs']) }}
                    </span>
                </button>

                <button @click="switchTab('news')" class="px-5 py-2.5 rounded-xl text-sm font-semibold transition-all flex items-center gap-2 whitespace-nowrap"
                    :class="activeTab === 'news' ? 'bg-amber-500 text-black shadow-lg shadow-amber-500/20 font-bold' : 'bg-white/5 hover:bg-white/10 text-slate-300'">
                    <i class="fas fa-newspaper"></i>
                    <span>Market News</span>
                    <span class="text-xs px-2 py-0.5 rounded-md font-mono" :class="activeTab === 'news' ? 'bg-black/25 text-black' : 'bg-white/10 text-slate-300'">
                        {{ number_format($stats['total_news']) }}
                    </span>
                </button>

                <button @click="switchTab('events')" class="px-5 py-2.5 rounded-xl text-sm font-semibold transition-all flex items-center gap-2 whitespace-nowrap"
                    :class="activeTab === 'events' ? 'bg-amber-500 text-black shadow-lg shadow-amber-500/20 font-bold' : 'bg-white/5 hover:bg-white/10 text-slate-300'">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Corporate Actions</span>
                    <span class="text-xs px-2 py-0.5 rounded-md font-mono" :class="activeTab === 'events' ? 'bg-black/25 text-black' : 'bg-white/10 text-slate-300'">
                        {{ number_format($stats['total_corporate_actions']) }}
                    </span>
                </button>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- TAB 1: STOCKS & EQUITIES                                                  --}}
        {{-- ========================================================================= --}}
        <div x-show="activeTab === 'stocks'" x-transition class="mt-6 space-y-5">
            {{-- Instrument Type Filter Pills (Equities vs Bonds vs All) --}}
            <div class="flex flex-wrap items-center justify-between gap-3 p-3.5 rounded-2xl bg-slate-900/60 border border-white/10">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-slate-400 mr-1 hidden sm:inline">Instrument:</span>
                    <button @click="setStockInstrumentType('stocks')"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all flex items-center gap-1.5"
                        :class="stockInstrumentType === 'stocks' ? 'bg-emerald-500 text-black font-bold shadow-md shadow-emerald-500/20' : 'bg-white/5 hover:bg-white/10 text-slate-300 border border-white/10'">
                        <i class="fas fa-shield-alt text-[10px]"></i>
                        <span>Equities / Stocks</span>
                        <span class="px-1.5 py-0.2 rounded text-[10px] font-mono" :class="stockInstrumentType === 'stocks' ? 'bg-black/25 text-black' : 'bg-white/10 text-slate-300'">
                            {{ number_format($instrumentCounts['stocks']) }}
                        </span>
                    </button>

                    <button @click="setStockInstrumentType('bonds')"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all flex items-center gap-1.5"
                        :class="stockInstrumentType === 'bonds' ? 'bg-amber-500 text-black font-bold shadow-md shadow-amber-500/20' : 'bg-white/5 hover:bg-white/10 text-slate-300 border border-white/10'">
                        <i class="fas fa-coins text-[10px]"></i>
                        <span>Bonds &amp; Debentures</span>
                        <span class="px-1.5 py-0.2 rounded text-[10px] font-mono" :class="stockInstrumentType === 'bonds' ? 'bg-black/25 text-black' : 'bg-white/10 text-slate-300'">
                            {{ number_format($instrumentCounts['bonds']) }}
                        </span>
                    </button>

                    <button @click="setStockInstrumentType('all')"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all flex items-center gap-1.5"
                        :class="stockInstrumentType === 'all' ? 'bg-white text-black font-bold shadow-md' : 'bg-white/5 hover:bg-white/10 text-slate-300 border border-white/10'">
                        <span>All ({{ number_format($instrumentCounts['all']) }})</span>
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-400 hidden md:inline">Exchange:</span>
                    <button @click="setStockFilter('all')" class="px-2.5 py-1 rounded-lg text-xs font-semibold border transition-all"
                        :class="stockFilter === 'all' ? 'bg-amber-500 text-black border-amber-500 font-bold' : 'bg-white/5 border-white/10 text-slate-300 hover:bg-white/10'">
                        All
                    </button>
                    <button @click="setStockFilter('live')" class="px-2.5 py-1 rounded-lg text-xs font-semibold border transition-all flex items-center gap-1"
                        :class="stockFilter === 'live' ? 'bg-emerald-500 text-black border-emerald-500 font-bold' : 'bg-white/5 border-white/10 text-slate-300 hover:bg-white/10'">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>Live Only</span>
                    </button>
                    <button @click="setStockFilter('nse')" class="px-2.5 py-1 rounded-lg text-xs font-semibold border transition-all"
                        :class="stockFilter === 'nse' ? 'bg-amber-500 text-black border-amber-500 font-bold' : 'bg-white/5 border-white/10 text-slate-300 hover:bg-white/10'">
                        NSE
                    </button>
                    <button @click="setStockFilter('bse')" class="px-2.5 py-1 rounded-lg text-xs font-semibold border transition-all"
                        :class="stockFilter === 'bse' ? 'bg-amber-500 text-black border-amber-500 font-bold' : 'bg-white/5 border-white/10 text-slate-300 hover:bg-white/10'">
                        BSE
                    </button>
                </div>
            </div>

            {{-- Search & Sort Control Bar --}}
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                {{-- Search Box --}}
                <div class="md:col-span-6 relative">
                    <i class="fas absolute left-3.5 top-3.5 text-amber-400 text-sm" :class="isStocksLoading ? 'fa-spinner fa-spin' : 'fa-search'"></i>
                    <input type="text" x-model="stockSearch" @input.debounce.300ms="stockSearchInput()" @keydown.enter.prevent="fetchStocks()"
                        placeholder="Search by company name, symbol (RELIANCE, TCS, INFY) or ISIN..."
                        class="w-full pl-10 pr-9 py-2.5 bg-slate-900 border border-white/20 rounded-xl text-sm text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 transition-colors shadow-inner">
                    <button x-show="stockSearch" @click="stockSearch = ''; stockSearchInput();" class="absolute right-3 top-3 text-slate-400 hover:text-white" title="Clear search">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>

                {{-- Sort Dropdown --}}
                <div class="md:col-span-4">
                    <select x-model="stockSort" @change="stockSortChange()"
                        class="market-dropdown w-full px-4 py-2.5 rounded-xl text-sm font-medium transition-colors cursor-pointer">
                        <option value="volume_desc">Most Active / Highest Volume</option>
                        <option value="name_asc">Company Name (A - Z)</option>
                        <option value="name_desc">Company Name (Z - A)</option>
                        <option value="price_desc">Price (High to Low)</option>
                        <option value="price_asc">Price (Low to High)</option>
                        <option value="gainers">Top Gainers (% Change)</option>
                        <option value="losers">Top Losers (% Change)</option>
                    </select>
                </div>

                {{-- Per Page Dropdown --}}
                <div class="md:col-span-2">
                    <select x-model="stockPerPage" @change="stockPerPageChange()"
                        class="market-dropdown w-full px-3 py-2.5 rounded-xl text-sm font-medium transition-colors cursor-pointer">
                        <option value="15">15 per page</option>
                        <option value="25">25 per page</option>
                        <option value="50">50 per page</option>
                        <option value="100">100 per page</option>
                    </select>
                </div>
            </div>

            {{-- Stocks Table --}}
            <div class="glass-card rounded-2xl border border-white/10 bg-slate-900/60 overflow-hidden shadow-2xl">
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left text-sm text-slate-300">
                        <thead class="bg-black/50 text-xs uppercase tracking-wider text-slate-400 border-b border-white/10 sticky top-0 backdrop-blur-md">
                            <tr>
                                <th class="py-3.5 px-4 font-semibold">Instrument &amp; Company</th>
                                <th class="py-3.5 px-4 font-semibold text-center">Exchange</th>
                                <th class="py-3.5 px-4 font-semibold text-right">LTP (₹)</th>
                                <th class="py-3.5 px-4 font-semibold text-right">Change</th>
                                <th class="py-3.5 px-4 font-semibold text-right">Day High / Low</th>
                                <th class="py-3.5 px-4 font-semibold text-right">Day Volume</th>
                                <th class="py-3.5 px-4 font-semibold text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 font-mono text-xs">
                            <template x-for="item in stocksList" :key="(item.isin || '') + '-' + (item.id || '') + '-' + (item.nse_symbol || item.bse_symbol || '')">
                                <tr class="hover:bg-white/5 transition-colors" :id="'row-' + item.isin">
                                    {{-- Instrument & Company --}}
                                    <td class="py-3 px-4 font-sans">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-white text-sm tracking-tight" x-text="item.nse_symbol || item.bse_symbol || item.isin"></span>
                                            {{-- Series Tag --}}
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase"
                                                :class="{
                                                    'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30': item.series === 'EQ',
                                                    'bg-amber-500/20 text-amber-300 border border-amber-500/30': item.series === 'BE' || item.series === 'SM',
                                                    'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30': item.series === 'GB',
                                                    'bg-purple-500/20 text-purple-300 border border-purple-500/30': item.series === 'F' || item.series === 'SG',
                                                    'bg-white/10 text-slate-300': !item.series || (item.series !== 'EQ' && item.series !== 'BE' && item.series !== 'GB')
                                                }"
                                                x-text="item.series === 'EQ' ? 'EQUITY' : (item.series === 'GB' ? 'GOLD BOND' : (item.series || 'EQ'))">
                                            </span>
                                            {{-- Live Badge --}}
                                            <template x-if="item.live_price">
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 flex items-center gap-1">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                                    <span>LIVE</span>
                                                </span>
                                            </template>
                                        </div>
                                        <div class="text-xs text-slate-300 truncate max-w-sm font-medium mt-0.5" x-text="item.company_name"></div>
                                        <div class="text-[10px] text-slate-500 font-mono mt-0.5" x-text="'ISIN: ' + item.isin"></div>
                                    </td>

                                    {{-- Exchange --}}
                                    <td class="py-3 px-4 font-sans text-center">
                                        <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-white/10 text-slate-200"
                                            x-text="item.nse_symbol ? 'NSE' : (item.bse_symbol ? 'BSE' : 'EQ')">
                                        </span>
                                    </td>

                                    {{-- LTP Price --}}
                                    <td class="py-3 px-4 text-right">
                                        <div class="text-base font-bold transition-all duration-500 rounded px-1.5 py-0.5 inline-block"
                                            :class="{
                                                'bg-emerald-500/25 text-emerald-300 font-extrabold ring-1 ring-emerald-500/50 shadow-lg shadow-emerald-500/20': priceFlash[item.isin] === 'up',
                                                'bg-rose-500/25 text-rose-300 font-extrabold ring-1 ring-rose-500/50 shadow-lg shadow-rose-500/20': priceFlash[item.isin] === 'down',
                                                'text-white': !priceFlash[item.isin]
                                            }"
                                            :id="'price-' + item.isin"
                                            x-text="formatNumber(item.live_price || item.prev_close)">
                                        </div>
                                    </td>

                                    {{-- Change --}}
                                    <td class="py-3 px-4 text-right">
                                        <template x-if="item.live_price && item.prev_close">
                                            <div>
                                                <div class="font-semibold text-xs"
                                                    :class="item.live_price >= item.prev_close ? 'text-emerald-400' : 'text-rose-400'"
                                                    x-text="formatSigned(item.live_price - item.prev_close)">
                                                </div>
                                                <div class="text-[11px] font-bold"
                                                    :class="item.live_price >= item.prev_close ? 'text-emerald-400' : 'text-rose-400'"
                                                    x-text="formatPercent(((item.live_price - item.prev_close) / item.prev_close) * 100)">
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="!item.live_price || !item.prev_close">
                                            <span class="text-slate-500">--</span>
                                        </template>
                                    </td>

                                    {{-- Day High / Low --}}
                                    <td class="py-3 px-4 text-right">
                                        <div class="text-slate-200">
                                            <span class="text-emerald-400 font-semibold" x-text="formatNumber(item.day_high)"></span>
                                            <span class="text-slate-500 mx-1">/</span>
                                            <span class="text-rose-400 font-semibold" x-text="formatNumber(item.day_low)"></span>
                                        </div>
                                    </td>

                                    {{-- Volume --}}
                                    <td class="py-3 px-4 text-right text-slate-300 font-medium" x-text="formatVolume(item.day_volume)"></td>

                                    {{-- Actions --}}
                                    <td class="py-3 px-4 text-center font-sans">
                                        <button @click="showApiPayload(item)" class="px-2.5 py-1 rounded bg-white/10 hover:bg-white/20 text-slate-200 text-xs font-semibold transition-colors" title="View Upstox API JSON">
                                            <i class="fas fa-code mr-1"></i> JSON
                                        </button>
                                    </td>
                                </tr>
                            </template>

                            {{-- Empty State --}}
                            <tr x-show="stocksList.length === 0 && !isStocksLoading">
                                <td colspan="7" class="py-14 text-center text-slate-400 font-sans">
                                    <i class="fas fa-search text-3xl mb-3 text-slate-600 block"></i>
                                    <p class="text-base font-semibold text-white">No stocks found matching your criteria</p>
                                    <p class="text-xs text-slate-400 mt-1">Try another keyword, change the instrument tab, or clear the search filters.</p>
                                    <button @click="resetStockFilters()" class="mt-4 px-4 py-2 rounded-xl text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 hover:bg-amber-500/30 transition-colors">
                                        Reset All Filters
                                    </button>
                                </td>
                            </tr>

                            {{-- Loading Row --}}
                            <tr x-show="isStocksLoading">
                                <td colspan="7" class="py-12 text-center text-slate-400 font-sans">
                                    <i class="fas fa-spinner fa-spin text-3xl mb-2 text-amber-400 block"></i>
                                    <p class="text-sm font-medium text-slate-300">Loading stocks feed...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Full Pagination Bar for Stocks --}}
                <div x-show="stocksPagination && stocksPagination.total > 0"
                    class="p-4 border-t border-white/10 bg-black/40 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs font-sans">
                    <div class="text-slate-400">
                        Showing <strong class="text-white" x-text="stocksPagination.from || 1"></strong> to
                        <strong class="text-white" x-text="stocksPagination.to || stocksList.length"></strong> of
                        <strong class="text-white font-mono" x-text="formatNumberNoDec(stocksPagination.total)"></strong> stocks
                    </div>

                    <div class="flex items-center gap-1">
                        {{-- First Page --}}
                        <button @click="goToStocksPage(1)" :disabled="stocksPagination.current_page === 1"
                            class="px-2.5 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-slate-300 disabled:opacity-30 disabled:pointer-events-none transition-colors" title="First Page">
                            <i class="fas fa-angle-double-left"></i>
                        </button>

                        {{-- Previous Page --}}
                        <button @click="goToStocksPage(stocksPagination.current_page - 1)" :disabled="stocksPagination.current_page === 1"
                            class="px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-slate-300 disabled:opacity-30 disabled:pointer-events-none transition-colors">
                            <i class="fas fa-chevron-left mr-1"></i> Prev
                        </button>

                        {{-- Current Page Pill --}}
                        <span class="px-3 py-1.5 rounded-lg bg-amber-500 text-black font-bold font-mono">
                            Page <span x-text="stocksPagination.current_page"></span> / <span x-text="stocksPagination.last_page"></span>
                        </span>

                        {{-- Next Page --}}
                        <button @click="goToStocksPage(stocksPagination.current_page + 1)" :disabled="stocksPagination.current_page >= stocksPagination.last_page"
                            class="px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-slate-300 disabled:opacity-30 disabled:pointer-events-none transition-colors">
                            Next <i class="fas fa-chevron-right ml-1"></i>
                        </button>

                        {{-- Last Page --}}
                        <button @click="goToStocksPage(stocksPagination.last_page)" :disabled="stocksPagination.current_page >= stocksPagination.last_page"
                            class="px-2.5 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-slate-300 disabled:opacity-30 disabled:pointer-events-none transition-colors" title="Last Page">
                            <i class="fas fa-angle-double-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- TAB 2: MUTUAL FUNDS (AMFI)                                                --}}
        {{-- ========================================================================= --}}
        <div x-show="activeTab === 'mf'" x-transition class="mt-6 space-y-5">
            {{-- Category Quick Pills --}}
            <div class="flex items-center gap-2 overflow-x-auto custom-scrollbar p-2 rounded-2xl bg-slate-900/60 border border-white/10">
                <button @click="setMfCategory('all')" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-all"
                    :class="mfCategory === 'all' ? 'bg-amber-500 text-black font-bold shadow-md' : 'bg-white/5 text-slate-300 hover:bg-white/10'">
                    All Categories
                </button>
                <button @click="setMfCategory('Equity')" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-all flex items-center gap-1.5"
                    :class="mfCategory === 'Equity' ? 'bg-emerald-500 text-black font-bold shadow-md' : 'bg-white/5 text-slate-300 hover:bg-white/10'">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    <span>Equity Funds</span>
                </button>
                <button @click="setMfCategory('Index')" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-all flex items-center gap-1.5"
                    :class="mfCategory === 'Index' ? 'bg-cyan-500 text-black font-bold shadow-md' : 'bg-white/5 text-slate-300 hover:bg-white/10'">
                    <span class="w-1.5 h-1.5 rounded-full bg-cyan-400"></span>
                    <span>Index Funds</span>
                </button>
                <button @click="setMfCategory('ETF')" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-all flex items-center gap-1.5"
                    :class="mfCategory === 'ETF' ? 'bg-amber-400 text-black font-bold shadow-md' : 'bg-white/5 text-slate-300 hover:bg-white/10'">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                    <span>ETFs</span>
                </button>
                <button @click="setMfCategory('Hybrid')" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-all flex items-center gap-1.5"
                    :class="mfCategory === 'Hybrid' ? 'bg-purple-500 text-black font-bold shadow-md' : 'bg-white/5 text-slate-300 hover:bg-white/10'">
                    <span class="w-1.5 h-1.5 rounded-full bg-purple-400"></span>
                    <span>Hybrid Funds</span>
                </button>
                <button @click="setMfCategory('Debt')" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-all flex items-center gap-1.5"
                    :class="mfCategory === 'Debt' ? 'bg-sky-500 text-black font-bold shadow-md' : 'bg-white/5 text-slate-300 hover:bg-white/10'">
                    <span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span>
                    <span>Debt Funds</span>
                </button>
                <button @click="setMfCategory('Other')" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-all"
                    :class="mfCategory === 'Other' ? 'bg-slate-400 text-black font-bold shadow-md' : 'bg-white/5 text-slate-300 hover:bg-white/10'">
                    Other Funds
                </button>
            </div>

            {{-- Search & Clearly Visible Dropdowns --}}
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                {{-- Prominent Search Box --}}
                <div class="md:col-span-5 relative">
                    <i class="fas absolute left-3.5 top-3.5 text-amber-400 text-sm" :class="isMfLoading ? 'fa-spinner fa-spin' : 'fa-search'"></i>
                    <input type="text" x-model="mfSearch" @input.debounce.300ms="mfSearchInput()" @keydown.enter.prevent="fetchMf()"
                        placeholder="Search mutual funds by scheme name, keyword or ISIN..."
                        class="w-full pl-10 pr-9 py-2.5 bg-slate-900 border border-white/20 rounded-xl text-sm text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 transition-colors shadow-inner">
                    <button x-show="mfSearch" @click="mfSearch = ''; mfSearchInput();" class="absolute right-3 top-3 text-slate-400 hover:text-white" title="Clear search">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>

                {{-- AMC Dropdown with High-Visibility Theme --}}
                <div class="md:col-span-4">
                    <select x-model="mfAmc" @change="mfFilterChange()"
                        class="market-dropdown w-full px-4 py-2.5 rounded-xl text-sm font-medium transition-colors cursor-pointer">
                        <option value="all" style="background-color: #0b1120; color: #ffffff;">All Fund Houses (AMCs)</option>
                        @foreach ($amcs as $amc)
                            <option value="{{ $amc }}" style="background-color: #0b1120; color: #ffffff;">{{ Str::limit($amc, 34) }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Sort Dropdown --}}
                <div class="md:col-span-2">
                    <select x-model="mfSort" @change="mfFilterChange()"
                        class="market-dropdown w-full px-3 py-2.5 rounded-xl text-sm font-medium transition-colors cursor-pointer">
                        <option value="popular">Prominent / Top AMCs</option>
                        <option value="name_asc">Scheme Name (A - Z)</option>
                        <option value="name_desc">Scheme Name (Z - A)</option>
                        <option value="nav_desc">Highest NAV</option>
                        <option value="nav_asc">Lowest NAV</option>
                        <option value="chg_1y_desc">1-Year Return (% Desc)</option>
                        <option value="chg_3y_desc">3-Year Return (% Desc)</option>
                    </select>
                </div>

                {{-- View Mode Toggle (Grid vs List) --}}
                <div class="md:col-span-1 flex items-center justify-end gap-1">
                    <button @click="mfViewMode = 'grid'" class="p-2.5 rounded-xl border transition-colors"
                        :class="mfViewMode === 'grid' ? 'bg-amber-500 text-black border-amber-500' : 'bg-white/5 border-white/10 text-slate-400 hover:bg-white/10'" title="Grid Cards View">
                        <i class="fas fa-th-large text-sm"></i>
                    </button>
                    <button @click="mfViewMode = 'table'" class="p-2.5 rounded-xl border transition-colors"
                        :class="mfViewMode === 'table' ? 'bg-amber-500 text-black border-amber-500' : 'bg-white/5 border-white/10 text-slate-400 hover:bg-white/10'" title="Table List View">
                        <i class="fas fa-list text-sm"></i>
                    </button>
                </div>
            </div>

            {{-- 1. Mutual Funds Grid View --}}
            <div x-show="mfViewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <template x-for="fund in mfList" :key="(fund.isin || '') + '-' + (fund.scheme_code || '')">
                    <div class="glass-card rounded-2xl p-5 border border-white/10 bg-slate-900/50 hover:border-amber-500/40 transition-all flex flex-col justify-between shadow-xl">
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                    :class="{
                                        'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30': fund.category === 'Equity',
                                        'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30': fund.category === 'Index',
                                        'bg-amber-500/20 text-amber-300 border border-amber-500/30': fund.category === 'ETF',
                                        'bg-purple-500/20 text-purple-300 border border-purple-500/30': fund.category === 'Hybrid',
                                        'bg-sky-500/20 text-sky-300 border border-sky-500/30': fund.category === 'Debt',
                                        'bg-white/10 text-slate-300': !fund.category || fund.category === 'Other'
                                    }"
                                    x-text="fund.category || 'Mutual Fund'">
                                </span>
                                <span class="text-[11px] text-slate-400 font-mono" x-text="fund.nav_date ? formatDateOnly(fund.nav_date) : 'Latest'"></span>
                            </div>
                            <h3 class="mt-2 text-base font-bold text-white line-clamp-2" x-text="fund.scheme_name"></h3>
                            <div class="mt-1 text-xs text-amber-400/90 font-medium line-clamp-1" x-text="fund.amc_name"></div>
                            <div class="text-[10px] text-slate-500 font-mono mt-0.5" x-text="'Scheme Code: ' + fund.scheme_code + (fund.isin ? ' | ' + fund.isin : '')"></div>
                        </div>

                        <div class="mt-5 pt-4 border-t border-white/10">
                            <div class="flex items-baseline justify-between">
                                <div class="text-xs uppercase tracking-wider text-slate-400">Net Asset Value (NAV)</div>
                                <div class="text-xl font-extrabold text-white font-mono">
                                    ₹<span x-text="formatNumber(fund.nav)"></span>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-2 mt-4 text-center font-mono">
                                <div class="rounded-xl bg-white/5 p-2">
                                    <div class="text-[10px] uppercase text-slate-400 font-sans">1D</div>
                                    <div class="text-xs font-bold mt-0.5"
                                        :class="fund.chg_1d >= 0 ? 'text-emerald-400' : 'text-rose-400'"
                                        x-text="formatPercent(fund.chg_1d)">
                                    </div>
                                </div>
                                <div class="rounded-xl bg-white/5 p-2">
                                    <div class="text-[10px] uppercase text-slate-400 font-sans">1Y</div>
                                    <div class="text-xs font-bold mt-0.5"
                                        :class="fund.chg_1y >= 0 ? 'text-emerald-400' : 'text-rose-400'"
                                        x-text="formatPercent(fund.chg_1y)">
                                    </div>
                                </div>
                                <div class="rounded-xl bg-white/5 p-2">
                                    <div class="text-[10px] uppercase text-slate-400 font-sans">3Y</div>
                                    <div class="text-xs font-bold mt-0.5"
                                        :class="fund.chg_3y >= 0 ? 'text-emerald-400' : 'text-rose-400'"
                                        x-text="formatPercent(fund.chg_3y)">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- 2. Mutual Funds Table View --}}
            <div x-show="mfViewMode === 'table'" class="glass-card rounded-2xl border border-white/10 bg-slate-900/60 overflow-hidden shadow-2xl">
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left text-sm text-slate-300">
                        <thead class="bg-black/50 text-xs uppercase tracking-wider text-slate-400 border-b border-white/10 sticky top-0 backdrop-blur-md">
                            <tr>
                                <th class="py-3.5 px-4 font-semibold">Scheme &amp; AMC</th>
                                <th class="py-3.5 px-4 font-semibold">Category</th>
                                <th class="py-3.5 px-4 font-semibold text-right">NAV (₹)</th>
                                <th class="py-3.5 px-4 font-semibold text-right">1D %</th>
                                <th class="py-3.5 px-4 font-semibold text-right">1Y %</th>
                                <th class="py-3.5 px-4 font-semibold text-right">3Y %</th>
                                <th class="py-3.5 px-4 font-semibold text-right">NAV Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 font-mono text-xs">
                            <template x-for="fund in mfList" :key="(fund.isin || '') + '-' + (fund.scheme_code || '')">
                                <tr class="hover:bg-white/5 transition-colors">
                                    <td class="py-3.5 px-4 font-sans">
                                        <div class="font-bold text-white text-sm" x-text="fund.scheme_name"></div>
                                        <div class="text-xs text-amber-400/90 font-medium" x-text="fund.amc_name"></div>
                                        <div class="text-[10px] text-slate-500 font-mono" x-text="'Code: ' + fund.scheme_code + (fund.isin ? ' | ISIN: ' + fund.isin : '')"></div>
                                    </td>
                                    <td class="py-3.5 px-4 font-sans">
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                            :class="{
                                                'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30': fund.category === 'Equity',
                                                'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30': fund.category === 'Index',
                                                'bg-amber-500/20 text-amber-300 border border-amber-500/30': fund.category === 'ETF',
                                                'bg-purple-500/20 text-purple-300 border border-purple-500/30': fund.category === 'Hybrid',
                                                'bg-sky-500/20 text-sky-300 border border-sky-500/30': fund.category === 'Debt',
                                                'bg-white/10 text-slate-300': !fund.category || fund.category === 'Other'
                                            }"
                                            x-text="fund.category || 'Other'">
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-bold text-white text-base">
                                        ₹<span x-text="formatNumber(fund.nav)"></span>
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-bold"
                                        :class="fund.chg_1d >= 0 ? 'text-emerald-400' : 'text-rose-400'"
                                        x-text="formatPercent(fund.chg_1d)">
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-bold"
                                        :class="fund.chg_1y >= 0 ? 'text-emerald-400' : 'text-rose-400'"
                                        x-text="formatPercent(fund.chg_1y)">
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-bold"
                                        :class="fund.chg_3y >= 0 ? 'text-emerald-400' : 'text-rose-400'"
                                        x-text="formatPercent(fund.chg_3y)">
                                    </td>
                                    <td class="py-3.5 px-4 text-right text-slate-400 font-mono" x-text="fund.nav_date ? formatDateOnly(fund.nav_date) : '--'"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Empty State for MF --}}
            <div x-show="mfList.length === 0 && !isMfLoading" class="glass-card rounded-2xl p-12 text-center border border-white/10 bg-slate-900/50 shadow-2xl">
                <i class="fas fa-pie-chart text-3xl mb-3 text-slate-600 block"></i>
                <p class="text-base font-semibold text-white">No mutual funds match your search or filter</p>
                <p class="text-xs text-slate-400 mt-1">Check your keyword, or change the selected AMC or Category.</p>
                <button @click="resetMfFilters()" class="mt-4 px-4 py-2 rounded-xl text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 hover:bg-amber-500/30 transition-colors">
                    Reset MF Filters
                </button>
            </div>

            {{-- Loading State for MF --}}
            <div x-show="isMfLoading" class="glass-card rounded-2xl p-12 text-center border border-white/10 bg-slate-900/50 shadow-2xl">
                <i class="fas fa-spinner fa-spin text-3xl mb-2 text-amber-400 block"></i>
                <p class="text-sm font-medium text-slate-300">Loading mutual funds data...</p>
            </div>

            {{-- Full Pagination Bar for Mutual Funds --}}
            <div x-show="mfPagination && mfPagination.total > 0"
                class="glass-card rounded-2xl p-4 border border-white/10 bg-slate-900/60 shadow-xl flex flex-col sm:flex-row items-center justify-between gap-4 text-xs font-sans">
                <div class="text-slate-400">
                    Showing <strong class="text-white" x-text="mfPagination.from || 1"></strong> to
                    <strong class="text-white" x-text="mfPagination.to || mfList.length"></strong> of
                    <strong class="text-white font-mono" x-text="formatNumberNoDec(mfPagination.total)"></strong> schemes
                </div>

                <div class="flex items-center gap-1">
                    {{-- First Page --}}
                    <button @click="goToMfPage(1)" :disabled="mfPagination.current_page === 1"
                        class="px-2.5 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-slate-300 disabled:opacity-30 disabled:pointer-events-none transition-colors" title="First Page">
                        <i class="fas fa-angle-double-left"></i>
                    </button>

                    {{-- Previous Page --}}
                    <button @click="goToMfPage(mfPagination.current_page - 1)" :disabled="mfPagination.current_page === 1"
                        class="px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-slate-300 disabled:opacity-30 disabled:pointer-events-none transition-colors">
                        <i class="fas fa-chevron-left mr-1"></i> Prev
                    </button>

                    {{-- Current Page Pill --}}
                    <span class="px-3 py-1.5 rounded-lg bg-amber-500 text-black font-bold font-mono">
                        Page <span x-text="mfPagination.current_page"></span> / <span x-text="mfPagination.last_page"></span>
                    </span>

                    {{-- Next Page --}}
                    <button @click="goToMfPage(mfPagination.current_page + 1)" :disabled="mfPagination.current_page >= mfPagination.last_page"
                        class="px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-slate-300 disabled:opacity-30 disabled:pointer-events-none transition-colors">
                        Next <i class="fas fa-chevron-right ml-1"></i>
                    </button>

                    {{-- Last Page --}}
                    <button @click="goToMfPage(mfPagination.last_page)" :disabled="mfPagination.current_page >= mfPagination.last_page"
                        class="px-2.5 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-slate-300 disabled:opacity-30 disabled:pointer-events-none transition-colors" title="Last Page">
                        <i class="fas fa-angle-double-right"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- TAB 3: UPSTOX FINANCIAL NEWS                                              --}}
        {{-- ========================================================================= --}}
        <div x-show="activeTab === 'news'" x-transition class="mt-6 space-y-6">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="relative w-full sm:w-96">
                    <i class="fas absolute left-3.5 top-3.5 text-amber-400 text-sm" :class="isNewsLoading ? 'fa-spinner fa-spin' : 'fa-search'"></i>
                    <input type="text" x-model="newsSearch" @input.debounce.300ms="newsSearchInput()" @keydown.enter.prevent="fetchNews()"
                        placeholder="Search financial news articles by keyword..."
                        class="w-full pl-10 pr-9 py-2.5 bg-slate-900 border border-white/20 rounded-xl text-sm text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 transition-colors shadow-inner">
                    <button x-show="newsSearch" @click="newsSearch = ''; newsSearchInput();" class="absolute right-3 top-3 text-slate-400 hover:text-white" title="Clear search">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
                <div class="text-xs text-slate-400 flex items-center gap-2">
                    <i class="fas fa-rss text-amber-400"></i>
                    <span>Synchronized every minute from Upstox News API</span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <template x-for="article in newsList" :key="article.id || article.title">
                    <div class="glass-card rounded-2xl p-6 border border-white/10 bg-slate-900/50 hover:border-amber-500/30 transition-all flex flex-col justify-between shadow-xl">
                        <div>
                            <div class="flex items-center justify-between gap-2 text-xs text-slate-400 mb-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-white/10 text-amber-300" x-text="article.symbol || 'MARKET'"></span>
                                <span x-text="formatDate(article.published_at)"></span>
                            </div>
                            <h3 class="text-lg font-bold text-white hover:text-amber-400 transition-colors">
                                <a :href="article.article_url" target="_blank" rel="noopener noreferrer" x-text="article.title"></a>
                            </h3>
                            <p class="mt-2.5 text-sm text-slate-300 line-clamp-3 leading-relaxed" x-text="article.summary"></p>
                        </div>

                        <div class="mt-5 pt-4 border-t border-white/10 flex items-center justify-between">
                            <span class="text-xs text-slate-400 flex items-center gap-1.5">
                                <i class="fas fa-building text-[10px] text-slate-500"></i>
                                <span x-text="article.source || 'Upstox'"></span>
                            </span>
                            <a :href="article.article_url" target="_blank" rel="noopener noreferrer" class="text-xs font-semibold text-amber-400 hover:text-amber-300 inline-flex items-center gap-1">
                                <span>Read Full Story</span>
                                <i class="fas fa-external-link-alt text-[10px]"></i>
                            </a>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Empty State for News --}}
            <div x-show="newsList.length === 0 && !isNewsLoading" class="glass-card rounded-2xl p-12 text-center border border-white/10 bg-slate-900/50 shadow-2xl">
                <i class="fas fa-newspaper text-3xl mb-3 text-slate-600 block"></i>
                <p class="text-base font-semibold text-white">No news articles found</p>
                <p class="text-xs text-slate-400 mt-1">Try another search keyword or clear the search input.</p>
                <button @click="newsSearch = ''; newsSearchInput();" class="mt-4 px-4 py-2 rounded-xl text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 hover:bg-amber-500/30 transition-colors">
                    Reset News Search
                </button>
            </div>

            {{-- Loading State for News --}}
            <div x-show="isNewsLoading" class="glass-card rounded-2xl p-12 text-center border border-white/10 bg-slate-900/50 shadow-2xl">
                <i class="fas fa-spinner fa-spin text-3xl mb-2 text-amber-400 block"></i>
                <p class="text-sm font-medium text-slate-300">Searching financial news...</p>
            </div>

            {{-- News Pagination Bar --}}
            <div x-show="newsPagination && newsPagination.total > 0"
                class="glass-card rounded-2xl p-4 border border-white/10 bg-slate-900/60 shadow-xl flex flex-col sm:flex-row items-center justify-between gap-4 text-xs font-sans">
                <div class="text-slate-400">
                    Showing <strong class="text-white" x-text="newsPagination.from || 1"></strong> to
                    <strong class="text-white" x-text="newsPagination.to || newsList.length"></strong> of
                    <strong class="text-white font-mono" x-text="formatNumberNoDec(newsPagination.total)"></strong> articles
                </div>

                <div class="flex items-center gap-1">
                    <button @click="goToNewsPage(newsPagination.current_page - 1)" :disabled="newsPagination.current_page === 1"
                        class="px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-slate-300 disabled:opacity-30 disabled:pointer-events-none transition-colors">
                        <i class="fas fa-chevron-left mr-1"></i> Prev
                    </button>
                    <span class="px-3 py-1.5 rounded-lg bg-amber-500 text-black font-bold font-mono">
                        Page <span x-text="newsPagination.current_page"></span> / <span x-text="newsPagination.last_page"></span>
                    </span>
                    <button @click="goToNewsPage(newsPagination.current_page + 1)" :disabled="newsPagination.current_page >= newsPagination.last_page"
                        class="px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-slate-300 disabled:opacity-30 disabled:pointer-events-none transition-colors">
                        Next <i class="fas fa-chevron-right ml-1"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- TAB 4: CORPORATE ACTIONS (SPLITS, BONUS, DIVIDENDS, EVENTS)              --}}
        {{-- ========================================================================= --}}
        <div x-show="activeTab === 'events'" x-transition class="mt-6 space-y-6">
            {{-- Category Filter Bar & Search --}}
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-2 overflow-x-auto custom-scrollbar pb-1">
                    <button @click="setEventType('ALL')" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold border transition-all"
                        :class="eventType === 'ALL' ? 'bg-amber-500 text-black border-amber-500 font-bold' : 'bg-white/5 border-white/10 text-slate-300 hover:bg-white/10'">
                        All Actions
                    </button>
                    <button @click="setEventType('SPLIT')" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold border transition-all"
                        :class="eventType === 'SPLIT' ? 'bg-cyan-500 text-black border-cyan-500 font-bold' : 'bg-white/5 border-white/10 text-slate-300 hover:bg-white/10'">
                        Stock Splits
                    </button>
                    <button @click="setEventType('BONUS')" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold border transition-all"
                        :class="eventType === 'BONUS' ? 'bg-purple-500 text-black border-purple-500 font-bold' : 'bg-white/5 border-white/10 text-slate-300 hover:bg-white/10'">
                        Bonus Issues
                    </button>
                    <button @click="setEventType('DIVIDEND')" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold border transition-all"
                        :class="eventType === 'DIVIDEND' ? 'bg-emerald-500 text-black border-emerald-500 font-bold' : 'bg-white/5 border-white/10 text-slate-300 hover:bg-white/10'">
                        Dividends
                    </button>
                    <button @click="setEventType('EVENT')" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold border transition-all"
                        :class="eventType === 'EVENT' ? 'bg-amber-500 text-black border-amber-500 font-bold' : 'bg-white/5 border-white/10 text-slate-300 hover:bg-white/10'">
                        Corporate Events
                    </button>
                </div>

                <div class="relative w-full sm:w-80">
                    <i class="fas absolute left-3.5 top-3 text-amber-400 text-sm" :class="isEventsLoading ? 'fa-spinner fa-spin' : 'fa-search'"></i>
                    <input type="text" x-model="eventSearch" @input.debounce.300ms="eventSearchInput()" @keydown.enter.prevent="fetchEvents()"
                        placeholder="Search company, action, details..."
                        class="w-full pl-10 pr-9 py-2 bg-slate-900 border border-white/20 rounded-xl text-sm text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 transition-colors shadow-inner">
                    <button x-show="eventSearch" @click="eventSearch = ''; eventSearchInput();" class="absolute right-3 top-2.5 text-slate-400 hover:text-white" title="Clear search">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
            </div>

            <div class="glass-card rounded-2xl border border-white/10 bg-slate-900/60 overflow-hidden shadow-2xl">
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left text-sm text-slate-300">
                        <thead class="bg-black/50 text-xs uppercase tracking-wider text-slate-400 border-b border-white/10 sticky top-0 backdrop-blur-md">
                            <tr>
                                <th class="py-3.5 px-4 font-semibold">Company &amp; Symbol</th>
                                <th class="py-3.5 px-4 font-semibold">Action Type</th>
                                <th class="py-3.5 px-4 font-semibold">Action Name</th>
                                <th class="py-3.5 px-4 font-semibold">Ex-Date (Expiry)</th>
                                <th class="py-3.5 px-4 font-semibold">Record Date</th>
                                <th class="py-3.5 px-4 font-semibold text-right">Ratio / Amount</th>
                                <th class="py-3.5 px-4 font-semibold">Details</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 text-xs">
                            <template x-for="event in eventsList" :key="event.id || (event.symbol + '-' + event.type + '-' + event.expiry_date)">
                                <tr class="hover:bg-white/5 transition-colors">
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-white font-sans text-sm" x-text="event.symbol || event.isin"></div>
                                        <div class="text-[11px] text-slate-400" x-text="event.company_name || '--'"></div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider"
                                            :class="{
                                                'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30': event.type === 'SPLIT',
                                                'bg-purple-500/20 text-purple-300 border border-purple-500/30': event.type === 'BONUS',
                                                'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30': event.type === 'DIVIDEND',
                                                'bg-amber-500/20 text-amber-300 border border-amber-500/30': event.type === 'EVENT' || event.type === 'RIGHTS'
                                            }"
                                            x-text="event.type">
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 font-semibold text-slate-200" x-text="event.name"></td>
                                    <td class="py-3.5 px-4 font-mono text-slate-300" x-text="formatDateOnly(event.expiry_date)"></td>
                                    <td class="py-3.5 px-4 font-mono text-slate-400" x-text="formatDateOnly(event.record_date) || '--'"></td>
                                    <td class="py-3.5 px-4 text-right font-mono font-bold text-amber-300"
                                        x-text="event.ratio ? 'Ratio ' + event.ratio : (event.amount ? '₹' + formatNumber(event.amount) : '--')">
                                    </td>
                                    <td class="py-3.5 px-4 text-slate-400 max-w-xs truncate" x-text="event.details || '--'"></td>
                                </tr>
                            </template>

                            {{-- Empty State --}}
                            <tr x-show="eventsList.length === 0 && !isEventsLoading">
                                <td colspan="7" class="py-14 text-center text-slate-400 font-sans">
                                    <i class="fas fa-calendar-times text-3xl mb-3 text-slate-600 block"></i>
                                    <p class="text-base font-semibold text-white">No corporate actions found</p>
                                    <p class="text-xs text-slate-400 mt-1">Try another action type or search term.</p>
                                    <button @click="setEventType('ALL'); eventSearch = ''; fetchEvents();" class="mt-4 px-4 py-2 rounded-xl text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 hover:bg-amber-500/30 transition-colors">
                                        Show All Actions
                                    </button>
                                </td>
                            </tr>

                            {{-- Loading Row --}}
                            <tr x-show="isEventsLoading">
                                <td colspan="7" class="py-12 text-center text-slate-400 font-sans">
                                    <i class="fas fa-spinner fa-spin text-3xl mb-2 text-amber-400 block"></i>
                                    <p class="text-sm font-medium text-slate-300">Searching corporate actions...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Corporate Actions Pagination Bar --}}
                <div x-show="eventsPagination && eventsPagination.total > 0"
                    class="p-4 border-t border-white/10 bg-black/40 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs font-sans">
                    <div class="text-slate-400">
                        Showing <strong class="text-white" x-text="eventsPagination.from || 1"></strong> to
                        <strong class="text-white" x-text="eventsPagination.to || eventsList.length"></strong> of
                        <strong class="text-white font-mono" x-text="formatNumberNoDec(eventsPagination.total)"></strong> actions
                    </div>

                    <div class="flex items-center gap-1">
                        <button @click="goToEventsPage(eventsPagination.current_page - 1)" :disabled="eventsPagination.current_page === 1"
                            class="px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-slate-300 disabled:opacity-30 disabled:pointer-events-none transition-colors">
                            <i class="fas fa-chevron-left mr-1"></i> Prev
                        </button>
                        <span class="px-3 py-1.5 rounded-lg bg-amber-500 text-black font-bold font-mono">
                            Page <span x-text="eventsPagination.current_page"></span> / <span x-text="eventsPagination.last_page"></span>
                        </span>
                        <button @click="goToEventsPage(eventsPagination.current_page + 1)" :disabled="eventsPagination.current_page >= eventsPagination.last_page"
                            class="px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-slate-300 disabled:opacity-30 disabled:pointer-events-none transition-colors">
                            Next <i class="fas fa-chevron-right ml-1"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- API JSON Preview Modal --}}
        <div x-show="payloadModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" x-cloak>
            <div class="glass-card w-full max-w-2xl rounded-3xl border border-white/20 bg-slate-950 p-6 shadow-2xl relative max-h-[85vh] flex flex-col"
                @click.away="payloadModalOpen = false">
                <div class="flex items-center justify-between pb-4 border-b border-white/10">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-terminal text-amber-400"></i>
                        <h3 class="text-base font-bold text-white">Upstox API Payload Preview</h3>
                    </div>
                    <button @click="payloadModalOpen = false" class="text-slate-400 hover:text-white">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
                <div class="mt-4 flex-1 overflow-y-auto">
                    <pre class="bg-black/60 p-4 rounded-xl font-mono text-xs text-emerald-400 overflow-x-auto leading-relaxed" x-text="currentPayloadJson"></pre>
                </div>
                <div class="mt-4 pt-3 border-t border-white/10 flex justify-end">
                    <button @click="payloadModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-bold bg-white/10 hover:bg-white/20 text-white">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function marketLiveApp() {
    return {
        activeTab: 'stocks',
        isLoading: false,
        isStocksLoading: false,
        isMfLoading: false,
        isNewsLoading: false,
        isEventsLoading: false,

        countdownTimer: null,
        countdownSeconds: 60,
        countdownText: '60s',

        marketStatus: @json($marketStatus),
        stats: @json($stats),
        instrumentCounts: @json($instrumentCounts ?? ['stocks' => 3401, 'bonds' => 14908, 'all' => 18344]),
        liveIstTime: '{{ $marketStatus['ist_time'] }}',
        lastSyncedAt: '{{ $stats['last_sync_time'] ?? '' }}',
        priceFlash: {},
        previousPrices: {},

        // Lists
        stocksList: @json($stocks),
        mfList: @json($mutualFunds),
        newsList: @json($news),
        eventsList: @json($corporateActions),

        // Pagination states
        stocksPagination: @json($stocksPagination ?? null),
        mfPagination: @json($mfPagination ?? null),
        newsPagination: @json($newsPagination ?? null),
        eventsPagination: @json($eventsPagination ?? null),

        // Stocks filters
        stockInstrumentType: 'stocks',
        stockSearch: '',
        stockFilter: 'all',
        stockSort: 'volume_desc',
        stockPerPage: 25,
        stockCurrentPage: 1,

        // Mutual Funds filters
        mfSearch: '',
        mfCategory: 'all',
        mfAmc: 'all',
        mfSort: 'popular',
        mfPerPage: 24,
        mfCurrentPage: 1,
        mfViewMode: 'grid', // 'grid' or 'table'

        // News & Events filters
        newsSearch: '',
        newsCurrentPage: 1,
        eventSearch: '',
        eventType: 'ALL',
        eventsCurrentPage: 1,

        payloadModalOpen: false,
        currentPayloadJson: '',

        initApp() {
            // Live clock in IST updating every second
            setInterval(() => {
                const now = new Date();
                const istString = now.toLocaleString("en-US", { timeZone: "Asia/Kolkata", dateStyle: "medium", timeStyle: "medium" });
                this.liveIstTime = istString + ' IST';
            }, 1000);

            // Populate initial price cache for flashing
            if (Array.isArray(this.stocksList)) {
                this.stocksList.forEach(item => {
                    if (item.isin) {
                        this.previousPrices[item.isin] = Number(item.live_price || item.prev_close || 0);
                    }
                });
            }

            // Auto-sync data automatically every 1 minute (60s)
            this.startOneMinuteAutoSync();
        },

        switchTab(tab) {
            this.activeTab = tab;
        },

        startOneMinuteAutoSync() {
            this.countdownSeconds = 60;
            this.countdownText = '60s';

            if (this.countdownTimer) clearInterval(this.countdownTimer);
            this.countdownTimer = setInterval(() => {
                this.countdownSeconds--;
                if (this.countdownSeconds <= 0) {
                    this.countdownSeconds = 60;
                    this.refreshAll(true);
                }
                this.countdownText = this.countdownSeconds + 's';
            }, 1000);
        },

        async fetchApi(url) {
            if (window.axios) {
                try {
                    const res = await window.axios.get(url);
                    return res.data;
                } catch (e) {
                    console.warn('Axios error, fallback to native fetch:', e);
                }
            }
            try {
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return await response.json();
            } catch (err) {
                console.error('Fetch request error:', err);
                return null;
            }
        },

        extractDataAndPagination(json) {
            if (!json || !json.success) return { items: [], pagination: null };
            const data = json.data;
            if (Array.isArray(data)) {
                return { items: data, pagination: null };
            }
            if (data && Array.isArray(data.data)) {
                return {
                    items: data.data,
                    pagination: {
                        current_page: data.current_page,
                        last_page: data.last_page,
                        per_page: data.per_page,
                        total: data.total,
                        from: data.from || 0,
                        to: data.to || 0,
                    }
                };
            }
            return { items: [], pagination: null };
        },

        async refreshAll(silent = false) {
            if (!silent) this.isLoading = true;
            try {
                const json = await this.fetchApi('{{ route("api.market.data") }}?section=overview');
                if (json && json.success) {
                    this.marketStatus = json.market_status;
                    this.stats = json.stats;
                }
                await Promise.all([
                    this.fetchStocks(true, true),
                    this.fetchMf(true),
                    this.fetchNews(true),
                    this.fetchEvents(true)
                ]);
                this.lastSyncedAt = new Date().toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
            } catch (e) {
                console.error('Auto-sync error:', e);
            } finally {
                if (!silent) this.isLoading = false;
            }
        },

        // --- STOCKS METHODS ---
        stockSearchInput() {
            this.stockCurrentPage = 1;
            this.fetchStocks();
        },

        stockSortChange() {
            this.stockCurrentPage = 1;
            this.fetchStocks();
        },

        stockPerPageChange() {
            this.stockCurrentPage = 1;
            this.fetchStocks();
        },

        setStockInstrumentType(type) {
            this.stockInstrumentType = type;
            this.stockCurrentPage = 1;
            this.fetchStocks();
        },

        setStockFilter(filter) {
            this.stockFilter = filter;
            this.stockCurrentPage = 1;
            this.fetchStocks();
        },

        resetStockFilters() {
            this.stockInstrumentType = 'stocks';
            this.stockSearch = '';
            this.stockFilter = 'all';
            this.stockSort = 'volume_desc';
            this.stockCurrentPage = 1;
            this.fetchStocks();
        },

        goToStocksPage(page) {
            if (!this.stocksPagination) return;
            if (page < 1 || page > this.stocksPagination.last_page) return;
            this.stockCurrentPage = page;
            this.fetchStocks();
        },

        async fetchStocks(silent = false, sync = false) {
            if (!silent) this.isStocksLoading = true;
            try {
                const params = new URLSearchParams({
                    section: 'stocks',
                    instrument_type: this.stockInstrumentType,
                    search: this.stockSearch ? this.stockSearch.trim() : '',
                    filter: this.stockFilter,
                    sort: this.stockSort,
                    per_page: this.stockPerPage,
                    page: this.stockCurrentPage,
                    ...(sync ? { sync: 1 } : {})
                });
                const json = await this.fetchApi(`{{ route("api.market.data") }}?${params.toString()}`);
                if (json) {
                    const result = this.extractDataAndPagination(json);

                    // Track price changes for flashing green / red
                    const newFlashes = {};
                    result.items.forEach(item => {
                        const currentPrice = Number(item.live_price || item.prev_close || 0);
                        const prev = this.previousPrices[item.isin];
                        if (prev !== undefined && prev > 0 && currentPrice > 0) {
                            if (currentPrice > prev) {
                                newFlashes[item.isin] = 'up';
                            } else if (currentPrice < prev) {
                                newFlashes[item.isin] = 'down';
                            }
                        }
                        if (item.isin) {
                            this.previousPrices[item.isin] = currentPrice;
                        }
                    });
                    if (Object.keys(newFlashes).length > 0) {
                        this.priceFlash = Object.assign({}, this.priceFlash, newFlashes);
                        setTimeout(() => {
                            this.priceFlash = {};
                        }, 2500);
                    }

                    this.stocksList = result.items;
                    if (result.pagination) this.stocksPagination = result.pagination;
                    if (json.last_sync) {
                        this.lastSyncedAt = json.last_sync;
                    }
                }
            } catch (e) {
                console.error('fetchStocks error:', e);
            } finally {
                if (!silent) this.isStocksLoading = false;
            }
        },

        // --- MUTUAL FUNDS METHODS ---
        mfSearchInput() {
            this.mfCurrentPage = 1;
            this.fetchMf();
        },

        mfFilterChange() {
            this.mfCurrentPage = 1;
            this.fetchMf();
        },

        setMfCategory(cat) {
            this.mfCategory = cat;
            this.mfCurrentPage = 1;
            this.fetchMf();
        },

        resetMfFilters() {
            this.mfSearch = '';
            this.mfCategory = 'all';
            this.mfAmc = 'all';
            this.mfSort = 'popular';
            this.mfCurrentPage = 1;
            this.fetchMf();
        },

        goToMfPage(page) {
            if (!this.mfPagination) return;
            if (page < 1 || page > this.mfPagination.last_page) return;
            this.mfCurrentPage = page;
            this.fetchMf();
        },

        async fetchMf(silent = false) {
            if (!silent) this.isMfLoading = true;
            try {
                const params = new URLSearchParams({
                    section: 'mf',
                    search: this.mfSearch ? this.mfSearch.trim() : '',
                    category: this.mfCategory,
                    amc: this.mfAmc,
                    sort: this.mfSort,
                    per_page: this.mfPerPage,
                    page: this.mfCurrentPage
                });
                const json = await this.fetchApi(`{{ route("api.market.data") }}?${params.toString()}`);
                if (json) {
                    const result = this.extractDataAndPagination(json);
                    this.mfList = result.items;
                    if (result.pagination) this.mfPagination = result.pagination;
                }
            } catch (e) {
                console.error('fetchMf error:', e);
            } finally {
                if (!silent) this.isMfLoading = false;
            }
        },

        // --- NEWS METHODS ---
        newsSearchInput() {
            this.newsCurrentPage = 1;
            this.fetchNews();
        },

        goToNewsPage(page) {
            if (!this.newsPagination) return;
            if (page < 1 || page > this.newsPagination.last_page) return;
            this.newsCurrentPage = page;
            this.fetchNews();
        },

        async fetchNews(silent = false) {
            if (!silent) this.isNewsLoading = true;
            try {
                const params = new URLSearchParams({
                    section: 'news',
                    search: this.newsSearch ? this.newsSearch.trim() : '',
                    page: this.newsCurrentPage
                });
                const json = await this.fetchApi(`{{ route("api.market.data") }}?${params.toString()}`);
                if (json) {
                    const result = this.extractDataAndPagination(json);
                    this.newsList = result.items;
                    if (result.pagination) this.newsPagination = result.pagination;
                }
            } catch (e) {
                console.error('fetchNews error:', e);
            } finally {
                if (!silent) this.isNewsLoading = false;
            }
        },

        // --- EVENTS METHODS ---
        eventSearchInput() {
            this.eventsCurrentPage = 1;
            this.fetchEvents();
        },

        setEventType(type) {
            this.eventType = type;
            this.eventsCurrentPage = 1;
            this.fetchEvents();
        },

        goToEventsPage(page) {
            if (!this.eventsPagination) return;
            if (page < 1 || page > this.eventsPagination.last_page) return;
            this.eventsCurrentPage = page;
            this.fetchEvents();
        },

        async fetchEvents(silent = false) {
            if (!silent) this.isEventsLoading = true;
            try {
                const params = new URLSearchParams({
                    section: 'events',
                    type: this.eventType,
                    search: this.eventSearch ? this.eventSearch.trim() : '',
                    page: this.eventsCurrentPage
                });
                const json = await this.fetchApi(`{{ route("api.market.data") }}?${params.toString()}`);
                if (json) {
                    const result = this.extractDataAndPagination(json);
                    this.eventsList = result.items;
                    if (result.pagination) this.eventsPagination = result.pagination;
                }
            } catch (e) {
                console.error('fetchEvents error:', e);
            } finally {
                if (!silent) this.isEventsLoading = false;
            }
        },

        // --- PAYLOAD PREVIEW & HELPERS ---
        showApiPayload(item) {
            let payload = {};
            try {
                payload = item.live_payload ? JSON.parse(item.live_payload) : item;
            } catch (e) {
                payload = item;
            }
            this.currentPayloadJson = JSON.stringify(payload, null, 2);
            this.payloadModalOpen = true;
        },

        formatNumber(val) {
            if (val === null || val === undefined || isNaN(val)) return '--';
            return Number(val).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        formatNumberNoDec(val) {
            if (val === null || val === undefined || isNaN(val)) return '0';
            return Number(val).toLocaleString('en-IN');
        },

        formatSigned(val) {
            if (val === null || val === undefined || isNaN(val)) return '--';
            const num = Number(val);
            const prefix = num > 0 ? '+' : '';
            return prefix + num.toFixed(2);
        },

        formatPercent(val) {
            if (val === null || val === undefined || isNaN(val)) return '--';
            const num = Number(val);
            const prefix = num > 0 ? '+' : '';
            return prefix + num.toFixed(2) + '%';
        },

        formatVolume(val) {
            if (!val || isNaN(val)) return '--';
            const num = Number(val);
            if (num >= 10000000) return (num / 10000000).toFixed(2) + ' Cr';
            if (num >= 100000) return (num / 100000).toFixed(2) + ' L';
            if (num >= 1000) return (num / 1000).toFixed(1) + ' k';
            return num.toLocaleString('en-IN');
        },

        formatDate(dateStr) {
            if (!dateStr) return '--';
            const d = new Date(dateStr);
            return isNaN(d) ? dateStr : d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
        },

        formatDateOnly(dateStr) {
            if (!dateStr) return '--';
            return dateStr.substring(0, 10);
        }
    };
}
</script>
@endsection
