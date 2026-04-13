@extends('layouts.app')

@section('content')
<div class="mb-8 flex flex-col sm:flex-row sm:items-start justify-between gap-4">
    <div>
        <a href="{{ route('equities.index') }}" class="text-sm font-bold text-gray-500 hover:text-indigo-600 transition-colors mb-2 inline-block">
            <i class="fas fa-arrow-left mr-1"></i> Back to Equities
        </a>
        <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">{{ $equity->company_name }}</h1>
        <div class="flex items-center gap-3 mt-1">
            <span class="px-2 py-0.5 text-[10px] font-black bg-gray-100 dark:bg-white/5 text-gray-500 dark:text-gray-400 rounded uppercase tracking-wider">{{ $equity->isin }}</span>
            <span class="w-1.5 h-1.5 rounded-full bg-gray-300 dark:bg-white/10"></span>
            <span class="text-sm text-gray-500 dark:text-gray-400 font-bold">{{ $equity->industry ?: 'Uncategorized Industry' }}</span>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('equities.edit', $equity->id) }}"
            class="px-5 py-2.5 text-sm font-bold rounded-xl border border-indigo-500/30 text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-500/10 hover:bg-indigo-600 hover:text-white hover:border-indigo-600 transition-all">
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
            @if($equity->is_active)
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
        <span class="px-2 py-1 text-[10px] font-bold bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 rounded-lg">Real-time Data</span>
    </h2>
</div>

{{-- Price History Table --}}
<div class="bg-white dark:bg-[#0f172a]/80 backdrop-blur-xl border border-gray-200 dark:border-white/5 rounded-2xl shadow-sm overflow-hidden text-xs">
    <div class="p-6 overflow-x-auto">
        <table id="historyTable" class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 dark:bg-white/5">
                    <th rowspan="2" class="p-4 rounded-tl-xl border-b border-gray-200 dark:border-white/10">Date</th>
                    <th colspan="2" class="p-2 text-center border-l border-b border-gray-200 dark:border-white/10 bg-indigo-500/5">NSE</th>
                    <th colspan="2" class="p-2 text-center border-l border-b border-gray-200 dark:border-white/10 bg-amber-500/5">BSE</th>
                    <th rowspan="2" class="p-4 rounded-tr-xl border-l border-b border-gray-200 dark:border-white/10">Spread</th>
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
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#historyTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 20,
            ajax: "{{ route('equities.show', $equity->id) }}",
            columns: [
                { 
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
                { data: 'nse_close', name: 'nse_close', className: 'font-bold text-indigo-600 dark:text-indigo-400' },
                { 
                    data: 'nse_volume', 
                    name: 'nse_volume',
                    render: function(data) { return data ? data.toLocaleString() : '—'; }
                },
                // BSE
                { data: 'bse_close', name: 'bse_close', className: 'font-bold text-amber-600 dark:text-amber-500' },
                { 
                    data: 'bse_volume', 
                    name: 'bse_volume',
                    render: function(data) { return data ? data.toLocaleString() : '—'; }
                },
                // Spread
                { 
                    data: 'spread', 
                    name: 'spread',
                    render: function(data) { return '<span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-white/5 font-mono">' + (data || 0) + '</span>'; }
                }
            ],
            order: [[0, 'desc']],
            dom: '<"flex justify-end p-2"f>rt<"flex justify-between items-center p-4"ip>',
        });
    });
</script>
@endpush
