@extends('layouts.app')

@section('header', 'Cron Jobs')

@section('content')
<style>
    @keyframes cron-progress {
        0% { transform: translateX(-100%); }
        50% { transform: translateX(75%); }
        100% { transform: translateX(250%); }
    }

    .cron-progress-bar {
        width: 40%;
        animation: cron-progress 1.5s ease-in-out infinite;
    }

    @media (max-width: 767px) {
        .cron-table thead { display: none; }
        .cron-table,
        .cron-table tbody,
        .cron-table tr,
        .cron-table td { display: block; width: 100%; }
        .cron-table tbody { padding: 0.75rem; }
        .cron-table tr {
            overflow: hidden;
            border: 1px solid rgb(229 231 235);
            border-radius: 1rem;
        }
        .cron-table tr + tr { margin-top: 0.75rem; }
        .dark .cron-table tr { border-color: rgb(255 255 255 / 0.1); }
        .cron-table td {
            display: grid;
            grid-template-columns: minmax(5.5rem, 30%) minmax(0, 1fr);
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            text-align: left;
        }
        .cron-table td + td { border-top: 1px solid rgb(243 244 246); }
        .dark .cron-table td + td { border-color: rgb(255 255 255 / 0.05); }
        .cron-table td::before {
            content: attr(data-label);
            color: rgb(156 163 175);
            font-size: 0.625rem;
            font-weight: 900;
            letter-spacing: 0.1em;
            line-height: 1.25rem;
            text-transform: uppercase;
        }
        .cron-table td:first-child { display: block; }
        .cron-table td:first-child::before {
            display: block;
            margin-bottom: 0.5rem;
        }
        .cron-table td:last-child > * { width: 100%; justify-content: center; }
    }
