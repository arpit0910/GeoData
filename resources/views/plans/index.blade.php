@extends('layouts.app')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Subscription Plans</h1>
        <p class="mt-1 text-sm text-gray-600">Manage the subscription plans and API limits.</p>
    </div>
    <div class="flex items-center space-x-4">
        <a href="{{ route('plans.create') }}" class="inline-flex items-center px-4 py-2 bg-amber-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-700 active:bg-amber-900 focus:outline-none focus:border-amber-900 focus:ring ring-amber-300 disabled:opacity-25 transition ease-in-out duration-150">
            <i class="fas fa-plus mr-2"></i> Add Plan
        </a>
    </div>
</div>

@if(session('success'))
<div class="mb-4 bg-green-50 border-l-4 border-green-400 p-4">
    <div class="flex">
        <div class="flex-shrink-0">
            <i class="fas fa-check-circle text-green-400"></i>
        </div>
        <div class="ml-3">
            <p class="text-sm text-green-700">
                {{ session('success') }}
            </p>
        </div>
    </div>
</div>
@endif

<div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <div class="p-6 bg-white border-b border-gray-200 overflow-x-auto">
        <table id="plansTable" class="w-full text-left border-collapse">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>API Limit</th>
                    <th>Amount</th>
                    <th>Discount</th>
                    <th>Status</th>
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
        var table = $('#plansTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 25,
            ajax: "{{ route('plans.index') }}",
            columns: [
                { data: 'id', name: 'id' },
                { data: 'name', name: 'name' },
                { 
                    data: 'billing_cycle', 
                    name: 'billing_cycle',
                    render: function(data) {
                        return data ? '<span class="px-2 py-1 text-xs font-medium rounded-md bg-blue-50 text-blue-700 border border-blue-200">' + data.charAt(0).toUpperCase() + data.slice(1) + '</span>' : '-';
                    }
                },
                { 
                    data: 'api_hits_limit', 
                    name: 'api_hits_limit',
                    render: function(data) {
                        return data ? new Intl.NumberFormat().format(data) : 'Unlimited';
                    }
                },
                { 
                    data: 'amount', 
                    name: 'amount',
                    render: function(data) {
                        return '$' + parseFloat(data).toFixed(2);
                    }
                },
                { 
                    data: 'discount_amount', 
                    name: 'discount_amount',
                    render: function(data) {
                        return data ? '$' + parseFloat(data).toFixed(2) : '-';
                    }
                },
                { 
                    data: 'status', 
                    name: 'status',
                    className: 'text-center',
                    render: function(data, type, row) {
                        const isActive = (data == 1 || data == true);
                        return `
                            <div class="flex justify-center">
                                <button type="button" class="status-toggle group relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-amber-600 focus:ring-offset-2 ${isActive ? 'bg-amber-600' : 'bg-gray-200'}" data-id="${row.id}">
                                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out ${isActive ? 'translate-x-5' : 'translate-x-0'}"></span>
                                </button>
                            </div>
                        `;
                    }
                },
                { 
                    data: 'id', 
                    name: 'action', 
                    orderable: false, 
                {
                    data: 'id',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    className: 'text-right whitespace-nowrap',
                    render: function(data) {
                        let editUrl = `/plans/${data}/edit`;
                        let deleteUrl = `/plans/${data}`;
                        let csrf = '{{ csrf_token() }}';

                        return `
                            <div class="flex justify-end space-x-2">
                                <a href="${editUrl}" class="p-2 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="Edit"><i class="fas fa-edit"></i></a>
                                <form action="${deleteUrl}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this plan?');">
                                    <input type="hidden" name="_token" value="${csrf}">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="p-2 text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        `;
                    }
                }
            ],
            order: [[0, "asc"]],
            dom: '<"flex flex-col sm:flex-row justify-between items-center mb-4"lf>rt<"flex flex-col sm:flex-row justify-between items-center mt-4"ip>',
        });

        // Status Toggle with modern feedback
        $(document).on('click', '.status-toggle', function() {
            var id = $(this).data('id');
            $.ajax({
                url: `/plans/${id}/toggle-status`,
                type: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    if(response.success) {
                        table.ajax.reload(null, false);
                    }
                },
                error: function(xhr) {
                    alert('Error updating status');
                }
            });
        });
    });
</script>
@endpush
