@extends('layouts.app')

@section('header', 'Cron Execution Logs')

@section('content')
<div class="mx-auto max-w-7xl">
    <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-3xl font-black tracking-tight text-gray-900 dark:text-white">Cron Execution Logs</h1>
            <p class="mt-2 text-sm font-medium text-gray-500 dark:text-gray-400">Review scheduled and manual cron runs with status, source, timestamps, and captured command output.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.crons.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-black text-gray-600 transition-all hover:bg-gray-50 dark:border-white/10 dark:bg-richdark-surface dark:text-gray-300 dark:hover:bg-white/5">
                <i class="fas fa-th-large mr-2 text-xs"></i> Cron Overview
            </a>
        </div>
    </div>

    <div class="mb-5 flex flex-wrap items-center gap-3">
        <label class="text-xs font-black uppercase tracking-widest text-gray-400 dark:text-gray-500">Filter by cron:</label>
        <a href="{{ route('admin.crons.logs') }}" class="rounded-xl px-3 py-1.5 text-xs font-bold transition-colors {{ !request('title') ? 'bg-amber-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10' }}">
            All
        </a>
        @foreach($titles as $t)
        <a href="{{ route('admin.crons.logs', ['title' => $t]) }}" class="rounded-xl px-3 py-1.5 text-xs font-bold transition-colors {{ request('title') === $t ? 'bg-amber-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10' }}">
            {{ $t }}
        </a>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-xl transition-all duration-300 dark:border-white/5 dark:bg-richdark-surface">
        <div class="p-6 md:p-8">
            <div class="overflow-x-auto">
                <table id="cronLogsTable" class="w-full border-separate border-spacing-0 text-left">
                    <thead>
                        <tr>
                            <th class="border-b border-gray-100 px-4 pb-4 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:border-white/5 dark:text-gray-500">ID</th>
                            <th class="border-b border-gray-100 px-4 pb-4 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:border-white/5 dark:text-gray-500">Cron Title</th>
                            <th class="border-b border-gray-100 px-4 pb-4 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:border-white/5 dark:text-gray-500">Source</th>
                            <th class="border-b border-gray-100 px-4 pb-4 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:border-white/5 dark:text-gray-500">Server IP</th>
                            <th class="border-b border-gray-100 px-4 pb-4 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:border-white/5 dark:text-gray-500">Status</th>
                            <th class="border-b border-gray-100 px-4 pb-4 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:border-white/5 dark:text-gray-500">Ran At</th>
                            <th class="border-b border-gray-100 px-4 pb-4 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:border-white/5 dark:text-gray-500">Finished</th>
                            <th class="border-b border-gray-100 px-4 pb-4 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:border-white/5 dark:text-gray-500">Relative</th>
                            <th class="border-b border-gray-100 px-4 pb-4 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:border-white/5 dark:text-gray-500">Output</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-white/5"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    const activeTitle = @json(request('title'));

    $('#cronLogsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.crons.logs') }}",
            data: function (d) {
                if (activeTitle) {
                    d.title = activeTitle;
                }
            }
        },
        columns: [
            {
                data: 'id',
                name: 'id',
                render: data => `<span class="text-xs font-black text-gray-400 dark:text-gray-600">#${data}</span>`
            },
            {
                data: 'title',
                name: 'title',
                render: data => `<code class="rounded bg-gray-100 px-2 py-1 text-xs font-bold text-amber-600 dark:bg-white/5 dark:text-amber-500">${data}</code>`
            },
            {
                data: 'source',
                name: 'source',
                render: data => {
                    const label = data || 'scheduled';
                    const classes = label === 'manual'
                        ? 'bg-blue-500/10 text-blue-600 dark:text-blue-400'
                        : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400';
                    return `<span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-black uppercase tracking-widest ${classes}">${label}</span>`;
                }
            },
            {
                data: 'ip',
                name: 'ip',
                render: data => data
                    ? `<span class="text-xs font-mono font-bold text-gray-700 dark:text-gray-300">${data}</span>`
                    : `<span class="text-xs italic text-gray-400">-</span>`
            },
            {
                data: 'status',
                name: 'status',
                render: data => {
                    const color = data ? 'green' : 'red';
                    const icon = data ? 'check-circle' : 'exclamation-circle';
                    const label = data ? 'Success' : 'Failed';

                    return `
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-${color}-500/10 px-2 py-0.5 text-[10px] font-black uppercase tracking-widest text-${color}-600 dark:text-${color}-400">
                            <i class="fas fa-${icon}"></i> ${label}
                        </span>
                    `;
                }
            },
            {
                data: 'ran_at',
                name: 'ran_at',
                render: data => formatDateCell(data)
            },
            {
                data: 'finished_at',
                name: 'finished_at',
                render: data => formatDateCell(data)
            },
            {
                data: 'ran_at',
                name: 'ran_at',
                orderable: false,
                searchable: false,
                render: data => {
                    if (!data) {
                        return '';
                    }

                    const diff = Math.floor((Date.now() - new Date(data)) / 1000);
                    let rel;

                    if (diff < 60) {
                        rel = `${diff}s ago`;
                    } else if (diff < 3600) {
                        rel = `${Math.floor(diff / 60)}m ago`;
                    } else if (diff < 86400) {
                        rel = `${Math.floor(diff / 3600)}h ago`;
                    } else {
                        rel = `${Math.floor(diff / 86400)}d ago`;
                    }

                    return `<span class="text-xs text-gray-400 dark:text-gray-500">${rel}</span>`;
                }
            },
            {
                data: 'output',
                name: 'output',
                orderable: false,
                searchable: false,
                render: data => {
                    if (!data) {
                        return '<span class="text-xs italic text-gray-400">No output captured</span>';
                    }

                    const escaped = $('<div>').text(data).html();
                    return `<details class="min-w-[18rem] max-w-xl"><summary class="cursor-pointer text-xs font-bold text-amber-600 dark:text-amber-500">View output</summary><pre class="mt-2 max-h-64 overflow-auto whitespace-pre-wrap break-words rounded-xl bg-gray-950 p-3 text-[11px] leading-relaxed text-green-400">${escaped}</pre></details>`;
                }
            }
        ],
        order: [[0, 'desc']],
        language: {
            search: '',
            searchPlaceholder: 'Search logs...',
            lengthMenu: '_MENU_',
            info: 'Showing _START_ to _END_ of _TOTAL_ entries',
            paginate: {
                previous: '<i class="fas fa-chevron-left text-xs"></i>',
                next: '<i class="fas fa-chevron-right text-xs"></i>'
            }
        },
        dom: '<"mb-6 flex flex-col items-center justify-between gap-4 md:flex-row"lf>rt<"mt-6 flex flex-col items-center justify-between gap-4 md:flex-row"ip>',
    });
});

function formatDateCell(data) {
    if (!data) {
        return '<span class="text-xs text-gray-400">-</span>';
    }

    const date = new Date(data);
    return `<span class="text-xs font-bold text-gray-700 dark:text-gray-300">${date.toLocaleString('en-IN', { timeZone: 'Asia/Kolkata', hour12: false })}</span>`;
}
</script>
@endpush
