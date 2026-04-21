<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CronLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class CronController extends Controller
{
    private function cronDefinitions(): array
    {
        return [
            [
                'title'       => 'currency:fetch-rates',
                'command'     => 'currency:fetch-rates',
                'args'        => [],
                'description' => 'Fetches latest USD/INR currency exchange rates via Frankfurt API.',
                'schedule'    => 'Daily at 20:30',
                'timezone'    => 'UTC',
                'overlap'     => false,
            ],
            [
                'title'       => 'equities:sync',
                'command'     => 'equities:sync',
                'args'        => [],
                'description' => 'Syncs equity OHLCV price data from NSE/BSE.',
                'schedule'    => 'Daily at 19:00',
                'timezone'    => 'Asia/Kolkata',
                'overlap'     => true,
            ],
            [
                'title'       => 'indices:sync',
                'command'     => 'indices:sync',
                'args'        => [],
                'description' => 'Syncs market index price data.',
                'schedule'    => 'Daily at 19:15',
                'timezone'    => 'Asia/Kolkata',
                'overlap'     => true,
            ],
            [
                'title'       => 'sync:mf-daily (21:30)',
                'command'     => 'sync:mf-daily',
                'args'        => ['--force' => true],
                'description' => 'Syncs mutual fund NAVs — first publish window (21:00–23:00 IST).',
                'schedule'    => 'Daily at 21:30',
                'timezone'    => 'Asia/Kolkata',
                'overlap'     => false,
            ],
            [
                'title'       => 'sync:mf-daily (23:15)',
                'command'     => 'sync:mf-daily',
                'args'        => ['--force' => true],
                'description' => 'Re-runs MF NAV sync to pick up late corrections.',
                'schedule'    => 'Daily at 23:15',
                'timezone'    => 'Asia/Kolkata',
                'overlap'     => false,
            ],
        ];
    }

    public function index()
    {
        $crons = $this->cronDefinitions();

        $lastRuns  = CronLog::selectRaw('title, MAX(ran_at) as last_ran_at')
            ->groupBy('title')->pluck('last_ran_at', 'title')->toArray();
        $totalRuns = CronLog::selectRaw('title, COUNT(*) as total_runs')
            ->groupBy('title')->pluck('total_runs', 'title')->toArray();

        foreach ($crons as &$cron) {
            $cron['last_ran_at'] = $lastRuns[$cron['title']] ?? null;
            $cron['total_runs']  = $totalRuns[$cron['title']] ?? 0;
        }

        return view('admin.crons.index', compact('crons'));
    }

    public function run(Request $request)
    {
        $request->validate(['title' => 'required|string']);

        $definitions = collect($this->cronDefinitions())->keyBy('title');
        $cron = $definitions->get($request->title);

        if (!$cron) {
            return response()->json(['success' => false, 'message' => 'Unknown cron job.'], 422);
        }

        try {
            Artisan::call($cron['command'], $cron['args']);
            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => "Command `{$cron['command']}` completed successfully.",
                'output'  => trim($output) ?: 'Command finished with no output.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function logs(Request $request)
    {
        if ($request->ajax()) {
            $query = CronLog::query();

            if ($request->filled('title')) {
                $query->where('title', $request->title);
            }

            if ($request->has('search') && !empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('ip', 'like', "%{$search}%");
                });
            }

            $total    = $query->count();
            $limit    = $request->length ?? 25;
            $start    = $request->start ?? 0;
            $orderCol = ['id', 'title', 'ip', 'ran_at'][$request->input('order.0.column', 0)] ?? 'id';
            $orderDir = $request->input('order.0.dir', 'desc');

            $logs = $query->orderBy($orderCol, $orderDir)->skip($start)->take($limit)->get();

            return response()->json([
                'draw'            => intval($request->draw),
                'recordsTotal'    => CronLog::count(),
                'recordsFiltered' => $total,
                'data'            => $logs,
            ]);
        }

        $titles = collect($this->cronDefinitions())->pluck('title');

        return view('admin.crons.logs', compact('titles'));
    }
}
