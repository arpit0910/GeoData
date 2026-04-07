@extends('layouts.app')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Bank Branches</h1>
        <p class="mt-1 text-sm text-gray-600">A detailed list of all the branches of various banks.</p>
    </div>
    <div class="flex items-center space-x-4">
        <a href="{{ route('bank-branches.create') }}" class="inline-flex items-center px-4 py-2 bg-amber-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-700 active:bg-amber-900 focus:outline-none focus:border-amber-900 focus:ring ring-amber-300 disabled:opacity-25 transition ease-in-out duration-150">
            <i class="fas fa-plus mr-2"></i> Add Branch
        </a>
    </div>
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
        <table id="branchesTable" class="w-full text-left border-collapse">
            <thead>
                <tr>
                    <th>Bank</th>
                    <th>IFSC</th>
                    <th>Branch</th>
                    <th>City</th>
                    <th>State</th>
                    <th>IMPS</th>
                    <th>RTGS</th>
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
        $('#branchesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('bank-branches.index') }}",
            columns: [
                { 
                    data: 'bank', 
                    name: 'bank.name',
                    render: function(data) {
                        return data ? data.name : 'N/A';
                    }
                },
                { data: 'ifsc', name: 'ifsc' },
                { data: 'branch', name: 'branch' },
                { 
                    data: 'city', 
                    name: 'city.name',
                    render: function(data) {
                        return data ? data.name : 'N/A';
                    }
                },
                { 
                    data: 'state', 
                    name: 'state.name',
                    render: function(data) {
                        return data ? data.name : 'N/A';
                    }
                },
                { 
                    data: 'imps', 
                    name: 'imps',
                    render: function(data) {
                        return data ? '<span class="text-green-600"><i class="fas fa-check"></i></span>' : '<span class="text-red-400"><i class="fas fa-times"></i></span>';
                    }
                },
                { 
                    data: 'rtgs', 
                    name: 'rtgs',
                    render: function(data) {
                        return data ? '<span class="text-green-600"><i class="fas fa-check"></i></span>' : '<span class="text-red-400"><i class="fas fa-times"></i></span>';
                    }
                },
                { 
                    data: 'id', 
                    name: 'action', 
                    orderable: false, 
                    searchable: false,
                    className: 'text-right whitespace-nowrap',
                    render: function(data) {
                        return `
                            <a href="/bank-branches/${data}/edit" class="text-indigo-600 hover:text-indigo-900 mr-3"><i class="fas fa-edit"></i></a>
                            <form action="/bank-branches/${data}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900"><i class="fas fa-trash"></i></button>
                            </form>
                        `;
                    }
                }
            ],
            order: [[1, "asc"]],
            dom: '<"flex flex-col sm:flex-row justify-between items-center mb-4"lf>rt<"flex flex-col sm:flex-row justify-between items-center mt-4"ip>',
        });
    });
</script>
@endpush
