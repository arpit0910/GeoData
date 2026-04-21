@extends('layouts.app')

@section('content')
{{-- Page Header --}}
<div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">Pincodes</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 font-medium">Manage all postal codes with location and area details.</p>
    </div>
    <div class="flex items-center gap-3">
        <button type="button" onclick="document.getElementById('importModal').classList.remove('hidden')"
            class="inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl border border-indigo-500/30 text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-500/10 hover:bg-indigo-600 hover:text-white hover:border-indigo-600 transition-all">
            <i class="fas fa-file-import mr-2"></i> Import
        </button>
        <a href="{{ route('pincodes.create') }}"
            class="inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl text-white bg-amber-600 hover:bg-amber-700 transition-all shadow-lg hover:scale-[1.02] active:scale-[0.98]">
            <i class="fas fa-plus mr-2"></i> Add Pincode
        </a>
    </div>
</div>

{{-- Import Modal --}}
<div id="importModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" onclick="document.getElementById('importModal').classList.add('hidden')"></div>
        <div class="relative bg-white dark:bg-[#0f172a] border border-gray-200 dark:border-white/10 rounded-2xl shadow-2xl w-full max-w-md p-6 z-10">
            <div class="flex items-center gap-3 mb-5">
                <span class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center">
                    <i class="fas fa-upload text-indigo-600 dark:text-indigo-400"></i>
                </span>
                <div>
                    <h3 class="text-base font-black text-gray-900 dark:text-white">Import Pincodes</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Upload a CSV file. Large files upload in chunks automatically.</p>
                </div>
            </div>

            <form id="importForm" action="javascript:void(0)">
                @csrf
                <div class="mb-4">
                    <input type="file" id="import_file" name="import_file" required accept=".csv"
                        class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-indigo-50 dark:file:bg-indigo-500/10 file:text-indigo-700 dark:file:text-indigo-400 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-500/20 cursor-pointer">
                </div>

                {{-- Progress --}}
                <div id="progressContainer" class="hidden mb-4">
                    <div class="flex justify-between text-xs mb-1.5">
                        <span id="progressText" class="font-bold text-indigo-600 dark:text-indigo-400">0%</span>
                        <span id="progressStatus" class="text-gray-500">Uploading...</span>
                    </div>
                    <div class="w-full bg-gray-100 dark:bg-white/5 rounded-full h-2">
                        <div id="progressBar" class="bg-indigo-600 h-2 rounded-full transition-all duration-300" style="width:0%"></div>
                    </div>
                </div>

                <div id="actionButtons" class="flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('importModal').classList.add('hidden')"
                        class="px-5 py-2.5 text-sm font-bold text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-white transition-colors">
                        Cancel
                    </button>
                    <button type="submit" id="importBtn"
                        class="inline-flex items-center px-6 py-2.5 text-sm font-black rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 transition-all">
                        <i class="fas fa-upload mr-2"></i> Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Flash Message --}}
@if(session('success'))
    <div class="mb-6 flex items-center gap-3 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-400 rounded-2xl px-5 py-4">
        <i class="fas fa-check-circle shrink-0"></i>
        <span class="text-sm font-medium">{{ session('success') }}</span>
    </div>
@endif

