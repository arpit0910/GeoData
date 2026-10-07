@extends('layouts.app')

@section('header', 'Market News')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white">Market News</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Monitor synchronized stories and their automated editorial status.</p>
        </div>
        <a href="{{ route('market.news') }}" target="_blank" rel="noopener" class="rounded-xl bg-amber-600 px-5 py-3 text-center text-sm font-black text-white hover:bg-amber-700">
            <i class="fas fa-arrow-up-right-from-square mr-2"></i>View Public News
        </a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach(['Total' => $summary['total'], 'Published' => $summary['published'], 'Pending' => $summary['pending'], 'Failed' => $summary['failed']] as $label => $value)
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/5 dark:bg-richdark-surface">
                <div class="text-xs font-black uppercase tracking-widest text-gray-400">{{ $label }}</div>
                <div class="mt-2 text-2xl font-black text-gray-900 dark:text-white">{{ number_format($value) }}</div>
            </div>
        @endforeach
    </div>

    <form method="GET" class="grid gap-3 rounded-2xl border border-gray-200 bg-white p-4 md:grid-cols-3 dark:border-white/5 dark:bg-richdark-surface">
        <input name="search" value="{{ request('search') }}" placeholder="Headline, symbol or ISIN" class="rounded-xl border-gray-200 bg-transparent text-sm dark:border-white/10 dark:text-white">
        <select name="status" class="rounded-xl border-gray-200 bg-transparent text-sm dark:border-white/10 dark:text-white">
            <option value="">All statuses</option>
            @foreach(['published' => 'Published', 'pending' => 'Pending', 'processing' => 'Processing', 'failed' => 'Failed'] as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="rounded-xl bg-gray-900 px-4 py-2 text-sm font-bold text-white dark:bg-white dark:text-gray-900">Filter</button>
    </form>

    <div class="overflow-x-auto rounded-2xl border border-gray-200 bg-white dark:border-white/5 dark:bg-richdark-surface">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead class="border-b border-gray-100 text-xs uppercase tracking-wider text-gray-400 dark:border-white/5">
                <tr><th class="p-4">Story</th><th class="p-4">Instrument</th><th class="p-4">Status</th><th class="p-4">Published</th><th class="p-4">Editorial details</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                @forelse($news as $story)
                    @php
                        $statusClasses = match($story->editorial_status) {
                            'published' => 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-300',
                            'failed' => 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300',
                            'processing' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300',
                            default => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
                        };
                    @endphp
                    <tr class="align-top">
                        <td class="p-4"><div class="max-w-xl font-bold text-gray-900 dark:text-white">{{ $story->title ?: $story->original_title }}</div>@if($story->article_url)<a href="{{ $story->article_url }}" target="_blank" rel="noopener noreferrer" class="mt-1 inline-block text-xs font-semibold text-amber-600 hover:text-amber-700">Open source article</a>@endif</td>
                        <td class="p-4"><div class="font-bold text-gray-700 dark:text-gray-200">{{ $story->symbol ?: '—' }}</div><div class="text-xs text-gray-400">{{ $story->isin ?: '—' }}</div></td>
                        <td class="p-4"><span class="rounded-full px-2 py-1 text-xs font-bold {{ $statusClasses }}">{{ ucfirst($story->editorial_status) }}</span></td>
                        <td class="p-4 text-gray-600 dark:text-gray-300">{{ $story->published_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A') ?: '—' }}</td>
                        <td class="p-4"><div class="max-w-md text-xs text-gray-500 dark:text-gray-400">{{ $story->rewrite_error ?: ($story->rewrite_model ? 'Model: '.$story->rewrite_model : 'No error reported.') }}</div></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-10 text-center text-gray-400">No market news records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $news->links() }}
</div>
@endsection
