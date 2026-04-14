@extends('layouts.app')

@section('content')
<div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Equity Prices</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 font-medium">Historical price records across NSE and BSE exchanges.</p>
    </div>
    <div class="flex items-center gap-3">
        <button type="button" onclick="document.getElementById('syncModal').classList.remove('hidden')"
            class="inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl text-white bg-amber-600 hover:bg-amber-700 transition-all shadow-lg hover:scale-[1.02] active:scale-[0.98]">
            <i class="fas fa-sync-alt mr-2"></i> Sync Equity Prices
        </button>
    </div>
</div>

{{-- Filters Row --}}
<div class="mb-6 bg-white dark:bg-[#0f172a]/60 border border-gray-200 dark:border-white/5 rounded-2xl p-4 md:p-6 shadow-sm">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <div>
            <label class="block text-xs font-bold text-gray-400 mb-2">Date From</label>
            <input type="date" id="filter_date_from" 
                class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 transition-all text-gray-700 dark:text-gray-300">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-400 mb-2">Date To</label>
            <input type="date" id="filter_date_to" 
                class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 transition-all text-gray-700 dark:text-gray-300">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-400 mb-2">Stock / ISIN</label>
            <input type="text" id="filter_isin" placeholder="Search Name, Symbol or ISIN..."
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

{{-- Sync Modal --}}
<div id="syncModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" onclick="document.getElementById('syncModal').classList.add('hidden')"></div>
        <div class="relative bg-white dark:bg-[#0f172a] border border-gray-200 dark:border-white/10 rounded-2xl shadow-2xl w-full max-w-sm p-6 z-10">
            <div class="mb-5">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Sync Equity Prices</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Select a date to fetch and sync consolidated NSE & BSE data.</p>
            </div>

            {{-- Form State --}}
            <div id="syncInitialState">
                <form id="syncForm">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-400 mb-2">Sync Date</label>
                            <input type="date" id="sync_date" value="{{ date('Y-m-d') }}" required
                                class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 transition-all font-bold">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-400 mb-2">Exchange</label>
                            <select id="sync_exchange" class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 transition-all font-bold">
                                <option value="">Both NSE & BSE</option>
                                <option value="NSE">NSE Only</option>
                                <option value="BSE">BSE Only</option>
                            </select>
                        </div>
                    </div>

                    <div id="syncStatus" class="hidden mt-6 text-center">
                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-amber-100 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 animate-bounce mb-3">
                            <i class="fas fa-spinner fa-spin"></i>
                        </div>
                        <p class="text-sm font-bold text-gray-900 dark:text-white">Syncing Data...</p>
                        <p class="text-xs text-gray-500 mt-1">This may take up to a minute.</p>
                    </div>

                    <div id="syncActions" class="mt-8 flex gap-3">
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

            {{-- Result State --}}
            <div id="syncResultState" class="hidden text-center py-4">
                <div id="successIcon" class="hidden inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 dark:bg-green-500/20 text-green-600 dark:text-green-400 text-2xl mb-4">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div id="errorIcon" class="hidden inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-100 dark:bg-red-500/20 text-red-600 dark:text-red-400 text-2xl mb-4">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                
                <h3 id="resultTitle" class="text-xl font-bold text-gray-900 dark:text-white mb-2">Sync Successful</h3>
                <p id="resultMessage" class="text-sm text-gray-500 dark:text-gray-400 mb-6 leading-relaxed">The equity data has been successfully imported.</p>
                
                <div id="errorDebug" class="hidden mb-6 text-left">
                    <label class="block text-xs font-bold text-red-500 mb-2">Error Details</label>
                    <div class="bg-red-50 dark:bg-red-500/10 border border-red-100 dark:border-red-500/20 rounded-xl p-3 max-h-40 overflow-y-auto text-xs text-red-700 dark:text-red-400">
                        <pre id="debugContent" class="whitespace-pre-wrap font-sans"></pre>
                    </div>
                </div>

                <button type="button" onclick="closeSyncModal()"
                    class="w-full px-4 py-3 text-sm font-bold text-white bg-gray-900 dark:bg-white/10 hover:bg-black dark:hover:bg-white/20 rounded-xl transition-all">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Price Detail Modal --}}
