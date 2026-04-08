@extends('layouts.app')

@section('header', 'System Banks')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">Financial Institutions</h1>
            <p class="mt-2 text-sm font-medium text-gray-500 dark:text-gray-400">Manage global and regional bank records for the SetuGeo network.</p>
        </div>
        <div>
            <a href="{{ route('banks.create') }}" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-sm font-black rounded-2xl shadow-xl text-white bg-amber-600 hover:bg-amber-700 transition-all transform hover:scale-[1.02] active:scale-[0.98]">
                <i class="fas fa-plus mr-2 text-xs"></i> Register New Bank
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
                <table id="banksTable" class="w-full text-left border-separate border-spacing-0">
                    <thead>
                        <tr>
                            <th class="pb-4 px-4 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-white/5">ID</th>
                            <th class="pb-4 px-4 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-white/5">Official Name</th>
                            <th class="pb-4 px-4 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-white/5">Slug</th>
                            <th class="pb-4 px-4 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-white/5">Created At</th>
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
        $('#banksTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('banks.index') }}",
            columns: [
                { 
                    data: 'id', 
                    name: 'id',
                    render: function(data) {
                        return `<span class="text-xs font-black text-gray-400 dark:text-gray-600">#${data}</span>`;
                    }
                },
                { 
                    data: 'name', 
                    name: 'name',
                    render: function(data) {
                        return `<span class="text-sm font-bold text-gray-900 dark:text-white">${data}</span>`;
                    }
                },
                { 
                    data: 'slug', 
                    name: 'slug',
                    render: function(data) {
                        return `<code class="text-xs font-mono bg-gray-100 dark:bg-white/5 px-2 py-1 rounded text-amber-600 dark:text-amber-500">${data}</code>`;
                    }
                },
                { 
                    data: 'created_at', 
                    name: 'created_at',
                    render: function(data) {
                        return `<span class="text-xs font-bold text-gray-500 dark:text-gray-400">${data ? new Date(data).toLocaleDateString() : 'N/A'}</span>`;
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
                                <a href="/banks/${data}/edit" class="p-2 text-amber-600 hover:bg-amber-600/10 rounded-lg transition-colors" title="Edit Bank">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="/banks/${data}" method="POST" class="inline-block delete-form" data-confirm-message="Are you sure you want to delete this bank and all its branches?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-red-500 hover:bg-red-500/10 rounded-lg transition-colors" title="Delete Bank">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        `;
                    }
                }
            ],
            order: [[0, "desc"]],
            language: {
                search: "",
                searchPlaceholder: "Search Banks...",
                lengthMenu: "_MENU_",
                info: "Showing _START_ to _END_ of _TOTAL_ Banks",
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
