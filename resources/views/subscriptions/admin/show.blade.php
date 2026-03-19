@extends('layouts.app')

@section('header')
    Subscription Details
@endsection

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Subscription #{{ $subscription->id }}</h1>
    </div>
    <a href="{{ route('admin.subscriptions.index') }}" class="text-amber-600 hover:text-amber-800 text-sm font-medium flex items-center bg-white px-3 py-1.5 rounded border border-gray-200 shadow-sm">
        <i class="fas fa-arrow-left mr-2"></i> Back
    </a>
</div>

<div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <div class="border-t border-gray-200">
        <dl>
            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 border-b border-gray-200">
                <dt class="text-sm font-medium text-gray-500">User</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $subscription->user->name ?? 'N/A' }} ({{ $subscription->user->email ?? 'N/A' }})</dd>
            </div>
            <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 border-b border-gray-200">
                <dt class="text-sm font-medium text-gray-500">Plan</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $subscription->plan->name ?? 'N/A' }}</dd>
            </div>
            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 border-b border-gray-200">
                <dt class="text-sm font-medium text-gray-500">Status</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 uppercase font-semibold {{ $subscription->status === 'active' ? 'text-green-600' : 'text-red-600' }}">{{ $subscription->status }}</dd>
            </div>
            <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 border-b border-gray-200">
                <dt class="text-sm font-medium text-gray-500">Amount Paid</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">₹{{ number_format($subscription->amount_paid, 2) }}</dd>
            </div>
            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 border-b border-gray-200">
                <dt class="text-sm font-medium text-gray-500">Gateway Order ID</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $subscription->razorpay_order_id ?? 'N/A' }}</dd>
            </div>
            <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 border-b border-gray-200">
                <dt class="text-sm font-medium text-gray-500">Gateway Payment ID</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $subscription->razorpay_payment_id ?? 'N/A' }}</dd>
            </div>
            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 border-b border-gray-200">
                <dt class="text-sm font-medium text-gray-500">Gateway Signature</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 break-all">{{ $subscription->razorpay_signature ?? 'N/A' }}</dd>
            </div>
            <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 border-b border-gray-200">
                <dt class="text-sm font-medium text-gray-500">Expires At</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $subscription->expires_at ? $subscription->expires_at->format('M d, Y h:i A') : 'N/A' }}</dd>
            </div>
            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Created At</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $subscription->created_at->format('M d, Y h:i A') }}</dd>
            </div>
        </dl>
    </div>
</div>
@endsection
