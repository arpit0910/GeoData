@extends('layouts.app')

@section('header', 'User Tickets')

@section('content')
<div class="container-fluid">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Support Tickets</h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm">Manage and resolve user inquiries.</p>
    </div>

    <div class="bg-white dark:bg-richdark-surface rounded-xl shadow-sm border border-gray-200 dark:border-white/5 overflow-hidden">
        <div class="p-6">
            <table class="table w-full" id="tickets-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Category</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#tickets-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('admin.tickets.index') }}",
            columns: [
                { data: 'id', name: 'id' },
                { data: 'user.name', name: 'user.name' },
                { data: 'category.name', name: 'category.name' },
                { data: 'title', name: 'title' },
                { 
                    data: 'status', 
                    name: 'status',
                    render: function(data) {
                        const colors = {
                            'pending': 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-500',
                            'resolved': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-500',
                            'closed': 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-500'
                        };
                        return `<span class="px-2.5 py-0.5 rounded-full text-xs font-medium ${colors[data] || colors['pending']} capitalize">${data}</span>`;
                    }
                },
                { 
                    data: 'created_at', 
                    name: 'created_at',
                    render: function(data) {
                        return new Date(data).toLocaleDateString();
                    }
                },
                {
                    data: 'id',
                    render: function(data) {
                        return `
                            <a href="/tickets/${data}" class="text-amber-600 hover:text-amber-800 font-medium text-sm">View & Reply</a>
                        `;
                    }
                }
            ]
        });
    });
</script>
@endpush
