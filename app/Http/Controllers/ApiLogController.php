<?php

namespace App\Http\Controllers;

use App\Models\ApiLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiLogController extends Controller
{
    /**
     * Display a listing of the API logs.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        if ($request->ajax()) {
            $query = ApiLog::with('user');

            if (!$user->is_admin) {
                $query->where('user_id', $user->id);
            }

            // Search logic
            if ($request->has('search') && !empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where(function($q) use ($search) {
                    $q->where('endpoint', 'like', "%{$search}%")
                      ->orWhere('method', 'like', "%{$search}%")
                      ->orWhere('ip_address', 'like', "%{$search}%")
                      ->orWhere('status_code', 'like', "%{$search}%")
                      ->orWhereHas('user', function($qu) use ($search) {
                          $qu->where('name', 'like', "%{$search}%");
                      });
                });
            }

            $total = $query->count();
            
            $limit = $request->length ?? 50;
            $start = $request->start ?? 0;
            
            // Ordering
            $orderColumnIndex = $request->order[0]['column'] ?? null;
            $orderDir = $request->order[0]['dir'] ?? 'desc';
            
            // Map column index to database column
            $columns = $user->is_admin 
                ? ['user_id', 'endpoint', 'method', 'status_code', 'credit_deducted', 'ip_address', 'created_at'] 
                : ['endpoint', 'method', 'status_code', 'credit_deducted', 'ip_address', 'created_at'];
            
            if ($orderColumnIndex !== null && isset($columns[$orderColumnIndex])) {
                $query->orderBy($columns[$orderColumnIndex], $orderDir);
            } else {
                $query->latest();
            }

            $logs = $query->skip($start)->take($limit)->get();

            $data = $logs->map(function($log) {
                $statusClass = $log->status_code == 200 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                $creditClass = $log->credit_deducted ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-800';
                $creditText = $log->credit_deducted ? 'Debited' : 'No Charge';

                return [
                    'user_name' => $log->user ? $log->user->name : 'N/A',
                    'endpoint' => $log->endpoint,
                    'method' => $log->method,
                    'status_badge' => '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . $statusClass . '">' . $log->status_code . '</span>',
                    'credit_badge' => '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . $creditClass . '">' . $creditText . '</span>',
                    'ip_address' => $log->ip_address,
                    'time' => $log->created_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'draw' => intval($request->draw),
                'recordsTotal' => ApiLog::count(),
                'recordsFiltered' => $total,
                'data' => $data
            ]);
        }

        return view('api-logs.index');
    }
}
