@extends('layouts.app')

@section('header', 'Cron Jobs')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">Scheduled Cron Jobs</h1>
            <p class="mt-2 text-sm font-medium text-gray-500 dark:text-gray-400">View and manually trigger all background scheduled tasks.</p>
        </div>
        <a href="{{ route('admin.crons.logs') }}" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-sm font-black rounded-2xl shadow-xl text-white bg-amber-600 hover:bg-amber-700 transition-all transform hover:scale-[1.02] active:scale-[0.98]">
            <i class="fas fa-list-alt mr-2 text-xs"></i> View All Logs
        </a>
    </div>

    <div class="bg-white dark:bg-richdark-surface rounded-3xl shadow-xl border border-gray-200 dark:border-white/5 overflow-hidden">
        <table class="w-full text-left border-separate border-spacing-0">
            <thead>
                <tr>
                    <th class="py-4 px-6 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-white/5">Command</th>
                    <th class="py-4 px-6 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-white/5">Schedule</th>
                    <th class="py-4 px-6 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-white/5">Last Run</th>
                    <th class="py-4 px-6 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-white/5">Total Runs</th>
                    <th class="py-4 px-6 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-white/5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-white/5">
                @foreach($crons as $cron)
                @php
                    $lastRan  = $cron['last_ran_at'] ? \Carbon\Carbon::parse($cron['last_ran_at']) : null;
                    $isRecent = $lastRan && $lastRan->gt(now()->subHours(26));
                @endphp
                <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02] transition-colors">
                    {{-- Command --}}
                    <td class="py-5 px-6">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest shrink-0
                                {{ $isRecent ? 'bg-green-500/10 text-green-600 dark:text-green-400' : ($lastRan ? 'bg-yellow-500/10 text-yellow-600 dark:text-yellow-400' : 'bg-gray-100 dark:bg-white/5 text-gray-400') }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $isRecent ? 'bg-green-500 animate-pulse' : ($lastRan ? 'bg-yellow-500' : 'bg-gray-400') }}"></span>
                                {{ $isRecent ? 'Active' : ($lastRan ? 'Idle' : 'Never') }}
                            </span>
                            <div>
                                <code class="block text-sm font-mono font-bold text-amber-600 dark:text-amber-500">{{ $cron['title'] }}</code>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $cron['description'] }}</p>
                            </div>
                        </div>
                    </td>
                    {{-- Schedule --}}
                    <td class="py-5 px-6">
                        <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ $cron['schedule'] }}</span>
                        <span class="block text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $cron['timezone'] }}</span>
                        @if($cron['overlap'])
                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-blue-500 dark:text-blue-400 mt-1">
                                <i class="fas fa-shield-alt text-[9px]"></i> No overlap
                            </span>
                        @endif
                    </td>
                    {{-- Last Run --}}
                    <td class="py-5 px-6">
                        @if($lastRan)
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ $lastRan->diffForHumans() }}</span>
                            <span class="block text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $lastRan->format('d M Y, H:i') }}</span>
                        @else
                            <span class="text-xs italic text-gray-400 dark:text-gray-600">No run recorded</span>
                        @endif
                    </td>
                    {{-- Total Runs --}}
                    <td class="py-5 px-6">
                        <a href="{{ route('admin.crons.logs', ['title' => $cron['title']]) }}"
                           class="inline-flex items-center gap-1.5 text-sm font-black text-gray-700 dark:text-gray-300 hover:text-amber-600 dark:hover:text-amber-500 transition-colors">
                            {{ number_format($cron['total_runs']) }}
                            <i class="fas fa-external-link-alt text-[10px] opacity-50"></i>
                        </a>
                    </td>
                    {{-- Actions --}}
                    <td class="py-5 px-6 text-right">
                        <button
                            onclick="runCron('{{ $cron['title'] }}')"
                            class="run-btn inline-flex items-center gap-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-black rounded-xl transition-all shadow-lg shadow-amber-600/20 hover:scale-[1.02] active:scale-95">
                            <i class="fas fa-play text-[10px]"></i> Run Now
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Output Modal --}}
<div id="cron-output-modal" class="fixed inset-0 z-[100] hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-gray-900/50 dark:bg-black/70 backdrop-blur-sm" onclick="closeOutputModal()"></div>
        <div class="relative bg-white dark:bg-richdark-card rounded-3xl shadow-2xl border border-gray-100 dark:border-white/5 w-full max-w-2xl z-10">
            <div class="p-6 border-b border-gray-100 dark:border-white/5 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-black text-gray-900 dark:text-white tracking-tight">Command Output</h3>
                    <p id="modal-cron-title" class="text-xs font-mono text-amber-600 dark:text-amber-500 mt-0.5"></p>
                </div>
                <button onclick="closeOutputModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-white/10">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6">
                {{-- Running state --}}
                <div id="modal-loading" class="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                    <i class="fas fa-circle-notch fa-spin text-amber-600"></i>
                    <span>Running command, please wait…</span>
                </div>
                {{-- Result state --}}
                <div id="modal-result" class="hidden">
                    <div id="modal-status-banner" class="flex items-center gap-2 text-sm font-bold rounded-xl px-4 py-2.5 mb-4"></div>
                    <pre id="modal-output" class="text-xs font-mono bg-gray-900 text-green-400 rounded-xl p-4 overflow-x-auto whitespace-pre-wrap max-h-64 overflow-y-auto leading-relaxed"></pre>
                </div>
            </div>
            <div id="modal-footer" class="hidden px-6 pb-6 flex justify-end">
                <button onclick="closeOutputModal()" class="px-5 py-2 bg-gray-100 dark:bg-white/5 text-gray-700 dark:text-gray-300 text-sm font-bold rounded-xl hover:bg-gray-200 dark:hover:bg-white/10 transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const RUN_URL  = "{{ route('admin.crons.run') }}";
const CSRF     = "{{ csrf_token() }}";

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
        success: function(res) {
            showResult(res.success, res.message, res.output);
        },
        error: function(xhr) {
            const err = xhr.responseJSON ?? {};
            showResult(false, err.message || 'An unexpected error occurred.', '');
        }
    });
}

function showResult(success, message, output) {
    document.getElementById('modal-loading').classList.add('hidden');
    document.getElementById('modal-result').classList.remove('hidden');
    document.getElementById('modal-footer').classList.remove('hidden');

    const banner = document.getElementById('modal-status-banner');
    if (success) {
        banner.className = 'flex items-center gap-2 text-sm font-bold rounded-xl px-4 py-2.5 mb-4 bg-green-500/10 text-green-600 dark:text-green-400';
        banner.innerHTML = '<i class="fas fa-check-circle"></i> ' + message;
    } else {
        banner.className = 'flex items-center gap-2 text-sm font-bold rounded-xl px-4 py-2.5 mb-4 bg-red-500/10 text-red-600 dark:text-red-400';
        banner.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + message;
    }

    const out = document.getElementById('modal-output');
    out.textContent = output || '(no output)';
    out.style.display = output ? 'block' : 'none';

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
