@extends('layouts.public')
@section('title', 'API Status - SetuGeo API')

@section('content')
<div class="bg-transparent py-24 sm:py-32">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-500/10 text-green-500 mb-6">
            <i class="fas fa-check-circle text-3xl"></i>
        </div>
        <h2 class="text-3xl font-extrabold text-white sm:text-4xl tracking-tight mb-4">All Systems Operational</h2>
        <p class="text-lg text-gray-400 max-w-2xl mx-auto font-medium mb-12">
            Our geography APIs and core infrastructure are currently running smoothly without any reported degradations.
        </p>
        
        <div class="max-w-3xl mx-auto glass-card rounded-2xl overflow-hidden mt-8">
            <ul class="divide-y divide-white/10 text-left">
                <li class="px-6 py-4 flex items-center justify-between transition-colors hover:bg-white/5">
                    <span class="font-bold text-white">API endpoints</span>
                    <span class="text-sm font-bold text-green-400 bg-green-500/10 px-3 py-1 rounded-full border border-green-500/20">Operational</span>
                </li>
                <li class="px-6 py-4 flex items-center justify-between transition-colors hover:bg-white/5">
                    <span class="font-bold text-white">Database & Replication</span>
                    <span class="text-sm font-bold text-green-400 bg-green-500/10 px-3 py-1 rounded-full border border-green-500/20">Operational</span>
                </li>
                <li class="px-6 py-4 flex items-center justify-between transition-colors hover:bg-white/5">
                    <span class="font-bold text-white">CDN & Edge Routing</span>
                    <span class="text-sm font-bold text-green-400 bg-green-500/10 px-3 py-1 rounded-full border border-green-500/20">Operational</span>
                </li>
            </ul>
        </div>
    </div>
</div>
@endsection
