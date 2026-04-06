@extends('layouts.app')

@section('header', 'Currency Conversions')

@section('content')
<div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">Currency Conversions</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 font-medium">Latest exchange rates for countries relative to USD and INR.</p>
    </div>
    <form action="{{ route('admin.currency-conversions.sync') }}" method="POST">
        @csrf
        <button type="submit" class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-bold rounded-xl shadow-lg text-white bg-amber-600 hover:bg-amber-700 transition-all hover:scale-[1.02] active:scale-[0.98]">
            <i class="fas fa-sync-alt mr-2"></i> Sync Latest Rates
        </button>
    </form>
</div>

@if(session('success'))
<div class="mb-6 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 rounded-xl p-4 flex items-center">
    <i class="fas fa-check-circle text-emerald-500 mr-3 text-lg"></i>
    <p class="text-sm font-medium text-emerald-700 dark:text-emerald-400">{{ session('success') }}</p>
</div>
@endif

@if(session('error'))
<div class="mb-6 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 rounded-xl p-4 flex items-center">
    <i class="fas fa-exclamation-circle text-red-500 mr-3 text-lg"></i>
    <p class="text-sm font-medium text-red-700 dark:text-red-400">{{ session('error') }}</p>
</div>
@endif

<div class="bg-white dark:bg-[#0f172a]/80 dark:backdrop-blur-xl border border-gray-200 dark:border-white/5 rounded-2xl shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-white/5 flex items-center justify-between">
        <h2 class="text-lg font-bold text-gray-800 dark:text-white flex items-center">
            <i class="fas fa-exchange-alt text-amber-500 mr-2.5"></i> All Rates
        </h2>
        <div class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">
            {{ $rates->count() }} <span class="font-medium normal-case">currencies</span>
        </div>
    </div>

    <div class="overflow-x-auto p-6">
        <table id="currencyConversionsTable" class="w-full text-left bg-transparent">
            <thead>
                <tr class="bg-gray-50/50 dark:bg-white/5 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    <th class="px-6 py-4">Country</th>
                    <th class="px-6 py-4">Currency Code</th>
                    <th class="px-6 py-4">USD Rate <span class="normal-case font-normal opacity-70">(1 USD = ?)</span></th>
                    <th class="px-6 py-4">INR Rate <span class="normal-case font-normal opacity-70">(1 INR = ?)</span></th>
                    <th class="px-6 py-4">Last Updated</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                @foreach($rates as $rate)
                <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02] transition-colors" data-updated="{{ $rate->updated_at->format('Y-m-d H:i:s') }}">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-bold text-gray-900 dark:text-white">
                            {{ $rate->country->name ?? 'N/A' }}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-indigo-100 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400 border border-indigo-200/50 dark:border-indigo-500/20">
                            {{ $rate->currency }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-black text-gray-900 dark:text-white">{{ number_format($rate->usd_conversion_rate, 4) }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-black text-gray-900 dark:text-white">{{ number_format($rate->inr_conversion_rate, 4) }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ $rate->updated_at->format('M d, Y') }}</div>
                        <div class="text-xs text-gray-400 dark:text-gray-500">{{ $rate->updated_at->format('h:i A') }}</div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#currencyConversionsTable').DataTable({
            processing: true,
            order: [[0, 'asc']],
            dom: '<"flex flex-col sm:flex-row justify-between items-center mb-4"lf>rt<"flex flex-col sm:flex-row justify-between items-center mt-4"ip>',
            language: {
                searchPlaceholder: "Search countries, currencies...",
                search: ""
            },
            columnDefs: [
                { orderable: true, targets: '_all' }
            ]
        });
    });
</script>
@endpush