</style>
<div class="max-w-7xl mx-auto">
    <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white sm:text-3xl">Scheduled Cron Jobs</h1>
            <p class="mt-2 text-sm font-medium text-gray-500 dark:text-gray-400">View scheduled tasks, trigger them manually, and inspect the latest execution output.</p>
        </div>
        <div class="flex w-full flex-col gap-3 sm:flex-row md:w-auto">
            <button id="request-upstox-token" onclick="requestUpstoxToken(this)" class="inline-flex items-center justify-center rounded-2xl border border-amber-600 bg-white px-6 py-3 text-sm font-black text-amber-700 shadow-lg transition-all hover:bg-amber-50 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-richdark-surface dark:text-amber-400 dark:hover:bg-white/5">
                <i class="fas fa-key mr-2 text-xs"></i> Generate Setu Token
            </button>
            <a href="{{ route('admin.crons.logs') }}" class="inline-flex items-center justify-center rounded-2xl bg-amber-600 px-6 py-3 text-sm font-black text-white shadow-xl transition-all hover:scale-[1.02] hover:bg-amber-700 active:scale-[0.98]">
                <i class="fas fa-list-alt mr-2 text-xs"></i> View All Logs
            </a>
        </div>
    </div>

    @php
        $tokenIsActive = $upstoxToken?->status === 'active' && $upstoxToken?->expires_at?->isFuture();
        $tokenIsPending = $upstoxToken?->authorization_expires_at?->isFuture();
    @endphp
    <div class="mb-6 flex flex-col gap-2 rounded-2xl border px-5 py-4 text-sm sm:flex-row sm:items-center sm:justify-between {{ $tokenIsActive ? 'border-green-200 bg-green-50 text-green-800 dark:border-green-500/20 dark:bg-green-500/10 dark:text-green-300' : ($tokenIsPending ? 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-300' : 'border-red-200 bg-red-50 text-red-800 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300') }}">
        <div class="font-bold">
            <i class="fas {{ $tokenIsActive ? 'fa-check-circle' : ($tokenIsPending ? 'fa-clock' : 'fa-exclamation-circle') }} mr-2"></i>
            Upstox token: {{ $tokenIsActive ? 'Active' : ($tokenIsPending ? 'Awaiting approval' : 'Unavailable') }}
        </div>
        <div class="text-xs font-semibold opacity-80">
            @if($tokenIsActive)
                Expires {{ $upstoxToken->expires_at->timezone('Asia/Kolkata')->format('d M Y, H:i') }} IST
            @elseif($tokenIsPending)
                Approve before {{ $upstoxToken->authorization_expires_at->timezone('Asia/Kolkata')->format('d M Y, H:i') }} IST
            @else
                Generate a request, then approve it in Upstox.
            @endif
        </div>
    </div>

    <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-xl dark:border-white/5 dark:bg-richdark-surface">
        <table class="cron-table w-full border-separate border-spacing-0 text-left">
            <thead>
                <tr>
                    <th class="border-b border-gray-100 px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:border-white/5 dark:text-gray-500">Command</th>
                    <th class="border-b border-gray-100 px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:border-white/5 dark:text-gray-500">Schedule</th>
                    <th class="border-b border-gray-100 px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:border-white/5 dark:text-gray-500">Last Execution</th>
                    <th class="border-b border-gray-100 px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:border-white/5 dark:text-gray-500">Total Runs</th>
                    <th class="border-b border-gray-100 px-6 py-4 text-right text-[10px] font-black uppercase tracking-widest text-gray-400 dark:border-white/5 dark:text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-white/5">
                @foreach($crons as $cron)
                @php
                    $lastRun = $cron['last_run'];
                    $lastRan = $lastRun?->ran_at;
                    $isRunning = $lastRun && $lastRun->finished_at === null;
                    $isRecent = $lastRan && $lastRan->gt(now()->subHours(26));
                    $isMaintenance = str_contains(strtolower($cron['schedule']), 'manual one-time maintenance');
                    $isRunnable = $cron['runnable'] ?? true;
                    $stateLabel = !$lastRun ? 'Never' : ($isRunning ? 'Running' : (!$lastRun->status ? 'Failed' : ($isRecent ? 'Healthy' : 'Completed')));
                    $stateClasses = !$lastRun
                        ? 'bg-gray-100 text-gray-400 dark:bg-white/5'
                        : ($isRunning
                            ? 'bg-blue-500/10 text-blue-600 dark:text-blue-400'
                            : (!$lastRun->status
                                ? 'bg-red-500/10 text-red-600 dark:text-red-400'
                                : ($isRecent ? 'bg-green-500/10 text-green-600 dark:text-green-400' : 'bg-yellow-500/10 text-yellow-600 dark:text-yellow-400')));
                    $dotClasses = !$lastRun ? 'bg-gray-400' : ($isRunning ? 'bg-blue-500 animate-pulse' : (!$lastRun->status ? 'bg-red-500' : ($isRecent ? 'bg-green-500 animate-pulse' : 'bg-yellow-500')));
                @endphp
                <tr class="transition-colors hover:bg-gray-50/50 dark:hover:bg-white/[0.02]">
                    <td data-label="Command" class="px-6 py-5">
                        <div class="flex items-start gap-3 md:items-center">
                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-widest {{ $stateClasses }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $dotClasses }}"></span>
                                {{ $stateLabel }}
                            </span>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <code class="block break-words text-sm font-bold text-amber-600 dark:text-amber-500">{{ $cron['title'] }}</code>
                                    @if($isMaintenance)
                                        <span class="inline-flex items-center rounded-full bg-sky-500/10 px-2 py-0.5 text-[10px] font-black uppercase tracking-widest text-sky-600 dark:text-sky-400">
                                            Maintenance
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">{{ $cron['description'] }}</p>
                            </div>
                        </div>
                    </td>
                    <td data-label="Schedule" class="px-6 py-5">
                        <div>
                        <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ $cron['schedule'] }}</span>
                        <span class="mt-0.5 block text-xs text-gray-400 dark:text-gray-500">{{ $cron['timezone'] }}</span>
                        @if($cron['overlap'])
                            <span class="mt-1 inline-flex items-center gap-1 text-[10px] font-bold text-blue-500 dark:text-blue-400">
                                <i class="fas fa-shield-alt text-[9px]"></i> No overlap
                            </span>
                        @endif
                        </div>
                    </td>
                    <td data-label="Last execution" class="px-6 py-5">
                        <div>
                        @if($lastRan)
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ $lastRan->diffForHumans() }}</span>
                            <span class="mt-0.5 block text-xs text-gray-400 dark:text-gray-500">{{ $lastRan->format('d M Y, H:i') }}</span>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span class="rounded-full px-2 py-0.5 text-[9px] font-black uppercase tracking-widest {{ $isRunning ? 'bg-blue-500/10 text-blue-600 dark:text-blue-400' : ($lastRun->status ? 'bg-green-500/10 text-green-600 dark:text-green-400' : 'bg-red-500/10 text-red-600 dark:text-red-400') }}">
                                    {{ $isRunning ? 'Running' : ($lastRun->status ? 'Success' : 'Failed') }}@if($lastRun->exit_code !== null) · Exit {{ $lastRun->exit_code }}@endif
                                </span>
                                <span class="rounded-full bg-blue-500/10 px-2 py-0.5 text-[9px] font-black uppercase tracking-widest text-blue-600 dark:text-blue-400">{{ $lastRun->source ?: 'unknown' }}</span>
                                @if($lastRun->started_at && $lastRun->finished_at)
                                    <span class="text-[10px] font-bold text-gray-400">{{ $lastRun->started_at->diffForHumans($lastRun->finished_at, true) }}</span>
                                @endif
                            </div>
                            @if($isRunning)
                                <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-blue-100 dark:bg-blue-500/10" role="progressbar" aria-label="Cron job is running">
                                    <div class="cron-progress-bar h-full rounded-full bg-blue-500"></div>
                                </div>
                            @endif
                            @if(!$isRunning && !$lastRun->status && filled($lastRun->output))
                                <details class="mt-3 max-w-md text-left">
                                    <summary class="cursor-pointer text-xs font-black text-red-600 dark:text-red-400">Show failure reason</summary>
                                    <pre class="mt-2 max-h-40 overflow-auto whitespace-pre-wrap break-words rounded-xl bg-gray-900 p-3 text-[11px] leading-relaxed text-red-300">{{ $lastRun->output }}</pre>
                                </details>
                            @endif
                        @else
                            <span class="text-xs italic text-gray-400 dark:text-gray-600">No run recorded</span>
                        @endif
                        </div>
                    </td>
                    <td data-label="Total runs" class="px-6 py-5">
                        <a href="{{ route('admin.crons.logs', ['title' => $cron['title']]) }}" class="inline-flex items-center gap-1.5 text-sm font-black text-gray-700 transition-colors hover:text-amber-600 dark:text-gray-300 dark:hover:text-amber-500">
                            {{ number_format($cron['total_runs']) }}
                            <i class="fas fa-external-link-alt text-[10px] opacity-50"></i>
                        </a>
                    </td>
                    <td data-label="Actions" class="px-6 py-5 text-right">
                        @if($isRunnable)
                            <button onclick="runCron({{ Illuminate\Support\Js::from($cron['title']) }}, this)" class="run-btn inline-flex items-center gap-2 rounded-xl bg-amber-600 px-4 py-2 text-xs font-black text-white shadow-lg shadow-amber-600/20 transition-all hover:scale-[1.02] hover:bg-amber-700 active:scale-95 disabled:cursor-not-allowed disabled:opacity-60">
                                <i class="fas fa-play text-[10px]"></i> Run Now
                            </button>
                        @else
                            <span class="inline-flex items-center gap-2 rounded-xl bg-gray-100 px-4 py-2 text-xs font-black text-gray-500 dark:bg-white/5 dark:text-gray-400">
                                <i class="fas fa-terminal text-[10px]"></i> CLI only
                            </span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div id="cron-output-modal" class="fixed inset-0 z-[100] hidden overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm dark:bg-black/70" onclick="closeOutputModal()"></div>
        <div class="relative z-10 my-4 max-h-[calc(100vh-2rem)] w-full max-w-3xl overflow-y-auto rounded-2xl border border-gray-100 bg-white shadow-2xl dark:border-white/5 dark:bg-richdark-card sm:rounded-3xl">
            <div class="flex items-start justify-between gap-4 border-b border-gray-100 p-4 dark:border-white/5 sm:p-6">
                <div>
                    <h3 class="text-lg font-black tracking-tight text-gray-900 dark:text-white">Command Output</h3>
                    <p id="modal-cron-title" class="mt-0.5 text-xs font-mono text-amber-600 dark:text-amber-500"></p>
                </div>
                <button onclick="closeOutputModal()" aria-label="Close command output" class="shrink-0 rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/10 dark:hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-4 sm:p-6">
                <div id="modal-loading" class="text-sm text-gray-500 dark:text-gray-400">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-circle-notch fa-spin mt-0.5 text-amber-600"></i>
                        <div>
                            <p>Running command. Output will appear here as soon as the execution completes.</p>
                            <p id="modal-elapsed" class="mt-1 text-xs font-bold text-gray-400">Elapsed: 0s</p>
                        </div>
                    </div>
                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-amber-100 dark:bg-amber-500/10" role="progressbar" aria-label="Command is running">
                        <div class="cron-progress-bar h-full rounded-full bg-amber-600"></div>
                    </div>
                </div>
                <div id="modal-result" class="hidden">
                    <div id="modal-status-banner" class="mb-4 flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold"></div>
                    <pre id="modal-output" class="max-h-[32rem] overflow-auto whitespace-pre-wrap break-words rounded-xl bg-gray-900 p-4 text-xs leading-relaxed text-green-400"></pre>
                </div>
            </div>
            <div id="modal-footer" class="hidden justify-end px-4 pb-4 sm:px-6 sm:pb-6">
                <button onclick="closeOutputModal()" class="rounded-xl bg-gray-100 px-5 py-2 text-sm font-bold text-gray-700 transition-colors hover:bg-gray-200 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const RUN_URL = "{{ route('admin.crons.run') }}";
