@extends('layouts.app')

@section('header', 'Review Market News')

@section('content')
@php
    $statusClasses = match($marketNews->editorial_status) {
        'published' => 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-300',
        'ready' => 'bg-purple-100 text-purple-700 dark:bg-purple-500/10 dark:text-purple-300',
        'failed' => 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300',
        'processing' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300',
        default => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
    };
@endphp
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <a href="{{ route('admin.market-news.index') }}" class="text-sm font-bold text-amber-600 hover:text-amber-700"><i class="fas fa-arrow-left mr-2"></i>Back to Market News</a>
            <div class="mt-3 flex flex-wrap items-center gap-3"><h1 class="text-3xl font-black text-gray-900 dark:text-white">Review News #{{ $marketNews->id }}</h1><span class="rounded-full px-3 py-1 text-xs font-bold {{ $statusClasses }}">{{ $marketNews->editorial_status === 'ready' ? 'Ready for approval' : ucfirst($marketNews->editorial_status) }}</span></div>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $marketNews->symbol ?: 'No symbol' }} · {{ $marketNews->isin ?: 'No ISIN' }} · {{ $marketNews->published_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A') ?: 'No story date' }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <form method="POST" action="{{ route('admin.market-news.regenerate', $marketNews) }}" onsubmit="return confirm('Refresh the Upstox source and regenerate this story?');">@csrf<button class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-black text-white hover:bg-blue-700"><i class="fas fa-rotate mr-2"></i>{{ $marketNews->rewritten_at ? 'Regenerate' : 'Generate' }}</button></form>
            @if($marketNews->editorial_status === 'ready')<form method="POST" action="{{ route('admin.market-news.approve', $marketNews) }}">@csrf<button class="rounded-xl bg-green-600 px-4 py-2.5 text-sm font-black text-white hover:bg-green-700"><i class="fas fa-check mr-2"></i>Approve & Publish</button></form>@endif
            @if($marketNews->is_published)<form method="POST" action="{{ route('admin.market-news.unpublish', $marketNews) }}" onsubmit="return confirm('Remove this story from the public website?');">@csrf<button class="rounded-xl bg-gray-700 px-4 py-2.5 text-sm font-black text-white hover:bg-gray-800"><i class="fas fa-eye-slash mr-2"></i>Unpublish</button></form>@endif
        </div>
    </div>

    @if(session('success') || session('error'))
        <div class="rounded-xl border px-4 py-3 text-sm font-semibold {{ session('error') ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300' : 'border-green-200 bg-green-50 text-green-700 dark:border-green-500/20 dark:bg-green-500/10 dark:text-green-300' }}">{{ session('error') ?: session('success') }}</div>
    @endif
    @if($marketNews->rewrite_error)<div class="rounded-xl border {{ $marketNews->rewrite_retry_at?->isFuture() ? 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300' : 'border-red-200 bg-red-50 text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300' }} p-4 text-sm"><div class="font-black">{{ $marketNews->rewrite_retry_at?->isFuture() ? 'Generation deferred' : 'Regeneration error' }}</div><div class="mt-1 whitespace-pre-wrap">{{ $marketNews->rewrite_error }}</div>@if($marketNews->rewrite_retry_at?->isFuture())<div class="mt-2 font-bold">Automatic retry after {{ $marketNews->rewrite_retry_at->timezone('Asia/Kolkata')->format('d M Y, h:i:s A') }}</div>@endif</div>@endif

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/5 dark:bg-richdark-surface">
            <div class="flex items-center justify-between gap-3"><h2 class="text-lg font-black text-gray-900 dark:text-white">Upstox source</h2>@if($marketNews->article_url)<a href="{{ $marketNews->article_url }}" target="_blank" rel="noopener noreferrer" class="text-xs font-bold text-amber-600 hover:text-amber-700">Open original <i class="fas fa-arrow-up-right-from-square ml-1"></i></a>@endif</div>
            <div class="mt-5 text-xs font-black uppercase tracking-wider text-gray-400">Title</div>
            <h3 class="mt-2 text-xl font-black leading-8 text-gray-900 dark:text-white">{{ $marketNews->original_title ?: 'No source title' }}</h3>
            <div class="mt-6 text-xs font-black uppercase tracking-wider text-gray-400">Summary supplied by Upstox</div>
            <div class="mt-2 whitespace-pre-line text-sm leading-7 text-gray-700 dark:text-gray-300">{{ $marketNews->original_summary ?: 'No source summary was supplied.' }}</div>
            <div class="mt-6 text-xs font-black uppercase tracking-wider text-gray-400">Extracted source article</div>
            <div class="mt-2 max-h-[32rem] overflow-y-auto whitespace-pre-line rounded-xl bg-gray-50 p-4 text-sm leading-7 text-gray-700 dark:bg-black/20 dark:text-gray-300">{{ $marketNews->original_content ?: 'The full source article has not been fetched yet. Generate the story to fetch and validate it.' }}</div>
            <div class="mt-3 text-xs text-gray-400">Source fetched: {{ $marketNews->source_fetched_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A') ?: 'Not yet' }}</div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/5 dark:bg-richdark-surface">
            <h2 class="text-lg font-black text-gray-900 dark:text-white">Regenerated draft</h2>
            <div class="mt-5 text-xs font-black uppercase tracking-wider text-gray-400">Title</div>
            <h3 class="mt-2 text-xl font-black leading-8 text-gray-900 dark:text-white">{{ $marketNews->rewritten_at ? $marketNews->title : 'Not regenerated yet' }}</h3>
            <div class="mt-6 text-xs font-black uppercase tracking-wider text-gray-400">Article</div>
            <div class="mt-2 max-h-[42rem] overflow-y-auto whitespace-pre-line rounded-xl bg-gray-50 p-4 text-sm leading-7 text-gray-700 dark:bg-black/20 dark:text-gray-300">{{ $marketNews->rewritten_at ? $marketNews->summary : 'Generate this story to create a verified draft.' }}</div>
            <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-2">
                <div><dt class="text-xs font-black uppercase tracking-wider text-gray-400">Model</dt><dd class="mt-1 font-semibold text-gray-700 dark:text-gray-200">{{ $marketNews->rewrite_model ?: '—' }}</dd></div>
                <div><dt class="text-xs font-black uppercase tracking-wider text-gray-400">Version</dt><dd class="mt-1 font-semibold text-gray-700 dark:text-gray-200">{{ $marketNews->rewrite_version ?: '—' }}</dd></div>
                <div><dt class="text-xs font-black uppercase tracking-wider text-gray-400">Regenerated</dt><dd class="mt-1 font-semibold text-gray-700 dark:text-gray-200">{{ $marketNews->rewritten_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A') ?: '—' }}</dd></div>
                <div><dt class="text-xs font-black uppercase tracking-wider text-gray-400">Approved</dt><dd class="mt-1 font-semibold text-gray-700 dark:text-gray-200">{{ $marketNews->reviewed_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A') ?: '—' }}</dd></div>
            </dl>
        </section>
    </div>
</div>
@endsection
