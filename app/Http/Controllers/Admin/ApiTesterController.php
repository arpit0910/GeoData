<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiTestReport;
use App\Models\User;
use App\Services\ApiTestReportExporter;
use App\Services\ApiTestRunnerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiTesterController extends Controller
{
    public function __construct(
        protected ApiTestRunnerService $runner,
        protected ApiTestReportExporter $exporter
    ) {
    }

    public function index(Request $request)
    {
        $adminUser = Auth::user();
        $companies = User::query()
            ->where('is_admin', false)
            ->where('account_type', 'client')
            ->orderByRaw('COALESCE(company_name, name)')
            ->get(['id', 'name', 'email', 'company_name', 'client_key', 'timezone']);
        $selectedCompany = $companies->firstWhere('id', (int) $request->integer('company'))
            ?? $companies->first();
        $endpoints = $this->runner->buildEndpointCatalog($selectedCompany);
        $reports = ApiTestReport::query()
            ->with(['generatedBy:id,name', 'targetUser:id,name,email,company_name'])
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.api-tester.index', compact('adminUser', 'companies', 'selectedCompany', 'endpoints', 'reports'));
    }

    public function run(Request $request)
    {
        $validated = $request->validate([
            'target_user_id' => ['required', 'integer', 'exists:users,id'],
            'mode' => ['required', 'in:demo,production'],
            'endpoints' => ['nullable', 'array'],
            'endpoints.*' => ['string'],
            'overrides' => ['nullable', 'array'],
        ]);

        $adminUser = Auth::user();

        if (! $adminUser || ! $adminUser->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'API Tester can only be run as an authenticated admin user.',
            ], 403);
        }

        $targetUser = User::query()->findOrFail($validated['target_user_id']);
        try {
            $report = $this->runner->runAndStore(
                $adminUser,
                $targetUser,
                $validated['endpoints'] ?? [],
                $validated['mode'],
                $validated['overrides'] ?? []
            )->load(['generatedBy:id,name', 'targetUser:id,name,email,company_name']);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'summary' => $report->summary,
            'results' => $report->results,
            'report' => [
                'id' => $report->id,
                'name' => $report->report_name,
                'mode' => $report->mode,
                'status' => $report->status,
                'company_name' => $report->targetUser->company_name ?: $report->targetUser->name,
                'company_email' => $report->targetUser->email,
                'download_urls' => [
                    'all_json' => route('admin.api-tester.reports.download', ['reportId' => $report->id, 'format' => 'json', 'result_set' => 'all'], false),
                    'all_pdf' => route('admin.api-tester.reports.download', ['reportId' => $report->id, 'format' => 'pdf', 'result_set' => 'all'], false),
                    'passed_json' => route('admin.api-tester.reports.download', ['reportId' => $report->id, 'format' => 'json', 'result_set' => 'passed'], false),
                    'passed_pdf' => route('admin.api-tester.reports.download', ['reportId' => $report->id, 'format' => 'pdf', 'result_set' => 'passed'], false),
                    'failed_json' => route('admin.api-tester.reports.download', ['reportId' => $report->id, 'format' => 'json', 'result_set' => 'failed'], false),
                    'failed_pdf' => route('admin.api-tester.reports.download', ['reportId' => $report->id, 'format' => 'pdf', 'result_set' => 'failed'], false),
                ],
                'created_at' => optional($report->created_at)->format('d M Y, h:i A'),
            ],
            'warning' => $validated['mode'] === 'demo'
                ? 'Demo mode bypasses subscription and credit checks while keeping the selected company context.'
                : 'Production mode runs in rollback-safe mode: all API types execute, and database changes are rolled back after each request.',
        ]);
    }

    public function download(Request $request, int $reportId)
    {
        $format = $request->query('format', 'json');
        $resultSet = $request->query('result_set', 'all');
        $report = ApiTestReport::query()
            ->with(['generatedBy:id,name,email', 'targetUser:id,name,email,company_name,client_key'])
            ->find($reportId);

        if (! $report) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'API test report not found.',
                ], 404);
            }

            return redirect()->route('admin.api-tester.index')->with('error', 'API test report not found.');
        }

        if (! in_array($format, ['json', 'pdf'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Unsupported report format.',
            ], 422);
        }

        if (! in_array($resultSet, ['all', 'passed', 'failed', 'skipped'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Unsupported report result set.',
            ], 422);
        }

        if ($format === 'pdf') {
            $pdf = $this->exporter->buildPdf($report, $resultSet);

            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="api-test-report-' . $report->id . '-' . $resultSet . '.pdf"',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
            ]);
        }

        $json = json_encode($this->exporter->buildPayload($report, $resultSet), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return response($json, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="api-test-report-' . $report->id . '-' . $resultSet . '.json"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
