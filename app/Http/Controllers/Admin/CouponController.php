<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Plan;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index()
    {
        $coupons = Coupon::with('plan')->latest()->paginate(20);
        return view('admin.coupons.index', compact('coupons'));
    }

    public function create()
    {
        $plans = Plan::where('status', 1)->get();
        return view('admin.coupons.create', compact('plans'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|unique:coupons,code',
            'discount_type' => 'required|in:fixed,percentage',
            'discount_value' => 'required|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'max_redemptions' => 'nullable|integer|min:1',
            'apply_to_cycles' => 'required|integer|min:1',
            'expires_at' => 'nullable|date|after:today',
            'plan_id' => 'nullable|exists:plans,id',
        ]);

        Coupon::create($request->all());

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon created successfully.');
    }

    public function edit(Coupon $coupon)
    {
        $plans = Plan::where('status', 1)->get();
        return view('admin.coupons.edit', compact('coupon', 'plans'));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $request->validate([
            'code' => 'required|unique:coupons,code,' . $coupon->id,
            'discount_type' => 'required|in:fixed,percentage',
            'discount_value' => 'required|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'max_redemptions' => 'nullable|integer|min:1',
            'apply_to_cycles' => 'required|integer|min:1',
            'expires_at' => 'nullable|date|after:today',
            'plan_id' => 'nullable|exists:plans,id',
        ]);

        $coupon->update($request->all());

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon updated successfully.');
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();
        return redirect()->route('admin.coupons.index')->with('success', 'Coupon deleted successfully.');
    }

    public function toggleStatus(Coupon $coupon)
    {
        $coupon->update(['status' => !$coupon->status]);
        return back()->with('success', 'Status updated successfully.');
    }
}

