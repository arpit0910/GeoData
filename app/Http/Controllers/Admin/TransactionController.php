<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TransactionHistory;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson() || $request->ajax()) {
            $query = TransactionHistory::with(['user', 'plan', 'coupon']);

            if ($request->has('search') && !empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where(function($q) use ($search) {
                    $q->where('plan_name', 'like', "%{$search}%")
                      ->orWhere('coupon_code', 'like', "%{$search}%")
                      ->orWhere('razorpay_payment_id', 'like', "%{$search}%")
                      ->orWhereHas('user', function($qu) use ($search) {
                          $qu->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                      });
                });
            }

            $total = $query->count();
            
            $limit = $request->length ?? 50;
            $start = $request->start ?? 0;
            
            $transactions = $query->latest()->skip($start)->take($limit)->get();

            return response()->json([
                'draw' => intval($request->draw),
                'recordsTotal' => TransactionHistory::count(),
                'recordsFiltered' => $total,
                'data' => $transactions
            ]);
        }

        $transactions = TransactionHistory::with(['user', 'plan', 'coupon'])
            ->latest()
            ->paginate(50);
            
        return view('admin.transactions.index', compact('transactions'));
    }

    public function show(TransactionHistory $transaction)
    {
        return view('admin.transactions.show', compact('transaction'));
    }
}
