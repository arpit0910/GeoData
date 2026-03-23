@extends('layouts.public')
@section('title', 'API Status - GeoData API')

@section('content')
<div class="bg-white py-24 sm:py-32">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 text-green-600 mb-6">
            <i class="fas fa-check-circle text-3xl"></i>
        </div>
        <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl tracking-tight mb-4">All Systems Operational</h2>
        <p class="text-lg text-gray-500 max-w-2xl mx-auto font-medium mb-12">
            Our geography APIs and core infrastructure are currently running smoothly without any reported degradations.
        </p>
        
        <div class="max-w-3xl mx-auto bg-gray-50 border border-gray-200 rounded-2xl overflow-hidden mt-8 shadow-sm">
            <ul class="divide-y divide-gray-200 text-left">
                <li class="px-6 py-4 flex items-center justify-between">
                    <span class="font-bold text-gray-800">API endpoints</span>
                    <span class="text-sm font-bold text-green-700 bg-green-100 px-3 py-1 rounded-full">Operational</span>
                </li>
                <li class="px-6 py-4 flex items-center justify-between">
                    <span class="font-bold text-gray-800">Database & Replication</span>
                    <span class="text-sm font-bold text-green-700 bg-green-100 px-3 py-1 rounded-full">Operational</span>
                </li>
                <li class="px-6 py-4 flex items-center justify-between">
                    <span class="font-bold text-gray-800">CDN & Edge Routing</span>
                    <span class="text-sm font-bold text-green-700 bg-green-100 px-3 py-1 rounded-full">Operational</span>
                </li>
            </ul>
        </div>
    </div>
</div>
@endsection
