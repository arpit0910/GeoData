@extends('layouts.public')

@section('title', 'GeoData API - The Most Accurate Geographic Data')

@section('content')
<!-- Hero Section -->
<div class="relative bg-white overflow-hidden">
    <!-- Decorative background blobs -->
    <div class="absolute inset-y-0 right-0 w-1/2 bg-amber-50 rounded-l-full opacity-60 transform translate-x-1/3 blur-3xl pointer-events-none"></div>
    <div class="absolute top-0 left-0 w-64 h-64 bg-yellow-50 rounded-br-full opacity-50 transform -translate-y-1/2 -translate-x-1/4 blur-2xl pointer-events-none"></div>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 pt-20 pb-24 sm:pt-28 sm:pb-32 lg:pt-36 lg:pb-40">
        <div class="text-center max-w-4xl mx-auto">
            <div class="inline-flex items-center px-4 py-2 rounded-full bg-amber-50 text-amber-800 mb-8 border border-amber-200 shadow-sm cursor-default">
                <span class="flex h-2 w-2 rounded-full bg-amber-500 mr-2 animate-pulse"></span>
                <span class="text-xs font-bold tracking-widest uppercase">GeoData Now Live</span>
            </div>
            <h1 class="text-5xl md:text-6xl lg:text-7xl font-extrabold text-gray-900 tracking-tight mb-8 leading-[1.1]">
                Integrate <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-600 to-amber-500">Accurate</span> Location Data in Minutes
            </h1>
            <p class="mt-4 max-w-2xl text-lg md:text-xl text-gray-500 mx-auto mb-10 leading-relaxed font-medium">
                Empower your application with lightning-fast APIs for countries, states, cities, coordinates, and pincodes. High precision data trusted by modern developers globally.
            </p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="{{ route('register') }}" class="inline-flex justify-center items-center px-8 py-4 border border-transparent text-lg font-bold rounded-xl text-white bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-700 hover:to-amber-600 shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-200">
                    Get Access Now <i class="fas fa-arrow-right ml-2 text-sm"></i>
                </a>
                <a href="{{ route('about') }}" class="inline-flex justify-center items-center px-8 py-4 border-2 border-gray-200 text-lg font-bold rounded-xl text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-300 transform hover:-translate-y-1 transition-all duration-200">
                    Learn More
                </a>
            </div>
            
            <!-- Terminal/Code snippet mock -->
            <div class="mt-20 max-w-3xl mx-auto hidden sm:block shadow-2xl rounded-2xl overflow-hidden border border-gray-800 bg-gray-900 transform transition-transform hover:scale-[1.02] duration-300">
                <div class="flex items-center px-4 py-3 bg-gray-800 border-b border-gray-700">
                    <div class="flex space-x-2">
                        <div class="w-3 h-3 rounded-full bg-red-500"></div>
                        <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                        <div class="w-3 h-3 rounded-full bg-green-500"></div>
                    </div>
                    <div class="ml-4 text-xs font-mono text-gray-400">GET /api/v1/countries/US/cities</div>
                </div>
                <div class="p-6 text-left overflow-x-auto text-sm font-mono text-gray-300 leading-relaxed">
                    <span class="text-pink-400">fetch</span>(<span class="text-green-300">'https://api.geodata.provider/v1/countries/US/cities'</span>, {<br>
                    &nbsp;&nbsp;headers: { <span class="text-amber-300">'Authorization'</span>: <span class="text-green-300">'Bearer YOUR_KEY'</span> }<br>
                    })<br>
                    .<span class="text-pink-400">then</span>(response => response.<span class="text-pink-400">json</span>())<br>
                    .<span class="text-pink-400">then</span>(data => {<br>
                    &nbsp;&nbsp;<span class="text-blue-300">console</span>.<span class="text-blue-300">log</span>(data.cities); <span class="text-gray-500">// [{ "name": "New York", "population": 8419000 ...}]</span><br>
                    });
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="bg-gray-50 py-24 sm:py-32 border-t border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-20">
            <h2 class="text-base text-amber-600 font-bold tracking-wide uppercase">Core Infrastructure</h2>
            <p class="mt-2 text-3xl font-extrabold text-gray-900 sm:text-4xl tracking-tight">Everything you need to map the world.</p>
            <p class="mt-4 text-lg text-gray-500 font-medium">We provide beautifully structured JSON payloads containing over 200+ countries, 4,000+ states, and millions of cities and zip codes.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-10 lg:gap-12">
            <!-- Feature 1 -->
            <div class="bg-white rounded-3xl p-10 shadow-sm border border-gray-100 hover:shadow-xl hover:border-amber-100 transition-all duration-300 group">
                <div class="w-14 h-14 bg-amber-50 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-amber-100 transition-colors">
                    <i class="fas fa-bullseye text-2xl text-amber-600"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3 tracking-tight">Millimeter Accuracy</h3>
                <p class="text-gray-500 leading-relaxed font-medium">Our geocoding algorithms and databases are updated daily from global authoritative sources guaranteeing >99.9% accuracy, keeping your applications flawless.</p>
            </div>
            
            <!-- Feature 2 -->
            <div class="bg-white rounded-3xl p-10 shadow-sm border border-gray-100 hover:shadow-xl hover:border-amber-100 transition-all duration-300 transform md:-translate-y-4 group">
                <div class="w-14 h-14 bg-amber-50 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-amber-100 transition-colors">
                    <i class="fas fa-bolt text-2xl text-amber-600"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3 tracking-tight">Sub-50ms Latency</h3>
                <p class="text-gray-500 leading-relaxed font-medium">Global CDN edge routing ensures that API queries resolve consistently blazing fast, no matter where your users or application servers are globally located.</p>
            </div>

            <!-- Feature 3 -->
            <div class="bg-white rounded-3xl p-10 shadow-sm border border-gray-100 hover:shadow-xl hover:border-amber-100 transition-all duration-300 group">
                <div class="w-14 h-14 bg-amber-50 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-amber-100 transition-colors">
                    <i class="fas fa-code text-2xl text-amber-600"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3 tracking-tight">Developer Friendly</h3>
                <p class="text-gray-500 leading-relaxed font-medium">Crystal clear documentation, comprehensive RESTful endpoints, and standard JSON formats meant to drop cleanly into your application workflow instantly.</p>
            </div>
        </div>
    </div>
</div>

<!-- CTA Section -->
<div class="bg-amber-600 border-b border-amber-700">
    <div class="max-w-7xl mx-auto py-16 px-4 sm:px-6 lg:py-20 lg:px-8 lg:flex lg:items-center lg:justify-between">
        <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
            <span class="block">Ready to map the globe?</span>
            <span class="block text-amber-200 mt-1">Start integrating our APIs today for free.</span>
        </h2>
        <div class="mt-8 flex lg:mt-0 lg:flex-shrink-0">
            <div class="inline-flex rounded-md shadow">
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-bold rounded-xl text-amber-600 bg-white hover:bg-gray-50 transition-colors shadow-md">
                    Get Started Free
                </a>
            </div>
            <div class="ml-3 inline-flex rounded-md shadow">
                <a href="{{ route('pricing') }}" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-bold rounded-xl text-white bg-amber-700 hover:bg-amber-800 transition-colors shadow-md">
                    View Pricing
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
