@extends('layouts.app')
@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Timezones</h1>
        <p class="mt-1 text-sm text-gray-600">A list of all the timezones in the database.</p>
    </div>
    <a href="{{ route('timezones.create') }}" class="inline-flex items-center px-4 py-2 bg-amber-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-700 active:bg-amber-900 focus:outline-none focus:border-amber-900 focus:ring ring-amber-300 disabled:opacity-25 transition ease-in-out duration-150">
        <i class="fas fa-plus mr-2"></i> Add Timezone
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
        <table id="timezonesTable" class="w-full text-left border-collapse">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Zone Name</th>
                    <th>Country</th>
                    <th>GMT Offset</th>
                    <th>Abbreviation</th>
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
        $('#timezonesTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 100,
            ajax: "{{ route('timezones.index') }}",
            columns: [
                { data: 'id', name: 'id' },
                { data: 'zone_name', name: 'zone_name' },
                { 
                    data: 'country', 
                    name: 'country.name',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return row.country ? row.country.name : 'N/A';
                    } 
                },
                { data: 'gmt_offset_name', name: 'gmt_offset_name' },
                { data: 'abbreviation', name: 'abbreviation' },
                { 
                    data: 'id', 
                    name: 'action', 
                    orderable: false, 
                    searchable: false,
                    className: 'text-right',
                    render: function(data) {
                        let editUrl = `/timezones/${data}/edit`;
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
