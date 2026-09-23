@extends('layouts.app')

@section('content')
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Equities</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 font-medium">Manage and view all listed companies in the
                database.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('equities.export') }}"
                class="inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl border border-gray-200 dark:border-white/10 text-gray-700 dark:text-gray-300 bg-white dark:bg-white/5 hover:bg-gray-50 transition-all">
                <i class="fas fa-download mr-2 text-amber-500"></i> Export CSV
            </a>
            <button type="button" onclick="document.getElementById('importModal').classList.remove('hidden')"
                class="inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl border border-gray-200 dark:border-white/10 text-gray-700 dark:text-gray-300 bg-white dark:bg-white/5 hover:bg-gray-50 transition-all">
                <i class="fas fa-upload mr-2 text-amber-500"></i> Import CSV
            </button>
            <button type="button" onclick="document.getElementById('upstoxImportModal').classList.remove('hidden')"
                class="inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl border border-violet-200 dark:border-violet-500/20 text-violet-700 dark:text-violet-300 bg-violet-50 dark:bg-violet-500/10 hover:bg-violet-100 transition-all">
                <i class="fas fa-cloud-upload-alt mr-2"></i> Sync Upstox JSON
            </button>
            <a href="{{ route('equities.prices') }}"
                class="inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl text-white bg-amber-600 hover:bg-amber-700 transition-all shadow-lg hover:scale-[1.02] active:scale-[0.98]">
                <i class="fas fa-chart-line mr-2"></i> Price Records
            </a>
        </div>
    </div>

    {{-- Upstox JSON Import Modal --}}
    <div id="upstoxImportModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm"
                onclick="document.getElementById('upstoxImportModal').classList.add('hidden')"></div>
            <div class="relative bg-white dark:bg-[#0f172a] border border-gray-200 dark:border-white/10 rounded-2xl shadow-2xl w-full max-w-lg p-6 z-10">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-violet-50 dark:bg-violet-500/10 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-link text-2xl text-violet-600 dark:text-violet-400"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Sync Upstox Instruments</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Upload Upstox <code>complete.json</code>. Records are matched by ISIN and NSE/BSE attributes are updated.</p>
                </div>

                <form id="upstoxImportForm" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-6">
                        <label class="block text-xs font-bold text-gray-400 mb-2">Upstox JSON File</label>
                        <input type="file" name="file" id="upstox_file" required accept=".json,application/json"
                            class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-3 text-xs focus:outline-none focus:ring-2 focus:ring-violet-500 transition-all">
                        <p class="mt-2 text-xs text-gray-400">Maximum size: 100 MB. Only NSE_EQ and BSE_EQ instruments with valid ISINs are imported.</p>
                    </div>

                    <div id="upstoxImportStatus" class="hidden mb-6 p-4 rounded-xl bg-violet-50 dark:bg-violet-500/10 border border-violet-100 dark:border-violet-500/20">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-circle-notch fa-spin text-violet-600 dark:text-violet-400"></i>
                            <span class="text-xs font-bold text-violet-700 dark:text-violet-300">Uploading and mapping instruments...</span>
                        </div>
                    </div>

                    <div id="upstoxImportResult" class="hidden mb-6 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-100 dark:border-emerald-500/20 text-xs text-emerald-800 dark:text-emerald-200"></div>

                    <div class="flex justify-end gap-3" id="upstoxImportActions">
                        <button type="button" onclick="document.getElementById('upstoxImportModal').classList.add('hidden')"
                            class="flex-1 px-4 py-3 text-sm font-bold text-gray-500 hover:bg-gray-100 dark:hover:bg-white/5 rounded-xl transition-all">Close</button>
                        <button type="submit"
                            class="flex-1 px-4 py-3 text-sm font-bold text-white bg-violet-600 hover:bg-violet-700 rounded-xl shadow-lg shadow-violet-500/30 transition-all">Upload & Sync</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Import Modal --}}
    <div id="importModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm"
                onclick="document.getElementById('importModal').classList.add('hidden')"></div>
            <div
                class="relative bg-white dark:bg-[#0f172a] border border-gray-200 dark:border-white/10 rounded-2xl shadow-2xl w-full max-w-sm p-6 z-10">
                <div class="text-center mb-6">
                    <div
                        class="w-16 h-16 bg-amber-50 dark:bg-amber-500/10 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-file-import text-2xl text-amber-600 dark:text-amber-400"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mt-4">Import Equities</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Upload a CSV to bulk add or update company
                        records.</p>
                </div>

                <form id="importForm" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-6">
                        <label class="block text-xs font-bold text-gray-400 mb-2">Select CSV File</label>
                        <input type="file" name="file" id="import_file" required accept=".csv"
                            class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-3 text-xs focus:outline-none focus:ring-2 focus:ring-amber-500 transition-all">
                        <p class="mt-2 text-xs text-gray-400">Headers: isin, company_name, nse_symbol, bse_symbol, industry,
                            market_cap, market_cap_category, face_value, listing_date, is_active</p>
                    </div>

                    <div id="importStatus"
                        class="hidden mb-6 p-4 rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-100 dark:border-amber-500/20">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-circle-notch fa-spin text-amber-600 dark:text-amber-400"></i>
                            <span class="text-xs font-bold text-amber-700 dark:text-amber-300">Processing file...</span>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3" id="importActions">
                        <button type="button" onclick="document.getElementById('importModal').classList.add('hidden')"
                            class="flex-1 px-4 py-3 text-sm font-bold text-gray-500 hover:bg-gray-100 dark:hover:bg-white/5 rounded-xl transition-all">
                            Cancel
                        </button>
                        <button type="submit"
                            class="flex-1 px-4 py-3 text-sm font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-xl shadow-lg shadow-amber-500/30 transition-all">
                            Upload
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div
        class="bg-white dark:bg-[#0f172a]/80 backdrop-blur-xl border border-gray-200 dark:border-white/5 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-6 overflow-x-auto">
            <table id="equitiesTable" class="w-full text-left border-collapse">
                <thead>
                    <tr>
                        <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4">
                            ISIN</th>
                        <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4">
                            Company Name</th>
                        <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4">
                            NSE Symbol</th>
                        <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4">
                            BSE Symbol</th>
                        <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4">
                            Upstox Keys <span class="text-[9px] text-rose-500">Admin only</span></th>
                        <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4">
                            Industry</th>
                        <th class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4">
                            Category</th>
                        <th
                            class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4 text-center">
                            Active</th>
                        <th
                            class="text-xs font-bold text-gray-400 border-b border-gray-100 dark:border-white/5 pb-4 px-4 text-right">
                            Actions</th>
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
                columns: [{
                        data: 'isin',
                        name: 'isin',
                        className: 'text-xs'
                    },
                    {
                        data: 'company_name',
                        name: 'company_name',
                        className: 'font-bold text-gray-900 dark:text-white'
                    },
                    {
                        data: 'nse_symbol',
                        name: 'nse_symbol',
                        render: function(data) {
                            return data ? data : '<span class="text-gray-400">N/A</span>';
                        }
                    },
                    {
                        data: 'bse_symbol',
                        name: 'bse_symbol',
                        render: function(data) {
                            return data ? data : '<span class="text-gray-400">N/A</span>';
                        }
                    },
                    {
                        data: null,
                        name: 'upstox_keys',
                        orderable: false,
                        render: function(data, type, row) {
                            const escape = value => $('<div>').text(value).html();
                            const keys = [];
                            if (row.upstox_nse_instrument_key) {
                                keys.push(`<div class="text-[10px] font-mono text-indigo-600 dark:text-indigo-400">${escape(row.upstox_nse_instrument_key)}</div>`);
                            }
                            if (row.upstox_bse_instrument_key) {
                                keys.push(`<div class="text-[10px] font-mono text-amber-600 dark:text-amber-400">${escape(row.upstox_bse_instrument_key)}</div>`);
                            }
                            return keys.length ? keys.join('') : '<span class="text-gray-400">N/A</span>';
                        }
                    },
                    {
                        data: 'industry',
                        name: 'industry',
                        render: function(data) {
                            return data ? data : '<span class="text-gray-400">N/A</span>';
                        }
                    },
                    {
                        data: 'market_cap_category',
                        name: 'market_cap_category',
                        render: function(data) {
                            return data ? data : '<span class="text-gray-400">N/A</span>';
                        }
                    },
                    {
                        data: 'is_active',
                        name: 'is_active',
                        className: 'text-center font-sans font-bold',
                        render: function(data) {
                            return data ?
                                '<span class="text-emerald-500">Active</span>' :
                                '<span class="text-gray-400">No</span>';
                        }
                    },
                    {
                        data: 'id',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-right whitespace-nowrap',
                        render: function(data, type, row) {
                            let viewUrl = "{{ route('equities.show', ':id') }}".replace(':id',
                                data);
                            let editUrl = "{{ route('equities.edit', ':id') }}".replace(':id',
                                data);
                            let pricesUrl = "{{ route('equities.prices') }}?isin=" + row.isin;
                            return `
                            <div class="flex justify-end gap-1">
                                <a href="${viewUrl}" class="p-2 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 rounded-lg transition-colors" title="View Details">
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
                order: [
                    [1, 'asc']
                ],
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
                        if (res.success) {
                            alert(res.message);
                            document.getElementById('importModal').classList.add('hidden');
                            table.ajax.reload();
                        } else {
                            alert('Error: ' + res.message);
                        }
                    },
                    error: function(err) {
                        let msg = 'Error importing data. Check CSV format.';
                        if (err.responseJSON && err.responseJSON.message) {
                            msg = err.responseJSON.message;
                        }
                        alert(msg);
                    },
                    complete: function() {
                        status.addClass('hidden');
                        actions.removeClass('opacity-50 pointer-events-none');
                    }
                });
            });

            $('#upstoxImportForm').on('submit', function(e) {
                e.preventDefault();
                const form = this;
                const status = $('#upstoxImportStatus');
                const result = $('#upstoxImportResult');
                const actions = $('#upstoxImportActions');

                status.removeClass('hidden');
                result.addClass('hidden').empty();
                actions.addClass('opacity-50 pointer-events-none');

                $.ajax({
                    url: "{{ route('equities.upstox.import') }}",
                    type: 'POST',
                    data: new FormData(form),
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        const stats = res.data;
                        result.removeClass('bg-rose-50 dark:bg-rose-500/10 border-rose-100 dark:border-rose-500/20 text-rose-800 dark:text-rose-200')
                            .addClass('bg-emerald-50 dark:bg-emerald-500/10 border-emerald-100 dark:border-emerald-500/20 text-emerald-800 dark:text-emerald-200')
                            .html(`
                            <p class="font-black mb-2">${res.message}</p>
                            <div class="grid grid-cols-2 gap-2">
                                <span>Unique ISINs: <strong>${stats.unique_isins}</strong></span>
                                <span>Matched: <strong>${stats.existing}</strong></span>
                                <span>Added: <strong>${stats.added}</strong></span>
                                <span>Updated: <strong>${stats.updated}</strong></span>
                                <span>Invalid rows: <strong>${stats.invalid_rows}</strong></span>
                                <span>Unmapped existing: <strong>${stats.unmatched_existing}</strong></span>
                            </div>
                            `).removeClass('hidden');
                        form.reset();
                        table.ajax.reload(null, false);
                    },
                    error: function(err) {
                        let message = 'Unable to upload the file. Verify the JSON and server upload limits.';
                        if (err.responseJSON && err.responseJSON.message) {
                            message = err.responseJSON.message;
                        }
                        result.text(message)
                            .removeClass('hidden bg-emerald-50 dark:bg-emerald-500/10 border-emerald-100 dark:border-emerald-500/20 text-emerald-800 dark:text-emerald-200')
                            .addClass('bg-rose-50 dark:bg-rose-500/10 border-rose-100 dark:border-rose-500/20 text-rose-800 dark:text-rose-200');
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
