@extends('layouts.app')

@section('header', 'Fundamental Record')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <a href="{{ route('admin.market-datasets.company-fundamentals') }}" class="text-sm font-bold text-amber-600"><i class="fas fa-arrow-left mr-2"></i>Back to fundamentals</a>
    <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-white/5 dark:bg-richdark-surface">
        <h1 class="text-2xl font-black text-gray-900 dark:text-white">{{ $companyFundamental->equity?->company_name ?: $companyFundamental->isin }}</h1>
        <div class="mt-4 grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
            <div><div class="text-xs font-bold uppercase text-gray-400">ISIN</div><div class="mt-1 font-semibold dark:text-white">{{ $companyFundamental->isin }}</div></div>
            <div><div class="text-xs font-bold uppercase text-gray-400">Dataset</div><div class="mt-1 font-semibold dark:text-white">{{ str_replace('-', ' ', $companyFundamental->dataset) }}</div></div>
            <div><div class="text-xs font-bold uppercase text-gray-400">Statement / Period</div><div class="mt-1 font-semibold dark:text-white">{{ $companyFundamental->statement_type }} / {{ $companyFundamental->time_period }}</div></div>
            <div><div class="text-xs font-bold uppercase text-gray-400">Synced</div><div class="mt-1 font-semibold dark:text-white">{{ $companyFundamental->synced_at?->format('d M Y, h:i:s A') }}</div></div>
        </div>
    </div>
    <div class="rounded-2xl border border-gray-200 bg-gray-950 p-6 shadow-xl dark:border-white/5">
        <div class="mb-4 text-xs font-black uppercase tracking-widest text-gray-400">Stored Payload</div>
        <pre class="overflow-x-auto whitespace-pre-wrap break-words text-sm leading-6 text-green-300">{{ json_encode($companyFundamental->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </div>
</div>
@endsection
