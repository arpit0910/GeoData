@extends('layouts.app')

@section('header', 'Corporate Action Details')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <a href="{{ route('admin.corporate-actions.index') }}" class="text-sm font-bold text-amber-600 hover:text-amber-700"><i class="fas fa-arrow-left mr-2"></i>Back to Corporate Actions</a>

    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/5 dark:bg-richdark-surface">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div><h1 class="text-2xl font-black text-gray-900 dark:text-white">{{ $corporateAction->company_name ?: $corporateAction->isin }}</h1><p class="mt-1 text-sm text-gray-500">{{ $corporateAction->symbol ?: 'No symbol' }} · {{ $corporateAction->isin }}</p></div>
            <span class="self-start rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">{{ $corporateAction->type }}</span>
        </div>
        <h2 class="mt-6 text-xl font-bold text-gray-900 dark:text-white">{{ $corporateAction->name }}</h2>
        <dl class="mt-6 grid gap-4 sm:grid-cols-2">
            @foreach([
                'Announcement date' => $corporateAction->announcement_date?->format('d M Y') ?: '—',
                'Ex / effective date' => $corporateAction->expiry_date?->format('d M Y') ?: '—',
                'Record date' => $corporateAction->record_date?->format('d M Y') ?: '—',
                'Amount' => $corporateAction->amount !== null ? '₹'.number_format((float) $corporateAction->amount, 4) : '—',
                'Ratio' => $corporateAction->ratio ?: '—',
                'Last updated' => $corporateAction->updated_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A') ?: '—',
            ] as $label => $value)
                <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5"><dt class="text-xs font-black uppercase tracking-widest text-gray-400">{{ $label }}</dt><dd class="mt-2 font-bold text-gray-900 dark:text-white">{{ $value }}</dd></div>
            @endforeach
        </dl>
        <div class="mt-6 rounded-xl bg-gray-50 p-4 dark:bg-white/5"><div class="text-xs font-black uppercase tracking-widest text-gray-400">Details</div><p class="mt-2 whitespace-pre-wrap text-sm leading-6 text-gray-700 dark:text-gray-200">{{ $corporateAction->details ?: 'No additional details were supplied.' }}</p></div>
    </div>
</div>
@endsection
