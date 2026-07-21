@extends('layouts.app')

@section('title', 'API Tester')

@php
    $totalRoutes = count($endpoints);
    $supportedRoutes = collect($endpoints)->where('supported', true)->count();
    $manualRoutes = $totalRoutes - $supportedRoutes;
    $authRoutes = collect($endpoints)->where('requires_auth', true)->count();
    $categories = collect($endpoints)->pluck('category')->filter()->unique()->sort()->values();
@endphp

@section('content')
    <div class="space-y-8">
        <div class="overflow-hidden rounded-[28px] border border-amber-200/80 bg-gradient-to-br from-white via-amber-50/60 to-orange-50/80 shadow-sm dark:border-white/5 dark:bg-gradient-to-br dark:from-[#111827] dark:via-[#0f172a] dark:to-[#111827]">
            <div class="grid gap-6 px-6 py-7 lg:grid-cols-[1.6fr_1fr] lg:px-8">
                <div>
                    <div class="inline-flex items-center rounded-full border border-amber-200 bg-white/80 px-3 py-1 text-[11px] font-black uppercase tracking-[0.25em] text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
                        Internal Admin Tool
                    </div>
                    <h1 class="mt-4 text-3xl font-black tracking-tight text-gray-900 dark:text-white">API Tester</h1>
                    <p class="mt-3 max-w-3xl text-sm leading-6 text-gray-600 dark:text-gray-300">
                        Run fast health checks across your API catalog, isolate failing routes, and inspect response previews without leaving the dashboard.
                    </p>

                    <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-2xl border border-gray-200 bg-white/80 p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
                            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-gray-400">Total Routes</p>
                            <p class="mt-2 text-2xl font-black text-gray-900 dark:text-white">{{ $totalRoutes }}</p>
                        </div>
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/90 p-4 shadow-sm dark:border-emerald-500/10 dark:bg-emerald-500/10">
                            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-emerald-600 dark:text-emerald-300">Supported</p>
                            <p class="mt-2 text-2xl font-black text-emerald-700 dark:text-emerald-200">{{ $supportedRoutes }}</p>
                        </div>
                        <div class="rounded-2xl border border-rose-200 bg-rose-50/90 p-4 shadow-sm dark:border-rose-500/10 dark:bg-rose-500/10">
                            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-rose-600 dark:text-rose-300">Manual Only</p>
                            <p class="mt-2 text-2xl font-black text-rose-700 dark:text-rose-200">{{ $manualRoutes }}</p>
                        </div>
                        <div class="rounded-2xl border border-sky-200 bg-sky-50/90 p-4 shadow-sm dark:border-sky-500/10 dark:bg-sky-500/10">
                            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-sky-600 dark:text-sky-300">Auth Routes</p>
                            <p class="mt-2 text-2xl font-black text-sky-700 dark:text-sky-200">{{ $authRoutes }}</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-4 rounded-[24px] border border-amber-200/80 bg-white/90 p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.25em] text-gray-400">Running As</p>
                        <div class="mt-3 rounded-2xl border border-gray-200 bg-gray-50/90 px-4 py-4 dark:border-white/10 dark:bg-white/5">
                            <p class="text-base font-black text-gray-900 dark:text-white">
                                {{ $adminUser->name ?? auth()->user()->name }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ $adminUser->email ?? auth()->user()->email }}
                            </p>
                            <div class="mt-3 inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-[11px] font-bold text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
                                Admin Mode
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-200">
                        Tests run as the signed-in admin and bypass subscription and credit checks only inside this internal tester.
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <button type="button" id="runAllBtn"
                            class="inline-flex items-center justify-center rounded-2xl bg-amber-600 px-4 py-3 text-sm font-black text-white shadow-sm transition-all hover:bg-amber-700 disabled:cursor-not-allowed disabled:opacity-60">
                            <i class="fas fa-play mr-2"></i> Run All Supported
                        </button>
                        <button type="button" id="runSelectedBtn"
                            class="inline-flex items-center justify-center rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm font-bold text-gray-700 transition-all hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-200 dark:hover:bg-white/10">
                            <i class="fas fa-layer-group mr-2"></i> Run Selected
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-8 xl:grid-cols-[1.05fr_1.95fr]">
            <div class="space-y-6">
                <div class="rounded-[24px] border border-gray-200 bg-white shadow-sm dark:border-white/5 dark:bg-[#0f172a]/80">
                    <div class="border-b border-gray-100 px-6 py-5 dark:border-white/5">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h2 class="text-base font-black text-gray-900 dark:text-white">Run Summary</h2>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Current run status and the latest result breakdown.</p>
                            </div>
                            <span id="summaryBadge"
                                class="hidden rounded-full px-3 py-1 text-[11px] font-bold bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300"></span>
                        </div>
                    </div>

                    <div class="p-6">
                        <div id="runMeta" class="hidden mb-5 grid gap-3 sm:grid-cols-3">
                            <div class="rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 dark:border-white/10 dark:bg-white/5">
                                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-gray-400">Executed</p>
                                <p id="metaExecuted" class="mt-1 text-xl font-black text-gray-900 dark:text-white">0</p>
                            </div>
                            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 dark:border-emerald-500/10 dark:bg-emerald-500/10">
                                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-emerald-600 dark:text-emerald-300">Passed</p>
                                <p id="metaPassed" class="mt-1 text-xl font-black text-emerald-700 dark:text-emerald-200">0</p>
                            </div>
                            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 dark:border-rose-500/10 dark:bg-rose-500/10">
                                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-rose-600 dark:text-rose-300">Failed</p>
                                <p id="metaFailed" class="mt-1 text-xl font-black text-rose-700 dark:text-rose-200">0</p>
                            </div>
                        </div>

                        <div id="emptyState" class="rounded-2xl border border-dashed border-gray-300 bg-gray-50/80 px-5 py-6 text-sm text-gray-500 dark:border-white/10 dark:bg-white/5 dark:text-gray-400">
                            Pick one route, a filtered set, or run the whole supported catalog. Results will appear here with status, duration, and response previews.
                        </div>

                        <div id="resultsContainer" class="hidden space-y-4"></div>
                    </div>
                </div>

                <div class="rounded-[24px] border border-gray-200 bg-white shadow-sm dark:border-white/5 dark:bg-[#0f172a]/80">
                    <div class="border-b border-gray-100 px-6 py-5 dark:border-white/5">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-base font-black text-gray-900 dark:text-white">Selection</h2>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Control the visible and selected routes before running tests.</p>
                            </div>
                            <span id="selectionBadge" class="rounded-full bg-gray-100 px-3 py-1 text-[11px] font-bold text-gray-600 dark:bg-white/10 dark:text-gray-300">
                                0 selected
                            </span>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <button type="button" id="selectVisibleBtn"
                                class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-bold text-gray-700 transition hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5">
                                Select Visible
                            </button>
                            <button type="button" id="clearSelectionBtn"
                                class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-bold text-gray-700 transition hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5">
                                Clear Selection
                            </button>
                        </div>

                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <button type="button" id="clearResultsBtn"
                                class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-bold text-gray-700 transition hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5">
                                Clear Results
                            </button>
                            <button type="button" id="showFailuresBtn"
                                class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-bold text-rose-700 transition hover:bg-rose-100 dark:border-rose-500/10 dark:bg-rose-500/10 dark:text-rose-300 dark:hover:bg-rose-500/20">
                                Show Manual Only
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-[24px] border border-gray-200 bg-white shadow-sm dark:border-white/5 dark:bg-[#0f172a]/80 overflow-hidden">
                <div class="border-b border-gray-100 px-6 py-5 dark:border-white/5">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                        <div>
                            <h2 class="text-lg font-black text-gray-900 dark:text-white">Endpoint Catalog</h2>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Search by route, filter by category or support status, then run only what you need.
                            </p>
                        </div>
                        <div class="text-xs font-bold uppercase tracking-[0.2em] text-gray-400">
                            <span id="visibleCount">{{ $totalRoutes }}</span> visible of {{ $totalRoutes }}
                        </div>
                    </div>

                    <div class="mt-5 grid gap-3 xl:grid-cols-[1.5fr_0.85fr_0.85fr_auto]">
                        <div class="relative">
                            <i class="fas fa-search pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm text-gray-400"></i>
                            <input id="endpointSearch" type="text"
                                class="w-full rounded-2xl border border-gray-200 bg-gray-50 pl-11 pr-4 py-3 text-sm text-gray-800 placeholder:text-gray-400 focus:border-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-100 dark:border-white/10 dark:bg-white/5 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-amber-500 dark:focus:ring-amber-500/10"
                                placeholder="Search routes, category, sample path or method">
                        </div>

                        <select id="categoryFilter"
                            class="rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-800 focus:border-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-100 dark:border-white/10 dark:bg-white/5 dark:text-white dark:focus:border-amber-500 dark:focus:ring-amber-500/10">
                            <option value="">All Categories</option>
                            @foreach ($categories as $category)
                                <option value="{{ strtolower($category) }}">{{ $category }}</option>
                            @endforeach
                        </select>

                        <select id="supportFilter"
                            class="rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-800 focus:border-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-100 dark:border-white/10 dark:bg-white/5 dark:text-white dark:focus:border-amber-500 dark:focus:ring-amber-500/10">
                            <option value="">All Statuses</option>
                            <option value="supported">Supported</option>
                            <option value="manual">Manual Only</option>
                            <option value="auth">Requires Auth</option>
                        </select>

                        <button type="button" id="resetFiltersBtn"
                            class="rounded-2xl border border-gray-200 px-4 py-3 text-sm font-bold text-gray-700 transition hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5">
                            Reset
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[980px]">
                        <thead class="bg-gray-50/80 dark:bg-white/5">
                            <tr>
                                <th class="px-6 py-4 text-left text-[11px] font-black uppercase tracking-[0.2em] text-gray-400">
                                    <input id="selectAllVisible" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                                </th>
                                <th class="px-6 py-4 text-left text-[11px] font-black uppercase tracking-[0.2em] text-gray-400">Endpoint</th>
                                <th class="px-6 py-4 text-left text-[11px] font-black uppercase tracking-[0.2em] text-gray-400">Category</th>
                                <th class="px-6 py-4 text-left text-[11px] font-black uppercase tracking-[0.2em] text-gray-400">Sample Request</th>
                                <th class="px-6 py-4 text-right text-[11px] font-black uppercase tracking-[0.2em] text-gray-400">Action</th>
                            </tr>
                        </thead>
                        <tbody id="endpointTableBody" class="divide-y divide-gray-100 dark:divide-white/5">
                            @foreach ($endpoints as $endpoint)
                                <tr
                                    class="endpoint-row transition-colors hover:bg-amber-50/50 dark:hover:bg-white/[0.03]"
                                    data-key="{{ $endpoint['key'] }}"
                                    data-category="{{ strtolower($endpoint['category']) }}"
                                    data-supported="{{ $endpoint['supported'] ? '1' : '0' }}"
                                    data-auth="{{ $endpoint['requires_auth'] ? '1' : '0' }}"
                                    data-search="{{ strtolower($endpoint['method'].' '.$endpoint['uri'].' '.$endpoint['category'].' '.$endpoint['sample_path'].' '.http_build_query($endpoint['sample_query'])) }}"
                                >
                                    <td class="px-6 py-5 align-top">
                                        @if ($endpoint['supported'])
                                            <input type="checkbox"
                                                class="endpoint-checkbox mt-1 h-4 w-4 rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                                                value="{{ $endpoint['key'] }}">
                                        @endif
                                    </td>
                                    <td class="px-6 py-5 align-top">
                                        <div class="flex items-center gap-3">
                                            <span class="rounded-full px-2.5 py-1 text-[11px] font-black {{ $endpoint['method'] === 'GET' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-sky-100 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300' }}">
                                                {{ $endpoint['method'] }}
                                            </span>
                                            <code class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $endpoint['uri'] }}</code>
                                        </div>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            @if ($endpoint['requires_auth'])
                                                <span class="rounded-full bg-amber-100 px-2 py-1 text-[10px] font-bold text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">Auth</span>
                                            @endif
                                            @if ($endpoint['supported'])
                                                <span class="rounded-full bg-emerald-100 px-2 py-1 text-[10px] font-bold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">Supported</span>
                                            @else
                                                <span class="rounded-full bg-rose-100 px-2 py-1 text-[10px] font-bold text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">Manual Only</span>
                                            @endif
                                        </div>
                                        @if (!$endpoint['supported'] && $endpoint['unsupported_reason'])
                                            <p class="mt-3 max-w-md text-xs leading-5 text-rose-600 dark:text-rose-300">
                                                {{ $endpoint['unsupported_reason'] }}
                                            </p>
                                        @endif
                                    </td>
                                    <td class="px-6 py-5 align-top text-sm font-medium text-gray-600 dark:text-gray-300">
                                        {{ $endpoint['category'] }}
                                    </td>
                                    <td class="px-6 py-5 align-top">
                                        <div class="rounded-2xl border border-gray-200 bg-gray-50/80 p-3 dark:border-white/10 dark:bg-white/5">
                                            <code class="break-all text-xs leading-5 text-gray-600 dark:text-gray-300">
                                                {{ $endpoint['sample_path'] }}@if(!empty($endpoint['sample_query']))?{{ http_build_query($endpoint['sample_query']) }}@endif
                                            </code>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 align-top text-right">
                                        @if ($endpoint['supported'])
                                            <button type="button"
                                                class="run-single-btn inline-flex items-center rounded-xl bg-gray-900 px-3.5 py-2.5 text-xs font-black text-white transition-all hover:bg-black dark:bg-white/10 dark:hover:bg-white/20"
                                                data-key="{{ $endpoint['key'] }}">
                                                <i class="fas fa-bolt mr-2"></i> Run
                                            </button>
                                        @else
                                            <span class="text-xs font-semibold text-gray-400">Manual test</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const RUN_URL = "{{ route('admin.api-tester.run') }}";
        const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const endpointSearch = document.getElementById('endpointSearch');
        const categoryFilter = document.getElementById('categoryFilter');
        const supportFilter = document.getElementById('supportFilter');
        const visibleCount = document.getElementById('visibleCount');
        const selectionBadge = document.getElementById('selectionBadge');
        const runAllBtn = document.getElementById('runAllBtn');
        const runSelectedBtn = document.getElementById('runSelectedBtn');
        const selectAllVisible = document.getElementById('selectAllVisible');

        function getRows() {
            return Array.from(document.querySelectorAll('.endpoint-row'));
        }

        function getVisibleRows() {
            return getRows().filter(row => !row.classList.contains('hidden'));
        }

        function getSelectedKeys() {
            return Array.from(document.querySelectorAll('.endpoint-checkbox:checked')).map(input => input.value);
        }

        function updateSelectionBadge() {
            const selectedCount = getSelectedKeys().length;
            selectionBadge.textContent = `${selectedCount} selected`;
            runSelectedBtn.disabled = selectedCount === 0;
        }

        function updateVisibleCount() {
            visibleCount.textContent = getVisibleRows().length;
        }

        function applyFilters() {
            const searchTerm = endpointSearch.value.trim().toLowerCase();
            const category = categoryFilter.value;
            const support = supportFilter.value;

            getRows().forEach(row => {
                const matchesSearch = !searchTerm || row.dataset.search.includes(searchTerm);
                const matchesCategory = !category || row.dataset.category === category;
                const matchesSupport = !support
                    || (support === 'supported' && row.dataset.supported === '1')
                    || (support === 'manual' && row.dataset.supported === '0')
                    || (support === 'auth' && row.dataset.auth === '1');

                row.classList.toggle('hidden', !(matchesSearch && matchesCategory && matchesSupport));
            });

            updateVisibleCount();
            syncSelectAllVisible();
        }

        function syncSelectAllVisible() {
            const visibleCheckboxes = getVisibleRows()
                .map(row => row.querySelector('.endpoint-checkbox'))
                .filter(Boolean);

            if (visibleCheckboxes.length === 0) {
                selectAllVisible.checked = false;
                selectAllVisible.indeterminate = false;
                return;
            }

            const checkedCount = visibleCheckboxes.filter(checkbox => checkbox.checked).length;
            selectAllVisible.checked = checkedCount > 0 && checkedCount === visibleCheckboxes.length;
            selectAllVisible.indeterminate = checkedCount > 0 && checkedCount < visibleCheckboxes.length;
        }

        function renderResults(payload) {
            const emptyState = document.getElementById('emptyState');
            const container = document.getElementById('resultsContainer');
            const badge = document.getElementById('summaryBadge');
            const runMeta = document.getElementById('runMeta');

            emptyState.classList.add('hidden');
            container.classList.remove('hidden');
            runMeta.classList.remove('hidden');
            badge.classList.remove('hidden');

            document.getElementById('metaExecuted').textContent = payload.summary.total;
            document.getElementById('metaPassed').textContent = payload.summary.passed;
            document.getElementById('metaFailed').textContent = payload.summary.failed;

            badge.textContent = `${payload.summary.passed}/${payload.summary.total} passed`;
            badge.className = `rounded-full px-3 py-1 text-[11px] font-bold ${
                payload.summary.failed === 0
                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'
                    : 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300'
            }`;

            container.innerHTML = payload.results.map(result => {
                const tone = result.ok
                    ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-500/10 dark:bg-emerald-500/5'
                    : 'border-rose-200 bg-rose-50 dark:border-rose-500/10 dark:bg-rose-500/5';
                const statusTone = result.ok ? 'text-emerald-600 dark:text-emerald-300' : 'text-rose-600 dark:text-rose-300';
                const durationTone = result.duration_ms > 1200
                    ? 'text-amber-600 dark:text-amber-300'
                    : 'text-gray-500 dark:text-gray-400';

                return `
                    <article class="rounded-[22px] border ${tone} p-5 shadow-sm">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-white/80 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.2em] text-gray-700 dark:bg-white/10 dark:text-gray-200">${result.method}</span>
                                    <p class="min-w-0 truncate text-sm font-black text-gray-900 dark:text-white">${result.uri}</p>
                                </div>
                                <p class="mt-2 break-all text-[11px] text-gray-500 dark:text-gray-400">${result.tested_uri}</p>
                            </div>
                            <div class="text-left lg:text-right">
                                <p class="text-lg font-black ${statusTone}">${result.status}</p>
                                <p class="text-[11px] font-semibold ${durationTone}">${result.duration_ms} ms</p>
                            </div>
                        </div>
                        <pre class="mt-4 overflow-x-auto rounded-2xl border border-white/40 bg-white/60 p-4 text-xs leading-5 text-gray-700 dark:border-white/5 dark:bg-[#020617]/40 dark:text-gray-200 whitespace-pre-wrap break-words">${escapeHtml(result.response_preview || '')}</pre>
                    </article>
                `;
            }).join('');
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.innerText = text;
            return div.innerHTML;
        }

        function setRunningState(isRunning, trigger = null) {
            [runAllBtn, runSelectedBtn].forEach(button => {
                button.disabled = isRunning || (button === runSelectedBtn && getSelectedKeys().length === 0);
            });

            document.querySelectorAll('.run-single-btn').forEach(button => {
                button.disabled = isRunning;
                button.classList.toggle('opacity-60', isRunning);
            });

            if (isRunning && trigger) {
                trigger.dataset.originalText = trigger.innerHTML;
                trigger.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Running';
            } else if (!isRunning && trigger?.dataset.originalText) {
                trigger.innerHTML = trigger.dataset.originalText;
                delete trigger.dataset.originalText;
            }

            runAllBtn.classList.toggle('opacity-60', isRunning);
            runSelectedBtn.classList.toggle('opacity-60', isRunning);
        }

        async function runTests(endpointKeys = [], trigger = null) {
            setRunningState(true, trigger);

            try {
                const response = await fetch(RUN_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
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
                setRunningState(false, trigger);
            }
        }

        endpointSearch.addEventListener('input', applyFilters);
        categoryFilter.addEventListener('change', applyFilters);
        supportFilter.addEventListener('change', applyFilters);

        document.getElementById('resetFiltersBtn').addEventListener('click', () => {
            endpointSearch.value = '';
            categoryFilter.value = '';
            supportFilter.value = '';
            applyFilters();
        });

        document.getElementById('selectVisibleBtn').addEventListener('click', () => {
            getVisibleRows().forEach(row => {
                const checkbox = row.querySelector('.endpoint-checkbox');
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
            updateSelectionBadge();
            syncSelectAllVisible();
        });

        document.getElementById('clearSelectionBtn').addEventListener('click', () => {
            document.querySelectorAll('.endpoint-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });
            updateSelectionBadge();
            syncSelectAllVisible();
        });

        document.getElementById('showFailuresBtn').addEventListener('click', () => {
            supportFilter.value = 'manual';
            applyFilters();
        });

        selectAllVisible.addEventListener('change', () => {
            getVisibleRows().forEach(row => {
                const checkbox = row.querySelector('.endpoint-checkbox');
                if (checkbox) {
                    checkbox.checked = selectAllVisible.checked;
                }
            });
            updateSelectionBadge();
            syncSelectAllVisible();
        });

        document.querySelectorAll('.endpoint-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                updateSelectionBadge();
                syncSelectAllVisible();
            });
        });

        runAllBtn.addEventListener('click', () => runTests([], runAllBtn));

        runSelectedBtn.addEventListener('click', () => {
            const selectedKeys = getSelectedKeys();
            if (selectedKeys.length === 0) {
                toastr.warning('Select at least one supported endpoint first.');
                return;
            }

            runTests(selectedKeys, runSelectedBtn);
        });

        document.querySelectorAll('.run-single-btn').forEach(button => {
            button.addEventListener('click', () => runTests([button.dataset.key], button));
        });

        document.getElementById('clearResultsBtn').addEventListener('click', () => {
            document.getElementById('resultsContainer').classList.add('hidden');
            document.getElementById('resultsContainer').innerHTML = '';
            document.getElementById('emptyState').classList.remove('hidden');
            document.getElementById('summaryBadge').classList.add('hidden');
            document.getElementById('runMeta').classList.add('hidden');
        });

        applyFilters();
        updateSelectionBadge();
    </script>
@endpush
