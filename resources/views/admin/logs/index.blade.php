@extends('layouts.app')

@section('title', 'Server Logs')

@section('content')
<div class="space-y-6">
    <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-slate-950">
        <div class="grid gap-6 px-6 py-6 xl:grid-cols-[1.5fr_1fr]">
            <div>
                <div class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-[11px] font-black uppercase tracking-[0.22em] text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
                    Monitoring
                </div>
                <h1 class="mt-4 text-3xl font-black tracking-tight text-slate-900 dark:text-white">Laravel Logs</h1>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                    Review application logs directly from the admin panel. The viewer shows the latest log files and the newest lines from the selected file for faster debugging.
                </p>
            </div>

            <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-5 dark:border-white/10 dark:bg-white/5">
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-white/10 dark:bg-slate-950">
                        <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-slate-400">Selected File</p>
                        <p class="mt-2 break-all text-sm font-black text-slate-900 dark:text-white">{{ $selectedFileName ?: 'No log file found' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-white/10 dark:bg-slate-950">
                        <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-slate-400">Visible Lines</p>
                        <p class="mt-2 text-2xl font-black text-slate-900 dark:text-white">{{ number_format($visibleLineCount) }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">of {{ number_format($totalLines) }} total lines</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[340px_minmax(0,1fr)]">
        <aside class="space-y-6">
            <section class="overflow-hidden rounded-[24px] border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-slate-950">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-white/10">
                    <h2 class="text-base font-black text-slate-900 dark:text-white">Log Files</h2>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Switch between files in `storage/logs`.</p>
                </div>
                <div class="max-h-[420px] space-y-3 overflow-y-auto p-4">
                    @forelse($logFiles as $file)
                        <a href="{{ route('admin.logs', ['file' => $file['name']]) }}" class="block rounded-2xl border px-4 py-4 transition {{ $selectedFileName === $file['name'] ? 'border-amber-300 bg-amber-50 dark:border-amber-500/30 dark:bg-amber-500/10' : 'border-slate-200 bg-white hover:bg-slate-50 dark:border-white/10 dark:bg-slate-950 dark:hover:bg-white/5' }}">
                            <p class="break-all text-sm font-black text-slate-900 dark:text-white">{{ $file['name'] }}</p>
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ $file['size_kb'] }} KB</p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $file['updated_at'] }}</p>
                        </a>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-sm text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-slate-400">
                            No `.log` files found in `storage/logs`.
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="overflow-hidden rounded-[24px] border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-slate-950">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-white/10">
                    <h2 class="text-base font-black text-slate-900 dark:text-white">Severity Snapshot</h2>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Counts from the selected file.</p>
                </div>
                <div class="grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-1">
                    @foreach($levelCounts as $level => $count)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-white/10 dark:bg-white/5">
                            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-400">{{ $level }}</p>
                            <p class="mt-2 text-xl font-black text-slate-900 dark:text-white">{{ number_format($count) }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        </aside>

        <section class="overflow-hidden rounded-[24px] border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-slate-950">
            <div class="border-b border-slate-200 px-6 py-4 dark:border-white/10">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-base font-black text-slate-900 dark:text-white">Log Output</h2>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Showing the latest {{ number_format($visibleLineCount) }} lines from {{ $selectedFileName ?: 'the selected file' }}.
                        </p>
                    </div>
                    @if($selectedFileName)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600 dark:border-white/10 dark:bg-white/5 dark:text-slate-300">
                            Updated {{ $selectedFileUpdatedAt }} • {{ $selectedFileSizeKb }} KB
                        </div>
                    @endif
                </div>
            </div>

            <div class="p-6">
                @if($selectedFileName && $visibleLineCount > 0)
                    <div class="mb-4">
                        <input id="logSearch" type="text" placeholder="Filter visible log lines"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-100 dark:border-white/10 dark:bg-white/5 dark:text-white dark:placeholder:text-slate-500 dark:focus:border-amber-500 dark:focus:ring-amber-500/10">
                    </div>
                    <div id="logViewer" class="max-h-[70vh] overflow-auto rounded-[22px] border border-slate-200 bg-slate-950 p-5 font-mono text-xs leading-6 text-slate-100 dark:border-white/10">
                        @foreach($visibleLines as $line)
                            @php
                                $lower = strtolower($line);
                                $tone = 'text-slate-200';
                                if (str_contains($lower, '.error:') || str_contains($lower, '.critical:') || str_contains($lower, '.emergency:') || str_contains($lower, '.alert:')) {
                                    $tone = 'text-rose-300';
                                } elseif (str_contains($lower, '.warning:')) {
                                    $tone = 'text-amber-300';
                                } elseif (str_contains($lower, '.info:') || str_contains($lower, '.notice:')) {
                                    $tone = 'text-sky-300';
                                } elseif (str_contains($lower, '.debug:')) {
                                    $tone = 'text-emerald-300';
                                }
                            @endphp
                            <div class="log-line {{ $tone }} break-words border-b border-white/5 py-1 last:border-b-0">{{ $line }}</div>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-sm text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-slate-400">
                        No log content available to display.
                    </div>
                @endif
            </div>
        </section>
    </section>
</div>
@endsection

@push('scripts')
<script>
    const logSearch = document.getElementById('logSearch');

    if (logSearch) {
        logSearch.addEventListener('input', () => {
            const term = logSearch.value.trim().toLowerCase();
            document.querySelectorAll('.log-line').forEach((line) => {
                line.classList.toggle('hidden', term && !line.textContent.toLowerCase().includes(term));
            });
        });
    }
</script>
@endpush
