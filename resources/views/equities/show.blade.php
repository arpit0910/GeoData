@extends('layouts.app')

@section('content')
    <div class="mb-8 flex flex-col sm:flex-row sm:items-start justify-between gap-4">
        <div>
            <a href="{{ route('equities.index') }}"
                class="text-sm font-bold text-gray-500 hover:text-amber-600 transition-colors mb-2 inline-block">
                <i class="fas fa-arrow-left mr-1"></i> Back to Equities
            </a>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">{{ $equity->company_name }}</h1>
            <div class="flex items-center gap-3 mt-1">
                <span
                    class="px-2 py-0.5 text-[10px] font-black bg-gray-100 dark:bg-white/5 text-gray-500 dark:text-gray-400 rounded uppercase tracking-wider">{{ $equity->isin }}</span>
                @if ($equity->industry)
                    <span class="w-1.5 h-1.5 rounded-full bg-gray-300 dark:bg-white/10"></span>
                    <span class="text-sm text-gray-500 dark:text-gray-400 font-bold">{{ $equity->industry }}</span>
                @endif
                @if ($equity->market_cap)
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500/30"></span>
                    <span
                        class="px-2 py-0.5 text-[10px] font-black bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 rounded uppercase tracking-wider">{{ $equity->market_cap }}</span>
                @endif
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('equities.edit', $equity->id) }}"
                class="px-5 py-2.5 text-sm font-bold rounded-xl border border-amber-500/30 text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-500/10 hover:bg-amber-600 hover:text-white hover:border-amber-600 transition-all">
                <i class="fas fa-edit mr-2"></i> Edit Details
            </a>
        </div>
    </div>

    {{-- Stock Info Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white dark:bg-[#0f172a]/80 border border-gray-200 dark:border-white/5 rounded-2xl p-5">
            <p class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">NSE Symbol</p>
            <p class="text-xl font-black text-indigo-600 dark:text-indigo-400">{{ $equity->nse_symbol ?: '—' }}</p>
        </div>
        <div class="bg-white dark:bg-[#0f172a]/80 border border-gray-200 dark:border-white/5 rounded-2xl p-5">
            <p class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">BSE Symbol</p>
            <p class="text-xl font-black text-amber-600 dark:text-amber-500">{{ $equity->bse_symbol ?: '—' }}</p>
        </div>
        <div class="bg-white dark:bg-[#0f172a]/80 border border-gray-200 dark:border-white/5 rounded-2xl p-5">
            <p class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Face Value</p>
            <p class="text-xl font-black text-gray-900 dark:text-white">₹{{ number_format($equity->face_value, 2) }}</p>
        </div>
        <div class="bg-white dark:bg-[#0f172a]/80 border border-gray-200 dark:border-white/5 rounded-2xl p-5">
            <p class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Status</p>
            <div class="flex items-center gap-2">
                @if ($equity->is_active)
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-[0_0_10px_rgba(16,185,129,0.5)]"></span>
                    <span class="text-lg font-black text-emerald-600 dark:text-emerald-400">Active</span>
                @else
                    <span class="w-2.5 h-2.5 rounded-full bg-gray-400"></span>
                    <span class="text-lg font-black text-gray-500">Inactive</span>
                @endif
            </div>
        </div>
    </div>

    <div class="mb-6">
        <h2 class="text-xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
            Price History
            <span
                class="px-2 py-1 text-[10px] font-bold bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 rounded-lg">Real-time
                Data</span>
        </h2>
    </div>

    {{-- Price History Table --}}
    <div
        class="bg-white dark:bg-[#0f172a]/80 backdrop-blur-xl border border-gray-200 dark:border-white/5 rounded-2xl shadow-sm overflow-hidden text-xs">
        <div class="p-6 overflow-x-auto">
            <table id="historyTable" class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-white/5">
                        <th rowspan="2" class="p-4 rounded-tl-xl border-b border-gray-200 dark:border-white/10">Date</th>
                        <th colspan="2"
                            class="p-2 text-center border-l border-b border-gray-200 dark:border-white/10 bg-indigo-500/5">
                            NSE</th>
                        <th colspan="2"
                            class="p-2 text-center border-l border-b border-gray-200 dark:border-white/10 bg-amber-500/5">
                            BSE</th>
                        <th rowspan="2"
                            class="p-4 border-l border-b border-gray-200 dark:border-white/10 bg-indigo-500/5">Spread</th>
                        <th rowspan="2" class="p-4 rounded-tr-xl border-l border-b border-gray-200 dark:border-white/10">
                            Actions</th>
                    </tr>
                    <tr class="bg-gray-50 dark:bg-white/5">
                        <th class="p-2 border-l border-b border-gray-200 dark:border-white/10">Close</th>
                        <th class="p-2 border-b border-gray-200 dark:border-white/10">Volume</th>
                        <th class="p-2 border-l border-b border-gray-200 dark:border-white/10">Close</th>
                        <th class="p-2 border-b border-gray-200 dark:border-white/10">Volume</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5"></tbody>
            </table>
        </div>
    </div>

    {{-- Price Detail Modal --}}
    <div id="priceDetailModal" class="fixed inset-0 z-[60] hidden">
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div
                class="bg-white dark:bg-[#0f172a] w-full max-w-5xl rounded-3xl shadow-2xl overflow-hidden border border-gray-200 dark:border-white/10 transform transition-all">
                <div class="p-5 border-b border-gray-100 dark:border-white/5 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-black text-gray-900 dark:text-white" id="modalTitle">Price Details</h3>
                        <p class="text-xs text-gray-500 font-bold" id="modalSubtitle"></p>
                    </div>
                    <button onclick="closeModal()"
                        class="w-10 h-10 flex items-center justify-center rounded-xl bg-gray-100 dark:bg-white/5 text-gray-500 hover:bg-gray-200 dark:hover:bg-white/10 transition-all">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-5">
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
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const modal = document.getElementById('priceDetailModal');

        function showPriceDetail(id) {
            modal.classList.remove('hidden');
            document.getElementById('modalContent').innerHTML = `
            <div class="col-span-2 flex justify-center py-12">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-amber-600"></div>
            </div>
        `;

            const url = "{{ route('equities.prices.show', ':id') }}".replace(':id', id);

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    const date = new Date(data.traded_date).toLocaleDateString('en-GB');
                    document.getElementById('modalTitle').innerText = data.equity ? data.equity.company_name :
                        'N/A';
                    document.getElementById('modalSubtitle').innerText = `Trading Report for ${date}`;

                    if (data.equity) {
                        document.getElementById('equityMetaBar').classList.remove('hidden');
                        document.getElementById('metaIndustry').innerText = data.equity.industry || 'N/A';
                        document.getElementById('metaCategory').innerText = data.equity.market_cap_category || 'N/A';
                        document.getElementById('metaCap').innerText = data.equity.market_cap || 'N/A';
                        const listingDate = data.equity.listing_date ? new Date(data.equity.listing_date).toLocaleDateString('en-GB') : 'N/A';
                        document.getElementById('metaListingDate').innerText = listingDate;
                    } else {
                        document.getElementById('equityMetaBar').classList.add('hidden');
                    }

                    const fmt = (val) => val && val > 0 ? parseFloat(val).toFixed(2) :
                        '<span class="text-gray-400 italic">N/A</span>';
                    const volFmt = (val) => val && val > 0 ? val.toLocaleString() :
                        '<span class="text-gray-400 italic">N/A</span>';

                    const returnFmt = (val) => {
                        if (val === null || val === undefined)
                            return '<span class="text-gray-400 italic">N/A</span>';
                        const num = parseFloat(val);
                        const color = num >= 0 ? 'text-emerald-500' : 'text-rose-500';
                        const icon = num >= 0 ? '<i class="fas fa-caret-up mr-1"></i>' :
                            '<i class="fas fa-caret-down mr-1"></i>';
                        return `<span class="text-[11px] font-black ${color}">${icon}${Math.abs(num).toFixed(2)}%</span>`;
                    };

                    const generateExchangeHtml = (exchange, priceData, colorClass, borderClass, bgClass,
                    badgeClass) => {
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
                                            ${['1d', '3d', '7d', '1m', '3m', '6m', '9m', '12m'].map(p => `
                                                    <div class="bg-white dark:bg-white/5 rounded-lg p-1.5 border border-gray-100 dark:border-white/5 text-center">
                                                        <p class="text-[8px] font-bold text-gray-400 mb-0.5 uppercase">${p}</p>
                                                        ${returnFmt(priceData[prefix + '_chg_' + p])}
                                                    </div>
                                                `).join('')}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    };

                    const nseHtml = generateExchangeHtml('NSE', data, 'text-indigo-600 dark:text-indigo-400',
                        'border-indigo-100/30 dark:border-indigo-500/10', 'bg-indigo-50/50 dark:bg-indigo-500/5',
                        'bg-indigo-600');
                    const bseHtml = generateExchangeHtml('BSE', data, 'text-amber-600 dark:text-amber-400',
                        'border-amber-100/30 dark:border-amber-500/10', 'bg-amber-50/50 dark:bg-amber-500/5',
                        'bg-amber-600');

                    document.getElementById('modalContent').innerHTML = nseHtml + bseHtml;
                });
        }

        function closeModal() {
            modal.classList.add('hidden');
        }

        $(document).ready(function() {
            $('#historyTable').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 20,
                ajax: "{{ route('equities.show', $equity->id) }}",
                columns: [{
                        data: 'traded_date',
                        name: 'traded_date',
                        className: 'whitespace-nowrap font-bold h-12',
                        render: function(data) {
                            if (!data) return '—';
                            const date = new Date(data);
                            return date.toLocaleDateString('en-GB');
                        }
                    },
                    // NSE
                    {
                        data: 'nse_close',
                        name: 'nse_close',
                        className: 'font-bold text-indigo-600 dark:text-indigo-400'
                    },
                    {
                        data: 'nse_volume',
                        name: 'nse_volume',
                        render: function(data) {
                            return data ? data.toLocaleString() : '—';
                        }
                    },
                    // BSE
                    {
                        data: 'bse_close',
                        name: 'bse_close',
                        className: 'font-bold text-amber-600 dark:text-amber-500'
                    },
                    {
                        data: 'bse_volume',
                        name: 'bse_volume',
                        render: function(data) {
                            return data ? data.toLocaleString() : '—';
                        }
                    },
                    // Spread
                    {
                        data: 'spread',
                        name: 'spread',
                        render: function(data) {
                            return '<span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-white/5 font-mono">' +
                                (data || 0) + '</span>';
                        }
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            return `
                            <button onclick="showPriceDetail(${row.id})" 
                                class="p-2 text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-500/10 rounded-lg transition-all"
                                title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                        `;
                        }
                    }
                ],
                order: [
                    [0, 'desc']
                ],
                dom: '<"flex justify-end p-2"f>rt<"flex justify-between items-center p-4"ip>',
            });
        });
    </script>
@endpush