<div id="priceDetailModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 py-8">
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" onclick="document.getElementById('priceDetailModal').classList.add('hidden')"></div>
        <div class="relative bg-white dark:bg-[#0f172a] border border-gray-200 dark:border-white/10 rounded-2xl shadow-2xl w-full max-w-5xl p-5 z-10">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 id="modalStockName" class="text-xl font-bold text-gray-900 dark:text-white">Stock Details</h3>
                    <p id="modalStockMeta" class="text-xs text-gray-400 mt-1 font-medium"></p>
                </div>
                <button onclick="document.getElementById('priceDetailModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 dark:hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            {{-- Equity Metadata Bar --}}
            <div id="equityMetaBar" class="hidden mb-6 grid grid-cols-2 md:grid-cols-4 gap-4 p-4 bg-gray-50 dark:bg-white/5 rounded-2xl border border-gray-100 dark:border-white/5">
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Industry</label>
                    <p id="metaIndustry" class="text-xs font-bold text-gray-700 dark:text-gray-300"></p>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Market Category</label>
                    <p id="metaCategory" class="text-xs font-bold text-gray-700 dark:text-gray-300"></p>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Market Cap</label>
                    <p id="metaCap" class="text-xs font-bold text-gray-700 dark:text-gray-300"></p>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Listing Date</label>
                    <p id="metaListingDate" class="text-xs font-bold text-gray-700 dark:text-gray-300"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="modalContent">
                {{-- Content injected via JS --}}
            </div>

            <div class="mt-5 flex justify-end">
                <button onclick="document.getElementById('priceDetailModal').classList.add('hidden')"
                    class="px-5 py-2 text-xs font-bold text-white bg-gray-900 dark:bg-white/10 hover:bg-black dark:hover:bg-white/20 rounded-xl transition-all">
                    Close Details
                </button>
            </div>
        </div>
    </div>
</div>

