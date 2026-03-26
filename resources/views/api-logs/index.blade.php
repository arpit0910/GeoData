@extends('layouts.app')

@section('header', 'API Logs')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">API Access Logs</h1>
    <p class="mt-1 text-sm text-gray-600">
        @if(auth()->user()->is_admin)
            Detailed audit trail of all API requests across the platform.
        @else
            Your personal API request history and credit usage audit.
        @endif
    </p>
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
    $(document).ready(function() {
        $('#logsTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 50,
            ajax: "{{ route('api-logs.index') }}",
            order: [[{{ auth()->user()->is_admin ? 6 : 5 }}, 'desc']],
            columns: [
                @if(auth()->user()->is_admin)
                { data: 'user_name', name: 'user.name' },
                @endif
                { 
                    data: 'endpoint', 
                    name: 'endpoint',
                    render: function(data) {
                        return `<code class="text-xs bg-gray-100 px-1 py-0.5 rounded">/${data}</code>`;
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
                lengthMenu: "_MENU_ per page"
            }
        });
    });
</script>
@endpush
