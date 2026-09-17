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
        return config('cron_jobs.jobs', []);
    }

    public function index()
    {
        $crons = $this->cronDefinitions();
        $titles = collect($crons)->pluck('title');
        $lastRuns = CronLog::query()
            ->whereIn('title', $titles)
            ->orderByDesc('ran_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy('title')
            ->map->first();
        $totalRuns = CronLog::query()
            ->whereIn('title', $titles)
            ->selectRaw('title, COUNT(*) as total_runs')
            ->groupBy('title')
            ->pluck('total_runs', 'title');

        foreach ($crons as &$cron) {
            $cron['last_run'] = $lastRuns->get($cron['title']);
            $cron['total_runs'] = (int) ($totalRuns[$cron['title']] ?? 0);
        }

        return view('admin.crons.index', compact('crons'));
    }

    public function run(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $request->validate(['title' => 'required|string']);
        $cron = collect($this->cronDefinitions())->keyBy('title')->get($request->title);

        if (! $cron) {
            return response()->json(['success' => false, 'message' => 'Unknown cron job.'], 422);
        }

        try {
            $exitCode = Artisan::call($cron['command'], $cron['args']);
            $output = Artisan::output();

            return response()->json([
                'success' => $exitCode === 0,
                'message' => $exitCode === 0
                    ? "Command `{$cron['command']}` completed successfully."
                    : "Command `{$cron['command']}` exited with code {$exitCode}.",
                'output' => $output,
                'exit_code' => $exitCode,
            ], $exitCode === 0 ? 200 : 500);
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'output' => $exception->getMessage(),
                'exit_code' => 1,
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

            if ($request->has('search') && ! empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('ip', 'like', "%{$search}%")
                        ->orWhere('source', 'like', "%{$search}%");
                });
            }

            $total = $query->count();
            $limit = (int) ($request->length ?? 25);
            $start = (int) ($request->start ?? 0);
            $orderColumns = ['id', 'title', 'source', 'ip', 'status', 'ran_at', 'finished_at'];
            $orderCol = $orderColumns[$request->input('order.0.column', 0)] ?? 'id';
            $orderDir = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';

            $logs = $query->orderBy($orderCol, $orderDir)->skip($start)->take($limit)->get();

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
}
