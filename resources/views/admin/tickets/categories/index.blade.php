@extends('layouts.app')

@section('header', 'Ticket Categories')

@section('content')
<div class="container-fluid">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Ticket Categories</h2>
        <a href="{{ route('admin.ticket-categories.create') }}" class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg transition-colors shadow-sm flex items-center">
            <i class="fas fa-plus mr-2"></i> Add Category
        </a>
    </div>

    <div class="bg-white dark:bg-richdark-surface rounded-xl shadow-sm border border-gray-200 dark:border-white/5 overflow-hidden">
        <div class="p-6">
            <table class="table w-full" id="categories-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Status</th>
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
        $('#categories-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('admin.ticket-categories.index') }}",
            columns: [
                { data: 'id', name: 'id' },
                { data: 'name', name: 'name' },
                { 
                    data: 'status', 
                    name: 'status',
                    render: function(data, type, row) {
                        return `
                            <button type="button" class="toggle-status px-3 py-1 text-[10px] font-black tracking-widest rounded-full transition-all border
                                ${data ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20 hover:bg-emerald-500 hover:text-white' : 'bg-red-500/10 text-red-600 border-red-500/20 hover:bg-red-600 hover:text-white'}" 
                                data-id="${row.id}">
                                ${data ? 'ACTIVE' : 'INACTIVE'}
                            </button>
                        `;
                    }
                },
                {
                    data: 'id',
                    render: function(data) {
                        return `
                            <div class="flex space-x-2">
                                <a href="/admin/ticket-categories/${data}/edit" class="p-2 bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-500 rounded-lg hover:bg-amber-600 hover:text-white dark:hover:bg-amber-500 transition-all"><i class="fas fa-edit"></i></a>
                                <form action="/admin/ticket-categories/${data}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-500 rounded-lg hover:bg-red-600 hover:text-white dark:hover:bg-red-500 transition-all"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        `;
                    }
                }
            ]
        });

        $(document).on('click', '.toggle-status', function() {
            const id = $(this).data('id');
            const btn = $(this);
            $.post(`/admin/ticket-categories/${id}/toggle-status`, {
                _token: '{{ csrf_token() }}'
            }, function(response) {
                if (response.success) {
                    $('#categories-table').DataTable().ajax.reload(null, false);
                }
            });
        });
    });
</script>
@endpush
