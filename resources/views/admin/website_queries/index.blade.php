@extends('layouts.app')

@section('header', 'Website Queries')

@section('content')
<div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">Website Queries</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 font-medium">Customer messages received from the Contact Us page.</p>
    </div>
</div>

<div class="bg-white dark:bg-richdark-surface rounded-2xl shadow-sm border border-gray-200 dark:border-white/5 overflow-hidden p-6">
    <div class="overflow-x-auto">
        <table id="queriesTable" class="min-w-full divide-y divide-gray-200 dark:divide-white/5">
            <thead>
                <tr>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Name</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Email</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Date</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Status</th>
                    <th class="px-6 py-4 text-right text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/5 text-sm">
            </tbody>
        </table>
    </div>
</div>

<!-- View Query Modal -->
<div id="queryModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" aria-hidden="true" onclick="hideQueryModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-richdark-surface rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full border border-white/10">
            <div class="p-8">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h3 class="text-2xl font-black text-gray-900 dark:text-white" id="modal-name"></h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 font-medium" id="modal-email"></p>
                    </div>
                    <span id="modal-status" class="px-3 py-1 text-[10px] font-black tracking-widest rounded-full uppercase"></span>
                </div>
                
                <div class="space-y-6">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Subject</label>
                        <p class="text-sm font-bold text-gray-700 dark:text-gray-300" id="modal-subject"></p>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Received On</label>
                        <p class="text-sm font-bold text-gray-700 dark:text-gray-300" id="modal-date"></p>
                    </div>
                    
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Message</label>
                        <div class="bg-gray-50 dark:bg-white/5 p-4 rounded-xl border border-gray-100 dark:border-white/5">
                            <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed whitespace-pre-wrap" id="modal-message"></p>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex justify-end space-x-3">
                    <button onclick="hideQueryModal()" class="px-6 py-2.5 bg-gray-100 dark:bg-white/10 text-gray-600 dark:text-gray-300 rounded-xl text-xs font-bold hover:bg-gray-200 transition-all">Close</button>
                    <button id="markViewedBtn" class="px-6 py-2.5 bg-amber-600 text-white rounded-xl text-xs font-bold hover:bg-amber-700 shadow-lg shadow-amber-900/20 transition-all">Mark as Viewed</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let queriesTable;
    $(document).ready(function() {
        queriesTable = $('#queriesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('admin.website-queries.index') }}",
            columns: [
                { 
                    data: 'name', 
                    name: 'name',
                    render: function(data) {
                        return `<div class="font-bold text-gray-900 dark:text-white">${data}</div>`;
                    }
                },
                { data: 'email', name: 'email' },
                { data: 'created_at', name: 'created_at' },
                { 
                    data: 'status', 
                    name: 'status',
                    render: function(data) {
                        let colorClass = data === 'pending' ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400' : 'bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-400';
                        return `<span class="px-2.5 py-1 text-[10px] font-black tracking-widest rounded-full uppercase ${colorClass}">${data === 'viewed' ? 'VIEWED' : data}</span>`;
                    }
                },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-right' }
            ],
            language: {
                searchPlaceholder: "Search queries...",
                search: ""
            },
            dom: '<"flex flex-col sm:flex-row justify-between items-center mb-4"lf>rt<"flex flex-col sm:flex-row justify-between items-center mt-4"ip>'
        });
    });

    function viewQuery(id) {
        $.get(`/website-queries/${id}`, function(data) {
            $('#modal-name').text(data.name);
            $('#modal-email').text(data.email);
            $('#modal-subject').text(data.subject || 'No Subject');
            $('#modal-date').text(data.formatted_date);
            $('#modal-message').text(data.message);
            
            let statusEl = $('#modal-status');
            statusEl.text(data.status);
            if (data.status === 'pending') {
                statusEl.attr('class', 'px-3 py-1 text-[10px] font-black tracking-widest rounded-full uppercase bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400');
                $('#markViewedBtn').show().attr('onclick', `markAsViewed(${data.id})`);
            } else {
                statusEl.attr('class', 'px-3 py-1 text-[10px] font-black tracking-widest rounded-full uppercase bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-400');
                $('#markViewedBtn').hide();
            }
            
            $('#queryModal').removeClass('hidden');
        });
    }

    function hideQueryModal() {
        $('#queryModal').addClass('hidden');
    }

    function markAsViewed(id) {
        $.post(`/website-queries/${id}/mark-viewed`, {
            _token: '{{ csrf_token() }}'
        }, function() {
            queriesTable.ajax.reload();
            hideQueryModal();
        });
    }

    function deleteQuery(id) {
        if (confirm('Are you sure you want to delete this query?')) {
            $.ajax({
                url: `/website-queries/${id}`,
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function() {
                    queriesTable.ajax.reload();
                }
            });
        }
    }
</script>
@endpush
@endsection
