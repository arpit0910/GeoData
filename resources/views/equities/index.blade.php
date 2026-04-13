@extends('layouts.app')

@section('content')
<div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Equities</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 font-medium">Manage and view all listed companies in the database.</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('equities.export') }}"
            class="inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl border border-gray-200 dark:border-white/10 text-gray-700 dark:text-gray-300 bg-white dark:bg-white/5 hover:bg-gray-50 transition-all">
            <i class="fas fa-download mr-2 text-indigo-500"></i> Export CSV
        </a>
        <button type="button" onclick="document.getElementById('importModal').classList.remove('hidden')"
            class="inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl border border-gray-200 dark:border-white/10 text-gray-700 dark:text-gray-300 bg-white dark:bg-white/5 hover:bg-gray-50 transition-all">
            <i class="fas fa-upload mr-2 text-indigo-500"></i> Import CSV
        </button>
        <a href="{{ route('equities.prices') }}"
            class="inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 transition-all shadow-lg hover:scale-[1.02] active:scale-[0.98]">
            <i class="fas fa-chart-line mr-2"></i> Price Records
        </a>
    </div>
</div>

{{-- Import Modal --}}
<div id="importModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" onclick="document.getElementById('importModal').classList.add('hidden')"></div>
        <div class="relative bg-white dark:bg-[#0f172a] border border-gray-200 dark:border-white/10 rounded-2xl shadow-2xl w-full max-w-sm p-6 z-10">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-indigo-50 dark:bg-indigo-500/10 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-file-import text-2xl text-indigo-600 dark:text-indigo-400"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mt-4">Import Equities</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Upload a CSV to bulk add or update company records.</p>
            </div>

            <form id="importForm" enctype="multipart/form-data">
                @csrf
                <div class="mb-6">
                    <label class="block text-xs font-bold text-gray-400 mb-2">Select CSV File</label>
                    <input type="file" name="file" id="import_file" required accept=".csv"
                        class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-3 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                    <p class="mt-2 text-xs text-gray-400">Headers: isin, company_name, nse_symbol, bse_symbol, industry, is_active</p>
                </div>

                <div id="importStatus" class="hidden mb-6 p-4 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-100 dark:border-indigo-500/20">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-circle-notch fa-spin text-indigo-600 dark:text-indigo-400"></i>
                        <span class="text-xs font-bold text-indigo-700 dark:text-indigo-300">Processing file...</span>
                    </div>
                </div>

                <div class="flex justify-end gap-3" id="importActions">
                    <button type="button" onclick="document.getElementById('importModal').classList.add('hidden')"
                        class="flex-1 px-4 py-3 text-sm font-bold text-gray-500 hover:bg-gray-100 dark:hover:bg-white/5 rounded-xl transition-all">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 px-4 py-3 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-lg shadow-indigo-500/30 transition-all">
                        Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="bg-white dark:bg-[#0f172a]/80 backdrop-blur-xl border border-gray-200 dark:border-white/5 rounded-2xl shadow-sm overflow-hidden">
    <div class="p-6 overflow-x-auto">
        <table id="equitiesTable" class="w-full text-left border-collapse">
            <thead>
                <tr>
                    <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4">ISIN</th>
                    <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4">Company Name</th>
                    <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4">NSE</th>
                    <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4">BSE</th>
                    <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4">Industry</th>
                    <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4 text-center">Active</th>
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
        const table = $('#equitiesTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 100,
            ajax: "{{ route('equities.index') }}",
            columns: [
                { data: 'isin', name: 'isin', className: 'text-xs' },
                { data: 'company_name', name: 'company_name', className: 'font-bold text-gray-900 dark:text-white' },
                { 
                    data: 'nse_symbol', 
                    name: 'nse_symbol',
                    render: function(data) {
                        return data ? `<span class="font-bold text-gray-700 dark:text-gray-300 text-sm">${data}</span>` : '<span class="text-gray-400 italic">N/A</span>';
                    }
                },
                { 
                    data: 'bse_symbol', 
                    name: 'bse_symbol',
                    render: function(data) {
                        return data ? `<span class="font-bold text-gray-700 dark:text-gray-300 text-sm">${data}</span>` : '<span class="text-gray-400 italic">N/A</span>';
                    }
                },
                { 
                    data: 'industry', 
                    name: 'industry',
                    render: function(data) {
                        return data ? data : '<span class="text-gray-400 italic">N/A</span>';
                    }
                },
                { 
                    data: 'is_active', 
                    name: 'is_active',
                    className: 'text-center',
                    render: function(data) {
                        return data 
                            ? '<span class="px-2 py-1 text-xs font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 rounded-lg">Active</span>'
                            : '<span class="px-2 py-1 text-xs font-bold bg-gray-100 text-gray-600 dark:bg-white/5 dark:text-gray-400 rounded-lg">Inactive</span>';
                    }
                },
                {
                    data: 'id',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    className: 'text-right whitespace-nowrap',
                    render: function(data) {
                        let viewUrl = "{{ route('equities.show', ':id') }}".replace(':id', data);
                        let editUrl = "{{ route('equities.edit', ':id') }}".replace(':id', data);
                        return `
                            <div class="flex justify-end gap-1">
                                <a href="${viewUrl}" class="p-2 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 rounded-lg transition-colors" title="View History">
                                    <i class="fas fa-history text-sm"></i>
                                </a>
                                <a href="${editUrl}" class="p-2 text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 rounded-lg transition-colors" title="Edit">
                                    <i class="fas fa-edit text-sm"></i>
                                </a>
                            </div>`;
                    }
                }
            ],
            order: [[1, 'asc']],
            dom: '<"flex flex-col sm:flex-row justify-between items-center mb-4"lf>rt<"flex flex-col sm:flex-row justify-between items-center mt-4"ip>',
        });

        $('#importForm').on('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const status = $('#importStatus');
            const actions = $('#importActions');
            
            status.removeClass('hidden');
            actions.addClass('opacity-50 pointer-events-none');

            $.ajax({
                url: "{{ route('equities.import') }}",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    alert(res.message);
                    document.getElementById('importModal').classList.add('hidden');
                    table.ajax.reload();
                },
                error: function(err) {
                    alert('Error importing data. Check CSV format.');
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
