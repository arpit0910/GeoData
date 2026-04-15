@extends('layouts.app')

@section('content')
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Index Prices</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 font-medium">Browse daily historical time-series for all
                market indices.</p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" onclick="document.getElementById('syncModal').classList.remove('hidden')"
                class="inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl text-white bg-amber-600 hover:bg-amber-700 transition-all shadow-lg hover:scale-[1.02] active:scale-[0.98]">
                <i class="fas fa-sync-alt mr-2"></i> Sync Index Prices
            </button>
            <a href="{{ route('admin.indices.index') }}"
                class="inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl border border-gray-200 dark:border-white/10 text-gray-700 dark:text-gray-300 bg-white dark:bg-white/5 hover:bg-gray-50 transition-all">
                <i class="fas fa-arrow-trend-up mr-2 text-emerald-500"></i> Index Master
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div
        class="bg-white dark:bg-[#0f172a]/80 backdrop-blur-xl border border-gray-200 dark:border-white/5 rounded-2xl shadow-sm p-6 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <label class="block text-xs font-bold text-gray-400 mb-2 uppercase tracking-wider">Index</label>
                <select id="filter_index"
                    class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-3 text-xs focus:ring-2 focus:ring-amber-500 transition-all">
                    <option value="">All Indices</option>
                    @foreach ($indices as $idx)
                        <option value="{{ $idx->index_code }}"
                            {{ request('index_code') == $idx->index_code ? 'selected' : '' }}>{{ $idx->index_name }}
                            ({{ $idx->index_code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-400 mb-2 uppercase tracking-wider">Date From</label>
                <input type="date" id="filter_date_from"
                    class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-3 text-xs focus:ring-2 focus:ring-amber-500 transition-all">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-400 mb-2 uppercase tracking-wider">Date To</label>
                <input type="date" id="filter_date_to"
                    class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-3 text-xs focus:ring-2 focus:ring-amber-500 transition-all">
            </div>
            <div class="flex items-end gap-2">
                <button type="button" @click="$dispatch('filter-prices')"
                    class="flex-1 bg-amber-600 hover:bg-amber-700 text-white font-bold py-3 px-4 rounded-xl text-xs transition-all shadow-lg active:scale-95">
                    <i class="fas fa-filter mr-2"></i> Apply Filters
                </button>
                <button type="button" onclick="window.location.href='{{ route('admin.indices.prices') }}'"
                    class="p-3 text-gray-400 hover:bg-gray-100 dark:hover:bg-white/5 rounded-xl transition-all border border-transparent hover:border-gray-200">
                    <i class="fas fa-undo text-sm"></i>
                </button>
            </div>
        </div>
    </div>

    <div
        class="bg-white dark:bg-[#0f172a]/80 backdrop-blur-xl border border-gray-200 dark:border-white/5 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-6 overflow-x-auto">
            <table id="indicesPricesTable" class="w-full text-left border-collapse">
                <thead>
                    <tr>
                        <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4">
                            Traded Date</th>
                        <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4">
                            Index</th>
                        <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4">
                            Closing Value</th>
                        <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4">
                            Change %</th>
                        <th
                            class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4 text-right">
                            Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5"></tbody>
            </table>
        </div>
    </div>

    <!-- Details Modal -->
    <div id="priceDetailModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" onclick="closeDetailModal()"></div>
            <div
                class="relative bg-white dark:bg-[#0f172a] border border-gray-200 dark:border-white/10 rounded-2xl shadow-2xl w-full max-w-4xl p-8 z-10">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h3 id="modalIndexName"
                            class="text-2xl font-black text-gray-900 dark:text-white uppercase tracking-tight">Index Details
                        </h3>
                        <p id="modalDate" class="text-xs text-gray-400 mt-1 font-bold tracking-widest uppercase"></p>
                    </div>
                    <button onclick="closeDetailModal()"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div id="modalLoading" class="py-20 text-center">
                    <div
                        class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-amber-100 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 animate-spin mb-4">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <p class="text-sm font-bold text-gray-400">Fetching Analytical Data...</p>
                </div>

                <div id="modalContent" class="hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Left: OHLC & Liquidity -->
                        <div class="space-y-6">
                            <div
                                class="bg-gray-50 dark:bg-white/5 rounded-2xl p-6 border border-gray-100 dark:border-white/5">
                                <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Session
                                    Statistics</h4>
                                <div class="grid grid-cols-2 gap-6">
                                    <div>
                                        <p class="text-[10px] text-gray-500 mb-1 uppercase">Open Price</p>
                                        <p id="detOpen" class="text-sm font-bold text-gray-900 dark:text-white"></p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-gray-500 mb-1 uppercase">Prev Close</p>
                                        <p id="detPrevClose" class="text-sm font-bold text-gray-900 dark:text-white"></p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-gray-500 mb-1 uppercase">High Price</p>
                                        <p id="detHigh" class="text-sm font-bold text-emerald-500"></p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-gray-500 mb-1 uppercase">Low Price</p>
                                        <p id="detLow" class="text-sm font-bold text-rose-500"></p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-gray-500 mb-1 uppercase">Closing Value</p>
                                        <p id="detClose" class="text-lg font-black text-amber-500"></p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-gray-500 mb-1 uppercase">Turnover (Cr)</p>
                                        <p id="detTurnover" class="text-sm font-bold text-indigo-500"></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Volatility Metrics -->
                            <div
                                class="bg-gray-50 dark:bg-white/5 rounded-2xl p-6 border border-gray-100 dark:border-white/5">
                                <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Volatility &
                                    Spreads</h4>
                                <div class="space-y-3">
                                    <div
                                        class="flex justify-between items-center py-1 border-b border-gray-100 dark:border-white/10 text-xs">
                                        <span class="text-gray-500">Gap Percentage</span>
                                        <span id="detGap" class="font-bold"></span>
                                    </div>
                                    <div
                                        class="flex justify-between items-center py-1 border-b border-gray-100 dark:border-white/10 text-xs">
                                        <span class="text-gray-500">Intraday Change %</span>
                                        <span id="detIntra" class="font-bold"></span>
                                    </div>
                                    <div class="flex justify-between items-center py-1 text-xs">
                                        <span class="text-gray-500">Day Range %</span>
                                        <span id="detRange" class="font-bold text-gray-900 dark:text-white"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Returns Grid -->
                        <div class="bg-gray-50 dark:bg-white/5 rounded-2xl p-6 border border-gray-100 dark:border-white/5">
                            <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-6">Historical
                                Returns (Compounded)</h4>
                            <div class="grid grid-cols-2 gap-4" id="detReturns">
                                <!-- Generated via JS -->
                            </div>

                            <div class="mt-8 pt-8 border-t border-gray-100 dark:border-white/10 grid grid-cols-2 gap-6">
                                <div>
                                    <p class="text-[10px] text-gray-500 mb-1 uppercase">P/E Ratio</p>
                                    <p id="detPE" class="text-sm font-bold text-gray-900 dark:text-white"></p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-gray-500 mb-1 uppercase">Div Yield</p>
                                    <p id="detYield" class="text-sm font-bold text-gray-900 dark:text-white"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end">
                        <button onclick="closeDetailModal()"
                            class="px-8 py-3 bg-gray-900 dark:bg-white/10 text-white text-xs font-black rounded-xl hover:bg-black dark:hover:bg-white/20 transition-all uppercase tracking-widest">
                            Close Inspection
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Sync Modal --}}
    <div id="syncModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm"
                onclick="document.getElementById('syncModal').classList.add('hidden')"></div>
            <div
                class="relative bg-white dark:bg-[#0f172a] border border-gray-200 dark:border-white/10 rounded-2xl shadow-2xl w-full max-w-sm p-6 z-10">
                <div class="text-center mb-6">
                    <div
                        class="w-16 h-16 bg-amber-50 dark:bg-amber-500/10 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-rotate text-2xl text-amber-600 dark:text-amber-400"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mt-4">Sync Index Data</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Fetch latest market performance from NSE/BSE.
                    </p>
                </div>

                <form id="syncForm">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-xs font-bold text-gray-400 mb-2 uppercase tracking-widest">Traded
                            Date</label>
                        <input type="date" name="date" id="sync_date" value="{{ date('Y-m-d') }}" required
                            class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-3 text-xs focus:outline-none focus:ring-2 focus:ring-amber-500 transition-all font-bold">
                    </div>

                    <div class="mb-6">
                        <label
                            class="block text-xs font-bold text-gray-400 mb-2 uppercase tracking-widest">Exchange</label>
                        <select name="exchange"
                            class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-3 text-xs focus:outline-none focus:ring-2 focus:ring-amber-500 transition-all font-bold">
                            <option value="">Both NSE & BSE</option>
                            <option value="NSE">NSE Only</option>
                            <option value="BSE">BSE Only</option>
                        </select>
                    </div>

                    <div id="syncStatus"
                        class="hidden mb-6 p-4 rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-100 dark:border-amber-500/20">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-circle-notch fa-spin text-amber-600 dark:text-amber-400"></i>
                            <span id="statusText" class="text-xs font-bold text-amber-700 dark:text-amber-300">Syncing
                                with exchange...</span>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3" id="syncActions">
                        <button type="button" onclick="document.getElementById('syncModal').classList.add('hidden')"
                            class="flex-1 px-4 py-3 text-sm font-bold text-gray-500 hover:bg-gray-100 dark:hover:bg-white/5 rounded-xl transition-all">
                            Cancel
                        </button>
                        <button type="submit"
                            class="flex-1 px-4 py-3 text-sm font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-xl shadow-lg shadow-amber-500/30 transition-all">
                            Start Sync
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            const table = $('#indicesPricesTable').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 25,
                ajax: {
                    url: "{{ route('admin.indices.prices') }}",
                    data: function(d) {
                        d.index_code = $('#filter_index').val();
                        d.date_from = $('#filter_date_from').val();
                        d.date_to = $('#filter_date_to').val();
                    }
                },
                columns: [{
                        data: 'traded_date',
                        name: 'traded_date',
                        render: function(data) {
                            if (!data) return '-';
                            const date = new Date(data);
                            const day = String(date.getDate()).padStart(2, '0');
                            const month = String(date.getMonth() + 1).padStart(2, '0');
                            const year = date.getFullYear();
                            return `${day}/${month}/${year}`;
                        }
                    },
                    {
                        data: 'index_code',
                        name: 'index_code'
                    },
                    {
                        data: 'close',
                        name: 'close',
                        render: function(data) {
                            return data ? '₹' + parseFloat(data).toFixed(2) : '-';
                        }
                    },
                    {
                        data: 'change_percent',
                        name: 'change_percent',
                        render: function(data) {
                            if (data === null || data === undefined) {
                                return '<span class="text-gray-400 italic">N/A</span>';
                            }
                            let color = data > 0 ? 'text-emerald-500' : (data < 0 ?
                                'text-rose-500' : 'text-gray-400');
                            return `<span class="${color} font-bold">${data}%</span>`;
                        }
                    },
                    {
                        data: 'id',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-right',
                        render: function(data) {
                            return `<button onclick="showPriceDetails(${data})" class="p-2 text-amber-500 hover:bg-amber-50 dark:hover:bg-amber-500/10 rounded-lg transition-colors" title="Detailed Analytics">
                                <i class="fas fa-eye text-sm"></i>
                            </button>`;
                        }
                    }
                ],
                order: [
                    [0, 'desc']
                ],
                dom: '<"flex justify-between items-center mb-4"lf>rt<"flex justify-between items-center mt-4"ip>',
            });

            // Re-apply filters
            window.addEventListener('filter-prices', () => {
                table.ajax.reload();
            });

            $('#filter_index, #filter_date_from, #filter_date_to').on('change', function() {
                table.ajax.reload();
            });

            $('#syncForm').on('submit', function(e) {
                e.preventDefault();
                const status = $('#syncStatus');
                const actions = $('#syncActions');

                status.removeClass('hidden');
                actions.addClass('opacity-50 pointer-events-none');

                $.ajax({
                    url: "{{ route('admin.indices.sync') }}",
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(res) {
                        if (res.success) {
                            toastr.success(res.message);
                            document.getElementById('syncModal').classList.add('hidden');
                            table.ajax.reload();
                        } else {
                            toastr.error(res.message);
                        }
                    },
                    error: function(err) {
                        toastr.error('Sync failed. Please check logs.');
                    },
                    complete: function() {
                        status.addClass('hidden');
                        actions.removeClass('opacity-50 pointer-events-none');
                    }
                });
            });
        });

        function showPriceDetails(id) {
            const modal = $('#priceDetailModal');
            const loading = $('#modalLoading');
            const content = $('#modalContent');

            modal.removeClass('hidden');
            loading.removeClass('hidden');
            content.addClass('hidden');

            const url = "{{ route('admin.indices.prices.show', ':id') }}".replace(':id', id);

            $.get(url, function(data) {
                $('#modalIndexName').text(data.index_code);

                const tradedDate = new Date(data.traded_date);
                $('#modalDate').text(tradedDate.toLocaleDateString('en-GB', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                }));

                const fmt = (val) => (val !== null && val !== undefined) ? parseFloat(val).toFixed(2) : 'N/A';
                const colorFmt = (val) => {
                    if (val === null || val === undefined)
                    return '<span class="text-gray-400 italic">N/A</span>';
                    const num = parseFloat(val);
                    const color = num >= 0 ? 'text-emerald-500' : 'text-rose-500';
                    return `<span class="${color} font-black">${num > 0 ? '+' : ''}${num.toFixed(2)}%</span>`;
                };

                $('#detOpen').text(data.open ? '₹' + fmt(data.open) : 'N/A');
                $('#detPrevClose').text(data.prev_close ? '₹' + fmt(data.prev_close) : 'N/A');
                $('#detHigh').text(data.high ? '₹' + fmt(data.high) : 'N/A');
                $('#detLow').text(data.low ? '₹' + fmt(data.low) : 'N/A');
                $('#detClose').text(data.close ? '₹' + fmt(data.close) : 'N/A');
                $('#detTurnover').text(data.turnover ? '₹' + parseFloat(data.turnover).toFixed(2) + ' Cr' : 'N/A');

                $('#detPE').text(data.pe_ratio || 'N/A');
                $('#detYield').text((data.div_yield !== null && data.div_yield !== undefined) ? data.div_yield +
                    '%' : 'N/A');

                $('#detGap').html(colorFmt(data.gap_pct));
                $('#detIntra').html(colorFmt(data.intraday_chg_pct));
                $('#detRange').text((data.range_pct !== null && data.range_pct !== undefined) ? fmt(data
                    .range_pct) + '%' : 'N/A');

                const returns = [
                    ['1 Day', 'chg_1d'],
                    ['3 Day', 'chg_3d'],
                    ['1 Week', 'chg_7d'],
                    ['1 Month', 'chg_1m'],
                    ['3 Month', 'chg_3m'],
                    ['6 Month', 'chg_6m'],
                    ['1 Year', 'chg_1y'],
                    ['3 Year', 'chg_3y']
                ];

                let returnHtml = '';
                returns.forEach(([label, field]) => {
                    const val = data[field];
                    const hasVal = val !== null && val !== undefined;
                    const numVal = hasVal ? parseFloat(val) : 0;

                    returnHtml += `
                        <div class="bg-white dark:bg-white/5 rounded-xl p-3 border border-gray-100 dark:border-white/5 text-center">
                            <p class="text-[8px] font-bold text-gray-400 mb-1 uppercase">${label}</p>
                            <p class="text-xs font-black ${!hasVal ? 'text-gray-400 italic' : (numVal >= 0 ? 'text-emerald-500' : 'text-rose-500')}">
                                ${hasVal ? (numVal > 0 ? '+' : '') + numVal.toFixed(2) + '%' : 'N/A'}
                            </p>
                        </div>
                    `;
                });
                $('#detReturns').html(returnHtml);

                loading.addClass('hidden');
                content.removeClass('hidden');
            });
        }

        function closeDetailModal() {
            $('#priceDetailModal').addClass('hidden');
        }
    </script>
@endpush
