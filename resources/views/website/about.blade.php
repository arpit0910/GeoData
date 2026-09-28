@extends('layouts.public')
@section('title', 'About SetuGeo - The Mission Behind Our Geographic Data API')
@section('meta_description', 'Learn about SetuGeo mission to build the most accurate and high-speed geographic data APIs for developers. Discover our commitment to reliability, accuracy, and accessibility.')
@section('meta_keywords', 'about setugeo, geographic data company, api providers, location data infrastructure, reliable data APIs')

@section('content')
<div class="bg-transparent py-16 sm:py-24 lg:py-32 overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center min-w-0">
            <div class="min-w-0">
                <h2 class="text-amber-500 font-bold tracking-wide uppercase text-sm">About SetuGeo</h2>
                <p class="mt-3 text-3xl font-extrabold text-white tracking-tight sm:text-5xl leading-tight break-words">Building the world's most accurate map data APIs.</p>
                <p class="mt-6 text-lg text-gray-300 leading-relaxed font-medium">
                    At SetuGeo, we believe that developers shouldn't have to wrestle with outdated, inaccurate, or slow geographic databases. Our mission is to provide an accessible, developer-first infrastructure that powers location-aware applications globally.
                </p>
                <p class="mt-4 text-lg text-gray-300 leading-relaxed font-medium">
                    We aggregate millions of data points across 200+ nations daily, validating coordinate accuracy down to the millimeter. Whether you're building a checkout form, a Commerce engine, or a global travel platform, SetuGeo guarantees reliability at sub-50ms latency.
                </p>
                
                <div class="mt-10 grid grid-cols-1 min-[380px]:grid-cols-2 gap-6 sm:gap-8 border-t border-white/10 pt-8 sm:pt-10">
                    <div>
                        <h4 class="text-4xl font-extrabold text-amber-500">99.9%</h4>
                        <p class="mt-2 font-bold text-white">Uptime SLA guaranteeing stability.</p>
                    </div>
                    <div>
                        <h4 class="text-4xl font-extrabold text-amber-500">1M+</h4>
                        <p class="mt-2 font-bold text-white">API Requests reliably served monthly.</p>
                    </div>
                </div>
            </div>
            <div class="relative min-w-0 px-1 sm:px-3">
                <div class="absolute inset-1 sm:inset-3 bg-gradient-to-tr from-amber-500/10 to-transparent rounded-3xl transform sm:rotate-3 sm:scale-105"></div>
                <img src="https://images.unsplash.com/photo-1524661135-423995f22d0b?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80" alt="Global Map Data" class="relative rounded-3xl shadow-2xl border-4 border-white/10 object-cover h-[340px] sm:h-[440px] lg:h-[550px] w-full transform sm:-rotate-2 sm:hover:rotate-0 transition-transform duration-700 ease-out">
            </div>
        </div>
    </div>
</div>
@endsection
