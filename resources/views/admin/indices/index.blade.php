@extends('layouts.app')

@section('content')
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Market Indices</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 font-medium">Manage and view all tracked stock market indices.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('admin.indices.export') }}" class="inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl border border-gray-200 dark:border-white/10 text-gray-700 dark:text-gray-300 bg-white dark:bg-white/5 hover:bg-gray-50 transition-all">
                <i class="fas fa-download mr-2 text-amber-500"></i> Export CSV
            </a>
            <button onclick="document.getElementById('importModal').classList.remove('hidden')" class="inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl border border-gray-200 dark:border-white/10 text-gray-700 dark:text-gray-300 bg-white dark:bg-white/5 hover:bg-gray-50 transition-all">
                <i class="fas fa-upload mr-2 text-amber-500"></i> Import CSV
            </button>
            <a href="{{ route('admin.indices.prices') }}"
                class="inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl text-white bg-amber-600 hover:bg-amber-700 transition-all shadow-lg shadow-amber-500/20 hover:scale-[1.02] active:scale-[0.98]">
                <i class="fas fa-chart-line mr-2"></i> Price Records
            </a>
        </div>
    </div>

    {{-- Import Modal --}}
    <div id="importModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm"
                onclick="document.getElementById('importModal').classList.add('hidden')"></div>
            <div class="relative bg-white dark:bg-[#0f172a] border border-gray-200 dark:border-white/10 rounded-2xl shadow-2xl w-full max-w-sm p-6 z-10">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-emerald-50 dark:bg-emerald-500/10 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-file-import text-2xl text-emerald-600 dark:text-emerald-400"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Import Indices</h3>
                    <p class="text-xs text-gray-500 mt-1">Upload a CSV file to bulk add/update indices.</p>
                </div>

                <form id="importForm" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-6">
                        <label class="block text-xs font-bold text-gray-400 mb-2 uppercase tracking-widest">CSV File</label>
                        <input type="file" name="file" accept=".csv" required
                            class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-3 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all font-bold">
                        <p class="text-[10px] text-gray-400 mt-2">Required Columns: index_code, index_name, exchange, category</p>
                    </div>

                    <div id="importStatus" class="hidden mb-6 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-100 dark:border-emerald-500/20">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-circle-notch fa-spin text-emerald-600 dark:text-emerald-400"></i>
                            <span class="text-xs font-bold text-emerald-700 dark:text-emerald-300">Processing records...</span>
                        </div>
                    </div>

                    <div class="flex gap-3" id="importActions">
                        <button type="button" onclick="document.getElementById('importModal').classList.add('hidden')"
                            class="flex-1 px-4 py-3 text-sm font-bold text-gray-500 hover:bg-gray-100 dark:hover:bg-white/5 rounded-xl transition-all">Cancel</button>
                        <button type="submit"
                            class="flex-1 px-4 py-3 text-sm font-bold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 transition-all shadow-lg active:scale-95">Start
                            Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-[#0f172a]/80 backdrop-blur-xl border border-gray-200 dark:border-white/5 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-6 overflow-x-auto">
            <table id="indicesTable" class="w-full text-left border-collapse">
                <thead>
                    <tr>
                        <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4">Code</th>
                        <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4">Index Name</th>
                        <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4">Exchange</th>
                        <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4">Category</th>
                        <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5"></tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            const table = $('#indicesTable').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 25,
                ajax: "{{ route('admin.indices.index') }}",
                columns: [
                    { data: 'index_code', name: 'index_code', className: 'text-xs' },
                    { data: 'index_name', name: 'index_name', className: 'text-gray-900 dark:text-white' },
                    { data: 'exchange', name: 'exchange', className: 'text-xs' },
                    { data: 'category', name: 'category', className: 'text-xs' },
                    {
                        data: 'index_code',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-right whitespace-nowrap',
                        render: function(data) {
                            let pricesUrl = "{{ route('admin.indices.prices') }}?index_code=" + data;
                            let showUrl = "{{ route('admin.indices.show', ':id') }}".replace(':id', data);
                            let editUrl = "{{ route('admin.indices.edit', ':id') }}".replace(':id', data);
                            return `
                            <div class="flex justify-end gap-1">
                                <a href="${showUrl}" class="p-2 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 rounded-lg transition-colors" title="View Details">
                                    <i class="fas fa-eye text-sm"></i>
                                </a>
                                <a href="${editUrl}" class="p-2 text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-500/10 rounded-lg transition-colors" title="Edit Metadata">
                                    <i class="fas fa-edit text-sm"></i>
                                </a>
                                <a href="${pricesUrl}" class="p-2 text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 rounded-lg transition-colors" title="Price Records">
                                    <i class="fas fa-list-ol text-sm"></i>
                                </a>
                            </div>`;
                        }
                    }
                ],
                order: [[1, 'asc']],
                dom: '<"flex justify-between items-center mb-4"lf>rt<"flex justify-between items-center mt-4"ip>',
            });

            $('#importForm').on('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const status = $('#importStatus');
                const actions = $('#importActions');

                status.removeClass('hidden');
                actions.addClass('opacity-50 pointer-events-none');

                $.ajax({
                    url: "{{ route('admin.indices.import') }}",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        if (res.success) {
                            toastr.success(res.message);
                            document.getElementById('importModal').classList.add('hidden');
                            table.ajax.reload();
                        } else {
                            toastr.error(res.message);
                        }
                    },
                    error: function(err) {
                        toastr.error('Import failed. Please check your CSV format.');
                    },
                    complete: function() {
                        status.addClass('hidden');
                        actions.removeClass('opacity-50 pointer-events-none');
                    }
                });
            });
        });
    </script>
@endpush
