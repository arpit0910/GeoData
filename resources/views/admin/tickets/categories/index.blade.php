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
                        const checked = data ? 'checked' : '';
                        return `
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only peer toggle-status" data-id="${row.id}" ${checked}>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 dark:peer-focus:ring-amber-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-amber-600"></div>
                            </label>
                        `;
                    }
                },
                {
                    data: 'id',
                    render: function(data) {
                        return `
                            <div class="flex space-x-2">
                                <a href="/ticket-categories/${data}/edit" class="text-blue-600 hover:text-blue-800"><i class="fas fa-edit"></i></a>
                                <form action="/ticket-categories/${data}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        `;
                    }
                }
            ]
        });

        $(document).on('change', '.toggle-status', function() {
            const id = $(this).data('id');
            $.post(`/admin/ticket-categories/${id}/toggle-status`, {
                _token: '{{ csrf_token() }}'
            });
        });
    });
</script>
@endpush
