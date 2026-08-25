@extends('layouts.app')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Subscription Features</h1>
        <p class="mt-1 text-sm text-gray-600">Manage feature flags that can be attached to plans.</p>
    </div>
    <a href="{{ route('subscription-features.create') }}" class="inline-flex items-center px-4 py-2 bg-amber-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-700 transition ease-in-out duration-150">
        <i class="fas fa-plus mr-2"></i> Add Feature
    </a>
</div>

@if(session('success'))
<div class="mb-4 bg-green-50 border-l-4 border-green-400 p-4">
    <p class="text-sm text-green-700">{{ session('success') }}</p>
</div>
@endif

<div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <div class="p-6 bg-white border-b border-gray-200 overflow-x-auto">
        <table id="featuresTable" class="w-full text-left border-collapse">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Key</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100"></tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const table = $('#featuresTable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 100,
        ajax: "{{ route('subscription-features.index') }}",
        language: {
            emptyTable: "No features found",
            zeroRecords: "No matching features found"
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'name', name: 'name' },
            { data: 'key', name: 'key', render: function(data) { return '<code>' + data + '</code>'; } },
            { data: 'description', name: 'description', render: function(data) { return data || '-'; } },
            {
                data: 'is_active',
                name: 'is_active',
                className: 'text-center',
                render: function(data, type, row) {
                    const isActive = (data == 1 || data === true);
                    return `<div class="flex justify-center"><button type="button" class="status-toggle inline-flex h-6 w-11 rounded-full border-2 border-transparent transition-colors ${isActive ? 'bg-amber-600' : 'bg-gray-200'}" data-id="${row.id}"><span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition ${isActive ? 'translate-x-5' : 'translate-x-0'}"></span></button></div>`;
                }
            },
            {
                data: 'id',
                name: 'action',
                orderable: false,
                searchable: false,
                className: 'text-right whitespace-nowrap',
                render: function(data, type, row) {
                    const editUrl = "{{ route('subscription-features.edit', ':id') }}".replace(':id', data);
                    const deleteUrl = "{{ route('subscription-features.destroy', ':id') }}".replace(':id', data);
                    const csrf = '{{ csrf_token() }}';
                    return `<div class="flex justify-end space-x-2">
                        <a href="${editUrl}" class="p-2 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="Edit"><i class="fas fa-edit"></i></a>
                        <form action="${deleteUrl}" method="POST" class="inline-block">
                            <input type="hidden" name="_token" value="${csrf}">
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="p-2 text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" onclick="return confirm('Delete feature ${row.name}?')" title="Delete"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>`;
                }
            }
        ],
        order: [[0, 'asc']]
    });

    $(document).on('click', '.status-toggle', function() {
        const id = $(this).data('id');
        const toggleUrl = "{{ route('subscription-features.toggle-status', ':id') }}".replace(':id', id);
        $.post(toggleUrl, { _token: '{{ csrf_token() }}' }, function() {
            table.ajax.reload(null, false);
        });
    });
});
</script>
@endpush
