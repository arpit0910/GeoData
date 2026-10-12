@extends('layouts.public')
@section('title', $index->index_name.' - SetuGeo Markets')
@section('meta_description', 'Live price, signal and recent performance for '.$index->index_name.'.')
@section('market_heading', $index->index_name)
@section('market_subheading', $index->index_code.' · '.$index->exchange.' index price and recent performance.')

@section('content')
@php
    $quote = $index->quote;
    $change = $quote->change_percent === null ? null : (float) $quote->change_percent;
    $signal = $change === null || $change == 0 ? 'Flat' : ($change > 0 ? 'Growth' : 'Fall');
    $quotedAt = $quote->quoted_at?->copy()->timezone('Asia/Kolkata');
@endphp
<div class="market-shell">
    @include('website.partials.market-nav')

    <main class="market-wrap py-5 sm:py-8">
        <a href="{{ route('market.indices') }}" class="mb-5 inline-flex items-center text-sm font-bold text-amber-400 hover:text-amber-300"><i class="fas fa-arrow-left mr-2"></i>All indices</a>

        <section class="market-panel market-grid-line p-5 sm:p-7">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="market-chip">{{ $index->exchange }}</span>
                        @if($index->category)<span class="market-chip">{{ $index->category }}</span>@endif
                        <span class="rounded-full border px-3 py-1 text-[11px] font-extrabold uppercase tracking-wide {{ $signal === 'Growth' ? 'border-emerald-400/25 bg-emerald-400/10 text-emerald-300' : ($signal === 'Fall' ? 'border-rose-400/25 bg-rose-400/10 text-rose-300' : 'border-slate-400/25 bg-slate-400/10 text-slate-300') }}">{{ $signal }} signal</span>
                    </div>
                    <h2 class="mt-4 text-2xl font-black text-white sm:text-3xl">{{ $index->index_name }}</h2>
                    <p class="mt-1 font-mono text-sm market-muted">{{ $index->index_code }}</p>
                </div>
                <div class="sm:text-right">
                    <div class="text-3xl font-black text-white sm:text-4xl">{{ number_format((float) $quote->price, 2) }}</div>
                    <div class="mt-2 font-mono text-base font-bold {{ $change === null ? 'text-slate-500' : ($change >= 0 ? 'text-emerald-400' : 'text-rose-400') }}">{{ $change === null ? '—' : (($change >= 0 ? '+' : '').number_format($change, 2).'%') }}</div>
                    @if($quotedAt)<p class="mt-2 text-xs market-muted">Updated {{ $quotedAt->format('d M Y, h:i A') }} IST</p>@endif
                </div>
            </div>

            <div class="mt-7 grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach([
                    ['Previous close', $quote->previous_close],
                    ['Net change', $quote->change],
                    ['Day high', $latestPrice?->high],
                    ['Day low', $latestPrice?->low],
                ] as [$label, $value])
                    <div class="rounded-xl border border-white/[.07] bg-white/[.025] p-4">
                        <p class="text-xs market-muted">{{ $label }}</p>
                        <p class="mt-2 font-mono text-lg font-extrabold text-white">{{ $value === null ? '—' : number_format((float) $value, 2) }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="mt-7">
            <div class="mb-4">
                <p class="market-kicker">Historical data</p>
                <h2 class="mt-1 text-2xl font-black text-white">Recent performance</h2>
            </div>
            <div class="market-panel overflow-x-auto">
                <table class="market-table market-mobile-cards">
                    <thead><tr><th>Date</th><th class="text-right">Open</th><th class="text-right">High</th><th class="text-right">Low</th><th class="text-right">Close</th><th class="text-right">Change</th></tr></thead>
                    <tbody>
                        @forelse($history as $price)
                            <tr>
                                <td data-label="Date" class="font-bold text-white">{{ $price->traded_date->format('d M Y') }}</td>
                                <td data-label="Open" class="text-right">{{ $price->open === null ? '—' : number_format((float) $price->open, 2) }}</td>
                                <td data-label="High" class="text-right">{{ $price->high === null ? '—' : number_format((float) $price->high, 2) }}</td>
                                <td data-label="Low" class="text-right">{{ $price->low === null ? '—' : number_format((float) $price->low, 2) }}</td>
                                <td data-label="Close" class="text-right font-bold text-white">{{ number_format((float) $price->close, 2) }}</td>
                                <td data-label="Change" class="text-right font-mono {{ (float) $price->change_percent >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">{{ $price->change_percent === null ? '—' : (((float) $price->change_percent >= 0 ? '+' : '').number_format((float) $price->change_percent, 2).'%') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-12 text-center market-muted">Historical prices will appear after the next EOD sync.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>
@endsection
