@extends('layouts.app')
@section('header', 'Asset management companies')
@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white sm:text-3xl">Asset management companies</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">View saved AMC details, favicons, and linked mutual fund schemes.</p>
        </div>
        <x-admin-sync-button title="amcs:sync-icons" label="Sync AMC details & favicons" />
    </header>

    <p class="text-sm text-gray-600 dark:text-gray-400"><strong class="text-gray-900 dark:text-white">{{ number_format($total) }}</strong> AMCs &middot; <strong class="text-gray-900 dark:text-white">{{ number_format($withFavicons) }}</strong> with favicons &middot; <strong class="text-gray-900 dark:text-white">{{ number_format($total - $withFavicons) }}</strong> missing favicons</p>

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-white/10 dark:bg-richdark-surface" aria-label="AMC directory">
        <form method="GET" class="flex flex-col gap-3 border-b border-gray-200 p-5 dark:border-white/10 sm:flex-row sm:items-end">
            <div class="min-w-0 flex-1">
                <label for="amc-search" class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">Search AMCs</label>
                <input id="amc-search" name="search" type="search" maxlength="200" value="{{ $search }}" placeholder="Fund house, company, or AMFI ID" class="w-full rounded-lg border-gray-300 bg-transparent text-sm text-gray-900 dark:border-gray-600 dark:text-white">
            </div>
            <div>
                <label for="amc-favicon" class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">Favicon</label>
                <select id="amc-favicon" name="favicon" class="w-full rounded-lg border-gray-300 bg-transparent text-sm text-gray-900 dark:border-gray-600 dark:text-white">
                    <option value="">All AMCs</option>
                    <option value="available" @selected(request('favicon') === 'available')>With favicon</option>
                    <option value="missing" @selected(request('favicon') === 'missing')>Missing favicon</option>
                </select>
            </div>
            <button type="submit" class="rounded-lg bg-amber-600 px-5 py-2 text-sm font-semibold text-white hover:bg-amber-700">Search</button>
            <a href="{{ route('admin.amcs.index') }}" class="px-2 py-2 text-sm font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">Reset</a>
        </form>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <caption class="px-5 py-3 text-left text-sm text-gray-600 dark:text-gray-400">{{ number_format($amcs->total()) }} {{ $amcs->total() === 1 ? 'AMC' : 'AMCs' }} found</caption>
                <thead class="border-y border-gray-200 bg-gray-50 text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-300">
                    <tr><th scope="col" class="px-5 py-3">AMC</th><th scope="col" class="px-4 py-3">AMFI ID</th><th scope="col" class="px-4 py-3 text-right">Schemes</th><th scope="col" class="px-4 py-3">Website</th><th scope="col" class="px-4 py-3">Last sync (IST)</th><th scope="col" class="px-5 py-3"><span class="sr-only">Details</span></th></tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                    @forelse($amcs as $amc)
                        <tr>
                            <th scope="row" class="px-5 py-4 font-normal">
                                <div class="flex min-w-64 items-center gap-3">
                                    @if($amc->favicon_url)<img src="{{ $amc->favicon_url }}" alt="" width="32" height="32" loading="lazy" class="h-8 w-8 shrink-0 rounded bg-white/95 object-contain p-1">@else<span class="flex h-8 w-8 shrink-0 items-center justify-center rounded bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-300" aria-hidden="true"><i class="fas fa-building"></i></span>@endif
                                    <div><a href="{{ route('admin.amcs.show', $amc) }}" class="font-semibold text-gray-900 hover:underline dark:text-white">{{ $amc->name }}</a><p class="mt-1 text-xs text-gray-600 dark:text-gray-400">{{ $amc->company_name ?: 'Company name unavailable' }}</p>@unless($amc->favicon_url)<p class="mt-1 text-xs text-gray-600 dark:text-gray-400">No favicon saved</p>@endunless</div>
                                </div>
                            </th>
                            <td class="px-4 py-4 text-gray-700 dark:text-gray-300">{{ $amc->amfi_id }}</td>
                            <td class="px-4 py-4 text-right tabular-nums text-gray-700 dark:text-gray-300">{{ number_format($amc->funds_count) }}</td>
                            <td class="px-4 py-4">@if($amc->website_url)<a href="{{ $amc->website_url }}" target="_blank" rel="noopener noreferrer" class="text-amber-700 underline underline-offset-4 dark:text-amber-400">Visit website<span class="sr-only"> for {{ $amc->name }} (opens in a new tab)</span></a>@else<span class="text-gray-500 dark:text-gray-400">Not available</span>@endif</td>
                            <td class="whitespace-nowrap px-4 py-4 text-gray-600 dark:text-gray-400">{{ $amc->synced_at?->timezone('Asia/Kolkata')->format('d M Y, H:i') ?: 'Never synced' }}</td>
                            <td class="px-5 py-4"><a href="{{ route('admin.amcs.show', $amc) }}" class="whitespace-nowrap font-semibold text-amber-700 hover:underline dark:text-amber-400">View details<span class="sr-only"> for {{ $amc->name }}</span></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center"><p class="font-semibold text-gray-900 dark:text-white">No AMCs found</p><p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $total ? 'Try another search or reset the filters.' : 'Sync AMC details and favicons to populate this directory.' }}</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($amcs->hasPages())<div class="border-t border-gray-200 p-5 dark:border-white/10">{{ $amcs->links() }}</div>@endif
    </section>
</div>
@endsection
