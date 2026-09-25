@extends('layouts.app')

@section('header', 'Company Fundamentals')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div>
        <h1 class="text-3xl font-black text-gray-900 dark:text-white">Complete Company Fundamentals</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Browse stored company datasets and run oldest-first historical backfills.</p>
    </div>

    @if(session('success') || session('error'))
        <div class="rounded-xl border px-4 py-3 text-sm font-semibold {{ session('error') ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300' : 'border-green-200 bg-green-50 text-green-700 dark:border-green-500/20 dark:bg-green-500/10 dark:text-green-300' }}">
            <pre class="max-h-64 overflow-auto whitespace-pre-wrap font-sans">{{ session('error') ?: session('success') }}</pre>
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach(['Stored Records' => $summary['records'], 'Synced Companies' => $summary['companies'], 'Pending Companies' => $summary['pending'], 'Last Sync' => $summary['last_sync'] ? \Carbon\Carbon::parse($summary['last_sync'])->format('d M Y, h:i A') : 'Never'] as $label => $value)
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/5 dark:bg-richdark-surface"><div class="text-xs font-black uppercase tracking-widest text-gray-400">{{ $label }}</div><div class="mt-2 text-xl font-black text-gray-900 dark:text-white">{{ $value }}</div></div>
        @endforeach
    </div>

    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-500/20 dark:bg-amber-500/5">
        <h2 class="font-black text-gray-900 dark:text-white">Historical / Old Data Sync</h2>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Leave ISINs blank to process never-synced and oldest-synced companies first. Every run is recorded in Cron Logs.</p>
        <form method="POST" action="{{ route('admin.market-datasets.company-fundamentals.sync') }}" class="mt-4 grid gap-3 lg:grid-cols-5">
            @csrf
            <textarea name="isins" rows="2" placeholder="Optional ISINs, comma or line separated" class="rounded-xl border-gray-200 bg-white text-sm lg:col-span-2 dark:border-white/10 dark:bg-richdark-surface dark:text-white">{{ old('isins') }}</textarea>
            <div><label class="mb-1 block text-xs font-bold text-gray-500">Company limit</label><input type="number" name="limit" value="{{ old('limit', 25) }}" min="1" max="1000" class="w-full rounded-xl border-gray-200 bg-white text-sm dark:border-white/10 dark:bg-richdark-surface dark:text-white"></div>
            <div><label class="mb-1 block text-xs font-bold text-gray-500">Only older than days</label><input type="number" name="stale_days" value="{{ old('stale_days', 30) }}" min="0" max="3650" class="w-full rounded-xl border-gray-200 bg-white text-sm dark:border-white/10 dark:bg-richdark-surface dark:text-white"></div>
            <div><label class="mb-1 block text-xs font-bold text-gray-500">Request delay (ms)</label><input type="number" name="delay" value="{{ old('delay', 250) }}" min="0" max="5000" class="w-full rounded-xl border-gray-200 bg-white text-sm dark:border-white/10 dark:bg-richdark-surface dark:text-white"></div>
            <div class="lg:col-span-4"><div class="flex flex-wrap gap-2">@foreach($datasets as $dataset)<label class="inline-flex items-center gap-1.5 rounded-lg bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-600 dark:bg-richdark-surface dark:text-gray-300"><input type="checkbox" name="datasets[]" value="{{ $dataset }}"> {{ str_replace('-', ' ', $dataset) }}</label>@endforeach</div></div>
            <button class="rounded-xl bg-amber-600 px-5 py-3 text-sm font-black text-white hover:bg-amber-700"><i class="fas fa-clock-rotate-left mr-2"></i>Run Oldest First</button>
        </form>
        @if($errors->any())<div class="mt-3 text-sm font-semibold text-red-600">{{ $errors->first() }}</div>@endif
    </div>

    <form method="GET" class="grid gap-3 rounded-2xl border border-gray-200 bg-white p-4 md:grid-cols-5 dark:border-white/5 dark:bg-richdark-surface">
        <input name="search" value="{{ request('search') }}" placeholder="Company, symbol or ISIN" class="rounded-xl border-gray-200 bg-transparent text-sm dark:border-white/10 dark:text-white">
        <select name="dataset" class="rounded-xl border-gray-200 bg-transparent text-sm dark:border-white/10 dark:text-white"><option value="">All datasets</option>@foreach($datasets as $dataset)<option value="{{ $dataset }}" @selected(request('dataset') === $dataset)>{{ str_replace('-', ' ', $dataset) }}</option>@endforeach</select>
        <select name="statement_type" class="rounded-xl border-gray-200 bg-transparent text-sm dark:border-white/10 dark:text-white"><option value="">All statement types</option>@foreach($statementTypes as $type)<option value="{{ $type }}" @selected(request('statement_type') === $type)>{{ $type }}</option>@endforeach</select>
        <select name="time_period" class="rounded-xl border-gray-200 bg-transparent text-sm dark:border-white/10 dark:text-white"><option value="">All periods</option>@foreach($timePeriods as $period)<option value="{{ $period }}" @selected(request('time_period') === $period)>{{ $period }}</option>@endforeach</select>
        <button class="rounded-xl bg-gray-900 px-4 py-2 text-sm font-bold text-white dark:bg-white dark:text-gray-900">Filter</button>
    </form>

    <div class="overflow-x-auto rounded-2xl border border-gray-200 bg-white dark:border-white/5 dark:bg-richdark-surface">
        <table class="w-full text-left text-sm"><thead class="border-b border-gray-100 text-xs uppercase tracking-wider text-gray-400 dark:border-white/5"><tr><th class="p-4">Company</th><th class="p-4">Dataset</th><th class="p-4">Statement</th><th class="p-4">Period</th><th class="p-4">Synced</th><th class="p-4"></th></tr></thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/5">@forelse($fundamentals as $row)<tr><td class="p-4"><div class="font-bold text-gray-900 dark:text-white">{{ $row->equity?->company_name ?: $row->isin }}</div><div class="text-xs text-gray-400">{{ $row->equity?->nse_symbol ?: $row->equity?->bse_symbol }} · {{ $row->isin }}</div></td><td class="p-4 font-semibold text-gray-700 dark:text-gray-300">{{ str_replace('-', ' ', $row->dataset) }}</td><td class="p-4 text-gray-600 dark:text-gray-300">{{ $row->statement_type }}</td><td class="p-4 text-gray-600 dark:text-gray-300">{{ $row->time_period }}</td><td class="p-4 text-xs text-gray-500">{{ $row->synced_at?->format('d M Y, h:i A') }}</td><td class="p-4 text-right"><a href="{{ route('admin.market-datasets.company-fundamentals.show', $row) }}" class="font-bold text-amber-600 hover:text-amber-700">View</a></td></tr>@empty<tr><td colspan="6" class="p-10 text-center text-gray-400">No company fundamentals found.</td></tr>@endforelse</tbody>
        </table>
    </div>
    {{ $fundamentals->links() }}
</div>
@endsection
