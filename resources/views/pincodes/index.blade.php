@extends('layouts.app')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Pincodes</h1>
        <p class="mt-1 text-sm text-gray-600">A list of all the pincodes in the database.</p>
    </div>
    <div class="flex items-center space-x-4">
        <button type="button" onclick="document.getElementById('importModal').classList.remove('hidden')" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
            <i class="fas fa-file-import mr-2"></i> Import
        </button>
        <a href="{{ route('pincodes.create') }}" class="inline-flex items-center px-4 py-2 bg-amber-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-700 active:bg-amber-900 focus:outline-none focus:border-amber-900 focus:ring ring-amber-300 disabled:opacity-25 transition ease-in-out duration-150">
            <i class="fas fa-plus mr-2"></i> Add Pincode
        </a>
    </div>
</div>

<!-- Import Modal -->
<div id="importModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" aria-hidden="true" onclick="document.getElementById('importModal').classList.add('hidden')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
            <div class="sm:flex sm:items-start">
                <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto bg-indigo-100 rounded-full sm:mx-0 sm:h-10 sm:w-10">
                    <i class="fas fa-upload text-indigo-600"></i>
                </div>
                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                    <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-title">Import Pincodes</h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-500">Please upload a valid Excel or CSV file. Large files will automatically be uploaded in chunks.</p>
                    </div>
                </div>
            </div>
            <form id="importForm" action="javascript:void(0)" class="mt-5 sm:mt-4">
                @csrf
                <div class="mb-4">
                    <input type="file" id="import_file" name="import_file" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel">
                </div>
                
                <!-- Progress Bar -->
                <div id="progressContainer" class="hidden mb-4">
                    <div class="flex justify-between text-xs mb-1">
                        <span id="progressText" class="font-medium text-indigo-700">0%</span>
                        <span id="progressStatus" class="font-medium text-gray-500">Uploading chunks...</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div id="progressBar" class="bg-indigo-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                </div>

                <div class="sm:flex sm:flex-row-reverse" id="actionButtons">
                    <button type="submit" id="importBtn" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white transition duration-150 ease-in-out bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:border-indigo-700 focus:ring-indigo sm:ml-3 sm:w-auto sm:text-sm">
                        Import
                    </button>
                    <button type="button" onclick="document.getElementById('importModal').classList.add('hidden')" class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 transition duration-150 ease-in-out bg-white border border-gray-300 rounded-md shadow-sm hover:text-gray-500 focus:outline-none focus:border-blue-300 focus:ring-blue sm:mt-0 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
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
        <table id="pincodesTable" class="w-full text-left border-collapse">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Postal Code</th>
                    <th>Country</th>
                    <th>State</th>
                    <th>City</th>
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
        $('#pincodesTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 100,
            ajax: "{{ route('pincodes.index') }}",
            columns: [
                { data: 'id', name: 'id' },
                { data: 'postal_code', name: 'postal_code' },
                { 
                    data: 'country', 
                    name: 'country.name',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return row.country ? row.country.name : 'N/A';
                    } 
                },
                { 
                    data: 'state', 
                    name: 'state.name',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return row.state ? row.state.name : 'N/A';
                    } 
                },
                { 
                    data: 'city', 
                    name: 'city.name',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return row.city ? row.city.name : 'N/A';
                    } 
                },
                { 
                    data: 'id', 
                    name: 'action', 
                    orderable: false, 
                    searchable: false,
                    className: 'text-right whitespace-nowrap',
                    render: function(data, type, row) {
                        let editUrl = "{{ route('pincodes.edit', ':id') }}".replace(':id', data);
                        let deleteUrl = "{{ route('pincodes.destroy', ':id') }}".replace(':id', data);
                        let csrf = '{{ csrf_token() }}';
                        
                        return `
                            <div class="flex justify-end space-x-2">
                                <a href="${editUrl}" class="text-indigo-600 hover:text-indigo-900" title="Edit"><i class="fas fa-edit"></i></a>
                                <form action="${deleteUrl}" method="POST" class="inline-block delete-form-actual">
                                    <input type="hidden" name="_token" value="${csrf}">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="button" class="p-2 text-rose-600 hover:bg-gray-100 dark:hover:bg-white/5 rounded-lg transition-colors delete-trigger" data-message="Are you sure you want to delete pincode '${row.postal_code}'?" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        `;
                    }
                }
            ],
            order: [[0, "asc"]],
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
