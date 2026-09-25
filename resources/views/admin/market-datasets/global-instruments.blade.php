@extends('layouts.app')

@section('header', 'Global Instruments')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white">Global Instruments</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Admin-only master data for global indices and economic indicators.</p>
        </div>
        <form method="POST" action="{{ route('admin.market-datasets.global-instruments.sync') }}">
            @csrf
            <button class="rounded-xl bg-amber-600 px-5 py-3 text-sm font-black text-white hover:bg-amber-700">
                <i class="fas fa-rotate mr-2"></i>Sync Now
            </button>
        </form>
    </div>

    @if(session('success') || session('error'))
        <div class="rounded-xl border px-4 py-3 text-sm font-semibold {{ session('error') ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300' : 'border-green-200 bg-green-50 text-green-700 dark:border-green-500/20 dark:bg-green-500/10 dark:text-green-300' }}">
            <pre class="whitespace-pre-wrap font-sans">{{ session('error') ?: session('success') }}</pre>
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach(['Total' => $summary['total'], 'Active' => $summary['active'], 'Countries' => $summary['countries'], 'Last Sync' => $summary['last_sync'] ? \Carbon\Carbon::parse($summary['last_sync'])->format('d M Y, h:i A') : 'Never'] as $label => $value)
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/5 dark:bg-richdark-surface">
                <div class="text-xs font-black uppercase tracking-widest text-gray-400">{{ $label }}</div>
                <div class="mt-2 text-xl font-black text-gray-900 dark:text-white">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <form method="GET" class="grid gap-3 rounded-2xl border border-gray-200 bg-white p-4 md:grid-cols-4 dark:border-white/5 dark:bg-richdark-surface">
        <input name="search" value="{{ request('search') }}" placeholder="Name, symbol or country" class="rounded-xl border-gray-200 bg-transparent text-sm dark:border-white/10 dark:text-white">
        <select name="segment" class="rounded-xl border-gray-200 bg-transparent text-sm dark:border-white/10 dark:text-white">
            <option value="">All segments</option>
            @foreach($segments as $segment)<option value="{{ $segment }}" @selected(request('segment') === $segment)>{{ $segment }}</option>@endforeach
        </select>
        <select name="status" class="rounded-xl border-gray-200 bg-transparent text-sm dark:border-white/10 dark:text-white">
            <option value="">All statuses</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
        </select>
        <button class="rounded-xl bg-gray-900 px-4 py-2 text-sm font-bold text-white dark:bg-white dark:text-gray-900">Filter</button>
    </form>

    <div class="overflow-x-auto rounded-2xl border border-gray-200 bg-white dark:border-white/5 dark:bg-richdark-surface">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-100 text-xs uppercase tracking-wider text-gray-400 dark:border-white/5"><tr><th class="p-4">Instrument</th><th class="p-4">Country</th><th class="p-4">Segment / Type</th><th class="p-4">Trading Hours</th><th class="p-4">Status</th><th class="p-4">Synced</th></tr></thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                @forelse($instruments as $instrument)
                    <tr><td class="p-4"><div class="font-bold text-gray-900 dark:text-white">{{ $instrument->name }}</div><div class="text-xs text-gray-400">{{ $instrument->trading_symbol ?: '—' }}</div></td><td class="p-4 text-gray-600 dark:text-gray-300">{{ $instrument->country ?: '—' }}</td><td class="p-4 text-gray-600 dark:text-gray-300">{{ $instrument->segment }} / {{ $instrument->instrument_type ?: '—' }}</td><td class="p-4 text-gray-600 dark:text-gray-300">{{ $instrument->start_time ?: '—' }} – {{ $instrument->end_time ?: '—' }}</td><td class="p-4"><span class="rounded-full px-2 py-1 text-xs font-bold {{ $instrument->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">{{ $instrument->is_active ? 'Active' : 'Inactive' }}</span></td><td class="p-4 text-xs text-gray-500">{{ $instrument->synced_at?->format('d M Y, h:i A') ?: '—' }}</td></tr>
                @empty<tr><td colspan="6" class="p-10 text-center text-gray-400">No global instruments found.</td></tr>@endforelse
            </tbody>
        </table>
    </div>
    {{ $instruments->links() }}
</div>
@endsection
