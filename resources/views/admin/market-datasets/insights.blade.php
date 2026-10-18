@extends('layouts.app')

@section('header', 'IPO & Institutional Activity')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white">IPO & FII/DII Activity</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Upstox IPO coverage and daily institutional cash-market flows.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <x-admin-sync-button title="market:sync-upstox-insights" label="Sync All" />
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach(['Total IPOs' => $summary['ipos'], 'Open IPOs' => $summary['open_ipos'], 'Activity records' => $summary['activity_records']] as $label => $value)
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/5 dark:bg-richdark-surface">
                <div class="text-xs font-black uppercase tracking-widest text-gray-400">{{ $label }}</div>
                <div class="mt-2 text-2xl font-black text-gray-900 dark:text-white">{{ number_format($value) }}</div>
            </div>
        @endforeach
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/5 dark:bg-richdark-surface">
            <div class="text-xs font-black uppercase tracking-widest text-gray-400">Last sync</div>
            <div class="mt-2 text-sm font-black text-gray-900 dark:text-white">{{ $summary['last_sync'] ? \Carbon\Carbon::parse($summary['last_sync'])->timezone('Asia/Kolkata')->format('d M Y, h:i A') : 'Never' }}</div>
        </div>
    </div>

    <section class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-black text-gray-900 dark:text-white">IPOs</h2>
            <form method="GET" class="flex gap-2">
                <select name="status" class="rounded-xl border-gray-200 bg-transparent text-sm dark:border-white/10 dark:text-white">
                    <option value="">All statuses</option>
                    @foreach($statuses as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach
                </select>
                <button class="rounded-xl bg-gray-900 px-4 text-sm font-bold text-white dark:bg-white dark:text-gray-900">Filter</button>
            </form>
        </div>
        <div class="overflow-x-auto rounded-2xl border border-gray-200 bg-white dark:border-white/5 dark:bg-richdark-surface">
            <table class="w-full min-w-[850px] text-left text-sm">
                <thead class="border-b border-gray-100 text-xs uppercase tracking-wider text-gray-400 dark:border-white/5"><tr><th class="p-4">Company</th><th class="p-4">Status</th><th class="p-4">Price band</th><th class="p-4">Bidding dates</th><th class="p-4 text-right">Subscription</th></tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse($ipos as $ipo)
                        <tr><td class="p-4"><div class="font-bold text-gray-900 dark:text-white">{{ $ipo->name }}</div><div class="text-xs text-gray-400">{{ $ipo->symbol ?: $ipo->isin ?: '—' }}{{ $ipo->industry ? ' · '.$ipo->industry : '' }}</div></td><td class="p-4"><span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-black uppercase text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">{{ $ipo->status }}</span></td><td class="p-4 font-semibold text-gray-700 dark:text-gray-200">{{ $ipo->minimum_price !== null ? '₹'.number_format($ipo->minimum_price, 2) : '—' }}{{ $ipo->maximum_price !== null ? ' – ₹'.number_format($ipo->maximum_price, 2) : '' }}</td><td class="p-4 text-gray-600 dark:text-gray-300">{{ $ipo->bidding_start_date?->format('d M Y') ?: '—' }} – {{ $ipo->bidding_end_date?->format('d M Y') ?: '—' }}</td><td class="p-4 text-right font-bold text-gray-900 dark:text-white">{{ $ipo->total_subscription !== null ? number_format($ipo->total_subscription, 2).'x' : '—' }}</td></tr>
                    @empty<tr><td colspan="5" class="p-10 text-center text-gray-400">No IPO data stored yet. Use Sync All to fetch it.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
        {{ $ipos->links() }}
    </section>

    <section class="space-y-4">
        <h2 class="text-xl font-black text-gray-900 dark:text-white">Daily cash-market activity</h2>
        <div class="overflow-x-auto rounded-2xl border border-gray-200 bg-white dark:border-white/5 dark:bg-richdark-surface">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="border-b border-gray-100 text-xs uppercase tracking-wider text-gray-400 dark:border-white/5"><tr><th class="p-4">Date</th><th class="p-4">Investor</th><th class="p-4 text-right">Buy</th><th class="p-4 text-right">Sell</th><th class="p-4 text-right">Net</th></tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse($activity as $row)
                        @php
                            $net = ($row->buy_amount ?? 0) - ($row->sell_amount ?? 0);
                        @endphp
                        <tr><td class="p-4 text-gray-600 dark:text-gray-300">{{ $row->activity_at?->timezone('Asia/Kolkata')->format('d M Y') }}</td><td class="p-4 font-black uppercase text-gray-900 dark:text-white">{{ $row->investor_type }}</td><td class="p-4 text-right text-emerald-600">₹{{ number_format((float) $row->buy_amount, 2) }} Cr</td><td class="p-4 text-right text-rose-600">₹{{ number_format((float) $row->sell_amount, 2) }} Cr</td><td class="p-4 text-right font-black {{ $net >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ $net >= 0 ? '+' : '' }}₹{{ number_format($net, 2) }} Cr</td></tr>
                    @empty<tr><td colspan="5" class="p-10 text-center text-gray-400">No institutional activity stored yet.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
        {{ $activity->links() }}
    </section>
</div>
@endsection
