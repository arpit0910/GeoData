@extends('layouts.app')

@section('header', 'Cron Execution Logs')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">Cron Execution Logs</h1>
            <p class="mt-2 text-sm font-medium text-gray-500 dark:text-gray-400">A full history of every scheduled cron run — title, server IP, and execution time.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.crons.index') }}" class="inline-flex items-center justify-center px-5 py-2.5 border border-gray-200 dark:border-white/10 text-sm font-black rounded-2xl text-gray-600 dark:text-gray-300 bg-white dark:bg-richdark-surface hover:bg-gray-50 dark:hover:bg-white/5 transition-all">
                <i class="fas fa-th-large mr-2 text-xs"></i> Cron Overview
            </a>
        </div>
    </div>

    {{-- Filter bar --}}
    <div class="mb-5 flex flex-wrap items-center gap-3">
        <label class="text-xs font-black uppercase tracking-widest text-gray-400 dark:text-gray-500">Filter by cron:</label>
        <a href="{{ route('admin.crons.logs') }}"
           class="px-3 py-1.5 rounded-xl text-xs font-bold transition-colors {{ !request('title') ? 'bg-amber-600 text-white' : 'bg-gray-100 dark:bg-white/5 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-white/10' }}">
            All
        </a>
        @foreach($titles as $t)
        <a href="{{ route('admin.crons.logs', ['title' => $t]) }}"
           class="px-3 py-1.5 rounded-xl text-xs font-bold transition-colors {{ request('title') === $t ? 'bg-amber-600 text-white' : 'bg-gray-100 dark:bg-white/5 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-white/10' }}">
            {{ $t }}
        </a>
        @endforeach
    </div>

    <div class="bg-white dark:bg-richdark-surface rounded-3xl shadow-xl border border-gray-200 dark:border-white/5 overflow-hidden transition-all duration-300">
        <div class="p-6 md:p-8">
            <div class="overflow-x-auto">
                <table id="cronLogsTable" class="w-full text-left border-separate border-spacing-0">
                    <thead>
                        <tr>
                            <th class="pb-4 px-4 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-white/5">ID</th>
                            <th class="pb-4 px-4 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-white/5">Cron Title</th>
                            <th class="pb-4 px-4 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-white/5">Server IP</th>
                            <th class="pb-4 px-4 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-white/5">Ran At</th>
                            <th class="pb-4 px-4 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-white/5">Relative</th>
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
                if (activeTitle) d.title = activeTitle;
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
                render: data => `<code class="text-xs font-mono font-bold bg-gray-100 dark:bg-white/5 px-2 py-1 rounded text-amber-600 dark:text-amber-500">${data}</code>`
            },
            {
                data: 'ip',
                name: 'ip',
                render: data => data
                    ? `<span class="text-xs font-mono font-bold text-gray-700 dark:text-gray-300">${data}</span>`
                    : `<span class="text-xs text-gray-400 italic">—</span>`
            },
            {
                data: 'ran_at',
                name: 'ran_at',
                render: data => {
                    if (!data) return '<span class="text-xs text-gray-400">—</span>';
                    const d = new Date(data);
                    return `<span class="text-xs font-bold text-gray-700 dark:text-gray-300">${d.toLocaleString('en-IN', { timeZone: 'Asia/Kolkata', hour12: false })}</span>`;
                }
            },
            {
                data: 'ran_at',
                name: 'ran_at',
                orderable: false,
                searchable: false,
                render: data => {
                    if (!data) return '';
                    const diff = Math.floor((Date.now() - new Date(data)) / 1000);
                    let rel;
                    if (diff < 60)        rel = `${diff}s ago`;
                    else if (diff < 3600) rel = `${Math.floor(diff / 60)}m ago`;
                    else if (diff < 86400) rel = `${Math.floor(diff / 3600)}h ago`;
                    else                  rel = `${Math.floor(diff / 86400)}d ago`;
                    return `<span class="text-xs text-gray-400 dark:text-gray-500">${rel}</span>`;
                }
            },
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
        dom: '<"flex flex-col md:flex-row justify-between items-center gap-4 mb-6"lf>rt<"flex flex-col md:flex-row justify-between items-center mt-6 gap-4"ip>',
    });
});
</script>
@endpush
