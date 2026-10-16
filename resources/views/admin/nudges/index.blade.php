@extends('layouts.app')

@section('header', 'Market Nudges')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white">Market Nudges</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Review stock, index and benchmark-comparison signals. All times are shown in IST.</p>
        </div>
        <a href="{{ route('market.nudges') }}" target="_blank" rel="noopener" class="rounded-xl bg-amber-600 px-5 py-3 text-center text-sm font-black text-white hover:bg-amber-700">
            <i class="fas fa-arrow-up-right-from-square mr-2"></i>View Today's Public Nudges
        </a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach(['Total' => $summary['total'], 'Stocks' => $summary['stocks'], 'Indices' => $summary['indices'], 'Significant' => $summary['significant']] as $label => $value)
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/5 dark:bg-richdark-surface">
                <div class="text-xs font-black uppercase tracking-widest text-gray-400">{{ $label }}</div>
                <div class="mt-2 text-2xl font-black text-gray-900 dark:text-white">{{ number_format($value) }}</div>
            </div>
        @endforeach
    </div>

    <form method="GET" class="grid gap-3 rounded-2xl border border-gray-200 bg-white p-4 md:grid-cols-5 dark:border-white/5 dark:bg-richdark-surface">
        <label class="grid gap-1 text-xs font-bold uppercase tracking-wide text-gray-500">
            From
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="rounded-xl border-gray-200 bg-transparent text-sm font-normal normal-case tracking-normal dark:border-white/10 dark:text-white">
        </label>
        <label class="grid gap-1 text-xs font-bold uppercase tracking-wide text-gray-500">
            To
            <input type="date" name="date_to" value="{{ $dateTo }}" class="rounded-xl border-gray-200 bg-transparent text-sm font-normal normal-case tracking-normal dark:border-white/10 dark:text-white">
        </label>
        <select name="category" class="rounded-xl border-gray-200 bg-transparent text-sm dark:border-white/10 dark:text-white">
            <option value="">All categories</option>
            @foreach(['growth' => 'Growth', 'fall' => 'Fall', 'volume' => 'Volume', 'comparison' => 'Comparison'] as $value => $label)
                <option value="{{ $value }}" @selected($category === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <input name="search" value="{{ request('search') }}" placeholder="Company, ISIN, index or title" class="rounded-xl border-gray-200 bg-transparent text-sm dark:border-white/10 dark:text-white">
        <button class="rounded-xl bg-gray-900 px-4 py-2 text-sm font-bold text-white dark:bg-white dark:text-gray-900">Filter</button>
    </form>

    <div class="overflow-x-auto rounded-2xl border border-gray-200 bg-white dark:border-white/5 dark:bg-richdark-surface">
        <table class="w-full min-w-[1050px] text-left text-sm">
            <thead class="border-b border-gray-100 text-xs uppercase tracking-wider text-gray-400 dark:border-white/5">
                <tr><th class="p-4">Signal</th><th class="p-4">Instrument</th><th class="p-4">Category</th><th class="p-4">Price</th><th class="p-4">Observed</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                @forelse($nudges as $nudge)
                    @php
                        $instrument = $nudge->equity?->company_name ?: $nudge->index?->index_name ?: $nudge->isin ?: $nudge->index_code ?: 'Market';
                        $symbol = $nudge->equity?->nse_symbol ?: $nudge->equity?->bse_symbol ?: $nudge->index_code;
                        $nudgeCategory = \App\Models\StockNudge::categoryForRule($nudge->rule) ?? 'market';
                    @endphp
                    <tr class="align-top">
                        <td class="p-4"><div class="max-w-md font-bold text-gray-900 dark:text-white">{{ $nudge->title }}</div><div class="mt-1 max-w-lg text-xs leading-5 text-gray-500 dark:text-gray-400">{{ $nudge->message }}</div></td>
                        <td class="p-4"><div class="font-bold text-gray-800 dark:text-gray-200">{{ $instrument }}</div><div class="mt-1 text-xs text-gray-400">{{ $symbol ?: '—' }} · {{ $nudge->exchange }}</div></td>
                        <td class="p-4"><span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold capitalize text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">{{ $nudgeCategory }}</span><div class="mt-2 text-xs capitalize text-gray-400">{{ str_replace('_', ' ', $nudge->rule) }} · {{ $nudge->severity }}</div></td>
                        <td class="p-4 font-mono text-gray-700 dark:text-gray-200">{{ $nudge->observed_price !== null ? number_format((float) $nudge->observed_price, 2) : '—' }}</td>
                        <td class="p-4 whitespace-nowrap text-gray-600 dark:text-gray-300">{{ $nudge->observed_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A') ?: '—' }} IST</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-10 text-center text-gray-400">No nudges found from {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} to {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $nudges->links() }}
</div>
@endsection
