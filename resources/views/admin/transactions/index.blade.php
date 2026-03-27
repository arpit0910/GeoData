@extends('layouts.app')

@section('header', 'Transaction History')

@section('content')
<div class="space-y-6">
    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-[#161e2d] p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-white/5 transition-all hover:shadow-md group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Volume</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">₹{{ number_format(\App\Models\TransactionHistory::where('status', 'success')->sum('amount'), 2) }}</h3>
                </div>
                <div class="h-12 w-12 rounded-xl bg-green-100 dark:bg-green-500/10 flex items-center justify-center text-green-600 dark:text-green-500 group-hover:scale-110 transition-transform">
                    <i class="fas fa-chart-line text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-[#161e2d] p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-white/5 transition-all hover:shadow-md group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Discount</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">₹{{ number_format(\App\Models\TransactionHistory::sum('discount_amount'), 2) }}</h3>
                </div>
                <div class="h-12 w-12 rounded-xl bg-amber-100 dark:bg-amber-500/10 flex items-center justify-center text-amber-600 dark:text-amber-500 group-hover:scale-110 transition-transform">
                    <i class="fas fa-tags text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-[#161e2d] p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-white/5 transition-all hover:shadow-md group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Successful Pymts</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ \App\Models\TransactionHistory::where('status', 'success')->count() }}</h3>
                </div>
                <div class="h-12 w-12 rounded-xl bg-indigo-100 dark:bg-indigo-500/10 flex items-center justify-center text-indigo-600 dark:text-indigo-500 group-hover:scale-110 transition-transform">
                    <i class="fas fa-check-circle text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table -->
    <div class="bg-white dark:bg-[#0f172a]/80 dark:backdrop-blur-xl border border-gray-200 dark:border-white/5 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-white/5 flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-800 dark:text-white">All System Transactions</h2>
            <div class="flex space-x-2">
                <button class="px-4 py-2 bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 text-gray-600 dark:text-gray-300 rounded-xl text-sm font-bold transition-all flex items-center">
                    <i class="fas fa-download mr-2 opacity-60"></i> Export CSV
                </button>
            </div>
        </div>

        <div class="overflow-x-auto p-6">
            <table id="adminTransactionsTable" class="w-full text-left bg-transparent">
                <thead>
                    <tr class="bg-gray-50/50 dark:bg-white/5 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        <th class="px-6 py-4">Date & Time</th>
                        <th class="px-6 py-4">Customer</th>
                        <th class="px-6 py-4">Plan Details</th>
                        <th class="px-6 py-4">Financials</th>
                        <th class="px-6 py-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#adminTransactionsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('admin.transactions.index') }}",
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
                    data: 'user', 
                    name: 'user.first_name',
                    render: function(data, type, row) {
                        if (!data) return 'N/A';
                        let initials = (data.first_name?.[0] || '') + (data.last_name?.[0] || '');
                        return `<div class="flex items-center">
                                    <div class="h-9 w-9 rounded-full bg-amber-500 flex items-center justify-center text-white font-bold text-xs ring-2 ring-white dark:ring-white/10 shadow-sm mr-3">
                                        ${initials}
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-gray-900 dark:text-white">${data.first_name} ${data.last_name}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">${data.email}</div>
                                    </div>
                                </div>`;
                    }
                },
                { 
                    data: 'plan_name', 
                    name: 'plan_name',
                    render: function(data, type, row) {
                        return `<div class="text-sm font-bold text-gray-900 dark:text-white">${data}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 uppercase">${row.billing_cycle}</div>`;
                    }
                },
                { 
                    data: 'amount', 
                    name: 'amount',
                    render: function(data, type, row) {
                        let amount = parseFloat(data);
                        let discount = parseFloat(row.discount_amount || 0);
                        let finalAmount = amount - discount;
                        let discountHTML = discount > 0 ? 
                            `<div class="flex items-center text-[10px] space-x-1.5 mt-0.5">
                                <span class="text-red-500 line-through">₹${amount.toFixed(2)}</span>
                                <span class="px-1.5 py-0.5 bg-red-100 dark:bg-red-500/10 text-red-600 dark:text-red-400 rounded-md font-bold">-₹${discount.toFixed(2)}</span>
                            </div>` : '';
                        return `<div class="text-sm font-black text-gray-900 dark:text-white">₹${finalAmount.toFixed(2)}</div>${discountHTML}`;
                    }
                },
                { 
                    data: 'status', 
                    name: 'status',
                    className: 'text-center',
                    render: function(data) {
                        if (data === 'success') {
                            return `<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-500 border border-green-200/50 dark:border-green-500/20">
                                        <i class="fas fa-check-circle mr-1.5"></i> Success
                                    </span>`;
                        } else {
                            return `<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-500 border border-red-200/50 dark:border-red-500/20">
                                        <i class="fas fa-times-circle mr-1.5"></i> Failed
                                    </span>`;
                        }
                    }
                }
            ],
            dom: '<"flex flex-col sm:flex-row justify-between items-center mb-4"lf>rt<"flex flex-col sm:flex-row justify-between items-center mt-4"ip>',
            language: {
                searchPlaceholder: "Search payments...",
                search: ""
            }
        });
    });
</script>
@endpush
@endsection
