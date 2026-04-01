@extends('layouts.app')

@section('header', 'Ticket Sub-Categories')

@section('content')
<div class="container-fluid">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Ticket Sub-Categories</h2>
        <a href="{{ route('admin.ticket-sub-categories.create') }}" class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg transition-colors shadow-sm flex items-center">
            <i class="fas fa-plus mr-2"></i> Add Sub-Category
        </a>
    </div>

    <div class="bg-white dark:bg-richdark-surface rounded-xl shadow-sm border border-gray-200 dark:border-white/5 overflow-hidden">
        <div class="p-6">
            <table class="table w-full" id="subcategories-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Parent Category</th>
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
        $('#subcategories-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('admin.ticket-sub-categories.index') }}",
            columns: [
                { data: 'id', name: 'id' },
                { data: 'category.name', name: 'category.name' },
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
                    render: function(data, type, row) {
                        let editUrl = "{{ route('admin.ticket-sub-categories.edit', ':id') }}".replace(':id', data);
                        let deleteUrl = "{{ route('admin.ticket-sub-categories.destroy', ':id') }}".replace(':id', data);
                        return `
                            <div class="flex space-x-2">
                                <a href="${editUrl}" class="p-2 bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-500 rounded-lg hover:bg-amber-600 hover:text-white dark:hover:bg-amber-500 transition-all"><i class="fas fa-edit"></i></a>
                                <form action="${deleteUrl}" method="POST" class="inline delete-form-actual">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="p-2 bg-gray-50 dark:bg-white/5 text-gray-500 dark:text-gray-400 rounded-lg hover:text-red-600 dark:hover:text-red-500 hover:bg-gray-200 dark:hover:bg-white/10 transition-all delete-trigger" data-message="Are you sure you want to delete sub-category '${row.name}'?"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        `;
                    }
                }
            ]
        });

        $(document).on('click', '.delete-trigger', function() {
            const btn = $(this);
            const form = btn.closest('form');
            const message = btn.data('message');
            
            openDeleteModal(message, function() {
                form.submit();
            });
        });

        $(document).on('click', '.toggle-status', function() {
            const id = $(this).data('id');
            let toggleUrl = "{{ route('admin.ticket-sub-categories.toggle-status', ':id') }}".replace(':id', id);
            $.post(toggleUrl, {
                _token: '{{ csrf_token() }}'
            }, function(response) {
                if (response.success) {
                    $('#subcategories-table').DataTable().ajax.reload(null, false);
                }
            });
        });
    });
</script>
@endpush
