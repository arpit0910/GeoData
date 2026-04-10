@extends('layouts.public')

@section('title', 'SetuGeo API - The Most Accurate Geographic Data')

@section('content')
<!-- Hero Section -->
<div class="relative overflow-hidden bg-transparent">
    <!-- Decorative background blobs -->
    <div class="absolute inset-y-0 right-0 w-1/2 bg-amber-500 rounded-l-full opacity-10 transform translate-x-1/3 blur-3xl pointer-events-none z-0"></div>
    <div class="absolute top-0 left-0 w-64 h-64 bg-yellow-500 rounded-br-full opacity-10 transform -translate-y-1/2 -translate-x-1/4 blur-2xl pointer-events-none z-0"></div>
    <!-- Floating SetuGeo impact elements -->
    <div class="hidden lg:block absolute top-[15%] right-[20%] z-30 bg-black/60 backdrop-blur-md border border-white/10 rounded-xl p-3 shadow-2xl animate-[bounce_4s_infinite]">
        <div class="text-[10px] font-mono text-gray-400 mb-1 flex items-center justify-between gap-4"><span>NODE: TOKYO</span><span class="text-green-400 text-xs">●</span></div>
        <div class="text-sm font-mono text-amber-500">35.6762° N, 139.6503° E</div>
    </div>
    
    <div class="hidden lg:block absolute bottom-[25%] right-[10%] z-30 bg-black/60 backdrop-blur-md border border-white/10 rounded-xl p-3 shadow-2xl animate-[bounce_5s_infinite_0.5s]">
        <div class="text-[10px] font-mono text-gray-400 mb-1 flex items-center justify-between gap-4"><span>NODE: LONDON</span><span class="text-green-400 text-xs">●</span></div>
        <div class="text-sm font-mono text-amber-500">51.5074° N, 0.1278° W</div>
    </div>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-20 pt-20 pb-24 sm:pt-28 sm:pb-32 lg:pt-36 lg:pb-40">
        <div class="text-center max-w-4xl mx-auto">
            <div class="inline-flex items-center px-4 py-2 rounded-full bg-white/10 backdrop-blur-md text-amber-500 mb-8 border border-white/20 shadow-lg cursor-default">
                <span class="flex h-2 w-2 rounded-full bg-amber-500 mr-2 animate-pulse"></span>
                <span class="text-xs font-bold tracking-widest uppercase text-amber-500">SetuGeo Now Live</span>
            </div>
            <h1 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-extrabold text-white tracking-tight mb-8 leading-[1.1]">
                Integrate <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 to-amber-300">Accurate</span> Location Data in Minutes
            </h1>
            <p class="mt-4 max-w-2xl text-lg md:text-xl text-gray-300 mx-auto mb-10 leading-relaxed font-medium">
                Empower your application with lightning-fast APIs for countries, states, cities, coordinates, and pincodes. High precision data trusted by modern developers globally.
            </p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="{{ route('register') }}" class="inline-flex justify-center items-center px-8 py-4 border border-transparent text-lg font-bold rounded-xl text-white bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-700 hover:to-amber-600 shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-200">
                    Get Access Now <i class="fas fa-arrow-right ml-2 text-sm"></i>
                </a>
                <a href="{{ route('about') }}" class="inline-flex justify-center items-center px-8 py-4 border-2 border-white/20 text-lg font-bold rounded-xl text-white bg-white/10 backdrop-blur-md hover:bg-white/20 transform hover:-translate-y-1 transition-all duration-200">
                    Learn More
                </a>
            </div>
            
            <!-- Terminal/Code snippet mock -->
            <div class="mt-20 max-w-3xl mx-auto hidden sm:block shadow-2xl rounded-2xl overflow-hidden border border-white/10 bg-gray-900/80 backdrop-blur-xl transform transition-transform hover:scale-[1.02] duration-300">
                <div class="flex items-center px-4 py-3 bg-white/5 border-b border-white/10">
                    <div class="flex space-x-2">
                        <div class="w-3 h-3 rounded-full bg-red-500/80"></div>
                        <div class="w-3 h-3 rounded-full bg-yellow-500/80"></div>
                        <div class="w-3 h-3 rounded-full bg-green-500/80"></div>
                    </div>
                    <div class="ml-4 text-xs font-mono text-gray-400 uppercase tracking-widest">GET /api/v1/countries/IN/cities</div>
                </div>
                <div class="p-6 text-left overflow-x-auto text-sm font-mono text-gray-300 leading-relaxed">
                    <span class="text-pink-400">fetch</span>(<span class="text-green-300">'https://api.setugeo.provider/v1/countries/IN/cities'</span>, {<br>
                    &nbsp;&nbsp;headers: { <span class="text-amber-300">'Authorization'</span>: <span class="text-green-300">'Bearer YOUR_KEY'</span> }<br>
                    })<br>
                    .<span class="text-pink-400">then</span>(response => response.<span class="text-pink-400">json</span>())<br>
                    .<span class="text-pink-400">then</span>(data => {<br>
                    &nbsp;&nbsp;<span class="text-blue-300">console</span>.<span class="text-blue-300">log</span>(data.cities); <span class="text-gray-500">// Explore SetuGeo API</span><br>
                    });
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="relative overflow-hidden bg-white/5 backdrop-blur-lg py-24 sm:py-32 border-t border-white/10">
    <!-- Rich Background Elements -->
    <!-- Subtle connecting lines SVG network -->
    <svg class="absolute inset-0 w-full h-full opacity-[0.05] pointer-events-none z-0" xmlns="http://www.w3.org/2000/svg">
        <line x1="10%" y1="20%" x2="40%" y2="50%" stroke="#f59e0b" stroke-width="2"/>
        <line x1="40%" y1="50%" x2="80%" y2="30%" stroke="#f59e0b" stroke-width="2"/>
        <line x1="80%" y1="30%" x2="90%" y2="70%" stroke="#f59e0b" stroke-width="2"/>
        <line x1="40%" y1="50%" x2="50%" y2="80%" stroke="#f59e0b" stroke-width="2"/>
        <line x1="10%" y1="80%" x2="50%" y2="80%" stroke="#f59e0b" stroke-width="2"/>
        <circle cx="10%" cy="20%" r="6" fill="#f59e0b"/>
        <circle cx="40%" cy="50%" r="8" fill="#f59e0b"/>
        <circle cx="80%" cy="30%" r="6" fill="#f59e0b"/>
        <circle cx="90%" cy="70%" r="5" fill="#f59e0b"/>
        <circle cx="50%" cy="80%" r="7" fill="#f59e0b"/>
        <circle cx="10%" cy="80%" r="5" fill="#f59e0b"/>
    </svg>
    <div class="absolute inset-0 z-0 bg-[linear-gradient(to_right,#80808012_1px,transparent_1px),linear-gradient(to_bottom,#80808012_1px,transparent_1px)] bg-[size:40px_40px]"></div>

    <div class="absolute left-0 right-0 top-0 -z-10 m-auto h-[310px] w-[310px] rounded-full bg-amber-500 opacity-20 blur-[100px]"></div>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-3xl mx-auto mb-20">
            <h2 class="text-base text-amber-500 font-bold tracking-wide uppercase">Core Infrastructure</h2>
            <p class="mt-2 text-3xl font-extrabold text-white sm:text-4xl tracking-tight">Everything you need to map the world.</p>
            <p class="mt-4 text-lg text-gray-300 font-medium">We provide beautifully structured JSON payloads containing over 200+ countries, 4,000+ states, and millions of cities and zip codes.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-10 lg:gap-12">
            <!-- Feature 1 -->
            <div class="bg-white/10 backdrop-blur-md rounded-3xl p-10 shadow-sm border border-white/10 hover:shadow-amber-500/20 hover:border-amber-500/50 transition-all duration-300 group">
                <div class="w-14 h-14 bg-amber-500/10 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-amber-500/20 transition-colors">
                    <i class="fas fa-bullseye text-2xl text-amber-500"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-3 tracking-tight">Millimeter Accuracy</h3>
                <p class="text-gray-400 leading-relaxed font-medium">Our geocoding algorithms and databases are updated daily from global authoritative sources guaranteeing >99.9% accuracy, keeping your applications flawless.</p>
            </div>
            
            <!-- Feature 2 -->
            <div class="bg-white/10 backdrop-blur-md rounded-3xl p-10 shadow-sm border border-white/10 hover:shadow-amber-500/20 hover:border-amber-500/50 transition-all duration-300 transform md:-translate-y-4 group">
                <div class="w-14 h-14 bg-amber-500/10 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-amber-500/20 transition-colors">
                    <i class="fas fa-bolt text-2xl text-amber-500"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-3 tracking-tight">Sub-50ms Latency</h3>
                <p class="text-gray-400 leading-relaxed font-medium">Global CDN edge routing ensures that API queries resolve consistently blazing fast, no matter where your users or application servers are globally located.</p>
            </div>

            <!-- Feature 3 -->
            <div class="bg-white/10 backdrop-blur-md rounded-3xl p-10 shadow-sm border border-white/10 hover:shadow-amber-500/20 hover:border-amber-500/50 transition-all duration-300 group">
                <div class="w-14 h-14 bg-amber-500/10 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-amber-500/20 transition-colors">
                    <i class="fas fa-code text-2xl text-amber-500"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-3 tracking-tight">Developer Friendly</h3>
                <p class="text-gray-400 leading-relaxed font-medium">Crystal clear documentation, comprehensive RESTful endpoints, and standard JSON formats meant to drop cleanly into your application workflow instantly.</p>
            </div>
        </div>
    </div>
