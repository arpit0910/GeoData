@extends('layouts.app')

@section('content')
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Mutual Funds</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 font-medium">Browse all AMFI-listed mutual fund schemes with latest NAV.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('mutual-funds.prices') }}"
                class="inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl text-white bg-amber-600 hover:bg-amber-700 transition-all shadow-lg hover:scale-[1.02] active:scale-[0.98]">
                <i class="fas fa-chart-pie mr-2"></i> NAV Price Records
            </a>
        </div>
    </div>

    <div class="bg-white dark:bg-[#0f172a]/80 backdrop-blur-xl border border-gray-200 dark:border-white/5 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-6 overflow-x-auto">
            <table id="mfTable" class="w-full text-left border-collapse">
                <thead>
                    <tr>
                        <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4">ISIN</th>
                        <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4">Scheme Name</th>
                        <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4">AMC</th>
                        <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4">Category</th>
                        <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4 text-right">Latest NAV</th>
                        <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4">NAV Date</th>
                        <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4 text-center">Active</th>
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
            $('#mfTable').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 100,
                ajax: "{{ route('mutual-funds.index') }}",
                columns: [
                    {
                        data: 'isin',
                        name: 'isin',
                        className: 'text-xs text-gray-500 dark:text-gray-400 font-mono'
                    },
                    {
                        data: 'scheme_name',
                        name: 'scheme_name',
                        className: 'font-semibold text-gray-900 dark:text-white text-xs max-w-xs',
                        render: function(data) {
                            return `<span title="${data}" class="line-clamp-2">${data}</span>`;
                        }
                    },
                    {
                        data: 'amc_name',
                        name: 'amc_name',
                        className: 'text-xs text-gray-600 dark:text-gray-300',
                        render: function(data) {
                            return data ? data : '<span class="text-gray-400">N/A</span>';
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
                                'Debt': 'bg-emerald-100 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400',
                                'Hybrid': 'bg-purple-100 dark:bg-purple-500/10 text-purple-700 dark:text-purple-400',
                                'ETF': 'bg-amber-100 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400',
                                'Index': 'bg-sky-100 dark:bg-sky-500/10 text-sky-700 dark:text-sky-400',
                            };
                            const cls = colors[data] || 'bg-gray-100 dark:bg-white/5 text-gray-600 dark:text-gray-400';
                            return `<span class="px-2 py-0.5 rounded-full text-[10px] font-bold ${cls}">${data}</span>`;
                        }
                    },
                    {
                        data: 'nav',
                        name: 'nav',
                        className: 'text-right font-bold text-amber-600 dark:text-amber-400 text-xs',
                        render: function(data) {
                            return data ? '₹' + parseFloat(data).toFixed(4) : '<span class="text-gray-400">N/A</span>';
                        }
                    },
                    {
                        data: 'nav_date',
                        name: 'nav_date',
                        className: 'text-xs text-gray-500',
                        render: function(data) {
                            if (!data) return '<span class="text-gray-400">—</span>';
                            return new Date(data).toLocaleDateString('en-GB');
                        }
                    },
                    {
                        data: 'is_active',
                        name: 'is_active',
                        className: 'text-center text-xs font-bold',
                        render: function(data) {
                            return data ? '<span class="text-emerald-500">Active</span>' : '<span class="text-gray-400">No</span>';
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
                            let pricesUrl = "{{ route('mutual-funds.prices') }}?isin=" + data;
                            return `
                            <div class="flex justify-end gap-1">
                                <a href="${viewUrl}" class="p-2 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 rounded-lg transition-colors" title="View Details">
                                    <i class="fas fa-eye text-sm"></i>
                                </a>
                                <a href="${pricesUrl}" class="p-2 text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 rounded-lg transition-colors" title="NAV History">
                                    <i class="fas fa-chart-line text-sm"></i>
                                </a>
                            </div>`;
                        }
                    }
                ],
                order: [[1, 'asc']],
                dom: '<"flex flex-col sm:flex-row justify-between items-center mb-4"lf>rt<"flex flex-col sm:flex-row justify-between items-center mt-4"ip>',
            });
        });
    </script>
@endpush
