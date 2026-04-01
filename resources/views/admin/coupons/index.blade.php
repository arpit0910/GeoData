@extends('layouts.app')

@section('header', 'Manage Coupons')

@section('content')
<div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">Coupon Codes</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 font-medium">Create and manage discounts for your plans.</p>
    </div>
    <a href="{{ route('admin.coupons.create') }}" class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-bold rounded-xl shadow-lg text-white bg-amber-600 hover:bg-amber-700 transition-all hover:scale-[1.02] active:scale-[0.98]">
        <i class="fas fa-plus mr-2"></i> Create Coupon
    </a>
</div>

<div class="bg-white dark:bg-richdark-surface rounded-2xl shadow-sm border border-gray-200 dark:border-white/5 overflow-hidden">
    <div class="p-0 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-white/5">
            <thead class="bg-gray-50 dark:bg-white/5">
                <tr>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Code</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Discount</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Usage</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Target Plans</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Status</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Expiry</th>
                    <th class="px-6 py-4 text-right text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                @forelse($coupons as $coupon)
                <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02] transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-3 py-1.5 text-xs font-mono bg-gray-100 dark:bg-white/10 rounded-lg text-gray-900 dark:text-white font-black uppercase tracking-wider border border-gray-200 dark:border-white/5">{{ $coupon->code }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <div class="font-bold text-gray-900 dark:text-white">
                            {{ $coupon->discount_type === 'percentage' ? $coupon->discount_value . '%' : '₹' . $coupon->discount_value }}
                        </div>
                        @if($coupon->max_discount)
                            <div class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-tight">Max: ₹{{ $coupon->max_discount }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-xs font-medium text-gray-600 dark:text-gray-400">
                        <div class="flex items-center gap-1.5 mb-1">
                            <span class="font-bold text-gray-900 dark:text-white">{{ $coupon->used_count }}</span>
                            <span class="text-gray-400 opacity-50">/</span>
                            <span class="text-gray-500">{{ $coupon->max_redemptions ?? '∞' }}</span>
                        </div>
                        <div class="text-[10px] font-black text-amber-600/60 dark:text-amber-500/40 uppercase tracking-widest">{{ $coupon->apply_to_cycles }} cycle(s)</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if(!$coupon->plan_id)
                            <span class="px-2.5 py-1 text-[10px] font-black bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 rounded-lg border border-emerald-200 dark:border-emerald-500/20 uppercase tracking-widest">All Plans</span>
                        @else
                            <span class="px-2.5 py-1 text-[10px] font-black bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400 rounded-lg border border-amber-200 dark:border-amber-500/20 uppercase tracking-widest line-clamp-1 truncate max-w-[120px]">{{ $coupon->plan->name }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <form action="{{ route('admin.coupons.toggle-status', $coupon) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-3 py-1 text-[10px] font-black tracking-widest rounded-full transition-all border
                                {{ $coupon->status ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20 hover:bg-emerald-500 hover:text-white' : 'bg-red-500/10 text-red-600 border-red-500/20 hover:bg-red-50 hover:text-white' }}">
                                {{ $coupon->status ? 'ACTIVE' : 'INACTIVE' }}
                            </button>
                        </form>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-600 dark:text-gray-400">
                        {{ $coupon->expires_at ? $coupon->expires_at->format('M d, Y') : 'Never' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('admin.coupons.edit', $coupon) }}" class="p-2 bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-500 rounded-lg hover:bg-amber-600 hover:text-white dark:hover:bg-amber-500 transition-all">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('admin.coupons.destroy', $coupon) }}" method="POST" class="inline delete-form" data-confirm-message="Are you sure you want to delete coupon '{{ $coupon->code }}'?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-2 bg-gray-50 dark:bg-white/5 text-gray-500 dark:text-gray-400 rounded-lg hover:text-red-600 dark:hover:text-red-500 hover:bg-gray-200 dark:hover:bg-white/10 transition-all">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400 font-medium">No coupons found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($coupons->hasPages())
    <div class="px-6 py-4 bg-gray-50 dark:bg-white/5 border-t border-gray-200 dark:border-white/5">
        {{ $coupons->links() }}
    </div>
    @endif
</div>
@endsection