</div>

<!-- Stats Section -->
<div class="relative overflow-hidden bg-transparent py-24 sm:py-32">
    <!-- Dynamic Line & Glow Background -->
    <i class="fas fa-map-marker-alt absolute top-10 left-[15%] text-amber-500/10 text-5xl transform -rotate-12 blur-[2px]"></i>
    <i class="fas fa-map-marker-alt absolute bottom-20 right-[20%] text-amber-500/10 text-7xl transform rotate-12 blur-[4px]"></i>
    <i class="fas fa-globe absolute top-1/3 right-[10%] text-amber-500/10 text-9xl blur-[2px] animate-[spin_60s_linear_infinite]"></i>
    
    <div class="absolute inset-x-0 -top-px h-px bg-gradient-to-r from-transparent via-amber-500/50 to-transparent"></div>
    <div class="absolute inset-0 z-0 bg-[radial-gradient(ellipse_at_center,rgba(245,158,11,0.05)_0,transparent_60%)]"></div>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
            <div class="text-center">
                <p class="text-5xl font-black text-white mb-2">99.9<span class="text-amber-500">%</span></p>
                <p class="text-sm font-bold text-gray-400 uppercase tracking-widest">Uptime SLA</p>
            </div>
            <div class="text-center">
                <p class="text-5xl font-black text-white mb-2">1<span class="text-amber-500">M+</span></p>
                <p class="text-sm font-bold text-gray-400 uppercase tracking-widest">API Requests</p>
            </div>
            <div class="text-center">
                <p class="text-5xl font-black text-white mb-2">200<span class="text-amber-500">+</span></p>
                <p class="text-sm font-bold text-gray-400 uppercase tracking-widest">Countries Covered</p>
            </div>
            <div class="text-center">
                <p class="text-5xl font-black text-white mb-2">50<span class="text-amber-500">ms</span></p>
                <p class="text-sm font-bold text-gray-400 uppercase tracking-widest">Avg Latency</p>
            </div>
        </div>
    </div>
