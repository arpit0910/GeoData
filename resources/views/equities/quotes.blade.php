@extends('layouts.app')
@section('title', 'Latest Stock Quotes')

@section('content')
<div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Latest Stock Quotes</h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Latest saved price per ISIN and exchange. All times are in IST.</p>
    </div>
    <a href="{{ request()->fullUrl() }}" class="px-5 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-sm font-bold"><i class="fas fa-rotate mr-2" aria-hidden="true"></i>Refresh view</a>
</div>
<form method="GET" action="{{ route('equities.quotes') }}" class="mb-6 p-5 rounded-2xl bg-white dark:bg-richdark-card border border-gray-200 dark:border-white/10 flex flex-wrap items-end gap-4">
    <div class="flex-1 min-w-48">
        <label for="search" class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">Company, symbol or ISIN</label>
        <input id="search" name="search" value="{{ $filters['search'] ?? '' }}" maxlength="100" placeholder="e.g. RELIANCE or INE002A01018" class="w-full border border-gray-300 dark:border-white/10 rounded-lg p-2.5 bg-transparent dark:text-white">
    </div>
    @foreach (['exchange' => ['' => 'All exchanges', 'NSE' => 'NSE', 'BSE' => 'BSE'], 'freshness' => ['' => 'All quote ages', 'recent' => 'Within 15 minutes', 'stale' => 'Older than 15 minutes']] as $field => $options)
    <div>
        <label for="{{ $field }}" class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">{{ ucfirst($field) }}</label>
        <select id="{{ $field }}" name="{{ $field }}" class="border border-gray-300 dark:border-white/10 rounded-lg p-2.5 bg-white dark:bg-richdark-card dark:text-white">
            @foreach ($options as $value => $label)
                <option value="{{ $value }}" @selected(($filters[$field] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    @endforeach
    <button class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-medium">Apply filters</button>
    <a href="{{ route('equities.quotes') }}" class="px-3 py-2.5 text-sm text-gray-600 dark:text-gray-300 underline">Reset</a>
</form>
<p class="mb-3 text-sm text-gray-500 dark:text-gray-400">{{ number_format($quotes->total()) }} saved quotes matching your filters. Refresh reloads stored data. Quotes may be older outside trading hours.</p>
<div class="rounded-2xl bg-white dark:bg-richdark-card border border-gray-200 dark:border-white/10 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left whitespace-nowrap">
            <thead class="bg-gray-50 dark:bg-white/5 text-gray-600 dark:text-gray-300">
                <tr>@foreach (['Company / ISIN', 'Symbol', 'Exchange', 'Price', 'Change (%)', 'Quote time (IST)', 'Fetched at (IST)', 'Quote age'] as $heading)<th scope="col" class="px-5 py-4">{{ $heading }}</th>@endforeach</tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/10 text-gray-700 dark:text-gray-300">
                @forelse ($quotes as $quote)
                <tr>
                    <td class="px-5 py-4"><div class="font-semibold text-gray-900 dark:text-white">{{ $quote->company_name ?: $quote->symbol }}</div><div class="text-xs text-gray-500 mt-1 font-mono">{{ $quote->isin }}</div></td>
                    <td class="px-5 py-4">{{ $quote->symbol }}</td>
                    <td class="px-5 py-4">{{ $quote->exchange }}</td>
                    <td class="px-5 py-4 font-semibold">{{ $quote->details['currency'] ?? '' }} {{ number_format($quote->price, 2) }}</td>
                    <td class="px-5 py-4">{{ isset($quote->details['dp']) ? number_format($quote->details['dp'], 2).'%' : '—' }}</td>
                    <td class="px-5 py-4">{{ $quote->quoted_time->format('d M Y, H:i:s') }}</td>
                    <td class="px-5 py-4">{{ $quote->fetched_time->format('d M Y, H:i:s') }}</td>
                    <td class="px-5 py-4"><span class="inline-block px-2.5 py-1 rounded-full text-xs font-medium {{ $quote->stale ? 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300' : 'bg-green-100 text-green-800 dark:bg-green-500/20 dark:text-green-300' }}">{{ $quote->stale ? 'Older than 15 min' : 'Within 15 min' }}</span><div class="text-xs text-gray-500 mt-1">{{ $quote->quoted_time->diffForHumans() }}</div></td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-5 py-14 text-center text-gray-500 dark:text-gray-400">No saved quotes found. Try clearing your filters or check again after the next stock fetch.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($quotes->hasPages())<div class="p-5 border-t border-gray-100 dark:border-white/10">{{ $quotes->links() }}</div>@endif
</div>
@endsection
