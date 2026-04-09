@extends('layouts.app')

@section('header', 'API Logs')

@section('content')
<div class="mb-6 flex justify-between items-start">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 group flex items-center">
            <i class="fas fa-history text-amber-500 mr-3"></i> API Access Logs
        </h1>
        <p class="mt-1 text-sm text-gray-600">
            @if(auth()->user()->is_admin)
                Detailed audit trail of all API requests across the platform.
            @else
                Your personal API request history and credit usage audit.
            @endif
        </p>
    </div>
    
    <div class="flex items-center space-x-4">
        <div class="text-xs font-bold text-gray-400 uppercase tracking-widest">
            Last Updated: <span id="lastUpdatedTime" class="text-gray-700">{{ now()->format('d-m-Y @ h:i A') }}</span>
        </div>
        <button onclick="logsTable.ajax.reload(null, false); const now = new Date(); const day = String(now.getDate()).padStart(2, '0'); const month = String(now.getMonth() + 1).padStart(2, '0'); const year = now.getFullYear(); const timeString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true }); $('#lastUpdatedTime').text(`${day}-${month}-${year} @ ${timeString}`);" class="px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-xs font-bold text-gray-600 hover:bg-gray-50 hover:text-amber-600 transition-all shadow-sm flex items-center cursor-pointer">
            <i class="fas fa-sync-alt mr-1.5"></i> Refresh
        </button>
    </div>
</div>

<div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <div class="p-6 bg-white border-b border-gray-200 overflow-x-auto">
        <table id="logsTable" class="w-full text-left border-collapse">
            <thead>
                <tr>
                    @if(auth()->user()->is_admin)
                        <th>User</th>
                    @endif
                    <th>Endpoint</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Credits</th>
                    <th>IP Address</th>
                    <th>Time</th>
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
    let logsTable;

    $(document).ready(function() {
        logsTable = $('#logsTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 50,
            ajax: {
                url: "{{ route('api-logs.index') }}",
                dataSrc: function (json) {
                    return json.data;
                }
            },
            order: [[{{ auth()->user()->is_admin ? 6 : 5 }}, 'desc']],
            columns: [
                @if(auth()->user()->is_admin)
                { data: 'user_name', name: 'user.name' },
                @endif
                { 
                    data: 'endpoint', 
                    name: 'endpoint',
                    render: function(data) {
                        return `<code class="text-xs bg-gray-100 px-1 py-0.5 rounded break-all" style="word-break: break-all; white-space: normal;">/${data}</code>`;
                    }
                },
                { data: 'method', name: 'method' },
                { data: 'status_badge', name: 'status_code', orderable: true },
                { data: 'credit_badge', name: 'credit_deducted', orderable: true },
                { data: 'ip_address', name: 'ip_address' },
                { data: 'time', name: 'created_at' }
            ],
            dom: '<"flex flex-col sm:flex-row justify-between items-center mb-4"lf>rt<"flex flex-col sm:flex-row justify-between items-center mt-4"ip>',
            language: {
                searchPlaceholder: "Search logs...",
                lengthMenu: "_MENU_ per page",
                infoFiltered: ""
            }
        });
    });
</script>
@endpush
