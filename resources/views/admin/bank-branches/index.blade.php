@extends('layouts.app')

@section('header', 'Bank Branches')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">Bank Branches</h1>
            <p class="mt-2 text-sm font-medium text-gray-500 dark:text-gray-400">Comprehensive registry of all individual bank branches and identification codes.</p>
        </div>
        <div>
            <a href="{{ route('bank-branches.create') }}" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-sm font-black rounded-2xl shadow-xl text-white bg-amber-600 hover:bg-amber-700 transition-all transform hover:scale-[1.02] active:scale-[0.98]">
                <i class="fas fa-plus mr-2 text-xs"></i> Add New Branch
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-6 animate-pulse-once bg-green-500/10 border border-green-500/20 rounded-2xl p-4 flex items-center">
        <i class="fas fa-check-circle text-green-500 mr-3"></i>
        <p class="text-sm font-bold text-green-600 dark:text-green-500">{{ session('success') }}</p>
    </div>
    @endif

    <div class="bg-white dark:bg-richdark-surface rounded-3xl shadow-xl border border-gray-200 dark:border-white/5 overflow-hidden transition-all duration-300">
        <div class="p-6 md:p-8">
            <div class="overflow-x-auto">
                <table id="branchesTable" class="w-full text-left border-separate border-spacing-0">
                    <thead>
                        <tr>
                            <th class="pb-4 px-4 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-white/5">Bank</th>
                            <th class="pb-4 px-4 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-white/5">IFSC / MICR</th>
                            <th class="pb-4 px-4 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-white/5">Branch</th>
                            <th class="pb-4 px-4 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-white/5">Location</th>
                            <th class="pb-4 px-4 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-white/5">IMPS</th>
                            <th class="pb-4 px-4 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-white/5">RTGS</th>
                            <th class="pb-4 px-4 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-white/5">NEFT</th>
                            <th class="pb-4 px-4 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-white/5">UPI</th>
                            <th class="pb-4 px-4 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-white/5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-white/5">
                    </tbody>
                </table>
            </div>
        </div>
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
                        return data ? `<span class="text-sm font-bold text-gray-900 dark:text-white">${data.name}</span>` : '<span class="text-xs text-gray-400">N/A</span>';
                    }
                },
                { 
                    data: 'ifsc', 
                    name: 'ifsc',
                    render: function(data, type, row) {
                        const micr = row.micr ? row.micr : 'N/A';
                        return `
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-gray-900 dark:text-white">${data}</span>
                                <span class="text-[10px] uppercase tracking-wider text-gray-400 dark:text-gray-500">MICR: ${micr}</span>
                            </div>
                        `;
                    }
                },
                { 
                    data: 'branch', 
                    name: 'branch',
                    render: function(data) {
                        return `<span class="text-sm font-medium text-gray-700 dark:text-gray-300">${data}</span>`;
                    }
                },
                { 
                    data: 'city', 
                    name: 'city.name',
                    render: function(city, type, row) {
                        const stateName = row.state ? row.state.name : 'N/A';
                        const cityName = city ? city.name : 'N/A';
                        return `
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-gray-900 dark:text-white">${cityName}</span>
                                <span class="text-[10px] uppercase tracking-wider text-gray-400 dark:text-gray-500">${stateName}</span>
                            </div>
                        `;
                    }
                },
                { 
                    data: 'imps', 
                    name: 'imps',
                    render: function(data) {
                        return data ? '<span class="text-green-500 bg-green-500/10 px-2 py-1 rounded text-[10px] font-black uppercase tracking-tighter">Enabled</span>' : '<span class="text-red-400 bg-red-400/10 px-2 py-1 rounded text-[10px] font-black uppercase tracking-tighter">Disabled</span>';
                    }
                },
                { 
                    data: 'rtgs', 
                    name: 'rtgs',
                    render: function(data) {
                        return data ? '<span class="text-green-500 bg-green-500/10 px-2 py-1 rounded text-[10px] font-black uppercase tracking-tighter">Enabled</span>' : '<span class="text-red-400 bg-red-400/10 px-2 py-1 rounded text-[10px] font-black uppercase tracking-tighter">Disabled</span>';
                    }
                },
                { 
                    data: 'neft', 
                    name: 'neft',
                    render: function(data) {
                        return data ? '<span class="text-green-500 bg-green-500/10 px-2 py-1 rounded text-[10px] font-black uppercase tracking-tighter">Enabled</span>' : '<span class="text-red-400 bg-red-400/10 px-2 py-1 rounded text-[10px] font-black uppercase tracking-tighter">Disabled</span>';
                    }
                },
                { 
                    data: 'upi', 
                    name: 'upi',
                    render: function(data) {
                        return data ? '<span class="text-green-500 bg-green-500/10 px-2 py-1 rounded text-[10px] font-black uppercase tracking-tighter">Enabled</span>' : '<span class="text-red-400 bg-red-400/10 px-2 py-1 rounded text-[10px] font-black uppercase tracking-tighter">Disabled</span>';
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
                             <div class="flex items-center justify-end space-x-2">
                                <a href="/bank-branches/${data}/edit" class="p-2 text-amber-600 hover:bg-amber-600/10 rounded-lg transition-colors" title="Edit Branch">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="/bank-branches/${data}" method="POST" class="inline-block delete-form" data-confirm-message="Are you sure you want to delete this branch?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-red-500 hover:bg-red-500/10 rounded-lg transition-colors" title="Delete Branch">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        `;
                    }
                }
            ],
            order: [[1, "asc"]],
            pageLength: 50,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            language: {
                search: "",
                searchPlaceholder: "Search Branches...",
                lengthMenu: "_MENU_",
                info: "Showing _START_ to _END_ of _TOTAL_ Branches",
                paginate: {
                    previous: '<i class="fas fa-chevron-left text-xs"></i>',
                    next: '<i class="fas fa-chevron-right text-xs"></i>'
                }
            },
            dom: '<"flex flex-col md:flex-row justify-between items-center gap-4 mb-6"lf>rt<"flex flex-col md:flex-row justify-between items-center mt-6 gap-4"ip>',
        });
    });
</script>
@endpush
