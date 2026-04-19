@extends('layouts.app')

@section('content')
    <div class="mb-8 flex flex-col sm:flex-row sm:items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <a href="{{ route('mutual-funds.index') }}" class="text-gray-400 hover:text-amber-500 transition-colors">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">{{ $scheme->scheme_name }}</h1>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 font-medium ml-7">
                {{ $scheme->amc_name ?? 'Unknown AMC' }}
                @if($scheme->category) • <span class="text-amber-500">{{ $scheme->category }}</span> @endif
                @if($scheme->type) • {{ $scheme->type }} @endif
            </p>
        </div>
        <div class="flex items-center gap-2 ml-7 sm:ml-0">
            <a href="{{ route('mutual-funds.prices') }}?isin={{ $scheme->isin }}"
                class="px-5 py-2.5 text-sm font-bold rounded-xl text-white bg-amber-600 hover:bg-amber-700 transition-all shadow-lg">
                <i class="fas fa-chart-line mr-2"></i> NAV History
            </a>
        </div>
    </div>

    <!-- Stats Bar -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white dark:bg-[#0f172a]/80 border border-gray-200 dark:border-white/5 rounded-2xl p-5 shadow-sm">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">ISIN</p>
            <h4 class="text-lg font-black text-indigo-600 dark:text-indigo-400 font-mono">{{ $scheme->isin }}</h4>
        </div>
        <div class="bg-white dark:bg-[#0f172a]/80 border border-gray-200 dark:border-white/5 rounded-2xl p-5 shadow-sm">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Scheme Code</p>
            <h4 class="text-2xl font-black text-amber-600 dark:text-amber-500">{{ $scheme->scheme_code }}</h4>
        </div>
        <div class="bg-white dark:bg-[#0f172a]/80 border border-gray-200 dark:border-white/5 rounded-2xl p-5 shadow-sm">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Latest NAV</p>
            <h4 class="text-2xl font-black text-gray-900 dark:text-white">₹{{ number_format($scheme->latest_nav ?? 0, 4) }}</h4>
            @if($scheme->latest_nav_date)
                <p class="text-[10px] text-gray-400 mt-1">as of {{ \Carbon\Carbon::parse($scheme->latest_nav_date)->format('d M Y') }}</p>
            @endif
        </div>
        <div class="bg-white dark:bg-[#0f172a]/80 border border-gray-200 dark:border-white/5 rounded-2xl p-5 shadow-sm">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Status</p>
            <div class="flex items-center gap-2 mt-1">
                @if($scheme->is_active)
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    <h4 class="text-2xl font-black text-emerald-500">Active</h4>
                @else
                    <span class="w-2.5 h-2.5 rounded-full bg-gray-400"></span>
                    <h4 class="text-2xl font-black text-gray-500">Inactive</h4>
                @endif
            </div>
        </div>
    </div>

    <!-- Returns Card -->
    <div class="bg-white dark:bg-[#0f172a]/80 border border-gray-200 dark:border-white/5 rounded-2xl p-6 shadow-sm mb-8">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-6 uppercase tracking-widest flex items-center gap-2">
            <i class="fas fa-calendar-alt text-amber-500"></i> Historical Returns
        </h3>
        <div class="grid grid-cols-3 md:grid-cols-6 gap-3">
            @foreach(['1D', '1M', '3M', '6M', '1Y', '3Y'] as $label)
                <div class="bg-gray-50 dark:bg-white/5 rounded-xl p-4 border border-gray-100 dark:border-white/5 text-center">
                    <p class="text-[9px] font-bold text-gray-400 mb-2 uppercase tracking-wider">{{ $label }}</p>
                    @if(isset($returns[$label]))
                        @php $val = $returns[$label]['return_pct']; @endphp
                        <p class="text-sm font-black {{ $val >= 0 ? 'text-emerald-500' : 'text-rose-500' }}">
                            {{ $val > 0 ? '+' : '' }}{{ number_format($val, 2) }}%
                        </p>
                        <p class="text-[9px] text-gray-400 mt-1">₹{{ number_format($returns[$label]['ref_nav'], 4) }}</p>
                        <p class="text-[9px] text-gray-400">{{ \Carbon\Carbon::parse($returns[$label]['ref_date'])->format('d M Y') }}</p>
                    @else
                        <p class="text-xs font-bold text-gray-400 italic">N/A</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- Scheme Details -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-white dark:bg-[#0f172a]/80 border border-gray-200 dark:border-white/5 rounded-2xl p-6 shadow-sm">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-5 uppercase tracking-widest flex items-center gap-2">
                <i class="fas fa-info-circle text-indigo-500"></i> Scheme Details
            </h3>
            <div class="space-y-3">
                <div class="flex justify-between items-start py-2 border-b border-gray-100 dark:border-white/5">
                    <span class="text-xs text-gray-500">AMC Name</span>
                    <span class="text-xs font-bold text-gray-900 dark:text-white text-right max-w-[60%]">{{ $scheme->amc_name ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-white/5">
                    <span class="text-xs text-gray-500">Category</span>
                    <span class="text-xs font-bold text-amber-600 dark:text-amber-400">{{ $scheme->category ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-white/5">
                    <span class="text-xs text-gray-500">Type</span>
                    <span class="text-xs font-bold text-gray-900 dark:text-white">{{ $scheme->type ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between items-center py-2">
                    <span class="text-xs text-gray-500">ISIN (Reinvest)</span>
                    <span class="text-xs font-mono text-gray-600 dark:text-gray-400">{{ $scheme->isin_reinvest ?? 'N/A' }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-[#0f172a]/80 border border-gray-200 dark:border-white/5 rounded-2xl p-6 shadow-sm">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-5 uppercase tracking-widest flex items-center justify-between">
                <span class="flex items-center gap-2"><i class="fas fa-chart-area text-emerald-500"></i> NAV Chart (12M)</span>
            </h3>
            <canvas id="navChart" height="160"></canvas>
        </div>
    </div>

    <!-- NAV History Table -->
    <div class="mb-6">
        <h2 class="text-xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
            <i class="fas fa-history text-amber-500"></i> Full NAV History
        </h2>
    </div>

    <div class="bg-white dark:bg-[#0f172a]/80 backdrop-blur-xl border border-gray-200 dark:border-white/5 rounded-2xl shadow-sm overflow-hidden text-xs">
        <div class="p-6 overflow-x-auto">
            <table id="navTable" class="w-full text-left border-collapse">
                <thead>
                    <tr>
                        <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4">Date</th>
                        <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4 text-right">NAV (₹)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5"></tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
    $(document).ready(function() {
        $('#navTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 50,
            ajax: "{{ route('mutual-funds.show', $scheme->isin) }}",
            columns: [
                {
                    data: 'nav_date',
                    name: 'nav_date',
                    className: 'font-bold text-gray-700 dark:text-gray-300 h-12 px-4',
                    render: function(data) {
                        if (!data) return '—';
                        return new Date(data).toLocaleDateString('en-GB');
                    }
                },
                {
                    data: 'nav',
                    name: 'nav',
                    className: 'text-right font-black text-amber-600 dark:text-amber-400 px-4',
                    render: function(data) {
                        return data ? '₹' + parseFloat(data).toFixed(4) : '—';
                    }
                }
            ],
            order: [[0, 'desc']],
            dom: '<"flex justify-end p-2"f>rt<"flex justify-between items-center p-4"ip>',
        });

        // 12-month chart from inline data
        const chartRows = @json($chartData ?? []);
        if (chartRows.length) {
            const labels = chartRows.map(r => r.nav_date);
            const values = chartRows.map(r => parseFloat(r.nav));
            const isDark = document.documentElement.classList.contains('dark');

            new Chart(document.getElementById('navChart'), {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        data: values,
                        borderColor: '#d97706',
                        backgroundColor: 'rgba(217,119,6,0.08)',
                        borderWidth: 2,
                        pointRadius: 0,
                        fill: true,
                        tension: 0.3,
                    }]
                },
                options: {
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { display: false },
                        y: {
                            ticks: { color: isDark ? '#9ca3af' : '#6b7280', font: { size: 10 } },
                            grid: { color: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)' }
                        }
                    },
                    responsive: true,
                    interaction: { intersect: false, mode: 'index' },
                }
            });
        }
    });
</script>
@endpush
