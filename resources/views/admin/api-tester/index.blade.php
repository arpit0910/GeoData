@extends('layouts.app')

@section('title', 'API Tester')

@php
    $totalRoutes = count($endpoints);
    $supportedRoutes = collect($endpoints)->where('supported', true)->count();
    $manualRoutes = $totalRoutes - $supportedRoutes;
    $authRoutes = collect($endpoints)->where('requires_auth', true)->count();
    $endpointPayload = collect($endpoints)->map(function ($endpoint) {
        $endpoint['sample_query_string'] = http_build_query($endpoint['sample_query']);
        return $endpoint;
    })->values();
@endphp

@section('content')
    <div class="space-y-6">
        <section class="rounded-[28px] border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-slate-950">
            <div class="grid gap-6 px-6 py-6 xl:grid-cols-[1.4fr_1fr]">
                <div>
                    <div class="inline-flex items-center rounded-full border border-cyan-200 bg-cyan-50 px-3 py-1 text-[11px] font-black uppercase tracking-[0.22em] text-cyan-700 dark:border-cyan-500/20 dark:bg-cyan-500/10 dark:text-cyan-300">
                        API Workspace
                    </div>
                    <h1 class="mt-4 text-3xl font-black tracking-tight text-slate-900 dark:text-white">Admin API Tester</h1>
                    <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                        Use this page like a lightweight Postman workspace. Pick one endpoint from the left, edit its path and parameters, run it, and inspect the response immediately.
                    </p>

                    <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-white/10 dark:bg-white/5">
                            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-slate-400">Routes</p>
                            <p class="mt-2 text-2xl font-black text-slate-900 dark:text-white">{{ $totalRoutes }}</p>
                        </div>
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-500/20 dark:bg-emerald-500/10">
                            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-emerald-600 dark:text-emerald-300">Runnable</p>
                            <p class="mt-2 text-2xl font-black text-emerald-700 dark:text-emerald-200">{{ $supportedRoutes }}</p>
                        </div>
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-500/20 dark:bg-amber-500/10">
                            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-amber-600 dark:text-amber-300">Auth</p>
                            <p class="mt-2 text-2xl font-black text-amber-700 dark:text-amber-200">{{ $authRoutes }}</p>
                        </div>
                        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-500/20 dark:bg-rose-500/10">
                            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-rose-600 dark:text-rose-300">Manual</p>
                            <p class="mt-2 text-2xl font-black text-rose-700 dark:text-rose-200">{{ $manualRoutes }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-5 dark:border-white/10 dark:bg-white/5">
                    <div class="grid gap-4">
                        <div>
                            <label for="companySelect" class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-400">Company Context</label>
                            <select id="companySelect" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 focus:border-cyan-400 focus:outline-none focus:ring-4 focus:ring-cyan-100 dark:border-white/10 dark:bg-slate-950 dark:text-white dark:focus:border-cyan-500 dark:focus:ring-cyan-500/10">
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}" {{ $selectedCompany && $selectedCompany->id === $company->id ? 'selected' : '' }}>
                                        {{ $company->company_name ?: $company->name }} - {{ $company->email }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="modeSelect" class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-400">Mode</label>
                            <select id="modeSelect" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 focus:border-cyan-400 focus:outline-none focus:ring-4 focus:ring-cyan-100 dark:border-white/10 dark:bg-slate-950 dark:text-white dark:focus:border-cyan-500 dark:focus:ring-cyan-500/10">
                                <option value="demo">Demo mode - bypass module and credit checks</option>
                                <option value="production">Production mode - use real company access</option>
                            </select>
                        </div>

                        <div class="rounded-2xl border border-cyan-200 bg-cyan-50 px-4 py-3 text-xs leading-5 text-cyan-800 dark:border-cyan-500/20 dark:bg-cyan-500/10 dark:text-cyan-200">
                            Pick a company first. Every sample path and parameter set in this workspace is prepared from that company context.
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[320px_minmax(0,1fr)]">
            <aside class="overflow-hidden rounded-[24px] border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-slate-950">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-white/10">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base font-black text-slate-900 dark:text-white">Endpoints</h2>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Pick one request to open in the builder.</p>
                        </div>
                        <span id="sidebarVisibleCount" class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-bold text-slate-600 dark:bg-white/10 dark:text-slate-300">{{ $totalRoutes }}</span>
                    </div>
                    <div class="mt-4 space-y-3">
                        <input id="endpointSearch" type="text" placeholder="Search endpoint or category"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-cyan-400 focus:outline-none focus:ring-4 focus:ring-cyan-100 dark:border-white/10 dark:bg-white/5 dark:text-white dark:placeholder:text-slate-500 dark:focus:border-cyan-500 dark:focus:ring-cyan-500/10">
                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                            <select id="supportFilter" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 focus:border-cyan-400 focus:outline-none focus:ring-4 focus:ring-cyan-100 dark:border-white/10 dark:bg-white/5 dark:text-white dark:focus:border-cyan-500 dark:focus:ring-cyan-500/10">
                                <option value="">All statuses</option>
                                <option value="supported">Runnable</option>
                                <option value="manual">Manual only</option>
                                <option value="auth">Requires auth</option>
                            </select>
                            <button id="resetFiltersBtn" type="button" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-white/10 dark:text-slate-200 dark:hover:bg-white/5">
                                Reset Filters
                            </button>
                        </div>
                    </div>
                </div>

                <div id="endpointList" class="max-h-[calc(100vh-280px)] overflow-y-auto p-3"></div>
            </aside>

            <div class="space-y-6">
                <section class="overflow-hidden rounded-[24px] border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-slate-950">
                    <div class="border-b border-slate-200 px-6 py-4 dark:border-white/10">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span id="activeMethodBadge" class="rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-black text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"></span>
                                    <span id="activeCategoryBadge" class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600 dark:bg-white/10 dark:text-slate-300"></span>
                                    <span id="activeStatusBadge" class="rounded-full bg-cyan-100 px-2.5 py-1 text-[11px] font-bold text-cyan-700 dark:bg-cyan-500/10 dark:text-cyan-300"></span>
                                </div>
                                <h2 id="activeUri" class="mt-3 text-lg font-black text-slate-900 dark:text-white"></h2>
                                <p id="activeHelpText" class="mt-1 text-xs text-slate-500 dark:text-slate-400"></p>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <button id="runActiveBtn" type="button" class="inline-flex items-center justify-center rounded-2xl bg-cyan-600 px-4 py-3 text-sm font-black text-white transition hover:bg-cyan-700 disabled:cursor-not-allowed disabled:opacity-60">
                                    <i class="fas fa-play mr-2"></i> Run Request
                                </button>
                                <button id="queueActiveBtn" type="button" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-slate-200 dark:hover:bg-white/10">
                                    <i class="fas fa-plus mr-2"></i> Add To Batch
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6 p-6">
                        <div>
                            <label for="requestPathInput" class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-400">Request Path</label>
                            <div class="mt-2 flex items-stretch gap-3">
                                <div class="hidden shrink-0 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-slate-300 lg:block">
                                    / 
                                </div>
                                <input id="requestPathInput" type="text" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 font-mono text-sm text-slate-800 focus:border-cyan-400 focus:outline-none focus:ring-4 focus:ring-cyan-100 dark:border-white/10 dark:bg-slate-900 dark:text-white dark:focus:border-cyan-500 dark:focus:ring-cyan-500/10">
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <h3 class="text-sm font-black text-slate-900 dark:text-white">Parameters</h3>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Edit the sample query values before running the request.</p>
                                </div>
                                <div class="flex gap-2">
                                    <button id="resetParamsBtn" type="button" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50 dark:border-white/10 dark:text-slate-200 dark:hover:bg-white/5">Reset</button>
                                    <button id="addParamBtn" type="button" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50 dark:border-white/10 dark:text-slate-200 dark:hover:bg-white/5">Add Param</button>
                                </div>
                            </div>

                            <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 dark:border-white/10">
                                <table class="w-full">
                                    <thead class="bg-slate-50 dark:bg-white/5">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-[0.18em] text-slate-400">Key</th>
                                            <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-[0.18em] text-slate-400">Value</th>
                                            <th class="px-4 py-3 text-right text-[11px] font-black uppercase tracking-[0.18em] text-slate-400">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="paramsTableBody" class="divide-y divide-slate-200 dark:divide-white/10"></tbody>
                                </table>
                            </div>
                        </div>

                        <div class="grid gap-4 lg:grid-cols-2">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-white/10 dark:bg-white/5">
                                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Preview URL</p>
                                <code id="previewUrl" class="mt-2 block break-all text-xs leading-6 text-slate-700 dark:text-slate-200"></code>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-white/10 dark:bg-white/5">
                                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Batch Queue</p>
                                <p id="batchSummary" class="mt-2 text-sm font-bold text-slate-800 dark:text-white">0 endpoints selected</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <button id="runBatchBtn" type="button" class="rounded-xl bg-slate-900 px-3 py-2 text-xs font-black text-white transition hover:bg-black dark:bg-white/10 dark:hover:bg-white/20">Run Batch</button>
                                    <button id="clearBatchBtn" type="button" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50 dark:border-white/10 dark:text-slate-200 dark:hover:bg-white/5">Clear Batch</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-[24px] border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-slate-950">
                    <div class="border-b border-slate-200 px-6 py-4 dark:border-white/10">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <h2 class="text-base font-black text-slate-900 dark:text-white">Live Response</h2>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">This panel updates after every request run.</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span id="responseStatusBadge" class="hidden rounded-full px-3 py-1 text-[11px] font-bold"></span>
                                <span id="responseTimeBadge" class="hidden rounded-full bg-slate-100 px-3 py-1 text-[11px] font-bold text-slate-600 dark:bg-white/10 dark:text-slate-300"></span>
                                <button id="liveResponseToggle" type="button" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-slate-200 dark:hover:bg-white/10" aria-expanded="true">
                                    <span id="liveResponseToggleLabel">Collapse</span>
                                    <i id="liveResponseChevron" class="fas fa-chevron-up ml-2 transition-transform"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div id="liveResponseBody" class="space-y-4 p-6">
                        <div id="responseMeta" class="hidden rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-white/10 dark:bg-white/5">
                            <p id="responseRequestLine" class="text-sm font-black text-slate-900 dark:text-white"></p>
                            <p id="responseReportLine" class="mt-1 text-xs text-slate-500 dark:text-slate-400"></p>
                        </div>
                        <div id="responseEmptyState" class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/80 px-5 py-8 text-sm text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-slate-400">
                            Run one request from the builder to see the response body, status, runtime, and generated report details here.
                        </div>
                        <pre id="responseOutput" class="hidden overflow-x-auto rounded-2xl border border-slate-200 bg-slate-950 p-5 text-xs leading-6 text-slate-100 dark:border-white/10"></pre>
                    </div>
                </section>
            </div>

            <aside class="grid gap-6 md:grid-cols-2 xl:col-span-2">
                <section class="overflow-hidden rounded-[24px] border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-slate-950">
                    <div class="border-b border-slate-200 px-5 py-4 dark:border-white/10">
                        <h2 class="text-base font-black text-slate-900 dark:text-white">Quick Run</h2>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Batch controls if you want more than one endpoint at once.</p>
                    </div>
                    <div class="space-y-3 p-5">
                        <button id="runAllBtn" type="button" class="inline-flex w-full items-center justify-center rounded-2xl bg-cyan-600 px-4 py-3 text-sm font-black text-white transition hover:bg-cyan-700 disabled:cursor-not-allowed disabled:opacity-60">
                            <i class="fas fa-play mr-2"></i> Run All Runnable
                        </button>
                        <button id="runSelectedBtn" type="button" class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-white/10 dark:bg-white/5 dark:text-slate-200 dark:hover:bg-white/10">
                            <i class="fas fa-layer-group mr-2"></i> Run Batch Queue
                        </button>
                    </div>
                </section>

                <section class="overflow-hidden rounded-[24px] border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-slate-950">
                    <div class="border-b border-slate-200 px-5 py-4 dark:border-white/10">
                        <h2 class="text-base font-black text-slate-900 dark:text-white">Saved Reports</h2>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Latest exports from this workspace.</p>
                    </div>
                    <div id="reportHistory" class="max-h-[520px] space-y-3 overflow-y-auto p-5">
                        @forelse ($reports as $report)
                            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-white/10 dark:bg-white/5">
                                <p class="text-sm font-black text-slate-900 dark:text-white">{{ $report->report_name }}</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    {{ $report->targetUser->company_name ?: $report->targetUser->name }} - {{ ucfirst($report->mode) }} - {{ $report->passed_endpoints }} passed, {{ $report->failed_endpoints }} failed, {{ $report->skipped_endpoints ?? 0 }} skipped
                                </p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <a href="{{ route('admin.api-tester.reports.download', ['reportId' => $report->id, 'format' => 'json', 'result_set' => 'all'], false) }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50 dark:border-white/10 dark:bg-slate-950 dark:text-slate-200 dark:hover:bg-white/5">
                                        <i class="fas fa-file-code mr-2"></i> All JSON
                                    </a>
                                    <a href="{{ route('admin.api-tester.reports.download', ['reportId' => $report->id, 'format' => 'pdf', 'result_set' => 'all'], false) }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50 dark:border-white/10 dark:bg-slate-950 dark:text-slate-200 dark:hover:bg-white/5">
                                        <i class="fas fa-file-pdf mr-2"></i> All PDF
                                    </a>
                                    <a href="{{ route('admin.api-tester.reports.download', ['reportId' => $report->id, 'format' => 'json', 'result_set' => 'passed'], false) }}" class="inline-flex items-center rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 transition hover:bg-emerald-100 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300 dark:hover:bg-emerald-500/20">
                                        <i class="fas fa-circle-check mr-2"></i> Success JSON
                                    </a>
                                    <a href="{{ route('admin.api-tester.reports.download', ['reportId' => $report->id, 'format' => 'pdf', 'result_set' => 'passed'], false) }}" class="inline-flex items-center rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 transition hover:bg-emerald-100 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300 dark:hover:bg-emerald-500/20">
                                        <i class="fas fa-file-pdf mr-2"></i> Success PDF
                                    </a>
                                    <a href="{{ route('admin.api-tester.reports.download', ['reportId' => $report->id, 'format' => 'json', 'result_set' => 'failed'], false) }}" class="inline-flex items-center rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700 transition hover:bg-rose-100 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300 dark:hover:bg-rose-500/20">
                                        <i class="fas fa-triangle-exclamation mr-2"></i> Failure JSON
                                    </a>
                                    <a href="{{ route('admin.api-tester.reports.download', ['reportId' => $report->id, 'format' => 'pdf', 'result_set' => 'failed'], false) }}" class="inline-flex items-center rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700 transition hover:bg-rose-100 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300 dark:hover:bg-rose-500/20">
                                        <i class="fas fa-file-pdf mr-2"></i> Failure PDF
                                    </a>
                                </div>
                            </article>
                        @empty
                            <div id="reportHistoryEmpty" class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-sm text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-slate-400">
                                No reports yet.
                            </div>
                        @endforelse
                    </div>
                </section>
            </aside>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        const RUN_URL = "{{ route('admin.api-tester.run') }}";
        const INDEX_URL = "{{ route('admin.api-tester.index') }}";
        const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const endpoints = @json($endpointPayload);
        const runnableEndpoints = endpoints.filter(endpoint => endpoint.supported);

        const state = {
            filteredEndpoints: [...endpoints],
            activeKey: runnableEndpoints[0]?.key || endpoints[0]?.key || null,
            batchKeys: new Set(),
            drafts: {},
            isRunning: false,
            responseCollapsed: false,
        };

        const endpointList = document.getElementById('endpointList');
        const endpointSearch = document.getElementById('endpointSearch');
        const supportFilter = document.getElementById('supportFilter');
        const sidebarVisibleCount = document.getElementById('sidebarVisibleCount');
        const companySelect = document.getElementById('companySelect');
        const modeSelect = document.getElementById('modeSelect');
        const runAllBtn = document.getElementById('runAllBtn');
        const runSelectedBtn = document.getElementById('runSelectedBtn');
        const runBatchBtn = document.getElementById('runBatchBtn');
        const runActiveBtn = document.getElementById('runActiveBtn');
        const queueActiveBtn = document.getElementById('queueActiveBtn');
        const clearBatchBtn = document.getElementById('clearBatchBtn');
        const addParamBtn = document.getElementById('addParamBtn');
        const resetParamsBtn = document.getElementById('resetParamsBtn');
        const requestPathInput = document.getElementById('requestPathInput');
        const paramsTableBody = document.getElementById('paramsTableBody');
        const previewUrl = document.getElementById('previewUrl');
        const liveResponseToggle = document.getElementById('liveResponseToggle');
        const liveResponseToggleLabel = document.getElementById('liveResponseToggleLabel');
        const liveResponseChevron = document.getElementById('liveResponseChevron');
        const liveResponseBody = document.getElementById('liveResponseBody');

        function notify(type, message) {
            if (window.toastr && typeof window.toastr[type] === 'function') {
                window.toastr[type](message);
                return;
            }

            if (type === 'error') {
                window.alert(message);
                return;
            }

            console.log(message);
        }

        function getEndpointByKey(key) {
            return endpoints.find(endpoint => endpoint.key === key) || null;
        }

        function getActiveEndpoint() {
            return getEndpointByKey(state.activeKey);
        }

        function getDraft(key) {
            if (!state.drafts[key]) {
                const endpoint = getEndpointByKey(key);
                state.drafts[key] = {
                    path: endpoint?.sample_path || '',
                    params: {...(endpoint?.sample_query || {})},
                };
            }

            return state.drafts[key];
        }

        function setActiveEndpoint(key) {
            state.activeKey = key;
            renderEndpointList();
            renderRequestBuilder();
        }

        function applyFilters() {
            const term = endpointSearch.value.trim().toLowerCase();
            const support = supportFilter.value;

            state.filteredEndpoints = endpoints.filter(endpoint => {
                const haystack = `${endpoint.method} ${endpoint.uri} ${endpoint.category} ${endpoint.sample_path} ${endpoint.sample_query_string}`.toLowerCase();
                const matchesSearch = !term || haystack.includes(term);
                const matchesSupport = !support
                    || (support === 'supported' && endpoint.supported)
                    || (support === 'manual' && !endpoint.supported)
                    || (support === 'auth' && endpoint.requires_auth);

                return matchesSearch && matchesSupport;
            });

            if (!state.filteredEndpoints.some(endpoint => endpoint.key === state.activeKey)) {
                state.activeKey = state.filteredEndpoints[0]?.key || state.activeKey;
            }

            renderEndpointList();
            renderRequestBuilder();
        }

        function renderEndpointList() {
            sidebarVisibleCount.textContent = state.filteredEndpoints.length;

            endpointList.innerHTML = state.filteredEndpoints.map(endpoint => {
                const isActive = endpoint.key === state.activeKey;
                const isQueued = state.batchKeys.has(endpoint.key);
                const supportTone = endpoint.supported
                    ? 'text-emerald-600 bg-emerald-100 dark:text-emerald-300 dark:bg-emerald-500/10'
                    : 'text-rose-600 bg-rose-100 dark:text-rose-300 dark:bg-rose-500/10';
                const methodTone = endpoint.method === 'GET'
                    ? 'text-emerald-700 bg-emerald-100 dark:text-emerald-300 dark:bg-emerald-500/10'
                    : 'text-sky-700 bg-sky-100 dark:text-sky-300 dark:bg-sky-500/10';

                return `
                    <button type="button"
                        class="endpoint-item mb-2 w-full rounded-2xl border px-4 py-4 text-left transition ${
                            isActive
                                ? 'border-cyan-300 bg-cyan-50 shadow-sm dark:border-cyan-500/30 dark:bg-cyan-500/10'
                                : 'border-slate-200 bg-white hover:bg-slate-50 dark:border-white/10 dark:bg-slate-950 dark:hover:bg-white/5'
                        }"
                        data-key="${escapeHtml(endpoint.key)}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full px-2.5 py-1 text-[10px] font-black ${methodTone}">${escapeHtml(endpoint.method)}</span>
                                    <span class="rounded-full px-2.5 py-1 text-[10px] font-bold ${supportTone}">${endpoint.supported ? 'Runnable' : 'Manual'}</span>
                                    ${endpoint.requires_auth ? '<span class="rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-bold text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">Auth</span>' : ''}
                                    ${isQueued ? '<span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold text-slate-700 dark:bg-white/10 dark:text-slate-300">Queued</span>' : ''}
                                </div>
                                <p class="mt-3 truncate text-sm font-black text-slate-900 dark:text-white">${escapeHtml(endpoint.uri)}</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">${escapeHtml(endpoint.category)}</p>
                            </div>
                        </div>
                    </button>
                `;
            }).join('') || `
                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-sm text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-slate-400">
                    No endpoints match the current filters.
                </div>
            `;

            endpointList.querySelectorAll('.endpoint-item').forEach(button => {
                button.addEventListener('click', () => setActiveEndpoint(button.dataset.key));
            });

            updateBatchSummary();
        }

        function renderRequestBuilder() {
            const endpoint = getActiveEndpoint();
            if (!endpoint) {
                return;
            }

            const draft = getDraft(endpoint.key);
            document.getElementById('activeMethodBadge').textContent = endpoint.method;
            document.getElementById('activeCategoryBadge').textContent = endpoint.category;
            document.getElementById('activeStatusBadge').textContent = endpoint.supported ? 'Runnable' : 'Manual only';
            document.getElementById('activeUri').textContent = endpoint.uri;
            document.getElementById('activeHelpText').textContent = endpoint.supported
                ? 'Edit the sample request below, then run this endpoint by itself or add it to the batch queue.'
                : (endpoint.unsupported_reason || 'This endpoint should be tested manually.');

            requestPathInput.value = draft.path;
            runActiveBtn.disabled = state.isRunning || !endpoint.supported;
            queueActiveBtn.disabled = state.isRunning || !endpoint.supported;
            queueActiveBtn.innerHTML = state.batchKeys.has(endpoint.key)
                ? '<i class="fas fa-check mr-2"></i> Added To Batch'
                : '<i class="fas fa-plus mr-2"></i> Add To Batch';

            renderParamsTable();
            updatePreview();
        }

        function renderParamsTable() {
            const endpoint = getActiveEndpoint();
            if (!endpoint) {
                return;
            }

            const draft = getDraft(endpoint.key);
            const entries = Object.entries(draft.params);

            paramsTableBody.innerHTML = (entries.length ? entries : [['', '']]).map(([key, value], index) => `
                <tr>
                    <td class="px-4 py-3">
                        <input type="text" class="param-key-input w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 focus:border-cyan-400 focus:outline-none focus:ring-4 focus:ring-cyan-100 dark:border-white/10 dark:bg-slate-900 dark:text-white dark:focus:border-cyan-500 dark:focus:ring-cyan-500/10" data-index="${index}" value="${escapeHtml(key)}" placeholder="parameter_name">
                    </td>
                    <td class="px-4 py-3">
                        <input type="text" class="param-value-input w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 focus:border-cyan-400 focus:outline-none focus:ring-4 focus:ring-cyan-100 dark:border-white/10 dark:bg-slate-900 dark:text-white dark:focus:border-cyan-500 dark:focus:ring-cyan-500/10" data-index="${index}" value="${escapeHtml(String(value ?? ''))}" placeholder="value">
                    </td>
                    <td class="px-4 py-3 text-right">
                        <button type="button" class="remove-param-btn rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50 dark:border-white/10 dark:text-slate-200 dark:hover:bg-white/5" data-index="${index}">
                            Remove
                        </button>
                    </td>
                </tr>
            `).join('');

            bindParamInputs();
        }

        function bindParamInputs() {
            paramsTableBody.querySelectorAll('.param-key-input, .param-value-input').forEach(input => {
                input.addEventListener('input', syncParamsFromTable);
            });

            paramsTableBody.querySelectorAll('.remove-param-btn').forEach(button => {
                button.addEventListener('click', () => {
                    const endpoint = getActiveEndpoint();
                    const draft = getDraft(endpoint.key);
                    const entries = Object.entries(draft.params);
                    entries.splice(Number(button.dataset.index), 1);
                    draft.params = Object.fromEntries(entries);
                    renderParamsTable();
                    updatePreview();
                });
            });
        }

        function syncParamsFromTable() {
            const endpoint = getActiveEndpoint();
            const draft = getDraft(endpoint.key);
            const rows = Array.from(paramsTableBody.querySelectorAll('tr'));
            const nextParams = {};

            rows.forEach(row => {
                const key = row.querySelector('.param-key-input')?.value.trim();
                const value = row.querySelector('.param-value-input')?.value ?? '';
                if (key) {
                    nextParams[key] = value.trim();
                }
            });

            draft.params = nextParams;
            updatePreview();
        }

        function addEmptyParam() {
            const endpoint = getActiveEndpoint();
            const draft = getDraft(endpoint.key);
            const entries = Object.entries(draft.params);
            entries.push(['', '']);
            draft.params = Object.fromEntries(entries);
            renderParamsTable();
        }

        function resetDraft() {
            const endpoint = getActiveEndpoint();
            state.drafts[endpoint.key] = {
                path: endpoint.sample_path,
                params: {...endpoint.sample_query},
            };
            renderRequestBuilder();
        }

        function updatePreview() {
            const endpoint = getActiveEndpoint();
            const draft = getDraft(endpoint.key);
            const query = new URLSearchParams();

            Object.entries(draft.params).forEach(([key, value]) => {
                if (key && String(value).trim() !== '') {
                    query.append(key, String(value));
                }
            });

            const queryString = query.toString();
            previewUrl.textContent = `/${draft.path}${queryString ? `?${queryString}` : ''}`;
        }

        function getOverrideForEndpoint(key) {
            const draft = getDraft(key);
            const params = {};

            Object.entries(draft.params).forEach(([paramKey, value]) => {
                if (paramKey && String(value).trim() !== '') {
                    params[paramKey] = String(value).trim();
                }
            });

            return {
                path: draft.path.trim(),
                params,
            };
        }

        function buildBatchResponse(results = []) {
            return results.map((result, index) => ({
                '#': index + 1,
                endpoint: `${result.method} ${result.tested_uri || result.uri}`,
                status: result.status,
                ok: result.ok,
                duration_ms: result.duration_ms,
                category: result.category,
                preview: result.response_preview,
            }));
        }

        function renderLiveResponseVisibility() {
            liveResponseBody.classList.toggle('hidden', state.responseCollapsed);
            liveResponseToggle.setAttribute('aria-expanded', String(!state.responseCollapsed));
            liveResponseToggleLabel.textContent = state.responseCollapsed ? 'Expand' : 'Collapse';
            liveResponseChevron.classList.toggle('rotate-180', state.responseCollapsed);
        }

        async function runTests(endpointKeys = [], trigger = null, options = {}) {
            const overrides = {};
            endpointKeys.forEach(key => {
                overrides[key] = getOverrideForEndpoint(key);
            });

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
                        target_user_id: companySelect.value,
                        mode: modeSelect.value,
                        endpoints: options.runAll ? [] : endpointKeys,
                        overrides,
                    }),
                });

                const payload = await response.json();
                if (!response.ok || !payload.success) {
                    notify('error', payload.message || 'API test run failed.');
                    return;
                }

                renderResponse(payload);
                prependReportHistory(payload.report, payload.summary);
                notify('success', payload.warning || 'API tests completed.');
            } catch (error) {
                notify('error', 'Unable to run API tests right now.');
            } finally {
                setRunningState(false, trigger);
            }
        }

        function renderResponse(payload) {
            const primaryResult = payload.results[0];
            const isBatch = payload.results.length > 1;
            const statusBadge = document.getElementById('responseStatusBadge');
            const timeBadge = document.getElementById('responseTimeBadge');
            const responseMeta = document.getElementById('responseMeta');
            const responseOutput = document.getElementById('responseOutput');
            const responseEmptyState = document.getElementById('responseEmptyState');

            state.responseCollapsed = false;
            renderLiveResponseVisibility();

            responseMeta.classList.remove('hidden');
            responseOutput.classList.remove('hidden');
            responseEmptyState.classList.add('hidden');
            statusBadge.classList.remove('hidden');
            timeBadge.classList.remove('hidden');

            document.getElementById('responseRequestLine').textContent = isBatch
                ? `${payload.results.length} endpoints executed`
                : `${primaryResult.method} ${primaryResult.tested_uri}`;
            document.getElementById('responseReportLine').textContent = `${payload.report.name} | ${payload.report.company_name} | ${payload.report.mode}`;

            statusBadge.textContent = isBatch
                ? `${payload.summary.passed} passed, ${payload.summary.failed} failed, ${payload.summary.skipped ?? 0} skipped`
                : `${String(primaryResult.status).toUpperCase()}`;
            statusBadge.className = `rounded-full px-3 py-1 text-[11px] font-bold ${
                (payload.summary.failed ?? 0) === 0
                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'
                    : 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300'
            }`;

            timeBadge.textContent = isBatch
                ? `avg ${payload.summary.average_duration_ms} ms`
                : `${primaryResult.duration_ms} ms`;

            responseOutput.textContent = isBatch
                ? JSON.stringify(buildBatchResponse(payload.results), null, 2)
                : primaryResult.response_preview || '';
        }

        function prependReportHistory(report, summary) {
            const history = document.getElementById('reportHistory');
            const empty = document.getElementById('reportHistoryEmpty');
            if (empty) {
                empty.remove();
            }

            const article = document.createElement('article');
            article.className = 'rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-white/10 dark:bg-white/5';
            article.innerHTML = `
                <p class="text-sm font-black text-slate-900 dark:text-white">${escapeHtml(report.name)}</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">${escapeHtml(report.company_name)} - ${escapeHtml(report.mode)} - ${summary.passed ?? 0} passed, ${summary.failed ?? 0} failed, ${summary.skipped ?? 0} skipped</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="${report.download_urls?.all_json || '#'}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50 dark:border-white/10 dark:bg-slate-950 dark:text-slate-200 dark:hover:bg-white/5">
                        <i class="fas fa-file-code mr-2"></i> All JSON
                    </a>
                    <a href="${report.download_urls?.all_pdf || '#'}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50 dark:border-white/10 dark:bg-slate-950 dark:text-slate-200 dark:hover:bg-white/5">
                        <i class="fas fa-file-pdf mr-2"></i> All PDF
                    </a>
                    <a href="${report.download_urls?.passed_json || '#'}" class="inline-flex items-center rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 transition hover:bg-emerald-100 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300 dark:hover:bg-emerald-500/20">
                        <i class="fas fa-circle-check mr-2"></i> Success JSON
                    </a>
                    <a href="${report.download_urls?.passed_pdf || '#'}" class="inline-flex items-center rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 transition hover:bg-emerald-100 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300 dark:hover:bg-emerald-500/20">
                        <i class="fas fa-file-pdf mr-2"></i> Success PDF
                    </a>
                    <a href="${report.download_urls?.failed_json || '#'}" class="inline-flex items-center rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700 transition hover:bg-rose-100 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300 dark:hover:bg-rose-500/20">
                        <i class="fas fa-triangle-exclamation mr-2"></i> Failure JSON
                    </a>
                    <a href="${report.download_urls?.failed_pdf || '#'}" class="inline-flex items-center rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700 transition hover:bg-rose-100 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300 dark:hover:bg-rose-500/20">
                        <i class="fas fa-file-pdf mr-2"></i> Failure PDF
                    </a>
                </div>
            `;

            history.prepend(article);
        }

        function updateBatchSummary() {
            const batchCount = state.batchKeys.size;
            document.getElementById('batchSummary').textContent = `${batchCount} endpoint${batchCount === 1 ? '' : 's'} selected`;
            runSelectedBtn.disabled = state.isRunning || batchCount === 0;
            runBatchBtn.disabled = state.isRunning || batchCount === 0;
        }

        function toggleActiveInBatch() {
            const endpoint = getActiveEndpoint();
            if (!endpoint || !endpoint.supported) {
                return;
            }

            if (state.batchKeys.has(endpoint.key)) {
                state.batchKeys.delete(endpoint.key);
            } else {
                state.batchKeys.add(endpoint.key);
            }

            renderEndpointList();
            renderRequestBuilder();
        }

        function clearBatch() {
            state.batchKeys.clear();
            renderEndpointList();
            renderRequestBuilder();
        }

        function setRunningState(isRunning, trigger = null) {
            state.isRunning = isRunning;

            runAllBtn.disabled = isRunning;
            clearBatchBtn.disabled = isRunning;
            addParamBtn.disabled = isRunning;
            resetParamsBtn.disabled = isRunning;

            companySelect.disabled = isRunning;
            modeSelect.disabled = isRunning;
            requestPathInput.disabled = isRunning;
            paramsTableBody.querySelectorAll('input,button').forEach(element => {
                element.disabled = isRunning;
            });

            if (isRunning && trigger) {
                trigger.dataset.originalText = trigger.innerHTML;
                trigger.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Running';
            } else if (!isRunning && trigger?.dataset.originalText) {
                trigger.innerHTML = trigger.dataset.originalText;
                delete trigger.dataset.originalText;
            }

            updateBatchSummary();
            renderEndpointList();
            renderRequestBuilder();
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.innerText = text;
            return div.innerHTML;
        }

        endpointSearch.addEventListener('input', applyFilters);
        supportFilter.addEventListener('change', applyFilters);
        document.getElementById('resetFiltersBtn').addEventListener('click', () => {
            endpointSearch.value = '';
            supportFilter.value = '';
            applyFilters();
        });

        companySelect.addEventListener('change', () => {
            const url = new URL(INDEX_URL, window.location.origin);
            url.searchParams.set('company', companySelect.value);
            window.location.href = url.toString();
        });

        requestPathInput.addEventListener('input', () => {
            const endpoint = getActiveEndpoint();
            getDraft(endpoint.key).path = requestPathInput.value.trim();
            updatePreview();
        });

        addParamBtn.addEventListener('click', addEmptyParam);
        resetParamsBtn.addEventListener('click', resetDraft);
        liveResponseToggle.addEventListener('click', () => {
            state.responseCollapsed = !state.responseCollapsed;
            renderLiveResponseVisibility();
        });
        queueActiveBtn.addEventListener('click', toggleActiveInBatch);
        clearBatchBtn.addEventListener('click', clearBatch);
        runActiveBtn.addEventListener('click', () => {
            const endpoint = getActiveEndpoint();
            if (!endpoint?.supported) {
                return;
            }
            runTests([endpoint.key], runActiveBtn);
        });
        runBatchBtn.addEventListener('click', () => runTests(Array.from(state.batchKeys), runBatchBtn));
        runSelectedBtn.addEventListener('click', () => runTests(Array.from(state.batchKeys), runSelectedBtn));
        runAllBtn.addEventListener('click', () => runTests([], runAllBtn, {runAll: true}));

        applyFilters();
        renderLiveResponseVisibility();
    </script>
@endpush
