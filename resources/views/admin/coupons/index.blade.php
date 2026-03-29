@extends('layouts.app')

@section('header', 'Manage Coupons')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Coupon Codes</h1>
        <p class="mt-1 text-sm text-gray-600">Create and manage discounts for your plans.</p>
    </div>
    <a href="{{ route('admin.coupons.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
        <i class="fas fa-plus mr-2"></i> Create Coupon
    </a>
</div>

<div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <div class="p-6 bg-white border-b border-gray-200 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Discount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usage</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plans</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expiry</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($coupons as $coupon)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-sm font-mono bg-gray-100 rounded text-gray-800 font-bold uppercase">{{ $coupon->code }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $coupon->discount_type === 'percentage' ? $coupon->discount_value . '%' : '₹' . $coupon->discount_value }}
                        @if($coupon->max_discount)
                            <div class="text-xs text-gray-400">Max: ₹{{ $coupon->max_discount }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $coupon->used_count }} / {{ $coupon->max_redemptions ?? '∞' }}
                        <div class="text-xs text-gray-400">{{ $coupon->apply_to_cycles }} cycle(s)</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        @if(!$coupon->plan_id)
                            <span class="text-green-600 font-medium text-xs">All Plans</span>
                        @else
                            <span class="px-2 py-0.5 text-xs bg-indigo-50 text-indigo-700 rounded-full border border-indigo-100">{{ $coupon->plan->name }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <form action="{{ route('admin.coupons.toggle-status', $coupon) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-3 py-1 text-xs font-bold rounded-full transition-colors {{ $coupon->status ? 'bg-green-500 text-white hover:bg-green-600' : 'bg-red-500 text-white hover:bg-red-600' }}">
                                {{ $coupon->status ? 'ACTIVE' : 'INACTIVE' }}
                            </button>
                        </form>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $coupon->expires_at ? $coupon->expires_at->format('M d, Y') : 'Never' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex justify-end space-x-2">
                            <a href="{{ route('admin.coupons.edit', $coupon) }}" class="text-indigo-600 hover:text-indigo-900"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('admin.coupons.destroy', $coupon) }}" method="POST" onsubmit="return confirm('Delete this coupon?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
        {{ $coupons->links() }}
    </div>
</div>
@endsection