{{-- Table Card --}}
<div class="bg-white dark:bg-[#0f172a]/80 backdrop-blur-xl border border-gray-200 dark:border-white/5 rounded-2xl shadow-sm overflow-hidden">
    <div class="p-6 overflow-x-auto">
        <table id="pincodesTable" class="w-full text-left border-collapse">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Postal Code</th>
                    <th>City</th>
                    <th>State</th>
                    <th>Country</th>
                    <th>Area</th>
                    <th class="text-right">Actions</th>
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
        $('#pincodesTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 100,
            ajax: "{{ route('pincodes.index') }}",
            columns: [
                { data: 'id',           name: 'id' },
                { data: 'postal_code',  name: 'postal_code' },
                { data: 'city_name',    name: 'city.name',    orderable: false, defaultContent: '—' },
                { data: 'state_name',   name: 'state.name',   orderable: false, defaultContent: '—' },
                { data: 'country_name', name: 'country.name', orderable: false, defaultContent: '—' },
                { data: 'area',         name: 'area',         orderable: false,
                    render: function(data) { return data || '—'; }
                },
                {
                    data: 'id',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    className: 'text-right whitespace-nowrap',
                    render: function(data, type, row) {
                        let editUrl   = "{{ route('pincodes.edit', ':id') }}".replace(':id', data);
                        let deleteUrl = "{{ route('pincodes.destroy', ':id') }}".replace(':id', data);
                        let csrf      = '{{ csrf_token() }}';
                        return `
                            <div class="flex justify-end items-center space-x-1">
                                <a href="${editUrl}" class="p-2 text-indigo-600 dark:text-indigo-400 hover:bg-gray-100 dark:hover:bg-white/5 rounded-lg transition-colors" title="Edit">
                                    <i class="fas fa-edit text-sm"></i>
                                </a>
                                <form action="${deleteUrl}" method="POST" class="inline-block delete-form-actual">
                                    <input type="hidden" name="_token" value="${csrf}">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="button" class="p-2 text-rose-500 hover:bg-gray-100 dark:hover:bg-white/5 rounded-lg transition-colors delete-trigger"
                                        data-message="Are you sure you want to delete pincode '${row.postal_code}'?" title="Delete">
                                        <i class="fas fa-trash text-sm"></i>
                                    </button>
                                </form>
                            </div>`;
                    }
                }
            ],
            order: [[0, 'asc']],
            dom: '<"flex flex-col sm:flex-row justify-between items-center mb-4"lf>rt<"flex flex-col sm:flex-row justify-between items-center mt-4"ip>',
        });

        $(document).on('click', '.delete-trigger', function() {
            const btn = $(this);
            const form = btn.closest('form');
            const message = btn.data('message');
            
            openDeleteModal(message, function() {
                form.submit();
            });
        });

        // Handle chunked file uploads
        document.getElementById('importForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const fileInput = document.getElementById('import_file');
            const file = fileInput.files[0];
            if (!file) return;

            const importBtn = document.getElementById('importBtn');
            const progressContainer = document.getElementById('progressContainer');
            const progressBar = document.getElementById('progressBar');
            const progressText = document.getElementById('progressText');
            const progressStatus = document.getElementById('progressStatus');
            const actionButtons = document.getElementById('actionButtons');

            // Disable UI
            importBtn.disabled = true;
            actionButtons.classList.add('opacity-50');
            progressContainer.classList.remove('hidden');
            
            progressBar.style.width = '0%';
            progressText.innerText = '0%';
            
            // Chunk size
            const chunkSize = 2 * 1024 * 1024; // 2MB
            const totalChunks = Math.ceil(file.size / chunkSize);
            const fileName = file.name;
            const csrfToken = document.querySelector('input[name="_token"]').value;

            for (let i = 0; i < totalChunks; i++) {
                const start = i * chunkSize;
                const end = Math.min(start + chunkSize, file.size);
                const chunk = file.slice(start, end);

                const formData = new FormData();
                formData.append('file', chunk);
                formData.append('chunkIndex', i);
                formData.append('totalChunks', totalChunks);
                formData.append('fileName', fileName);
                formData.append('_token', csrfToken);

                try {
                    if(i === totalChunks - 1) {
                        progressStatus.innerText = "Processing records... This may take a few minutes.";
                    } else {
                        progressStatus.innerText = `Uploading chunk ${i+1} of ${totalChunks}...`;
                    }

                    const response = await fetch("{{ route('pincodes.uploadChunk') }}", {
                        method: 'POST',
                        body: formData
                    });
                    
                    if(!response.ok) {
                        const err = await response.text();
                        throw new Error("Server responded with " + response.status + ": " + err);
                    }
                    
                    const data = await response.json();
                    
                    if(data.status === 'chunk_uploaded' || data.status === 'success') {
                        let percent = data.progress || 100;
                        progressBar.style.width = percent + '%';
                        progressText.innerText = percent + '%';
                        
                        if(data.status === 'success') {
                            alert(data.message);
                            window.location.reload();
                        }
                    } else {
                        alert('Error during upload: ' + JSON.stringify(data));
                        break;
                    }
                } catch (error) {
                    console.error("Chunk upload failed:", error);
                    alert('Upload failed: ' + error.message);
                    break;
                }
            }

            // Re-enable UI if it broke or finished
            importBtn.disabled = false;
            actionButtons.classList.remove('opacity-50');
        });
    });
</script>
@endpush