</div>

<!-- Integrations Section -->
<div class="bg-white/5 backdrop-blur-lg py-24 sm:py-32 border-y border-white/10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-base text-amber-500 font-bold tracking-wide uppercase mb-12">Works with your stack</h2>
        <div class="flex flex-wrap justify-center gap-12 opacity-50 grayscale hover:grayscale-0 transition-all duration-500 italic font-black text-3xl text-gray-400">
            <span class="flex items-center gap-2"><i class="fab fa-laravel text-red-500"></i> Laravel</span>
            <span class="flex items-center gap-2"><i class="fab fa-react text-blue-400"></i> React</span>
            <span class="flex items-center gap-2"><i class="fab fa-python text-yellow-500"></i> Python</span>
            <span class="flex items-center gap-2"><i class="fab fa-node-js text-green-500"></i> Node.js</span>
            <span class="flex items-center gap-2"><i class="fab fa-php text-purple-400"></i> PHP</span>
        </div>
    </div>
</div>

<!-- Testimonials Section -->
<div class="relative overflow-hidden bg-transparent py-24 sm:py-32">
    <!-- Ambient glowing abstract shapes -->
    <div class="absolute top-1/2 left-0 w-96 h-96 bg-amber-600 rounded-full opacity-10 mix-blend-screen blur-[120px] -translate-y-1/2 -translate-x-1/2 pointer-events-none"></div>
    <div class="absolute top-1/2 right-0 w-96 h-96 bg-yellow-500 rounded-full opacity-10 mix-blend-screen blur-[120px] -translate-y-1/2 translate-x-1/2 pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <h2 class="text-base text-amber-500 font-bold tracking-wide uppercase mb-4">Trusted by Developers</h2>
        <p class="text-4xl font-extrabold text-white mb-20 tracking-tight">The data layer for industry leaders.</p>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 text-left">
            <div class="bg-white/10 backdrop-blur-md p-10 rounded-[2.5rem] border border-white/10">
                <p class="text-gray-400 text-sm italic mb-6">"SetuGeo has been a game-changer for our platform. The precision and speed of their API are unmatched in the industry."</p>
                <div class="flex items-center">
                    <div class="h-10 w-10 flex-shrink-0">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-amber-500/10 border border-amber-500/20">
                            <span class="text-sm font-bold text-amber-500">JD</span>
                        </span>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-bold text-white">James Dalton</p>
                        <p class="text-gray-500 text-sm">CTO, Global Commerce</p>
                    </div>
                </div>
            </div>
            <div class="bg-white/10 backdrop-blur-md p-10 rounded-[2.5rem] border border-white/10">
                <p class="text-xl text-gray-300 italic mb-8 font-medium border-l-4 border-amber-500 pl-6 leading-relaxed">
                    "Integrating the API was a breeze. The documentation is perfect, and the sub-50ms latency is exactly what we needed for our scale."
                </p>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-amber-500 rounded-full flex items-center justify-center font-bold text-white">SK</div>
                    <div>
                        <p class="text-white font-bold">Sarah Koenig</p>
                        <p class="text-gray-500 text-sm">Senior Developer, SaaS Travel</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FAQ Section -->