const TOKEN_URL = "{{ route('admin.crons.upstox-token') }}";
const CSRF = "{{ csrf_token() }}";
let cronElapsedTimer = null;
let activeRunButton = null;

function requestUpstoxToken(button) {
    if (button.disabled) return;

    button.disabled = true;
    button.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-2 text-xs"></i> Requesting';

    $.ajax({
        url: TOKEN_URL,
        type: 'POST',
        data: { _token: CSRF },
        success: function (res) {
            toastr.success(res.message);
            setTimeout(() => location.reload(), 1500);
        },
        error: function (xhr) {
            const err = xhr.responseJSON ?? {};
            toastr.error(err.message || 'The Upstox token request could not be created.');
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-key mr-2 text-xs"></i> Generate Setu Token';
        }
    });
}

function runCron(title, button) {
    if (activeRunButton) return;

    activeRunButton = button;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-circle-notch fa-spin text-[10px]"></i> Running';
    document.getElementById('cron-output-modal').classList.remove('hidden');
    document.getElementById('modal-cron-title').textContent = title;
    document.getElementById('modal-loading').classList.remove('hidden');
    document.getElementById('modal-result').classList.add('hidden');
    document.getElementById('modal-footer').classList.add('hidden');
    document.getElementById('modal-elapsed').textContent = 'Elapsed: 0s';

    const startedAt = Date.now();
    clearInterval(cronElapsedTimer);
    cronElapsedTimer = setInterval(function () {
        const seconds = Math.floor((Date.now() - startedAt) / 1000);
        const minutes = Math.floor(seconds / 60);
        document.getElementById('modal-elapsed').textContent = 'Elapsed: '
            + (minutes ? minutes + 'm ' : '') + (seconds % 60) + 's';
    }, 1000);

    $.ajax({
        url: RUN_URL,
        type: 'POST',
        data: { _token: CSRF, title },
        success: function (res) {
            showResult(res.success, res.message, res.output);
        },
        error: function (xhr) {
            const err = xhr.responseJSON ?? {};
            showResult(false, err.message || 'An unexpected error occurred.', err.output || '');
        }
    });
}

