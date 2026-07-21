@extends('layouts.app')

@section('title', 'API Tester')

@section('content')
    <div class="mb-8 flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">API Tester</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 font-medium">
                Run live checks against supported API endpoints from the admin panel.
            </p>
        </div>
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-medium text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-200 max-w-xl">
            Admin-mode tests bypass subscription and credit checks only inside this internal tester. Non-admin runs still use the normal API rules.
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        <div class="xl:col-span-1 space-y-6">
            <div class="bg-white dark:bg-[#0f172a]/80 border border-gray-200 dark:border-white/5 rounded-2xl shadow-sm p-6">
                <label class="block text-xs font-bold text-gray-400 mb-2 uppercase tracking-widest">Run Tests As</label>
                <select id="testUser"
                    class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-amber-500 transition-all">
                    <option value="">Select an admin or subscribed user</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(auth()->id() === $user->id)>{{ $user->name }} ({{ $user->email }}){{ $user->is_admin ? ' - Admin' : '' }}</option>
                    @endforeach
                </select>

                <div class="mt-6 flex gap-3">
                    <button type="button" id="runAllBtn"
                        class="flex-1 inline-flex items-center justify-center px-4 py-3 rounded-xl bg-amber-600 text-white font-bold text-sm hover:bg-amber-700 transition-all">
                        <i class="fas fa-play mr-2"></i> Run All Supported
                    </button>
                    <button type="button" id="clearResultsBtn"
                        class="px-4 py-3 rounded-xl border border-gray-200 dark:border-white/10 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5 transition-all">
                        <i class="fas fa-eraser"></i>
                    </button>
                </div>
            </div>

            <div class="bg-white dark:bg-[#0f172a]/80 border border-gray-200 dark:border-white/5 rounded-2xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-black uppercase tracking-widest text-gray-500 dark:text-gray-400">Summary</h2>
                    <span id="summaryBadge"
                        class="hidden px-3 py-1 rounded-full text-[11px] font-bold bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300"></span>
                </div>

                <div id="emptyState" class="text-sm text-gray-500 dark:text-gray-400">
                    Run a test to see response statuses and payload previews here.
                </div>

                <div id="resultsContainer" class="hidden space-y-3"></div>
            </div>
        </div>

        <div class="xl:col-span-2 bg-white dark:bg-[#0f172a]/80 border border-gray-200 dark:border-white/5 rounded-2xl shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-100 dark:border-white/5 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Endpoint Catalog</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Use the row action to test a single endpoint or run everything supported.</p>
                </div>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">{{ count($endpoints) }} routes</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr>
                            <th class="text-left text-xs font-bold text-gray-400 uppercase tracking-wider px-6 py-4">Endpoint</th>
                            <th class="text-left text-xs font-bold text-gray-400 uppercase tracking-wider px-6 py-4">Category</th>
                            <th class="text-left text-xs font-bold text-gray-400 uppercase tracking-wider px-6 py-4">Sample</th>
                            <th class="text-right text-xs font-bold text-gray-400 uppercase tracking-wider px-6 py-4">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach ($endpoints as $endpoint)
                            <tr>
                                <td class="px-6 py-4 align-top">
                                    <div class="flex items-center gap-3">
                                        <span class="px-2.5 py-1 rounded-full text-[11px] font-black {{ $endpoint['method'] === 'GET' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-sky-100 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300' }}">
                                            {{ $endpoint['method'] }}
                                        </span>
                                        <code class="text-sm text-gray-800 dark:text-gray-200">{{ $endpoint['uri'] }}</code>
                                    </div>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @if ($endpoint['requires_auth'])
                                            <span class="px-2 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">Auth</span>
                                        @endif
                                        @if (!$endpoint['supported'])
                                            <span class="px-2 py-1 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">Manual</span>
                                        @else
                                            <span class="px-2 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">Supported</span>
                                        @endif
                                    </div>
                                    @if (!$endpoint['supported'] && $endpoint['unsupported_reason'])
                                        <p class="mt-2 text-xs text-rose-600 dark:text-rose-300">{{ $endpoint['unsupported_reason'] }}</p>
                                    @endif
                                </td>
                                <td class="px-6 py-4 align-top text-sm text-gray-600 dark:text-gray-300">
                                    {{ $endpoint['category'] }}
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <code class="text-xs text-gray-600 dark:text-gray-300 break-all">{{ $endpoint['sample_path'] }}@if(!empty($endpoint['sample_query']))?{{ http_build_query($endpoint['sample_query']) }}@endif</code>
                                </td>
                                <td class="px-6 py-4 align-top text-right">
                                    @if ($endpoint['supported'])
                                        <button type="button"
                                            class="run-single-btn inline-flex items-center px-3 py-2 rounded-lg bg-gray-900 text-white text-xs font-bold hover:bg-black dark:bg-white/10 dark:hover:bg-white/20 transition-all"
                                            data-key="{{ $endpoint['key'] }}">
                                            <i class="fas fa-bolt mr-2"></i> Run
                                        </button>
                                    @else
                                        <span class="text-xs text-gray-400">Manual only</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const RUN_URL = "{{ route('admin.api-tester.run') }}";
        const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function ensureUserSelected() {
            const userId = document.getElementById('testUser').value;
            if (!userId) {
                toastr.error('Please select an active subscribed user first.');
                return null;
            }
            return userId;
        }

        function renderResults(payload) {
            const emptyState = document.getElementById('emptyState');
            const container = document.getElementById('resultsContainer');
            const badge = document.getElementById('summaryBadge');

            emptyState.classList.add('hidden');
            container.classList.remove('hidden');
            badge.classList.remove('hidden');

            badge.textContent = `${payload.summary.passed}/${payload.summary.total} passed`;
            badge.className = `px-3 py-1 rounded-full text-[11px] font-bold ${
                payload.summary.failed === 0
                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'
                    : 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300'
            }`;

            container.innerHTML = payload.results.map(result => {
                const tone = result.ok
                    ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-500/10 dark:bg-emerald-500/5'
                    : 'border-rose-200 bg-rose-50 dark:border-rose-500/10 dark:bg-rose-500/5';
                const statusTone = result.ok ? 'text-emerald-600 dark:text-emerald-300' : 'text-rose-600 dark:text-rose-300';

                return `
                    <div class="rounded-2xl border ${tone} p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-black text-gray-900 dark:text-white">${result.method} ${result.uri}</p>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1 break-all">${result.tested_uri}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-black ${statusTone}">${result.status}</p>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400">${result.duration_ms} ms</p>
                            </div>
                        </div>
                        <pre class="mt-3 text-xs text-gray-700 dark:text-gray-200 whitespace-pre-wrap break-words overflow-x-auto">${escapeHtml(result.response_preview || '')}</pre>
                    </div>
                `;
            }).join('');
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.innerText = text;
            return div.innerHTML;
        }

        async function runTests(endpointKeys = []) {
            const userId = ensureUserSelected();
            if (!userId) return;

            const runAllBtn = document.getElementById('runAllBtn');
            runAllBtn.disabled = true;
            runAllBtn.classList.add('opacity-60', 'pointer-events-none');

            try {
                const response = await fetch(RUN_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        endpoints: endpointKeys
                    })
                });

                const payload = await response.json();
                if (!response.ok || !payload.success) {
                    toastr.error(payload.message || 'API test run failed.');
                    return;
                }

                renderResults(payload);
                toastr.success(payload.warning || 'API tests completed.');
            } catch (error) {
                toastr.error('Unable to run API tests right now.');
            } finally {
                runAllBtn.disabled = false;
                runAllBtn.classList.remove('opacity-60', 'pointer-events-none');
            }
        }

        document.getElementById('runAllBtn').addEventListener('click', () => runTests());

        document.querySelectorAll('.run-single-btn').forEach(button => {
            button.addEventListener('click', () => runTests([button.dataset.key]));
        });

        document.getElementById('clearResultsBtn').addEventListener('click', () => {
            document.getElementById('resultsContainer').classList.add('hidden');
            document.getElementById('resultsContainer').innerHTML = '';
            document.getElementById('emptyState').classList.remove('hidden');
            document.getElementById('summaryBadge').classList.add('hidden');
        });
    </script>
@endpush