<div class="relative overflow-hidden bg-white/5 backdrop-blur-lg py-24 sm:py-32 border-t border-white/10" x-data="{ active: null }">
    <!-- Delicate dot pattern -->
    <div class="absolute inset-0 z-0 bg-[radial-gradient(#ffffff1a_1px,transparent_1px)] [background-size:20px_20px] opacity-30"></div>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <h2 class="text-base text-amber-500 font-bold tracking-wide uppercase mb-4">Common Questions</h2>
        <p class="text-4xl font-extrabold text-white mb-16 tracking-tight">Got questions? We've got answers.</p>
        
        <div class="space-y-4 text-left" x-data="{ active: null }">
            @forelse($faqs as $i => $item)
            <div class="bg-white/5 rounded-3xl border border-white/10 overflow-hidden transition-all hover:border-amber-500/30 hover:bg-white/[0.07] group">
                <button @click="active = (active === {{ $i }} ? null : {{ $i }})" class="w-full flex items-center justify-between px-8 sm:px-10 pt-8 pb-5 text-left transition-colors">
                    <span class="text-xl font-bold text-white group-hover:text-amber-500 transition-colors">{{ $item->question }}</span>
                    <i class="fas fa-chevron-down text-amber-500 transition-transform duration-300" :class="active === {{ $i }} ? 'rotate-180' : ''"></i>
                </button>
                <div x-show="active === {{ $i }}" x-collapse class="px-8 sm:px-10 pt-0 pb-8 text-gray-400 font-medium leading-relaxed text-lg">
                    {{ $item->answer }}
                </div>
            </div>
            @empty
            <div class="text-center py-12">
                <p class="text-gray-500">No FAQs available at the moment.</p>
            </div>
            @endforelse
        </div>
    </div>
</div>

<!-- CTA Section -->
<div class="bg-white/5 backdrop-blur-xl border-y border-white/10">
    <div class="max-w-7xl mx-auto py-24 sm:py-32 px-4 sm:px-6 lg:px-8 lg:flex lg:items-center lg:justify-between">
        <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-5xl">
            <span class="block">Ready to map the globe?</span>
            <span class="block text-amber-500 mt-2">Start integrating our APIs today for free.</span>
        </h2>
        <div class="mt-10 flex lg:mt-0 lg:flex-shrink-0">
            <div class="inline-flex rounded-xl shadow">
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-bold rounded-xl text-white bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-700 hover:to-amber-600 transition-colors shadow-md">
                    Get Started Free
                </a>
            </div>
            <div class="ml-3 inline-flex rounded-xl shadow">
                <a href="{{ route('pricing') }}" class="inline-flex items-center justify-center px-6 py-3 border border-white/10 text-base font-bold rounded-xl text-white bg-white/10 hover:bg-white/20 transition-colors shadow-md backdrop-blur-md">
                    View Pricing
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