function showResult(success, message, output) {
    clearInterval(cronElapsedTimer);
    cronElapsedTimer = null;
    if (activeRunButton) {
        activeRunButton.disabled = false;
        activeRunButton.innerHTML = '<i class="fas fa-play text-[10px]"></i> Run Now';
        activeRunButton = null;
    }
    document.getElementById('modal-loading').classList.add('hidden');
    document.getElementById('modal-result').classList.remove('hidden');
    document.getElementById('modal-footer').classList.remove('hidden');

    const banner = document.getElementById('modal-status-banner');
    if (success) {
        banner.className = 'mb-4 flex items-center gap-2 rounded-xl bg-green-500/10 px-4 py-2.5 text-sm font-bold text-green-600 dark:text-green-400';
        banner.innerHTML = '<i class="fas fa-check-circle"></i> ' + message;
    } else {
        banner.className = 'mb-4 flex items-center gap-2 rounded-xl bg-red-500/10 px-4 py-2.5 text-sm font-bold text-red-600 dark:text-red-400';
        banner.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + message;
    }

    const out = document.getElementById('modal-output');
    out.textContent = output || '(no output)';
    out.style.display = 'block';

    if (success) {
        toastr.success(message);
        setTimeout(() => location.reload(), 2000);
    } else {
        toastr.error(message);
    }
}

function closeOutputModal() {
    document.getElementById('cron-output-modal').classList.add('hidden');
}
</script>
@endpush
