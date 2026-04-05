@extends('layouts.app')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Currency Conversions</h1>
        <p class="mt-1 text-sm text-gray-600">Latest exchange rates for countries relative to USD and INR.</p>
    </div>
    <div class="flex items-center space-x-4">
        <form action="{{ route('admin.currency-conversions.sync') }}" method="POST">
            @csrf
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                <i class="fas fa-sync-alt mr-2"></i> Sync Latest Rates
            </button>
        </form>
    </div>
</div>

@if(session('success'))
<div class="mb-4 bg-green-50 border-l-4 border-green-400 p-4">
    <div class="flex">
        <div class="flex-shrink-0">
            <i class="fas fa-check-circle text-green-400"></i>
        </div>
        <div class="ml-3">
            <p class="text-sm text-green-700">
                {{ session('success') }}
            </p>
        </div>
    </div>
</div>
@endif

@if(session('error'))
<div class="mb-4 bg-red-50 border-l-4 border-red-400 p-4">
    <div class="flex">
        <div class="flex-shrink-0">
            <i class="fas fa-exclamation-circle text-red-400"></i>
        </div>
        <div class="ml-3">
            <p class="text-sm text-red-700">
                {{ session('error') }}
            </p>
        </div>
    </div>
</div>
@endif

<div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <div class="p-6 bg-white border-b border-gray-200 overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 uppercase text-xs font-semibold text-gray-600">
                    <th class="px-6 py-3 border-b">Country</th>
                    <th class="px-6 py-3 border-b">Currency</th>
                    <th class="px-6 py-3 border-b">USD Rate (1 USD = ?)</th>
                    <th class="px-6 py-3 border-b">INR Rate (1 INR = ?)</th>
                    <th class="px-6 py-3 border-b">Last Updated</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($rates as $rate)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center text-sm font-medium text-gray-900">
                            {{ $rate->country->name }}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        {{ $rate->currency }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold">
                        {{ number_format($rate->usd_conversion_rate, 4) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold">
                        {{ number_format($rate->inr_conversion_rate, 4) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $rate->updated_at->format('M d, Y H:i') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">
                        No currency conversion data found. Click "Sync" to fetch data.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($rates->hasPages())
    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
        {{ $rates->links() }}
    </div>
    @endif
</div>
@endsection
