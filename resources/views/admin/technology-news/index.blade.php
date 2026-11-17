@extends('layouts.app')
@section('header', 'Technology News')
@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div><h1 class="text-3xl font-black text-gray-900 dark:text-white">Technology News</h1><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Compare source and regenerated copy, generate full reports, and control publication.</p></div>
        <div class="flex flex-wrap gap-2"><x-admin-sync-button title="news:sync" label="Fetch News" /><x-admin-sync-button title="news:rewrite" label="Process Pending" class="bg-blue-600 hover:bg-blue-700 text-white" icon="fa-wand-magic-sparkles" /><a href="{{ route('technology-news.index') }}" class="rounded-xl bg-gray-900 px-5 py-3 text-center text-sm font-black text-white hover:bg-gray-800 dark:bg-white dark:text-gray-900">View Public News</a></div>
    </div>
    @include('admin.technology-news.navigation')
    @include('admin.technology-news.flash')
    <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-6">@foreach($summary as $label => $value)<div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/5 dark:bg-richdark-surface"><div class="text-xs font-bold text-gray-500 dark:text-gray-400">{{ $label }}</div><div class="mt-2 text-2xl font-black text-gray-900 dark:text-white">{{ number_format($value) }}</div></div>@endforeach</div>
    <form method="GET" class="grid gap-3 rounded-2xl border border-gray-200 bg-white p-4 md:grid-cols-3 dark:border-white/5 dark:bg-richdark-surface">
        <input name="search" value="{{ request('search', request('q')) }}" maxlength="150" aria-label="Search news" placeholder="Headline, article or topic" class="rounded-xl border-gray-200 bg-transparent text-sm dark:border-white/10 dark:text-white">
        <select name="status" aria-label="Publication status" class="rounded-xl border-gray-200 bg-transparent text-sm dark:border-white/10 dark:text-white"><option value="">All statuses</option>@foreach(['published' => 'Published', 'pending' => 'Pending', 'processing' => 'Processing', 'failed' => 'Failed', 'hidden' => 'Hidden'] as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select>
        <button class="rounded-xl bg-gray-900 px-4 py-2 text-sm font-bold text-white dark:bg-white dark:text-gray-900">Filter</button>
    </form>
    <p class="text-sm text-gray-500 dark:text-gray-400">Successful full reports are verified and automatically published. Use Generate to process an individual story.</p>
    <div class="overflow-x-auto rounded-2xl border border-gray-200 bg-white dark:border-white/5 dark:bg-richdark-surface">
        <table class="w-full min-w-[1100px] text-left text-sm"><thead class="border-b border-gray-100 text-xs text-gray-500 dark:border-white/5 dark:text-gray-400"><tr><th class="p-4">Source title</th><th class="p-4">Regenerated title</th><th class="p-4">Source / category</th><th class="p-4">Status</th><th class="p-4">Story date</th><th class="p-4 text-right">Actions</th></tr></thead><tbody class="divide-y divide-gray-100 dark:divide-white/5">
            @forelse($articles as $article)
                @php
                    $status = $article->status === 'hidden' ? 'hidden' : $article->editorial_status;
                    $statusClasses = match($status) {
                        'published' => 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-300',
                        'failed' => 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300',
                        'processing' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300',
                        'hidden' => 'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300',
                        default => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
                    };
                @endphp
                <tr class="align-top"><td class="p-4"><div class="max-w-sm font-bold text-gray-900 dark:text-white">{{ $article->title }}</div>@if($article->description)<details class="mt-2 max-w-sm text-xs text-gray-500 dark:text-gray-400"><summary class="cursor-pointer font-semibold">Source summary</summary><p class="mt-2 leading-5">{{ $article->description }}</p></details>@endif</td>
                <td class="p-4"><div class="max-w-sm font-bold text-gray-900 dark:text-white">{{ $article->ai_title ?: 'Not regenerated yet' }}</div>@if($article->ai_summary)<p class="mt-2 max-w-sm text-xs leading-5 text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Str::limit($article->ai_summary, 200) }}</p>@endif @if($article->rewrite_error)<p class="mt-2 max-w-sm rounded-lg bg-red-50 p-2 text-xs text-red-700 dark:bg-red-500/10 dark:text-red-300">{{ \Illuminate\Support\Str::limit($article->rewrite_error, 180) }}</p>@endif</td>
                <td class="p-4"><p class="font-bold text-gray-700 dark:text-gray-200">{{ $article->source->name }}</p><p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $article->category->name }}</p></td>
                <td class="p-4"><span class="whitespace-nowrap rounded-full px-2 py-1 text-xs font-bold {{ $statusClasses }}">{{ ucfirst($status) }}</span>@if($article->rewrite_retry_at?->isFuture())<p class="mt-2 text-xs text-amber-700 dark:text-amber-300">Retry after {{ $article->rewrite_retry_at->copy()->timezone('Asia/Kolkata')->format('d M, h:i A') }} IST</p>@endif</td>
                <td class="p-4 text-gray-600 dark:text-gray-300">{{ $article->published_at->copy()->timezone('Asia/Kolkata')->format('d M Y, h:i A') }} IST</td><td class="p-4">@include('admin.technology-news.actions')</td></tr>
            @empty<tr><td colspan="6" class="p-10 text-center text-gray-500 dark:text-gray-400">No technology news records found. Fetch News to import full reports from approved sources.</td></tr>@endforelse
        </tbody></table>
    </div>{{ $articles->links() }}
</div>
@endsection
