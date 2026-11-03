@extends('layouts.app')
@section('header', 'AMC details')
@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <a href="{{ route('admin.amcs.index') }}" class="inline-block text-sm font-semibold text-amber-700 hover:underline dark:text-amber-400">&larr; All AMCs</a>
    <header class="flex items-start gap-4">
        @if($amc->favicon_url)<img src="{{ $amc->favicon_url }}" alt="" width="48" height="48" class="h-12 w-12 rounded bg-white/95 object-contain p-1">@endif
        <div class="min-w-0"><h1 class="break-words text-2xl font-bold text-gray-900 dark:text-white">{{ $amc->name }}</h1><p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $amc->company_name ?: 'Company name unavailable' }}</p></div>
    </header>
    <section aria-label="AMC overview" class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-richdark-surface sm:p-6">
        <dl class="grid gap-6 text-sm sm:grid-cols-2">
            <div><dt class="text-gray-600 dark:text-gray-400">AMFI member ID</dt><dd class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $amc->amfi_id }}</dd></div>
            <div><dt class="text-gray-600 dark:text-gray-400">Linked mutual fund schemes</dt><dd class="mt-1 font-semibold text-gray-900 dark:text-white">{{ number_format($amc->funds_count) }}</dd></div>
            <div><dt class="text-gray-600 dark:text-gray-400">Official website</dt><dd class="mt-1 break-all">@if($amc->website_url)<a href="{{ $amc->website_url }}" target="_blank" rel="noopener noreferrer" class="text-amber-700 underline underline-offset-4 dark:text-amber-400">{{ $amc->website_url }}<span class="sr-only"> (opens in a new tab)</span></a>@else<span class="text-gray-700 dark:text-gray-300">Not available</span>@endif</dd></div>
            <div><dt class="text-gray-600 dark:text-gray-400">Favicon</dt><dd class="mt-1 text-gray-900 dark:text-white">{{ $amc->favicon_url ? 'Saved locally' : 'Not available' }}</dd></div>
            <div><dt class="text-gray-600 dark:text-gray-400">Details last synced (IST)</dt><dd class="mt-1 text-gray-900 dark:text-white">{{ $amc->synced_at?->timezone('Asia/Kolkata')->format('d M Y, H:i') ?: 'Never synced' }}</dd></div>
            <div><dt class="text-gray-600 dark:text-gray-400">Favicon last synced (IST)</dt><dd class="mt-1 text-gray-900 dark:text-white">{{ $amc->icon_synced_at?->timezone('Asia/Kolkata')->format('d M Y, H:i') ?: 'Never synced' }}</dd></div>
        </dl>
    </section>
    <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-richdark-surface sm:p-6" aria-labelledby="amc-information-heading">
        <h2 id="amc-information-heading" class="mb-6 text-lg font-bold text-gray-900 dark:text-white">Saved AMC information</h2>
        @if($amc->details)@include('admin.amcs.fields', ['fields' => $amc->details])@else<p class="text-sm text-gray-600 dark:text-gray-400">No additional AMC information has been synchronized.</p>@endif
    </section>
</div>
@endsection
