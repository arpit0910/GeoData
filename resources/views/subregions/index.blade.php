@extends('layouts.app')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Sub Regions</h1>
        <p class="mt-1 text-sm text-gray-600">A list of all the sub regions in the database.</p>
    </div>
    <a href="{{ route('subregions.create') }}" class="inline-flex items-center px-4 py-2 bg-amber-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-700 active:bg-amber-900 focus:outline-none focus:border-amber-900 focus:ring ring-amber-300 disabled:opacity-25 transition ease-in-out duration-150">
        <i class="fas fa-plus mr-2"></i> Add Sub Region
    </a>
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
        <table id="subRegionsTable" class="w-full text-left border-collapse">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Region Name</th>
                    <th>WikiData ID</th>
                    <th>Actions</th>
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
        $('#subRegionsTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 100,
            ajax: "{{ route('subregions.index') }}",
            columns: [
                { data: 'id', name: 'id' },
                { data: 'name', name: 'name' },
                { 
                    data: 'region', 
                    name: 'region.name',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return row.region ? row.region.name : '';
                    } 
                },
                { data: 'wikiDataId', name: 'wikiDataId' },
                { 
                    data: 'id', 
                    name: 'action', 
                    orderable: false, 
                    searchable: false,
                    className: 'text-right',
                    render: function(data) {
                        let editUrl = `/subregions/${data}/edit`;
                        return `<a href="${editUrl}" class="text-indigo-600 hover:text-indigo-900 mr-3" title="Edit"><i class="fas fa-edit"></i></a>`;
                    }
                }
            ],
            order: [[0, "asc"]],
            dom: '<"flex flex-col sm:flex-row justify-between items-center mb-4"lf>rt<"flex flex-col sm:flex-row justify-between items-center mt-4"ip>',
        });
    });
</script>
@endpush
