@extends('layouts.app')

@section('header')
    Countries
@endsection

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Countries</h1>
        <p class="mt-1 text-sm text-gray-600">A list of all the countries in the database.</p>
    </div>
    <a href="{{ route('countries.create') }}" class="inline-flex items-center px-4 py-2 bg-amber-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-700 active:bg-amber-900 focus:outline-none focus:border-amber-900 focus:ring ring-amber-300 disabled:opacity-25 transition ease-in-out duration-150">
        <i class="fas fa-plus mr-2"></i> Add Country
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
    <div class="p-6 bg-white border-b border-gray-200">
        <table id="countriesTable" class="min-w-full divide-y divide-gray-200 dataTable no-footer">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Native</th>
                    <th>ISO2</th>
                    <th>Capital</th>
                    <th>Currency</th>
                    <th>Region</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @php $sno = 1; @endphp
                @foreach($countries as $country)
                <tr>
                    <td>{{ $sno++ }}</td>
                    <td>
                        {{ $country->name }}
                    </td>
                    <td>
                        {{ $country->native ?? 'N/A' }}
                    </td>
                    <td>{{ $country->iso2 }}</td>
                    <td>{{ $country->capital }}</td>
                    <td>{{ $country->currency }} ({{ $country->currency_symbol }})</td>
                    <td>
                        {{ $country->Region?$country->Region->name : 'N/A' }}
                        @if($country->SubRegion)
                            <div class="text-xs text-gray-500">{{ $country->SubRegion->name }}</div>
                        @endif
                    </td>
                    <td class="text-right text-sm font-medium whitespace-nowrap">
                        <a href="{{ route('countries.edit', $country->id) }}" class="text-indigo-600 hover:text-indigo-900 mr-3" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <div class="mt-4">
            {{ $countries->links() }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#countriesTable').DataTable({
            "paging": false, // We use Laravel's pagination for now
            "info": false,
            "searching": true,
            "order": [[1, "asc"]], // Sort by name ascending
            "columnDefs": [
                { "orderable": false, "targets": 6 } // Disable sorting on actions column
            ]
        });
    });
</script>
@endpush
