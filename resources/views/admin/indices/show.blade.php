@extends('layouts.app')

@section('content')
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <a href="{{ route('admin.indices.index') }}" class="text-gray-400 hover:text-amber-500 transition-colors">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $index->index_name }}</h1>
                <span class="px-2 py-1 rounded bg-amber-500/10 text-amber-500 text-[10px] font-black uppercase tracking-widest border border-amber-500/20">
                    {{ $index->exchange }}
                </span>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 font-medium ml-7">{{ $index->category ?? 'General Index' }} • History & Analytics</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.indices.edit', $index->index_code) }}" class="px-5 py-2.5 text-sm font-bold rounded-xl border border-gray-200 dark:border-white/10 text-gray-700 dark:text-gray-300 bg-white dark:bg-white/5 hover:bg-gray-50 transition-all">
                <i class="fas fa-edit mr-2 text-amber-500"></i> Edit Metadata
            </a>
        </div>
    </div>

    <!-- Stats Bar -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        @php
            $latest = $prices->first();
        @endphp
        <div class="bg-white dark:bg-[#0f172a]/80 border border-gray-200 dark:border-white/5 rounded-2xl p-5 shadow-sm">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Latest Close</p>
            <h4 class="text-2xl font-black text-gray-900 dark:text-white">₹{{ number_format($latest?->close ?? 0, 2) }}</h4>
        </div>
        <div class="bg-white dark:bg-[#0f172a]/80 border border-gray-200 dark:border-white/5 rounded-2xl p-5 shadow-sm">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Day Change %</p>
            @php $chg = $latest?->change_percent ?? 0; @endphp
            <h4 class="text-2xl font-black {{ $chg >= 0 ? 'text-emerald-500' : 'text-rose-500' }}">
                {{ $chg > 0 ? '+' : '' }}{{ number_format($chg, 2) }}%
            </h4>
        </div>
        <div class="bg-white dark:bg-[#0f172a]/80 border border-gray-200 dark:border-white/5 rounded-2xl p-5 shadow-sm">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Volume</p>
            <h4 class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($latest?->volume ?? 0) }}</h4>
        </div>
        <div class="bg-white dark:bg-[#0f172a]/80 border border-gray-200 dark:border-white/5 rounded-2xl p-5 shadow-sm">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">P/E Ratio</p>
            <h4 class="text-2xl font-black text-gray-900 dark:text-white">{{ $latest?->pe_ratio ?? 'N/A' }}</h4>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
        <!-- Intraday & Volatility Analytics -->
        <div class="bg-white dark:bg-[#0f172a]/80 border border-gray-200 dark:border-white/5 rounded-2xl p-6 shadow-sm">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-6 uppercase tracking-widest flex items-center gap-2">
                <i class="fas fa-chart-line text-amber-500"></i> Intraday Analytics
            </h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-white/5">
                    <span class="text-xs text-gray-500">Gap Percentage</span>
                    <span class="text-xs font-bold {{ ($latest?->gap_pct ?? 0) >= 0 ? 'text-emerald-500' : 'text-rose-500' }}">
                        {{ number_format($latest?->gap_pct ?? 0, 2) }}%
                    </span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-white/5">
                    <span class="text-xs text-gray-500">Intraday Change %</span>
                    <span class="text-xs font-bold {{ ($latest?->intraday_chg_pct ?? 0) >= 0 ? 'text-emerald-500' : 'text-rose-500' }}">
                        {{ number_format($latest?->intraday_chg_pct ?? 0, 2) }}%
                    </span>
                </div>
                <div class="flex justify-between items-center py-2">
                    <span class="text-xs text-gray-500">Day Range %</span>
                    <span class="text-xs font-bold text-gray-900 dark:text-white">
                        {{ number_format($latest?->range_pct ?? 0, 2) }}%
                    </span>
                </div>
            </div>
        </div>

        <!-- Historical Performance Grid -->
        <div class="bg-white dark:bg-[#0f172a]/80 border border-gray-200 dark:border-white/5 rounded-2xl p-6 shadow-sm">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-6 uppercase tracking-widest flex items-center gap-2">
                <i class="fas fa-calendar-alt text-amber-500"></i> Performance Returns
            </h3>
            <div class="grid grid-cols-3 gap-3">
                @php
                    $metrics = [
                        '1D' => 'chg_1d', '3D' => 'chg_3d', '7D' => 'chg_7d',
                        '1M' => 'chg_1m', '3M' => 'chg_3m', '6M' => 'chg_6m',
                        '9M' => 'chg_9m', '1Y' => 'chg_1y', '3Y' => 'chg_3y'
                    ];
                @endphp
                @foreach($metrics as $label => $field)
                    <div class="bg-gray-50 dark:bg-white/5 rounded-xl p-3 border border-gray-100 dark:border-white/5 text-center">
                        <p class="text-[9px] font-bold text-gray-400 mb-1 uppercase">{{ $label }}</p>
                        @php $val = $latest?->$field; @endphp
                        @if($val !== null)
                            <p class="text-xs font-black {{ $val >= 0 ? 'text-emerald-500' : 'text-rose-500' }}">
                                {{ $val > 0 ? '+' : '' }}{{ number_format($val, 2) }}%
                            </p>
                        @else
                            <p class="text-xs font-bold text-gray-400 italic">N/A</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-[#0f172a]/80 backdrop-blur-xl border border-gray-200 dark:border-white/5 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                <i class="fas fa-history text-amber-500"></i> Full Price History
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-white/5">
                            <th class="p-4 text-xs font-bold text-gray-400 first:rounded-tl-xl">Date</th>
                            <th class="p-4 text-xs font-bold text-gray-400">Open</th>
                            <th class="p-4 text-xs font-bold text-gray-400">High</th>
                            <th class="p-4 text-xs font-bold text-gray-400">Low</th>
                            <th class="p-4 text-xs font-bold text-gray-400">Close</th>
                            <th class="p-4 text-xs font-bold text-gray-400">Change%</th>
                            <th class="p-4 text-xs font-bold text-gray-400 last:rounded-tr-xl">Volume</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse($prices as $p)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-colors">
                                <td class="p-4 text-sm font-medium text-gray-900 dark:text-white">{{ $p->traded_date->format('d/m/Y') }}</td>
                                <td class="p-4 text-sm text-gray-600 dark:text-gray-400">{{ number_format($p->open, 2) }}</td>
                                <td class="p-4 text-sm text-gray-600 dark:text-gray-400">{{ number_format($p->high, 2) }}</td>
                                <td class="p-4 text-sm text-gray-600 dark:text-gray-400">{{ number_format($p->low, 2) }}</td>
                                <td class="p-4 text-sm font-bold text-gray-900 dark:text-white">{{ number_format($p->close, 2) }}</td>
                                <td class="p-4 text-sm font-bold {{ $p->change_percent >= 0 ? 'text-emerald-500' : 'text-rose-500' }}">
                                    {{ $p->change_percent > 0 ? '+' : '' }}{{ number_format($p->change_percent, 2) }}%
                                </td>
                                <td class="p-4 text-sm text-gray-600 dark:text-gray-400">{{ number_format($p->volume) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-12 text-center text-gray-500 italic">No price records found for this index.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-8 px-4">
                {{ $prices->links() }}
            </div>
        </div>
    </div>
@endsection
