<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\TransactionHistory;
use App\Services\ApiTestRunnerService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $query = User::where('is_admin', 0);

            if ($request->has('search') && !empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('company_name', 'like', "%{$search}%");
                });
            }

            $total = $query->count();
            
            $limit = $request->length ?? 10;
            $start = $request->start ?? 0;
            
            $users = $query->skip($start)->take($limit)->get();

            return response()->json([
                'draw' => $request->draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'data' => $users
            ]);
        }

        return view('user.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('user.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'company_name' => 'nullable|string',
            'company_website' => 'nullable|url',
            'gst_number' => 'nullable|string',
            'status' => 'required|in:1,0',
        ]);

        $user = new User();
        $user->fill($validated);
        $user->password = Hash::make($validated['password']);
        $user->is_admin = 0;
        $user->account_type = 'client';
        $user->save();

        if ($request->wantsJson()) {
            return sendResponse($user, 'User created successfully');
        }

        return redirect()->route('user.list')->with('success', 'User created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        return view('user.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        $plans = Plan::query()
            ->where('status', 1)
            ->orderBy('amount')
            ->orderBy('name')
            ->get();

        $activeSubscription = $user->subscriptions()
            ->with('plan')
            ->where('status', 'active')
            ->latest()
            ->first();

        return view('user.edit', compact('user', 'plans', 'activeSubscription'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|min:8|confirmed',
            'company_name' => 'nullable|string',
            'company_website' => 'nullable|url',
            'gst_number' => 'nullable|string',
            'status' => 'required|in:1,0',
            'plan_id' => 'nullable|exists:plans,id',
        ]);

        $selectedPlan = null;
        if ($request->filled('plan_id')) {
            $selectedPlan = Plan::findOrFail($request->plan_id);
        }

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        unset($validated['plan_id']);

        $user->fill($validated);
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        if ($user->account_type === 'client') {
            $this->syncManualPlanAssignment($user, $selectedPlan);
        }

        if ($request->wantsJson()) {
            return sendResponse($user, 'User updated successfully');
        }

        return redirect()->route('user.list')->with('success', 'User updated successfully');
    }

    private function syncManualPlanAssignment(User $user, ?Plan $plan): void
    {
        DB::transaction(function () use ($user, $plan) {
            Subscription::where('user_id', $user->id)
                ->where('status', 'active')
                ->update(['status' => 'expired']);

            if (!$plan) {
                $user->forceFill([
                    'plan_id' => null,
                    'available_credits' => 0,
                ])->save();

                return;
            }

            $expiresAt = now();
            if ($plan->billing_cycle === 'monthly') {
                $expiresAt = $expiresAt->copy()->addMonth();
            } elseif ($plan->billing_cycle === 'yearly') {
                $expiresAt = $expiresAt->copy()->addYear();
            } else {
                $expiresAt = $expiresAt->copy()->addYears(100);
            }

            $creditsToAdd = $plan->api_hits_limit ?? 999999999;
            $manualReference = 'admin-manual-' . $user->id . '-' . Str::lower(Str::random(10));

            $subscription = Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'razorpay_order_id' => $manualReference,
                'amount_paid' => 0,
                'discount_amount' => 0,
                'remaining_discount_cycles' => 0,
                'status' => 'active',
                'expires_at' => $expiresAt,
                'total_credits' => $creditsToAdd,
                'used_credits' => 0,
                'available_credits' => $creditsToAdd,
                'last_credit_refresh' => now(),
            ]);

            TransactionHistory::create([
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'plan_id' => $plan->id,
                'razorpay_order_id' => $manualReference,
                'amount' => 0,
                'discount_amount' => 0,
                'plan_name' => $plan->name,
                'billing_cycle' => $plan->billing_cycle,
                'status' => 'success',
                'type' => 'admin_assignment',
                'credits' => $creditsToAdd,
            ]);

            $user->forceFill([
                'plan_id' => $plan->id,
                'available_credits' => $creditsToAdd,
                'status' => 1,
            ])->save();
        });
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, User $user)
    {
        $user->delete();

        if ($request->wantsJson()) {
            return sendResponse(null, 'User deleted successfully');
        }

        return redirect()->route('user.list')->with('success', 'User deleted successfully');
    }

    public function toggleStatus(Request $request, User $user)
    {
        $user->status = $user->status ? 0 : 1;
        $user->save();

        return sendResponse(['status' => $user->status], 'Status updated successfully');
    }

    public function generateApiReport(Request $request, User $user, ApiTestRunnerService $runner)
    {
        $validated = $request->validate([
            'mode' => 'nullable|in:demo,production',
            'download_format' => 'nullable|in:json,pdf',
        ]);

        $adminUser = Auth::user();

        if (! $adminUser || ! $adminUser->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can generate API reports.',
            ], 403);
        }

        $mode = $validated['mode'] ?? 'production';
        $downloadFormat = $validated['download_format'] ?? 'json';

        try {
            $report = $runner->runAndStore($adminUser, $user, [], $mode);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $mode === 'production'
                ? 'API report generated successfully in production rollback-safe mode.'
                : 'API report generated successfully.',
            'report' => [
                'id' => $report->id,
                'name' => $report->report_name,
                'mode' => $report->mode,
                'download_urls' => [
                    'all_json' => route('admin.api-tester.reports.download', ['reportId' => $report->id, 'format' => 'json', 'result_set' => 'all'], false),
                    'all_pdf' => route('admin.api-tester.reports.download', ['reportId' => $report->id, 'format' => 'pdf', 'result_set' => 'all'], false),
                    'passed_json' => route('admin.api-tester.reports.download', ['reportId' => $report->id, 'format' => 'json', 'result_set' => 'passed'], false),
                    'passed_pdf' => route('admin.api-tester.reports.download', ['reportId' => $report->id, 'format' => 'pdf', 'result_set' => 'passed'], false),
                    'failed_json' => route('admin.api-tester.reports.download', ['reportId' => $report->id, 'format' => 'json', 'result_set' => 'failed'], false),
                    'failed_pdf' => route('admin.api-tester.reports.download', ['reportId' => $report->id, 'format' => 'pdf', 'result_set' => 'failed'], false),
                ],
                'preferred_download_url' => route('admin.api-tester.reports.download', ['reportId' => $report->id, 'format' => $downloadFormat, 'result_set' => 'all'], false),
                'summary' => $report->summary,
            ],
        ]);
    }
}