<div class="bg-white dark:bg-[#0f172a]/80 backdrop-blur-xl border border-gray-200 dark:border-white/5 rounded-2xl shadow-sm overflow-hidden text-xs">
    <div class="p-6 overflow-x-auto">
        <table id="pricesTable" class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 dark:bg-white/5">
                    <th class="p-4 rounded-tl-xl border-b border-gray-200 dark:border-white/10 text-xs font-bold text-gray-400">Date</th>
                    <th class="p-4 border-b border-gray-200 dark:border-white/10 text-xs font-bold text-gray-400">Stock Name</th>
                    <th class="p-4 border-b border-gray-200 dark:border-white/10 text-xs font-bold text-gray-400">ISIN</th>
                    <th class="p-4 border-b border-gray-200 dark:border-white/10 text-xs font-bold text-indigo-400">NSE Close</th>
                    <th class="p-4 border-b border-gray-200 dark:border-white/10 text-xs font-bold text-amber-500">BSE Close</th>
                    <th class="p-4 border-b border-gray-200 dark:border-white/10 text-xs font-bold text-gray-400">Spread</th>
                    <th class="p-4 rounded-tr-xl border-b border-gray-200 dark:border-white/10 text-xs font-bold text-gray-400 text-right">Actions</th>
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
        const table = $('#pricesTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 100,
            ajax: {
                url: "{{ route('equities.prices.data') }}",
                data: function(d) {
                    d.date_from = $('#filter_date_from').val();
                    d.date_to = $('#filter_date_to').val();
                    d.isin = $('#filter_isin').val();
                }
            },
            columns: [
                { 
                    data: 'traded_date', 
                    name: 'traded_date', 
                    className: 'whitespace-nowrap font-bold',
                    render: function(data) {
                        if (!data) return '—';
                        const date = new Date(data);
                        return date.toLocaleDateString('en-GB'); // Formats as DD/MM/YYYY
                    }
                },
                { 
                    data: 'equity', 
                    name: 'name',
                    className: 'font-bold text-gray-900 dark:text-white',
                    render: function(data) {
                        return data ? data.company_name : '<span class="text-gray-400 italic">N/A</span>';
                    }
                },
                { 
                    data: 'isin', 
                    name: 'isin',
                    className: 'text-xs text-gray-500'
                },
                // NSE Close
                { 
                    data: 'nse_close', 
                    name: 'nse_close', 
                    className: 'font-bold text-indigo-600 dark:text-indigo-400',
                    render: function(data) {
                        return data && data > 0 ? parseFloat(data).toFixed(2) : '<span class="text-gray-400 italic">N/A</span>';
                    }
                },
                // BSE Close
                { 
                    data: 'bse_close', 
                    name: 'bse_close', 
                    className: 'font-bold text-amber-600 dark:text-amber-400',
                    render: function(data) {
                        return data && data > 0 ? parseFloat(data).toFixed(2) : '<span class="text-gray-400 italic">N/A</span>';
                    }
                },
                // Spread
                { 
                    data: 'spread', 
                    name: 'spread',
                    render: function(data) { 
                        if (!data || data === 0 || data === '0.00') {
                            return '<span class="text-gray-400 italic">N/A</span>';
                        }
                        return `<span class="font-bold text-gray-700 dark:text-gray-300">₹${parseFloat(data).toFixed(2)}</span>`; 
                    }
                },
                // Actions
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    className: 'text-right',
                    render: function(data) {
                        return `<button onclick="showPriceDetail(${data.id})" class="p-2 text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-500/10 rounded-lg transition-colors" title="View Details">
                            <i class="fas fa-eye text-sm"></i>
                        </button>`;
                    }
                }
            ],
            order: [[0, 'desc']],
            dom: '<"flex flex-col sm:flex-row justify-between items-center mb-4"lf>rt<"flex flex-col sm:flex-row justify-between items-center mt-4"ip>',
        });

        $('#filter_date_from, #filter_date_to, #filter_isin').on('keypress', function(e) {
            if (e.which == 13) {
                table.ajax.reload();
            }
        });

        $('#applyFilters').on('click', function() {
            table.ajax.reload();
        });

        $('#resetFilters').on('click', function() {
            $('#filter_date_from, #filter_date_to, #filter_isin').val('');
            table.ajax.reload();
        });

        $('#syncForm').on('submit', function(e) {
            e.preventDefault();
            const date = $('#sync_date').val();
            const exchange = $('#sync_exchange').val();
            const status = $('#syncStatus');
            const actions = $('#syncActions');
            
            status.removeClass('hidden');
            actions.addClass('opacity-50 pointer-events-none');

            $.post("{{ route('equities.sync') }}", {
                _token: "{{ csrf_token() }}",
                date: date,
                exchange: exchange
            })
            .done(function(res) {
                $('#syncInitialState').addClass('hidden');
                $('#syncResultState').removeClass('hidden');
                $('#successIcon').removeClass('hidden');
                $('#resultTitle').text('Sync Successful');
                $('#resultMessage').text(res.message);
                table.ajax.reload();
            })
            .fail(function(err) {
                $('#syncInitialState').addClass('hidden');
                $('#syncResultState').removeClass('hidden');
                $('#errorIcon').removeClass('hidden');
                $('#resultTitle').text('Sync Failed');
                
                let msg = 'An unexpected error occurred during synchronization.';
                if (err.responseJSON) {
                    msg = err.responseJSON.message;
                    if (err.responseJSON.debug) {
                        $('#errorDebug').removeClass('hidden');
                        $('#debugContent').text(err.responseJSON.debug);
                    }
                }
                $('#resultMessage').text(msg);
            })
            .always(function() {
                status.addClass('hidden');
                actions.removeClass('opacity-50 pointer-events-none');
            });
        });
    });

    function closeSyncModal() {
        document.getElementById('syncModal').classList.add('hidden');
        setTimeout(() => {
            $('#syncInitialState').removeClass('hidden');
            $('#syncResultState').addClass('hidden');
            $('#successIcon, #errorIcon, #errorDebug').addClass('hidden');
        }, 300);
    }

    function showPriceDetail(id) {
        const modal = document.getElementById('priceDetailModal');
        modal.classList.remove('hidden');
        
        $('#nseDetails, #bseDetails').html(`
            <div class="col-span-2 flex justify-center py-12">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-amber-600"></div>
            </div>
        `);

        const url = "{{ route('equities.prices.show', ':id') }}".replace(':id', id);
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                $('#modalStockName').text(data.equity ? data.equity.company_name : 'N/A');
                const date = new Date(data.traded_date).toLocaleDateString('en-GB');
                $('#modalStockMeta').text(`ISIN: ${data.isin} | Traded Date: ${date} | Spread: ${data.spread}`);

                if (data.equity) {
                    $('#equityMetaBar').removeClass('hidden');
                    $('#metaIndustry').text(data.equity.industry || 'N/A');
                    $('#metaCategory').text(data.equity.market_cap_category || 'N/A');
                    $('#metaCap').text(data.equity.market_cap || 'N/A');
                    const listingDate = data.equity.listing_date ? new Date(data.equity.listing_date).toLocaleDateString('en-GB') : 'N/A';
                    $('#metaListingDate').text(listingDate);
                } else {
                    $('#equityMetaBar').addClass('hidden');
                }

                const fmt = (val) => val && val > 0 ? parseFloat(val).toFixed(2) : '<span class="text-gray-400 italic">N/A</span>';
                const volFmt = (val) => val && val > 0 ? val.toLocaleString() : '<span class="text-gray-400 italic">N/A</span>';
                
                const returnFmt = (val) => {
                    if (val === null || val === undefined) return '<span class="text-gray-400 italic">N/A</span>';
                    const num = parseFloat(val);
                    const color = num >= 0 ? 'text-emerald-500' : 'text-rose-500';
                    const icon = num >= 0 ? '<i class="fas fa-caret-up mr-1"></i>' : '<i class="fas fa-caret-down mr-1"></i>';
                    return `<span class="text-[11px] font-black ${color}">${icon}${Math.abs(num).toFixed(2)}%</span>`;
                };

                const generateExchangeHtml = (exchange, priceData, colorClass, borderClass, bgClass, badgeClass) => {
                    const prefix = exchange.toLowerCase();
                    return `
                        <div class="${bgClass} rounded-2xl p-4 border ${borderClass} h-full">
                            <div class="flex items-center gap-2 mb-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-black text-white ${badgeClass}">${exchange}</span>
                                <h4 class="text-[10px] font-bold ${colorClass} uppercase tracking-widest">${exchange === 'NSE' ? 'National Stock' : 'Bombay Stock'} Exchange</h4>
                            </div>
                            
                            <div class="space-y-1.5">
                                <div class="flex justify-between items-center py-0.5 border-b ${borderClass}">
                                    <span class="text-[11px] text-gray-500">Open Price</span>
                                    <span class="text-[11px] font-bold dark:text-gray-300 text-gray-700">₹${fmt(priceData[prefix + '_open'])}</span>
                                </div>
                                <div class="flex justify-between items-center py-0.5 border-b ${borderClass}">
                                    <span class="text-[11px] text-gray-500">Day High</span>
                                    <span class="text-[11px] font-bold dark:text-gray-300 text-gray-700">₹${fmt(priceData[prefix + '_high'])}</span>
                                </div>
                                <div class="flex justify-between items-center py-0.5 border-b ${borderClass}">
                                    <span class="text-[11px] text-gray-500">Day Low</span>
                                    <span class="text-[11px] font-bold dark:text-gray-300 text-gray-700">₹${fmt(priceData[prefix + '_low'])}</span>
                                </div>
                                <div class="flex justify-between items-center py-0.5 border-b ${borderClass}">
                                    <span class="text-[11px] text-gray-500">Closing Price</span>
                                    <span class="text-xs font-bold ${colorClass}">₹${fmt(priceData[prefix + '_close'])}</span>
                                </div>
                                <div class="flex justify-between items-center py-0.5 border-b ${borderClass}">
                                    <span class="text-[11px] text-gray-500">Avg. Price (VWAP)</span>
                                    <span class="text-[11px] font-bold dark:text-gray-300 text-gray-700">₹${fmt(priceData[prefix + '_avg_price'])}</span>
                                </div>
                                <div class="flex justify-between items-center py-0.5 border-b ${borderClass}">
                                    <span class="text-[11px] text-gray-500">Volume</span>
                                    <span class="text-[11px] font-bold dark:text-gray-300 text-gray-700">${volFmt(priceData[prefix + '_volume'])}</span>
                                </div>
                                <div class="flex justify-between items-center py-0.5 border-b ${borderClass}">
                                    <span class="text-[11px] text-gray-500">Turnover</span>
                                    <span class="text-[11px] font-bold dark:text-gray-300 text-gray-700">₹${volFmt(priceData[prefix + '_turnover'])}</span>
                                </div>
                                <div class="flex justify-between items-center py-0.5">
                                    <span class="text-[11px] text-gray-500">Total Trades</span>
                                    <span class="text-[11px] font-bold dark:text-gray-300 text-gray-700">${volFmt(priceData[prefix + '_trades'])}</span>
                                </div>
                                
                                <div class="mt-4 pt-4 border-t ${borderClass}">
                                    <p class="text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-3">Performance Returns</p>
                                    <div class="grid grid-cols-4 gap-2">
                                        ${['1d', '3d', '7d', '1m', '3m', '6m', '9m', '1y', '3y'].map(p => `
                                            <div class="bg-white dark:bg-white/5 rounded-lg p-1.5 border border-gray-100 dark:border-white/5 text-center">
                                                <p class="text-[8px] font-bold text-gray-400 mb-0.5 uppercase">${p}</p>
                                                ${returnFmt(priceData[prefix + '_chg_' + p])}
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>

                                <div class="mt-4 pt-4 border-t ${borderClass}">
                                    <p class="text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-3">Intraday Analytics</p>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="flex justify-between items-center py-0.5 border-b ${borderClass}">
                                            <span class="text-[10px] text-gray-500">Gap %</span>
                                            ${returnFmt(priceData[prefix + '_gap_pct'])}
                                        </div>
                                        <div class="flex justify-between items-center py-0.5 border-b ${borderClass}">
                                            <span class="text-[10px] text-gray-500">Day Range %</span>
                                            <span class="text-[10px] font-bold dark:text-gray-300 text-gray-700">${priceData[prefix + '_range_pct'] ? parseFloat(priceData[prefix + '_range_pct']).toFixed(2) + '%' : 'N/A'}</span>
                                        </div>
                                        <div class="flex justify-between items-center py-0.5 border-b ${borderClass}">
                                            <span class="text-[10px] text-gray-500">Intraday Chg %</span>
                                            ${returnFmt(priceData[prefix + '_intraday_chg_pct'])}
                                        </div>
                                        <div class="flex justify-between items-center py-0.5 border-b ${borderClass}">
                                            <span class="text-[10px] text-gray-500">Avg Ticket Size</span>
                                            <span class="text-[10px] font-bold dark:text-gray-300 text-gray-700">₹${priceData[prefix + '_avg_ticket_size'] ? parseFloat(priceData[prefix + '_avg_ticket_size']).toLocaleString() : 'N/A'}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                };

                const nseHtml = generateExchangeHtml('NSE', data, 'text-indigo-600 dark:text-indigo-400', 'border-indigo-100/30 dark:border-indigo-500/10', 'bg-indigo-50/50 dark:bg-indigo-500/5', 'bg-indigo-600');
                const bseHtml = generateExchangeHtml('BSE', data, 'text-amber-600 dark:text-amber-400', 'border-amber-100/30 dark:border-amber-500/10', 'bg-amber-50/50 dark:bg-amber-500/5', 'bg-amber-600');

                $('#modalContent').html(nseHtml + bseHtml);
            });
    }
</script>
@endpush
