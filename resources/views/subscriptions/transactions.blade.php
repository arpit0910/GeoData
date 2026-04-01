@extends('layouts.app')

@section('header', 'Transaction History')

@section('content')
<div class="mb-6 flex justify-between items-start">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white group flex items-center">
            <i class="fas fa-receipt text-amber-500 mr-3"></i> My Transactions
        </h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            View your billing history and download receipts.
        </p>
    </div>
    
    <div class="flex items-center space-x-4">
        <div class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest hidden sm:block">
            Last Updated: <span class="text-gray-700 dark:text-gray-300">{{ now()->format('h:i:s A') }}</span>
        </div>
        <button onclick="window.location.reload()" class="px-3 py-1.5 bg-white dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-lg text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-50 hover:text-amber-600 dark:hover:bg-white/10 dark:hover:text-amber-500 transition-all shadow-sm flex items-center cursor-pointer">
            <i class="fas fa-sync-alt mr-1.5"></i> Refresh
        </button>
    </div>
</div>

<div class="space-y-6">
    <!-- Transactions List -->
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/5 dark:bg-[#0f172a]/80 dark:backdrop-blur-xl">
        <div class="border-b border-gray-100 px-6 py-4 dark:border-white/5">
            <h3 class="text-lg font-bold text-gray-800 dark:text-white">Recent Transactions</h3>
        </div>

        <div class="overflow-x-auto p-6">
            <table id="userTransactionsTable" class="w-full text-left bg-transparent">
                <thead>
                    <tr class="bg-gray-50/50 dark:bg-white/5 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        <th class="px-6 py-4">Date</th>
                        <th class="px-6 py-4">Description</th>
                        <th class="px-6 py-4">Amount</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right">Receipt</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                </tbody>
            </table>
        </div>
    </div>

    <!-- Help Card -->
    <div class="flex items-center space-x-4 rounded-2xl bg-blue-50 p-4 dark:bg-blue-500/5 dark:border dark:border-blue-500/10">
        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400">
            <i class="fas fa-info-circle"></i>
        </div>
        <div>
            <h4 class="text-sm font-bold text-blue-900 dark:text-blue-300">Need help with a payment?</h4>
            <p class="text-xs text-blue-700 dark:text-blue-400/70">If you have any questions about your transactions, please contact our support team.</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#userTransactionsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('transactions.index') }}",
            columns: [
                { 
                    data: 'created_at', 
                    name: 'created_at',
                    render: function(data) {
                        let date = new Date(data);
                        return `<div class="text-sm font-medium text-gray-900 dark:text-white">${date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">${date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}</div>`;
                    }
                },
                { 
                    data: 'plan_name', 
                    name: 'plan_name',
                    render: function(data, type, row) {
                        let couponHTML = row.coupon_code ? `<div class="text-xs text-amber-600 dark:text-amber-500">Coupon: ${row.coupon_code}</div>` : '';
                        return `<div class="flex items-center">
                                    <div class="mr-3 flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-amber-600 dark:bg-amber-500/10 dark:text-amber-500">
                                        <i class="fas fa-bolt text-xs"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-gray-900 dark:text-white">${data}</div>
                                        ${couponHTML}
                                    </div>
                                </div>`;
                    }
                },
                { 
                    data: 'amount', 
                    name: 'amount',
                    render: function(data, type, row) {
                        let finalAmount = parseFloat(data) - parseFloat(row.discount_amount);
                        let discountHTML = row.discount_amount > 0 ? `<div class="text-xs text-red-500 line-through">₹${parseFloat(data).toFixed(2)}</div>` : '';
                        return `<div class="text-sm font-bold text-gray-900 dark:text-white">₹${finalAmount.toFixed(2)}</div>${discountHTML}`;
                    }
                },
                { 
                    data: 'status', 
                    name: 'status',
                    className: 'text-center',
                    render: function(data) {
                        if (data === 'success') {
                            return `<span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-500/10 dark:text-green-500">
                                        <span class="mr-1.5 h-1.5 w-1.5 rounded-full bg-green-600 dark:bg-green-500"></span>
                                        Completed
                                    </span>`;
                        } else {
                            return `<span class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700 dark:bg-red-500/10 dark:text-red-500">
                                        <span class="mr-1.5 h-1.5 w-1.5 rounded-full bg-red-600 dark:bg-red-500"></span>
                                        Failed
                                    </span>`;
                        }
                    }
                },
                { 
                    data: 'id', 
                    name: 'receipt',
                    orderable: false,
                    className: 'text-right',
                    render: function(data, type, row) {
                        return `<a href="/transactions/${row.id}/receipt" target="_blank" class="text-amber-600 hover:text-amber-700 font-bold dark:text-amber-500 dark:hover:text-amber-400">
                                    <i class="fas fa-file-invoice mr-1 text-xs"></i> RECEIPT
                                </a>`;
                    }
                }
            ],
            dom: '<"flex flex-col sm:flex-row justify-between items-center mb-4"lf>rt<"flex flex-col sm:flex-row justify-between items-center mt-4"ip>',
            language: {
                searchPlaceholder: "Search transactions...",
                search: ""
            }
        });
    });

    // Auto-refresh the page periodically (every 60 seconds)
    setTimeout(function() {
        window.location.reload();
    }, 60000);
</script>
@endpush
