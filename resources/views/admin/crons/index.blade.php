@extends('layouts.app')

@section('header', 'Cron Jobs')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-3xl font-black tracking-tight text-gray-900 dark:text-white">Scheduled Cron Jobs</h1>
            <p class="mt-2 text-sm font-medium text-gray-500 dark:text-gray-400">View scheduled tasks, trigger them manually, and inspect the latest execution output.</p>
        </div>
        <a href="{{ route('admin.crons.logs') }}" class="inline-flex items-center justify-center rounded-2xl bg-amber-600 px-6 py-3 text-sm font-black text-white shadow-xl transition-all hover:scale-[1.02] hover:bg-amber-700 active:scale-[0.98]">
            <i class="fas fa-list-alt mr-2 text-xs"></i> View All Logs
        </a>
    </div>

    <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-xl dark:border-white/5 dark:bg-richdark-surface">
        <table class="w-full border-separate border-spacing-0 text-left">
            <thead>
                <tr>
                    <th class="border-b border-gray-100 px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:border-white/5 dark:text-gray-500">Command</th>
                    <th class="border-b border-gray-100 px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:border-white/5 dark:text-gray-500">Schedule</th>
                    <th class="border-b border-gray-100 px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:border-white/5 dark:text-gray-500">Last Run</th>
                    <th class="border-b border-gray-100 px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:border-white/5 dark:text-gray-500">Total Runs</th>
                    <th class="border-b border-gray-100 px-6 py-4 text-right text-[10px] font-black uppercase tracking-widest text-gray-400 dark:border-white/5 dark:text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-white/5">
                @foreach($crons as $cron)
                @php
                    $lastRan = $cron['last_ran_at'] ? \Carbon\Carbon::parse($cron['last_ran_at']) : null;
                    $isRecent = $lastRan && $lastRan->gt(now()->subHours(26));
                @endphp
                <tr class="transition-colors hover:bg-gray-50/50 dark:hover:bg-white/[0.02]">
                    <td class="px-6 py-5">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-widest {{ $isRecent ? 'bg-green-500/10 text-green-600 dark:text-green-400' : ($lastRan ? 'bg-yellow-500/10 text-yellow-600 dark:text-yellow-400' : 'bg-gray-100 text-gray-400 dark:bg-white/5') }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $isRecent ? 'bg-green-500 animate-pulse' : ($lastRan ? 'bg-yellow-500' : 'bg-gray-400') }}"></span>
                                {{ $isRecent ? 'Active' : ($lastRan ? 'Idle' : 'Never') }}
                            </span>
                            <div>
                                <code class="block text-sm font-bold text-amber-600 dark:text-amber-500">{{ $cron['title'] }}</code>
                                <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">{{ $cron['description'] }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-5">
                        <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ $cron['schedule'] }}</span>
                        <span class="mt-0.5 block text-xs text-gray-400 dark:text-gray-500">{{ $cron['timezone'] }}</span>
                        @if($cron['overlap'])
                            <span class="mt-1 inline-flex items-center gap-1 text-[10px] font-bold text-blue-500 dark:text-blue-400">
                                <i class="fas fa-shield-alt text-[9px]"></i> No overlap
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-5">
                        @if($lastRan)
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ $lastRan->diffForHumans() }}</span>
                            <span class="mt-0.5 block text-xs text-gray-400 dark:text-gray-500">{{ $lastRan->format('d M Y, H:i') }}</span>
                        @else
                            <span class="text-xs italic text-gray-400 dark:text-gray-600">No run recorded</span>
                        @endif
                    </td>
                    <td class="px-6 py-5">
                        <a href="{{ route('admin.crons.logs', ['title' => $cron['title']]) }}" class="inline-flex items-center gap-1.5 text-sm font-black text-gray-700 transition-colors hover:text-amber-600 dark:text-gray-300 dark:hover:text-amber-500">
                            {{ number_format($cron['total_runs']) }}
                            <i class="fas fa-external-link-alt text-[10px] opacity-50"></i>
                        </a>
                    </td>
                    <td class="px-6 py-5 text-right">
                        <button onclick="runCron('{{ $cron['title'] }}')" class="run-btn inline-flex items-center gap-2 rounded-xl bg-amber-600 px-4 py-2 text-xs font-black text-white shadow-lg shadow-amber-600/20 transition-all hover:scale-[1.02] hover:bg-amber-700 active:scale-95">
                            <i class="fas fa-play text-[10px]"></i> Run Now
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div id="cron-output-modal" class="fixed inset-0 z-[100] hidden overflow-y-auto">
    <div class="flex min-h-screen items-center justify-center px-4">
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm dark:bg-black/70" onclick="closeOutputModal()"></div>
        <div class="relative z-10 w-full max-w-3xl rounded-3xl border border-gray-100 bg-white shadow-2xl dark:border-white/5 dark:bg-richdark-card">
            <div class="flex items-center justify-between border-b border-gray-100 p-6 dark:border-white/5">
                <div>
                    <h3 class="text-lg font-black tracking-tight text-gray-900 dark:text-white">Command Output</h3>
                    <p id="modal-cron-title" class="mt-0.5 text-xs font-mono text-amber-600 dark:text-amber-500"></p>
                </div>
                <button onclick="closeOutputModal()" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/10 dark:hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6">
                <div id="modal-loading" class="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                    <i class="fas fa-circle-notch fa-spin text-amber-600"></i>
                    <span>Running command. Output will appear here as soon as the execution completes.</span>
                </div>
                <div id="modal-result" class="hidden">
                    <div id="modal-status-banner" class="mb-4 flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold"></div>
                    <pre id="modal-output" class="max-h-[32rem] overflow-auto whitespace-pre-wrap break-words rounded-xl bg-gray-900 p-4 text-xs leading-relaxed text-green-400"></pre>
                </div>
            </div>
            <div id="modal-footer" class="hidden justify-end px-6 pb-6">
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
const CSRF = "{{ csrf_token() }}";

function runCron(title) {
    document.getElementById('cron-output-modal').classList.remove('hidden');
    document.getElementById('modal-cron-title').textContent = title;
    document.getElementById('modal-loading').classList.remove('hidden');
    document.getElementById('modal-result').classList.add('hidden');
    document.getElementById('modal-footer').classList.add('hidden');

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
