@extends('layouts.public')
@section('title', 'About Us - GeoData API')

@section('content')
<div class="bg-white py-24 sm:py-32">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            <div>
                <h2 class="text-amber-600 font-bold tracking-wide uppercase text-sm">About GeoData</h2>
                <p class="mt-3 text-4xl font-extrabold text-gray-900 tracking-tight sm:text-5xl leading-tight">Building the world's most accurate map data APIs.</p>
                <p class="mt-6 text-lg text-gray-500 leading-relaxed font-medium">
                    At GeoData, we believe that developers shouldn't have to wrestle with outdated, inaccurate, or slow geographic databases. Our mission is to provide an accessible, developer-first infrastructure that powers location-aware applications globally.
                </p>
                <p class="mt-4 text-lg text-gray-500 leading-relaxed font-medium">
                    We aggregate millions of data points across 200+ nations daily, validating coordinate accuracy down to the millimeter. Whether you're building a checkout form, a logistics engine, or a global travel platform, GeoData guarantees reliability at sub-50ms latency.
                </p>
                
                <div class="mt-12 grid grid-cols-2 gap-8 border-t border-gray-100 pt-10">
                    <div>
                        <h4 class="text-4xl font-extrabold text-amber-600">99.9%</h4>
                        <p class="mt-2 font-bold text-gray-900">Uptime SLA guaranteeing stability.</p>
                    </div>
                    <div>
                        <h4 class="text-4xl font-extrabold text-amber-600">14B+</h4>
                        <p class="mt-2 font-bold text-gray-900">API Requests reliably served monthly.</p>
                    </div>
                </div>
            </div>
            <div class="relative">
                <div class="absolute inset-0 bg-gradient-to-tr from-amber-100 to-amber-50 rounded-3xl transform rotate-3 scale-105"></div>
                <img src="https://images.unsplash.com/photo-1524661135-423995f22d0b?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80" alt="Global Map Data" class="relative rounded-3xl shadow-2xl border-4 border-white object-cover h-[550px] w-full transform -rotate-2 hover:rotate-0 transition-transform duration-700 ease-out">
            </div>
        </div>
    </div>
</div>
@endsection
