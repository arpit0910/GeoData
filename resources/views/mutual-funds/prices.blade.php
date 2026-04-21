@extends('layouts.app')

@section('header', 'MF NAV Prices')

@section('content')
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <a href="{{ route('mutual-funds.index') }}" class="text-gray-400 hover:text-amber-500 transition-colors">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">MF NAV Prices</h1>
            </div>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 font-medium ml-7">Historical NAV records for all mutual fund schemes.</p>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="mb-6 bg-white dark:bg-[#0f172a]/60 border border-gray-200 dark:border-white/5 rounded-2xl p-4 md:p-6 shadow-sm">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-xs font-bold text-gray-400 mb-2">Date From</label>
                <input type="date" id="filter_date_from" value="{{ $latestDate ?? '' }}"
                    class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 transition-all text-gray-700 dark:text-gray-300">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-400 mb-2">Date To</label>
                <input type="date" id="filter_date_to" value="{{ $latestDate ?? '' }}"
                    class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 transition-all text-gray-700 dark:text-gray-300">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-400 mb-2">Scheme / ISIN</label>
                <input type="text" id="filter_isin" placeholder="Search scheme name or ISIN..."
                    value="{{ request('isin') }}"
                    class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 transition-all text-gray-700 dark:text-gray-300">
            </div>
            <div class="flex gap-2">
                <button id="applyFilters" class="flex-1 bg-gray-900 dark:bg-white/10 text-white px-4 py-2.5 text-sm font-bold rounded-xl hover:bg-black dark:hover:bg-white/20 transition-all">
                    Apply Filters
                </button>
                <button id="resetFilters" class="px-4 py-2.5 text-sm font-bold text-gray-500 hover:text-red-600 transition-colors">
                    Reset
                </button>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-[#0f172a]/80 backdrop-blur-xl border border-gray-200 dark:border-white/5 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-6 overflow-x-auto">
            <table id="pricesTable" class="w-full text-left border-collapse">
                <thead>
                    <tr>
                        <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4">Date</th>
                        <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4">Scheme Name</th>
                        <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4">AMC</th>
                        <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4">Category</th>
                        <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4">ISIN</th>
                        <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4 text-right">NAV (₹)</th>
                        <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5"></tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            const isinFromUrl = new URLSearchParams(window.location.search).get('isin') ?? '';
            if (isinFromUrl) $('#filter_isin').val(isinFromUrl);

            const table = $('#pricesTable').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 100,
                ajax: {
                    url: "{{ route('mutual-funds.prices') }}",
                    data: function(d) {
                        d.date_from = $('#filter_date_from').val();
                        d.date_to   = $('#filter_date_to').val();
                        d.isin      = $('#filter_isin').val();
                    }
                },
                columns: [
                    {
                        data: 'nav_date',
                        name: 'nav_date',
                        className: 'text-xs font-bold text-gray-700 dark:text-gray-300 whitespace-nowrap px-4',
                        render: function(data) {
                            if (!data) return '—';
                            return new Date(data).toLocaleDateString('en-GB');
                        }
                    },
                    {
                        data: 'scheme_name',
                        name: 'scheme_name',
                        className: 'text-xs font-semibold text-gray-900 dark:text-white max-w-xs',
                        render: function(data) {
                            return `<span title="${data}" class="line-clamp-1">${data ?? '—'}</span>`;
                        }
                    },
                    {
                        data: 'amc_name',
                        name: 'amc_name',
                        className: 'text-xs text-gray-500 dark:text-gray-400',
                        render: function(data) {
                            return data ?? '<span class="text-gray-400">N/A</span>';
                        }
                    },
                    {
                        data: 'category',
                        name: 'category',
                        className: 'text-xs',
                        render: function(data) {
                            if (!data) return '<span class="text-gray-400">—</span>';
                            const colors = {
                                'Equity': 'bg-indigo-100 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400',
                                'Debt':   'bg-emerald-100 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400',
                                'Hybrid': 'bg-purple-100 dark:bg-purple-500/10 text-purple-700 dark:text-purple-400',
                                'ETF':    'bg-amber-100 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400',
                                'Index':  'bg-sky-100 dark:bg-sky-500/10 text-sky-700 dark:text-sky-400',
                            };
                            const cls = colors[data] || 'bg-gray-100 dark:bg-white/5 text-gray-600 dark:text-gray-400';
                            return `<span class="px-2 py-0.5 rounded-full text-[10px] font-bold ${cls}">${data}</span>`;
                        }
                    },
                    {
                        data: 'isin',
                        name: 'isin',
                        className: 'text-xs font-mono text-gray-500 dark:text-gray-400'
                    },
                    {
                        data: 'nav',
                        name: 'nav',
                        className: 'text-right font-black text-amber-600 dark:text-amber-400 text-xs',
                        render: function(data) {
                            return data ? '₹' + parseFloat(data).toFixed(4) : '—';
                        }
                    },
                    {
                        data: 'isin',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-right whitespace-nowrap',
                        render: function(data) {
                            let viewUrl = "{{ route('mutual-funds.show', ':isin') }}".replace(':isin', data);
                            return `<a href="${viewUrl}" class="p-2 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 rounded-lg transition-colors inline-flex" title="View Scheme">
                                <i class="fas fa-eye text-sm"></i>
                            </a>`;
                        }
                    }
                ],
                order: [[0, 'desc']],
                dom: '<"flex flex-col sm:flex-row justify-between items-center mb-4"lf>rt<"flex flex-col sm:flex-row justify-between items-center mt-4"ip>',
            });

            $('#applyFilters').on('click', function() {
                table.ajax.reload();
            });

            $('#filter_date_from, #filter_date_to, #filter_isin').on('keypress', function(e) {
                if (e.which === 13) table.ajax.reload();
            });

            $('#resetFilters').on('click', function() {
                $('#filter_date_from').val('{{ $latestDate ?? '' }}');
                $('#filter_date_to').val('{{ $latestDate ?? '' }}');
                $('#filter_isin').val('');
                table.ajax.reload();
            });
        });
    </script>
@endpush
