@extends('layouts.app')

@section('header', 'Corporate Actions')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white">Corporate Actions</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Monitor Upstox company-event coverage and inspect dividends, splits, bonuses, rights and other events. Automatic batches run every ten minutes.</p>
        </div>
        <form method="POST" action="{{ route('admin.corporate-actions.sync') }}" class="flex flex-col gap-2 rounded-2xl border border-gray-200 bg-white p-3 shadow-sm sm:flex-row dark:border-white/5 dark:bg-richdark-surface">
            @csrf
            <input name="isin" value="{{ old('isin') }}" placeholder="Optional ISIN" class="rounded-xl border-gray-200 bg-transparent text-sm uppercase dark:border-white/10 dark:text-white">
            <select name="limit" class="rounded-xl border-gray-200 bg-transparent text-sm dark:border-white/10 dark:text-white">
                @foreach([25, 50, 100, 250, 500, 1000, 2500, 5000] as $limit)<option value="{{ $limit }}" @selected(old('limit', 500) == $limit)>Next {{ number_format($limit) }}</option>@endforeach
            </select>
            <button onclick="return confirm('Start corporate-action synchronization now?');" class="whitespace-nowrap rounded-xl bg-amber-600 px-5 py-2.5 text-sm font-black text-white hover:bg-amber-700">
                <i class="fas fa-rotate mr-2"></i>Sync Now
            </button>
        </form>
    </div>

    @if($errors->any() || session('success') || session('error'))
        <div class="rounded-xl border px-4 py-3 text-sm font-semibold {{ $errors->any() || session('error') ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300' : 'border-green-200 bg-green-50 text-green-700 dark:border-green-500/20 dark:bg-green-500/10 dark:text-green-300' }}">
            @if($errors->any())<ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@else<pre class="whitespace-pre-wrap font-sans">{{ session('error') ?: session('success') }}</pre>@endif
        </div>
    @endif

    @unless($syncTrackingEnabled)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
            <strong>Synchronization tracking is awaiting the latest database migration.</strong>
            Stored corporate actions remain available, but coverage and failure statistics will appear after migrations are applied.
        </div>
    @endunless

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            'Stored events' => number_format($summary['events']),
            'Companies with events' => number_format($summary['companies_with_events']),
            'Successfully checked' => number_format($summary['synced_companies']).' / '.number_format($summary['eligible_companies']),
            'Pending / failed' => number_format($summary['pending_companies']).' / '.number_format($summary['failed_companies']),
        ] as $label => $value)
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/5 dark:bg-richdark-surface">
                <div class="text-xs font-black uppercase tracking-widest text-gray-400">{{ $label }}</div>
                <div class="mt-2 text-2xl font-black text-gray-900 dark:text-white">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4 text-sm dark:border-white/5 dark:bg-richdark-surface">
        <span class="font-bold text-gray-700 dark:text-gray-200">Last attempt:</span>
        <span class="text-gray-500 dark:text-gray-400">{{ $summary['last_attempt'] ? \Carbon\Carbon::parse($summary['last_attempt'], 'UTC')->timezone('Asia/Kolkata')->format('d M Y, h:i A') : 'Never' }}</span>
        <span class="mx-3 text-gray-300">|</span>
        <span class="font-bold text-gray-700 dark:text-gray-200">Last success:</span>
        <span class="text-gray-500 dark:text-gray-400">{{ $summary['last_success'] ? \Carbon\Carbon::parse($summary['last_success'], 'UTC')->timezone('Asia/Kolkata')->format('d M Y, h:i A') : 'Never' }}</span>
    </div>

    @if($failedCompanies->isNotEmpty())
        <div class="rounded-2xl border border-red-200 bg-red-50 p-5 dark:border-red-500/20 dark:bg-red-500/10">
            <h2 class="font-black text-red-800 dark:text-red-300"><i class="fas fa-triangle-exclamation mr-2"></i>Recent sync failures</h2>
            <div class="mt-3 space-y-2">
                @foreach($failedCompanies as $company)
                    <div class="rounded-xl bg-white/70 p-3 text-sm dark:bg-black/10">
                        <div class="font-bold text-gray-900 dark:text-white">{{ $company->company_name ?: $company->isin }} <span class="font-normal text-gray-500">({{ $company->isin }})</span></div>
                        <div class="mt-1 break-words text-red-700 dark:text-red-300">{{ $company->corporate_actions_sync_error }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <form method="GET" class="grid gap-3 rounded-2xl border border-gray-200 bg-white p-4 md:grid-cols-5 dark:border-white/5 dark:bg-richdark-surface">
        <input name="search" value="{{ request('search') }}" placeholder="Company, symbol, event or ISIN" class="rounded-xl border-gray-200 bg-transparent text-sm md:col-span-2 dark:border-white/10 dark:text-white">
        <select name="type" class="rounded-xl border-gray-200 bg-transparent text-sm dark:border-white/10 dark:text-white">
            <option value="">All action types</option>
            @foreach(['DIVIDEND', 'SPLIT', 'BONUS', 'RIGHTS', 'EVENT'] as $type)<option value="{{ $type }}" @selected(request('type') === $type)>{{ ucfirst(strtolower($type)) }}</option>@endforeach
        </select>
        <input type="date" name="from" value="{{ request('from') }}" aria-label="From date" class="rounded-xl border-gray-200 bg-transparent text-sm dark:border-white/10 dark:text-white">
        <div class="flex gap-2"><input type="date" name="to" value="{{ request('to') }}" aria-label="To date" class="min-w-0 flex-1 rounded-xl border-gray-200 bg-transparent text-sm dark:border-white/10 dark:text-white"><button class="rounded-xl bg-gray-900 px-4 text-sm font-bold text-white dark:bg-white dark:text-gray-900">Filter</button></div>
    </form>

    <div class="overflow-x-auto rounded-2xl border border-gray-200 bg-white dark:border-white/5 dark:bg-richdark-surface">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead class="border-b border-gray-100 text-xs uppercase tracking-wider text-gray-400 dark:border-white/5"><tr><th class="p-4">Company</th><th class="p-4">Type / event</th><th class="p-4">Ex date</th><th class="p-4">Record date</th><th class="p-4">Amount / ratio</th><th class="p-4 text-right">Details</th></tr></thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                @forelse($actions as $action)
                    <tr>
                        <td class="p-4"><div class="font-bold text-gray-900 dark:text-white">{{ $action->company_name ?: $action->isin }}</div><div class="text-xs text-gray-400">{{ $action->symbol ?: 'No symbol' }} · {{ $action->isin }}</div></td>
                        <td class="p-4"><span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-black text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">{{ $action->type }}</span><div class="mt-2 font-semibold text-gray-700 dark:text-gray-200">{{ $action->name }}</div></td>
                        <td class="p-4 text-gray-600 dark:text-gray-300">{{ $action->expiry_date?->format('d M Y') ?: '—' }}</td>
                        <td class="p-4 text-gray-600 dark:text-gray-300">{{ $action->record_date?->format('d M Y') ?: '—' }}</td>
                        <td class="p-4 text-gray-600 dark:text-gray-300">@if($action->amount !== null)₹{{ number_format((float) $action->amount, 4) }}@elseif($action->ratio){{ $action->ratio }}@else—@endif</td>
                        <td class="p-4 text-right"><a href="{{ route('admin.corporate-actions.show', $action) }}" class="font-bold text-amber-600 hover:text-amber-700">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-12 text-center text-gray-400">No corporate actions found. Run synchronization to fetch company events.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $actions->links() }}
</div>
@endsection
