@extends('layouts.app')

@section('title', 'Laravel Logs')
@section('header', 'Laravel Logs')

@section('content')
<div class="space-y-6 text-slate-900 dark:text-slate-100">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Laravel Logs</h1>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Newest entries first. Open an entry to inspect its context and stack trace.</p>
        </div>
        <a href="{{ route('admin.logs', array_filter(['file' => $selectedFileName, 'level' => $level, 'q' => $search])) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold hover:bg-slate-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-600 dark:border-slate-600 dark:hover:bg-slate-800">Refresh logs</a>
    </div>

    <form method="GET" action="{{ route('admin.logs') }}" class="grid items-end gap-4 rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-950 sm:grid-cols-2 xl:grid-cols-[minmax(180px,1fr)_160px_minmax(240px,2fr)_auto]">
        <div class="min-w-0">
            <label for="log-file" class="mb-2 block text-sm font-medium">Log file</label>
            <select id="log-file" name="file" class="w-full rounded-lg border-slate-300 bg-white text-sm focus:border-amber-600 focus:ring-amber-600 dark:border-slate-600 dark:bg-slate-900">
                @forelse($logFiles as $file)
                    <option value="{{ $file['name'] }}" @selected($selectedFileName === $file['name'])>{{ $file['name'] }} ({{ number_format($file['size_kb']) }} KB)</option>
                @empty
                    <option value="">No log files</option>
                @endforelse
            </select>
        </div>
        <div>
            <label for="log-level" class="mb-2 block text-sm font-medium">Severity</label>
            <select id="log-level" name="level" class="w-full rounded-lg border-slate-300 bg-white text-sm focus:border-amber-600 focus:ring-amber-600 dark:border-slate-600 dark:bg-slate-900">
                <option value="">All levels</option>
                @foreach($levelCounts as $name => $count)
                    <option value="{{ $name }}" @selected($level === $name)>{{ ucfirst($name) }} ({{ number_format($count) }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="log-search" class="mb-2 block text-sm font-medium">Search messages and traces</label>
            <input id="log-search" name="q" type="search" maxlength="200" value="{{ $search }}" placeholder="Exception, command or message" class="w-full rounded-lg border-slate-300 bg-white text-sm placeholder:text-slate-500 focus:border-amber-600 focus:ring-amber-600 dark:border-slate-600 dark:bg-slate-900 dark:placeholder:text-slate-400">
        </div>
        <button type="submit" class="rounded-lg bg-amber-700 px-5 py-2 text-sm font-semibold text-white hover:bg-amber-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-600">Apply filters</button>
    </form>

    <section aria-label="Log entries" class="min-w-0 overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-950">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4 text-sm dark:border-slate-700">
            <p><span class="font-semibold">{{ number_format(count($entries)) }}</span> shown / {{ number_format($matchingEntries) }} matching entries <span class="text-slate-500 dark:text-slate-400">({{ number_format($totalEntries) }} in file)</span></p>
            @if($selectedFileName)
                <p class="text-xs text-slate-500 dark:text-slate-400">Updated {{ $selectedFileUpdatedAt }}</p>
            @endif
        </div>
        @if($readError)
            <p role="alert" class="p-6 text-sm text-rose-700 dark:text-rose-300">{{ $readError }}</p>
        @elseif(count($entries))
            @if($matchingEntries > count($entries))
                <p class="border-b border-slate-200 px-5 py-3 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">Showing the latest {{ count($entries) }} matches. Narrow your search to find older entries.</p>
            @endif
            <div class="divide-y divide-slate-200 dark:divide-slate-700">
                @foreach($entries as $entry)
                    @php
                        $isError = in_array($entry['level'], ['error', 'critical', 'alert', 'emergency']);
                        $tone = $isError ? 'bg-rose-50 text-rose-800 dark:bg-rose-950 dark:text-rose-200' : ($entry['level'] === 'warning' ? 'bg-amber-50 text-amber-900 dark:bg-amber-950 dark:text-amber-200' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200');
                    @endphp
                    <details class="log-entry group">
                        <summary class="cursor-pointer px-5 py-4 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-inset focus-visible:outline-amber-600 dark:hover:bg-slate-900">
                            <span class="ml-1 inline-flex max-w-full flex-wrap items-center gap-x-3 gap-y-2 align-middle">
                                <span class="rounded px-2 py-1 text-xs font-semibold uppercase {{ $tone }}">{{ $entry['level'] }}</span>
                                <time class="text-xs tabular-nums text-slate-600 dark:text-slate-300">{{ $entry['timestamp'] ?: 'No timestamp' }}</time>
                                <span class="text-xs text-slate-500 dark:text-slate-400">{{ $entry['channel'] ?: 'Unstructured log' }} &middot; line {{ number_format($entry['line']) }}</span>
                            </span>
                            <span class="mt-2 block break-words text-sm font-medium leading-6 [overflow-wrap:anywhere]">{{ $entry['message'] ?: 'Empty log message' }}</span>
                            <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400"><span class="group-open:hidden">View details</span><span class="hidden group-open:inline">Hide details</span></span>
                        </summary>
                        <div class="border-t border-slate-200 bg-slate-50 p-5 dark:border-slate-700 dark:bg-slate-900">
                            <p class="mb-3 text-xs font-semibold text-slate-600 dark:text-slate-300">Message, context and stack trace</p>
                            @if($entry['truncated'])
                                <p class="mb-3 text-sm text-amber-800 dark:text-amber-200">This entry exceeds the display limit. Showing its first 64 KB; the complete entry remains in the log file.</p>
                            @endif
                            <pre class="max-h-[32rem] overflow-auto whitespace-pre-wrap break-words font-mono text-xs leading-6 [overflow-wrap:anywhere]">{{ rtrim($entry['details']) }}</pre>
                        </div>
                    </details>
                @endforeach
            </div>
        @else
            <div class="px-5 py-12 text-center">
                <h2 class="font-semibold">{{ $selectedFileName ? 'No matching log entries' : 'No log files available' }}</h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ $selectedFileName ? 'Try another severity or search term, or refresh after new activity.' : 'Log files will appear here when the application records activity.' }}</p>
                @if($level !== '' || $search !== '')
                    <a href="{{ route('admin.logs', ['file' => $selectedFileName]) }}" class="mt-4 inline-block text-sm font-semibold text-amber-800 underline underline-offset-4 dark:text-amber-300">Clear filters</a>
                @endif
            </div>
        @endif
    </section>
</div>
@endsection
