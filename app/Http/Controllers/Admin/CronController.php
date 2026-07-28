<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CronLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class CronController extends Controller
{
    private function cronDefinitions(): array
    {
        return [
            [
                'title' => 'currency:fetch-rates',
                'command' => 'currency:fetch-rates',
                'args' => [],
                'description' => 'Fetches latest USD/INR currency exchange rates via Frankfurt API.',
                'schedule' => 'Daily at 20:30',
                'timezone' => 'Asia/Kolkata',
                'overlap' => false,
            ],
            [
                'title' => 'equities:sync',
                'command' => 'equities:sync',
                'args' => [],
                'description' => 'Syncs equity OHLCV price data from NSE/BSE.',
                'schedule' => 'Daily at 19:00',
                'timezone' => 'Asia/Kolkata',
                'overlap' => true,
            ],
            [
                'title' => 'indices:sync',
                'command' => 'indices:sync',
                'args' => [],
                'description' => 'Syncs market index price data.',
                'schedule' => 'Daily at 19:15',
                'timezone' => 'Asia/Kolkata',
                'overlap' => true,
            ],
            [
                'title' => 'sync:mf-daily (21:30)',
                'command' => 'sync:mf-daily',
                'args' => ['--force' => true],
                'description' => 'Syncs mutual fund NAVs for the first publish window.',
                'schedule' => 'Daily at 21:30',
                'timezone' => 'Asia/Kolkata',
                'overlap' => false,
            ],
            [
                'title' => 'sync:mf-daily (23:15)',
                'command' => 'sync:mf-daily',
                'args' => ['--force' => true],
                'description' => 'Re-runs MF NAV sync to pick up late corrections.',
                'schedule' => 'Daily at 23:15',
                'timezone' => 'Asia/Kolkata',
                'overlap' => false,
            ],
            [
                'title' => 'mf:sync-and-calculate (23:30)',
                'command' => 'mf:sync-and-calculate',
                'args' => [],
                'description' => 'Orchestrator that syncs MF NAVs then computes performance returns.',
                'schedule' => 'Daily at 23:30',
                'timezone' => 'Asia/Kolkata',
                'overlap' => false,
            ],
            [
                'title' => 'equities:sync-metadata',
                'command' => 'equities:sync-metadata',
                'args' => [],
                'description' => 'Syncs equity metadata from AMFI.',
                'schedule' => 'Daily at 08:00',
                'timezone' => 'Asia/Kolkata',
                'overlap' => false,
            ],
            [
                'title' => 'equities:sync-fundamentals',
                'command' => 'equities:sync-fundamentals',
                'args' => [],
                'description' => 'Syncs daily equity fundamentals from the API.',
                'schedule' => 'Daily at 20:00',
                'timezone' => 'Asia/Kolkata',
                'overlap' => false,
            ],
            [
                'title' => 'market:fetch-live',
                'command' => 'market:fetch-live',
                'args' => [],
                'description' => 'Fetches live market data every minute.',
                'schedule' => 'Every minute',
                'timezone' => 'Asia/Kolkata',
                'overlap' => false,
            ],
            [
                'title' => 'market:repair-performance-values',
                'command' => 'market:repair-performance-values',
                'args' => [],
                'description' => 'One-time repair for equity NSE/BSE performance values and compounded index historical returns.',
                'schedule' => 'Manual one-time maintenance run',
                'timezone' => 'Asia/Kolkata',
                'overlap' => false,
            ],
            [
                'title' => 'indices:sync (refresh holdings)',
                'command' => 'indices:sync',
                'args' => ['--refresh-overview' => true],
                'description' => 'One-time maintenance refresh for saved index holdings and overview payloads.',
                'schedule' => 'Manual one-time maintenance run',
                'timezone' => 'Asia/Kolkata',
                'overlap' => false,
            ],
        ];
    }

    public function index()
    {
        $crons = $this->cronDefinitions();

        $lastRuns = CronLog::selectRaw('title, MAX(ran_at) as last_ran_at')
            ->groupBy('title')
            ->pluck('last_ran_at', 'title')
            ->toArray();

        $totalRuns = CronLog::selectRaw('title, COUNT(*) as total_runs')
            ->groupBy('title')
            ->pluck('total_runs', 'title')
            ->toArray();

        foreach ($crons as &$cron) {
            $cron['last_ran_at'] = $lastRuns[$cron['title']] ?? null;
            $cron['total_runs'] = $totalRuns[$cron['title']] ?? 0;
        }

        return view('admin.crons.index', compact('crons'));
    }

    public function run(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $request->validate(['title' => 'required|string']);

        $definitions = collect($this->cronDefinitions())->keyBy('title');
        $cron = $definitions->get($request->title);

        if (! $cron) {
            return response()->json(['success' => false, 'message' => 'Unknown cron job.'], 422);
        }

        $exitCode = 0;
        $output = '';
        $success = true;
        $startedAt = now('Asia/Kolkata');
        $finishedAt = null;

        try {
            $exitCode = Artisan::call($cron['command'], $cron['args']);
            $output = Artisan::output();
            $finishedAt = now('Asia/Kolkata');

            if ($exitCode !== 0) {
                $success = false;

                $response = response()->json([
                    'success' => false,
                    'message' => "Command `{$cron['command']}` exited with code {$exitCode}.",
                    'output' => $output,
                    'exit_code' => $exitCode,
                ], 500);
            } else {
                $response = response()->json([
                    'success' => true,
                    'message' => "Command `{$cron['command']}` completed successfully.",
                    'output' => $output,
                    'exit_code' => $exitCode,
                ]);
            }
        } catch (\Throwable $e) {
            $success = false;
            $exitCode = 1;
            $finishedAt = now('Asia/Kolkata');
            $output = trim($output . PHP_EOL . $e->getMessage());

            $response = response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'output' => $output,
                'exit_code' => $exitCode,
            ], 500);
        } finally {
            try {
                DB::reconnect();
                $this->logCronRun(
                    $cron['title'],
                    $success,
                    'manual',
                    $exitCode,
                    $startedAt,
                    $finishedAt ?? now('Asia/Kolkata')
                );
            } catch (\Throwable $ignored) {
            }
        }

        return $response;
    }

    public function logs(Request $request)
    {
        if ($request->ajax()) {
            $query = CronLog::query();

            if ($request->filled('title')) {
                $query->where('title', $request->title);
            }

            if ($request->has('search') && ! empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('ip', 'like', "%{$search}%")
                        ->orWhere('source', 'like', "%{$search}%");
                });
            }

            $total = $query->count();
            $limit = $request->length ?? 25;
            $start = $request->start ?? 0;
            $orderColumns = ['id', 'title', 'source', 'ip', 'status', 'ran_at', 'finished_at'];
            $orderCol = $orderColumns[$request->input('order.0.column', 0)] ?? 'id';
            $orderDir = $request->input('order.0.dir', 'desc');

            $logs = $query->orderBy($orderCol, $orderDir)
                ->skip($start)
                ->take($limit)
                ->get();

            return response()->json([
                'draw' => (int) $request->draw,
                'recordsTotal' => CronLog::count(),
                'recordsFiltered' => $total,
                'data' => $logs,
            ]);
        }

        $titles = collect($this->cronDefinitions())->pluck('title');

        return view('admin.crons.logs', compact('titles'));
    }

    private function logCronRun(
        string $title,
        bool $status = true,
        string $source = 'scheduled',
        ?int $exitCode = null,
        $startedAt = null,
        $finishedAt = null
    ): void {
        CronLog::create([
            'title' => $title,
            'ip' => request()->ip() ?? gethostbyname(gethostname()),
            'source' => $source,
            'status' => $status,
            'exit_code' => $exitCode,
            'started_at' => $startedAt,
            'finished_at' => $finishedAt,
            'ran_at' => $finishedAt ?? now('Asia/Kolkata'),
        ]);
    }
}
