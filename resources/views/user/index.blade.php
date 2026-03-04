@extends('layouts.app')

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="p-6 border-b border-gray-200 flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
        <div>
            <h3 class="text-xl font-bold text-gray-900">Registered Users</h3>
            <p class="text-sm text-gray-500 mt-1">Manage all system users, API keys, and statuses.</p>
        </div>
        <a href="{{ route('user.create') }}" class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-all transform hover:scale-105">
            <i class="fas fa-user-plus mr-2"></i>
            Create New User
        </a>
    </div>

    <div class="p-6">
        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-md flex items-center shadow-sm">
                <i class="fas fa-check-circle mr-3"></i>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table id="usersTable" class="w-full text-left border-collapse">
                <thead>
                    <tr>
                        <th class="px-4 py-3">User Details</th>
                        <th class="px-4 py-3">Company</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    var table = $('#usersTable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 100,
        ajax: "{{ route('user.list') }}",
        dom: '<"flex flex-col sm:flex-row justify-between items-center mb-4"lf>rt<"flex flex-col sm:flex-row justify-between items-center mt-4"ip>',
        columns: [
            { 
                data: 'name', 
                name: 'name',
                render: function(data, type, row) {
                    return `
                        <div class="flex items-center">
                            <div class="h-10 w-10 flex-shrink-0 bg-amber-100 rounded-full flex items-center justify-center text-amber-700 font-bold mr-3">
                                ${data.charAt(0).toUpperCase()}
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900">${data}</div>
                                <div class="text-xs text-gray-500">${row.email}</div>
                            </div>
                        </div>
                    `;
                }
            },
            { data: 'company_name', name: 'company_name' },
            { 
                data: 'status', 
                name: 'status',
                className: 'text-center',
                render: function(data, type, row) {
                    const isActive = data === 'active';
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
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-right',
                render: function(data, type, row) {
                    return `
                        <div class="flex justify-end space-x-2">
                            <a href="/user/show/${row.id}" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="/user/edit/${row.id}" class="p-2 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button class="delete-btn p-2 text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" data-id="${row.id}" title="Delete">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ]
    });

    // Status Toggle with modern feedback
    $(document).on('click', '.status-toggle', function() {
        var id = $(this).data('id');
        var btn = $(this);
            $.ajax({
            url: `/user/toggle-status/${id}`,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                table.ajax.reload(null, false);
            },
            error: function(xhr) {
                alert('Error updating status');
            }
        });
    });

    // Modern Delete confirmation
    $(document).on('click', '.delete-btn', function() {
        var id = $(this).data('id');
        if(confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
            $.ajax({
                url: `/user/delete/${id}`,
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    table.ajax.reload(null, false);
                },
                error: function(xhr) {
                    alert('Error deleting user');
                }
            });
        }
    });
});
</script>
@endpush
@endsection
