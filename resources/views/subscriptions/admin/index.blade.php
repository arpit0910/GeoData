@extends('layouts.app')

@section('header')
    Subscriptions
@endsection

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Subscriptions</h1>
        <p class="mt-1 text-sm text-gray-600">View and manage user subscriptions.</p>
    </div>
</div>

<div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <div class="p-6 bg-white border-b border-gray-200 overflow-x-auto">
        <table id="subscriptionsTable" class="w-full text-left border-collapse">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Plan</th>
                    <th>Amount Paid</th>
                    <th>Status</th>
                    <th>Expires At</th>
                    <th>Gateway Order ID</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        var table = $('#subscriptionsTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 25,
            ajax: "{{ route('admin.subscriptions.index') }}",
            language: {
                emptyTable: "No subscriptions found",
                zeroRecords: "No matching subscriptions found"
            },
            columns: [
                { data: 'id', name: 'id' },
                { 
                    data: 'user', 
                    name: 'user.name',
                    render: function(data) {
                        return data ? data.name : '-';
                    }
                },
                { 
                    data: 'plan', 
                    name: 'plan.name',
                    render: function(data) {
                        return data ? data.name : '-';
                    }
                },
                { 
                    data: 'amount_paid', 
                    name: 'amount_paid',
                    render: function(data) {
                        return '₹' + parseFloat(data).toFixed(2);
                    }
                },
                { 
                    data: 'status', 
                    name: 'status',
                    render: function(data) {
                        let badgeClass = data === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                        return '<span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ' + badgeClass + '">' + (data ? data.charAt(0).toUpperCase() + data.slice(1) : '-') + '</span>';
                    }
                },
                { 
                    data: 'expires_at', 
                    name: 'expires_at',
                    render: function(data) {
                        if (!data) return '-';
                        let date = new Date(data);
                        return date.toLocaleDateString();
                    }
                },
                { 
                    data: 'razorpay_order_id', 
                    name: 'razorpay_order_id'
                },
                {
                    data: 'id',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    className: 'text-right whitespace-nowrap',
                    render: function(data) {
                        let showUrl = `/admin/subscriptions/${data}`;
                        return `
                            <div class="flex justify-end space-x-2">
                                <a href="${showUrl}" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="View Details"><i class="fas fa-eye"></i></a>
                            </div>
                        `;
                    }
                }
            ],
            order: [[0, "desc"]],
            dom: '<"flex flex-col sm:flex-row justify-between items-center mb-4"lf>rt<"flex flex-col sm:flex-row justify-between items-center mt-4"ip>',
        });
    });
</script>
@endpush
