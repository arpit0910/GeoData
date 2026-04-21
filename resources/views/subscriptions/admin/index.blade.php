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
                    <th>Credits</th>
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

@section('modals')
<!-- Assign Credits Modal -->
<div id="assignCreditsModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="assignCreditsForm">
                @csrf
                <input type="hidden" id="subscription_id" name="subscription_id">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-coins text-blue-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Assign Credits</h3>
                            <div class="mt-4">
                                <p class="text-sm text-gray-500 mb-4">Assign extra API credits to <span id="userName" class="font-bold text-gray-900"></span>'s account.</p>
                                <label for="credits" class="block text-sm font-medium text-gray-700">Credits to Assign</label>
                                <input type="number" name="credits" id="creditsInput" min="1" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="e.g. 500" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Assign
                    </button>
                    <button type="button" onclick="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
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
        var table = $('#subscriptionsTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 100,
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
                    data: 'available_credits',
                    name: 'available_credits',
                    render: function(data) {
                        return '<span class="font-semibold">' + data + '</span>';
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
                    render: function(data, type, row) {
                        let showUrl = `/admin/subscriptions/${data}`;
                        let userName = row.user ? row.user.name : 'User';
                        return `
                            <div class="flex justify-end space-x-2">
                                <button onclick="openAssignModal(${data}, '${userName}')" class="p-2 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="Assign Credits"><i class="fas fa-coins"></i></button>
                                <a href="${showUrl}" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="View Details"><i class="fas fa-eye"></i></a>
                            </div>
                        `;
                    }
                }
            ],
            order: [[0, "desc"]],
            dom: '<"flex flex-col sm:flex-row justify-between items-center mb-4"lf>rt<"flex flex-col sm:flex-row justify-between items-center mt-4"ip>',
        });

        $('#assignCreditsForm').on('submit', function(e) {
            e.preventDefault();
            const id = $('#subscription_id').val();
            const credits = $('#creditsInput').val();
            
            $.ajax({
                url: `/admin/subscriptions/${id}/assign-credits`,
                type: 'POST',
                data: {
                    _token: $('input[name="_token"]').val(),
                    credits: credits
                },
                success: function(response) {
                    if (response.status) {
                        toastr.success(response.message);
                        closeModal();
                        table.ajax.reload(null, false);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    let msg = 'Something went wrong';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    toastr.error(msg);
                }
            });
        });
    });

    function openAssignModal(id, name) {
        $('#subscription_id').val(id);
        $('#userName').text(name);
        $('#creditsInput').val('');
        $('#assignCreditsModal').removeClass('hidden');
    }

    function closeModal() {
        $('#assignCreditsModal').addClass('hidden');
    }
</script>
@endpush
