@extends('layouts.public')

@section('title', 'SetuGeo - Enterprise-Grade Geographic Data APIs')

@section('content')
<style>
    @keyframes slideUp { from { opacity:0; transform:translateY(40px); } to { opacity:1; transform:translateY(0); } }
    @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
    @keyframes countUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
    @keyframes pulse-ring { 0% { transform:scale(0.8); opacity:0.5; } 50% { transform:scale(1.2); opacity:0; } 100% { transform:scale(0.8); opacity:0.5; } }
    @keyframes float { 0%,100% { transform:translateY(0); } 50% { transform:translateY(-12px); } }
    @keyframes glow { 0%,100% { box-shadow:0 0 20px rgba(245,158,11,0.15); } 50% { box-shadow:0 0 40px rgba(245,158,11,0.3); } }
    @keyframes typewriter { from { width:0; } to { width:100%; } }
    @keyframes blink { 0%,100% { border-color:transparent; } 50% { border-color:#f59e0b; } }
    .anim-slide-up { animation: slideUp 0.8s ease-out forwards; }
    .anim-slide-up-d1 { animation: slideUp 0.8s 0.15s ease-out both; }
    .anim-slide-up-d2 { animation: slideUp 0.8s 0.3s ease-out both; }
    .anim-slide-up-d3 { animation: slideUp 0.8s 0.45s ease-out both; }
    .anim-fade-in { animation: fadeIn 1s 0.6s ease-out both; }
    .anim-float { animation: float 6s ease-in-out infinite; }
    .anim-glow { animation: glow 3s ease-in-out infinite; }
    .metric-card:hover .metric-icon { transform: scale(1.15) rotate(-5deg); }
    .endpoint-card { transition: all 0.3s cubic-bezier(.4,0,.2,1); }
    .endpoint-card:hover { transform: translateY(-6px); box-shadow: 0 20px 40px rgba(245,158,11,0.1); }
    .step-connector { position:relative; }
    .step-connector::after { content:''; position:absolute; top:50%; left:100%; width:100%; height:2px; background:linear-gradient(to right,rgba(245,158,11,0.4),transparent); }
    @media (max-width:768px) { .step-connector::after { display:none; } }
</style>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!--  HERO — Split-Screen Command Center                               -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<section class="relative overflow-hidden min-h-[80vh] flex items-center">
    <!-- Background layers -->
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_left,rgba(245,158,11,0.08)_0%,transparent_50%)]"></div>
    <div class="absolute inset-0 bg-[linear-gradient(to_right,#80808008_1px,transparent_1px),linear-gradient(to_bottom,#80808008_1px,transparent_1px)] bg-[size:60px_60px]"></div>
    <div class="absolute top-20 right-20 w-[500px] h-[500px] bg-amber-500/5 rounded-full blur-[120px]"></div>
    <div class="absolute bottom-0 left-0 w-[400px] h-[400px] bg-amber-600/5 rounded-full blur-[100px]"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 w-full py-16 lg:py-0">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">
            <!-- Left — Copy -->
            <div>
                <div class="anim-slide-up inline-flex items-center gap-2 px-4 py-2 rounded-full bg-amber-500/10 border border-amber-500/20 mb-6">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-amber-500"></span>
                    </span>
                    <span class="text-xs font-bold tracking-widest uppercase text-amber-500">Platform Live · 99.9% Uptime</span>
                </div>

                <h1 class="anim-slide-up-d1 text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-extrabold text-white leading-[1.08] tracking-tight mb-6">
                    The <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 via-amber-300 to-yellow-400">Geographic Data</span> API for Modern Apps
                </h1>

                <p class="anim-slide-up-d2 text-lg lg:text-xl text-gray-400 leading-relaxed max-w-lg mb-8 font-medium">
                    Countries, states, cities, pincodes, coordinates & currency data — all served through one blazing-fast RESTful API with sub-50ms global latency.
                </p>

                <div class="anim-slide-up-d3 flex flex-col sm:flex-row gap-4">
                    <a href="{{ route('register') }}" class="group inline-flex justify-center items-center px-8 py-4 text-base font-bold rounded-2xl text-white bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-500 hover:to-amber-400 shadow-lg shadow-amber-600/20 hover:shadow-amber-500/30 transform hover:-translate-y-0.5 transition-all duration-300">
                        Start Building Free
                        <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </a>
                    <a href="{{ route('docs') }}" class="inline-flex justify-center items-center px-8 py-4 text-base font-bold rounded-2xl text-white bg-white/5 border border-white/10 hover:bg-white/10 hover:border-white/20 transition-all duration-300">
                        <svg class="w-5 h-5 mr-2 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Read the Docs
                    </a>
                </div>

                <!-- Trust badges -->
                <div class="anim-fade-in mt-8 flex items-center gap-6 text-gray-500 text-sm font-medium">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-shield-alt text-green-500"></i>
                        <span>SOC 2 Ready</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-lock text-green-500"></i>
                        <span>256-bit SSL</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-globe text-green-500"></i>
                        <span>Global CDN</span>
                    </div>
                </div>
            </div>

            <!-- Right — Live Terminal Mock -->
            <div class="anim-fade-in anim-float hidden lg:block">
                <div class="anim-glow rounded-2xl overflow-hidden border border-white/10 bg-gray-950/90 backdrop-blur-xl shadow-2xl">
                    <!-- Terminal header -->
                    <div class="flex items-center justify-between px-5 py-3.5 bg-white/[0.03] border-b border-white/10">
                        <div class="flex space-x-2">
                            <div class="w-3 h-3 rounded-full bg-red-500/80"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-500/80"></div>
                            <div class="w-3 h-3 rounded-full bg-green-500/80"></div>
                        </div>
                        <span class="text-[11px] font-mono text-gray-500 tracking-wider">setugeo-api — bash</span>
                        <div class="w-12"></div>
                    </div>
                    <!-- Terminal body -->
                    <div class="p-6 font-mono text-sm leading-relaxed space-y-3">
                        <div class="text-gray-500"># Fetch all cities in India</div>
                        <div><span class="text-green-400">$</span> <span class="text-amber-300">curl</span> <span class="text-gray-300">-H</span> <span class="text-emerald-300">"Authorization: Bearer sk_live_..."</span> \</div>
                        <div class="pl-6 text-sky-300">https://api.setugeo.com/v1/countries/IN/cities</div>
                        <div class="mt-4 text-gray-500"># Response (42ms)</div>
                        <div class="bg-white/[0.03] rounded-xl p-4 border border-white/5 mt-2">
                            <div class="text-gray-300">{</div>
                            <div class="pl-4"><span class="text-amber-400">"status"</span>: <span class="text-emerald-400">"success"</span>,</div>
                            <div class="pl-4"><span class="text-amber-400">"count"</span>: <span class="text-sky-400">58,274</span>,</div>
                            <div class="pl-4"><span class="text-amber-400">"data"</span>: [</div>
                            <div class="pl-8">{ <span class="text-amber-400">"name"</span>: <span class="text-emerald-400">"Mumbai"</span>, <span class="text-amber-400">"lat"</span>: <span class="text-sky-400">19.076</span>, <span class="text-amber-400">"lng"</span>: <span class="text-sky-400">72.877</span> },</div>
                            <div class="pl-8">{ <span class="text-amber-400">"name"</span>: <span class="text-emerald-400">"Delhi"</span>, <span class="text-amber-400">"lat"</span>: <span class="text-sky-400">28.614</span>, <span class="text-amber-400">"lng"</span>: <span class="text-sky-400">77.209</span> },</div>
                            <div class="pl-8 text-gray-600">// ... 58,272 more</div>
                            <div class="pl-4">]</div>
                            <div class="text-gray-300">}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!--  METRICS DASHBOARD — Animated Stats                               -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<section class="relative py-14 sm:py-20 border-t border-white/5">
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_bottom,rgba(245,158,11,0.04)_0%,transparent_60%)]"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 lg:gap-8">
            <!-- Metric 1 -->
            <div class="metric-card group bg-white/[0.04] hover:bg-white/[0.07] backdrop-blur-xl rounded-2xl p-6 lg:p-8 border border-white/5 hover:border-amber-500/30 transition-all duration-300">
                <div class="metric-icon w-12 h-12 bg-amber-500/10 rounded-xl flex items-center justify-center mb-4 transition-transform duration-300">
                    <i class="fas fa-globe-americas text-xl text-amber-500"></i>
                </div>
                <p class="text-3xl lg:text-4xl font-black text-white mb-1">200<span class="text-amber-500">+</span></p>
                <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Countries</p>
            </div>
            <!-- Metric 2 -->
            <div class="metric-card group bg-white/[0.04] hover:bg-white/[0.07] backdrop-blur-xl rounded-2xl p-6 lg:p-8 border border-white/5 hover:border-amber-500/30 transition-all duration-300">
                <div class="metric-icon w-12 h-12 bg-emerald-500/10 rounded-xl flex items-center justify-center mb-4 transition-transform duration-300">
                    <i class="fas fa-city text-xl text-emerald-500"></i>
                </div>
                <p class="text-3xl lg:text-4xl font-black text-white mb-1">150<span class="text-amber-500">K+</span></p>
                <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Cities</p>
            </div>
            <!-- Metric 3 -->
            <div class="metric-card group bg-white/[0.04] hover:bg-white/[0.07] backdrop-blur-xl rounded-2xl p-6 lg:p-8 border border-white/5 hover:border-amber-500/30 transition-all duration-300">
                <div class="metric-icon w-12 h-12 bg-sky-500/10 rounded-xl flex items-center justify-center mb-4 transition-transform duration-300">
                    <i class="fas fa-bolt text-xl text-sky-500"></i>
                </div>
                <p class="text-3xl lg:text-4xl font-black text-white mb-1"><span class="text-amber-500">&lt;</span>50<span class="text-amber-500">ms</span></p>
                <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Avg Latency</p>
            </div>
            <!-- Metric 4 -->
            <div class="metric-card group bg-white/[0.04] hover:bg-white/[0.07] backdrop-blur-xl rounded-2xl p-6 lg:p-8 border border-white/5 hover:border-amber-500/30 transition-all duration-300">
                <div class="metric-icon w-12 h-12 bg-purple-500/10 rounded-xl flex items-center justify-center mb-4 transition-transform duration-300">
                    <i class="fas fa-server text-xl text-purple-500"></i>
                </div>
                <p class="text-3xl lg:text-4xl font-black text-white mb-1">99.9<span class="text-amber-500">%</span></p>
                <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Uptime SLA</p>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!--  API ENDPOINTS — Horizontal Showcase Cards                        -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<section class="relative py-14 sm:py-20 border-t border-white/5 overflow-hidden">
    <div class="absolute inset-0 bg-[linear-gradient(to_right,#80808008_1px,transparent_1px),linear-gradient(to_bottom,#80808008_1px,transparent_1px)] bg-[size:40px_40px]"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-3xl mx-auto mb-10">
            <h2 class="text-amber-500 font-bold tracking-widest uppercase text-sm mb-3">Comprehensive API Suite</h2>
            <p class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white tracking-tight">One API. Every data point you need.</p>
            <p class="mt-4 text-lg text-gray-400 font-medium">Structured, validated JSON responses designed for plug-and-play integration.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Endpoint 1 -->
            <div class="endpoint-card bg-white/[0.04] backdrop-blur-xl rounded-2xl p-8 border border-white/5 hover:border-amber-500/30 group">
                <div class="flex items-center gap-3 mb-5">
                    <span class="px-3 py-1 rounded-lg bg-emerald-500/10 text-emerald-400 text-xs font-bold tracking-wider">GET</span>
                    <span class="font-mono text-sm text-gray-400">/v1/countries</span>
                </div>
                <h3 class="text-xl font-bold text-white mb-2 group-hover:text-amber-400 transition-colors">Countries</h3>
                <p class="text-gray-500 text-sm leading-relaxed font-medium">Full list of 200+ countries with ISO codes, dial codes, currencies, flags, and region data.</p>
            </div>
            <!-- Endpoint 2 -->
            <div class="endpoint-card bg-white/[0.04] backdrop-blur-xl rounded-2xl p-8 border border-white/5 hover:border-amber-500/30 group">
                <div class="flex items-center gap-3 mb-5">
                    <span class="px-3 py-1 rounded-lg bg-emerald-500/10 text-emerald-400 text-xs font-bold tracking-wider">GET</span>
                    <span class="font-mono text-sm text-gray-400">/v1/states/{country}</span>
                </div>
                <h3 class="text-xl font-bold text-white mb-2 group-hover:text-amber-400 transition-colors">States & Provinces</h3>
                <p class="text-gray-500 text-sm leading-relaxed font-medium">4,000+ states and provinces with coordinates, type classification, and parent relationships.</p>
            </div>
            <!-- Endpoint 3 -->
            <div class="endpoint-card bg-white/[0.04] backdrop-blur-xl rounded-2xl p-8 border border-white/5 hover:border-amber-500/30 group">
                <div class="flex items-center gap-3 mb-5">
                    <span class="px-3 py-1 rounded-lg bg-emerald-500/10 text-emerald-400 text-xs font-bold tracking-wider">GET</span>
                    <span class="font-mono text-sm text-gray-400">/v1/cities/{state}</span>
                </div>
                <h3 class="text-xl font-bold text-white mb-2 group-hover:text-amber-400 transition-colors">Cities & Towns</h3>
                <p class="text-gray-500 text-sm leading-relaxed font-medium">150K+ cities with precise lat/lng coordinates, state mappings, and metadata.</p>
            </div>
            <!-- Endpoint 4 -->
            <div class="endpoint-card bg-white/[0.04] backdrop-blur-xl rounded-2xl p-8 border border-white/5 hover:border-amber-500/30 group">
                <div class="flex items-center gap-3 mb-5">
                    <span class="px-3 py-1 rounded-lg bg-emerald-500/10 text-emerald-400 text-xs font-bold tracking-wider">GET</span>
                    <span class="font-mono text-sm text-gray-400">/v1/pincodes/{code}</span>
                </div>
                <h3 class="text-xl font-bold text-white mb-2 group-hover:text-amber-400 transition-colors">Pincodes & ZIP Codes</h3>
                <p class="text-gray-500 text-sm leading-relaxed font-medium">Millions of postal codes mapped to their cities, states, coordinates, and areas.</p>
            </div>
            <!-- Endpoint 5 -->
            <div class="endpoint-card bg-white/[0.04] backdrop-blur-xl rounded-2xl p-8 border border-white/5 hover:border-amber-500/30 group">
                <div class="flex items-center gap-3 mb-5">
                    <span class="px-3 py-1 rounded-lg bg-emerald-500/10 text-emerald-400 text-xs font-bold tracking-wider">GET</span>
                    <span class="font-mono text-sm text-gray-400">/v1/timezones</span>
                </div>
                <h3 class="text-xl font-bold text-white mb-2 group-hover:text-amber-400 transition-colors">Timezones</h3>
                <p class="text-gray-500 text-sm leading-relaxed font-medium">Accurate timezone data with GMT offsets, abbreviations, and DST information by country.</p>
            </div>
            <!-- Endpoint 6 -->
            <div class="endpoint-card bg-white/[0.04] backdrop-blur-xl rounded-2xl p-8 border border-white/5 hover:border-amber-500/30 group">
                <div class="flex items-center gap-3 mb-5">
                    <span class="px-3 py-1 rounded-lg bg-emerald-500/10 text-emerald-400 text-xs font-bold tracking-wider">GET</span>
                    <span class="font-mono text-sm text-gray-400">/v1/currencies/{code}</span>
                </div>
                <h3 class="text-xl font-bold text-white mb-2 group-hover:text-amber-400 transition-colors">Currency Conversions</h3>
                <p class="text-gray-500 text-sm leading-relaxed font-medium">Exchange rates against USD and INR, refreshed weekly from authoritative sources.</p>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!--  HOW IT WORKS — Three Steps                                       -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<section class="relative py-14 sm:py-20 border-t border-white/5">
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(245,158,11,0.03)_0%,transparent_50%)]"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-3xl mx-auto mb-10">
            <h2 class="text-amber-500 font-bold tracking-widest uppercase text-sm mb-3">Get Started in Minutes</h2>
            <p class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white tracking-tight">Three steps to location intelligence.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-8 lg:gap-12">
            <!-- Step 1 -->
            <div class="relative text-center step-connector">
                <div class="w-16 h-16 mx-auto bg-gradient-to-br from-amber-500/20 to-amber-600/10 rounded-2xl flex items-center justify-center mb-4 border border-amber-500/20">
                    <span class="text-3xl font-black text-amber-500">1</span>
                </div>
                <h3 class="text-xl font-bold text-white mb-3">Create an Account</h3>
                <p class="text-gray-500 font-medium leading-relaxed">Sign up free in seconds. No credit card required. Get instant access to your dashboard.</p>
            </div>
            <!-- Step 2 -->
            <div class="relative text-center step-connector">
                <div class="w-20 h-20 mx-auto bg-gradient-to-br from-amber-500/20 to-amber-600/10 rounded-2xl flex items-center justify-center mb-6 border border-amber-500/20">
                    <span class="text-3xl font-black text-amber-500">2</span>
                </div>
                <h3 class="text-xl font-bold text-white mb-3">Get Your API Keys</h3>
                <p class="text-gray-500 font-medium leading-relaxed">Generate your API token and secret from the dashboard. Use Bearer authentication.</p>
            </div>
            <!-- Step 3 -->
            <div class="relative text-center">
                <div class="w-20 h-20 mx-auto bg-gradient-to-br from-amber-500/20 to-amber-600/10 rounded-2xl flex items-center justify-center mb-6 border border-amber-500/20">
                    <span class="text-3xl font-black text-amber-500">3</span>
                </div>
                <h3 class="text-xl font-bold text-white mb-3">Start Querying</h3>
                <p class="text-gray-500 font-medium leading-relaxed">Make your first API call. Get structured JSON responses in under 50ms globally.</p>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!--  FEATURES — Deep Dive Grid                                        -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<section class="relative py-14 sm:py-20 border-t border-white/5 overflow-hidden">
    <div class="absolute inset-0 bg-[radial-gradient(#ffffff06_1px,transparent_1px)] [background-size:24px_24px]"></div>
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[600px] h-[300px] bg-amber-500/5 rounded-full blur-[100px]"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-3xl mx-auto mb-10">
            <h2 class="text-amber-500 font-bold tracking-widest uppercase text-sm mb-3">Why SetuGeo</h2>
            <p class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white tracking-tight">Built for developers who demand excellence.</p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-white/[0.03] rounded-2xl p-8 border border-white/5 hover:border-amber-500/20 transition-all duration-300 group">
                <div class="w-12 h-12 bg-amber-500/10 rounded-xl flex items-center justify-center mb-5 group-hover:bg-amber-500/20 transition-colors">
                    <i class="fas fa-bullseye text-lg text-amber-500"></i>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">99.9% Accuracy</h3>
                <p class="text-gray-500 text-sm leading-relaxed font-medium">Weekly updates from authoritative global sources ensure sub-millimeter coordinate precision.</p>
            </div>
            <div class="bg-white/[0.03] rounded-2xl p-8 border border-white/5 hover:border-sky-500/20 transition-all duration-300 group">
                <div class="w-12 h-12 bg-sky-500/10 rounded-xl flex items-center justify-center mb-5 group-hover:bg-sky-500/20 transition-colors">
                    <i class="fas fa-network-wired text-lg text-sky-500"></i>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Global CDN Edge</h3>
                <p class="text-gray-500 text-sm leading-relaxed font-medium">Requests routed through edge locations worldwide for consistent sub-50ms response times.</p>
            </div>
            <div class="bg-white/[0.03] rounded-2xl p-8 border border-white/5 hover:border-emerald-500/20 transition-all duration-300 group">
                <div class="w-12 h-12 bg-emerald-500/10 rounded-xl flex items-center justify-center mb-5 group-hover:bg-emerald-500/20 transition-colors">
                    <i class="fas fa-file-code text-lg text-emerald-500"></i>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Clean JSON Schemas</h3>
                <p class="text-gray-500 text-sm leading-relaxed font-medium">Consistent, well-documented JSON response structures designed for zero-config integration.</p>
            </div>
            <div class="bg-white/[0.03] rounded-2xl p-8 border border-white/5 hover:border-purple-500/20 transition-all duration-300 group">
                <div class="w-12 h-12 bg-purple-500/10 rounded-xl flex items-center justify-center mb-5 group-hover:bg-purple-500/20 transition-colors">
                    <i class="fas fa-shield-alt text-lg text-purple-500"></i>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Enterprise Security</h3>
                <p class="text-gray-500 text-sm leading-relaxed font-medium">Bearer token authentication, rate limiting, and 256-bit SSL encryption on every request.</p>
            </div>
            <div class="bg-white/[0.03] rounded-2xl p-8 border border-white/5 hover:border-pink-500/20 transition-all duration-300 group">
                <div class="w-12 h-12 bg-pink-500/10 rounded-xl flex items-center justify-center mb-5 group-hover:bg-pink-500/20 transition-colors">
                    <i class="fas fa-chart-line text-lg text-pink-500"></i>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Usage Analytics</h3>
                <p class="text-gray-500 text-sm leading-relaxed font-medium">Real-time dashboard to monitor API calls, response times, and credit consumption.</p>
            </div>
            <div class="bg-white/[0.03] rounded-2xl p-8 border border-white/5 hover:border-orange-500/20 transition-all duration-300 group">
                <div class="w-12 h-12 bg-orange-500/10 rounded-xl flex items-center justify-center mb-5 group-hover:bg-orange-500/20 transition-colors">
                    <i class="fas fa-headset text-lg text-orange-500"></i>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Priority Support</h3>
                <p class="text-gray-500 text-sm leading-relaxed font-medium">In-dashboard ticketing system with dedicated support for all subscription tiers.</p>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!--  WORKS WITH YOUR STACK                                             -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<section class="relative py-12 border-t border-white/5">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-amber-500 font-bold tracking-widest uppercase text-sm mb-8">Works with your stack</h2>
        <div class="flex flex-wrap justify-center gap-8 lg:gap-14">
            <div class="flex items-center gap-3 text-gray-500 hover:text-white transition-colors duration-300 group">
                <i class="fab fa-laravel text-3xl text-red-500/50 group-hover:text-red-500 transition-colors"></i>
                <span class="text-lg font-bold">Laravel</span>
            </div>
            <div class="flex items-center gap-3 text-gray-500 hover:text-white transition-colors duration-300 group">
                <i class="fab fa-react text-3xl text-sky-500/50 group-hover:text-sky-500 transition-colors"></i>
                <span class="text-lg font-bold">React</span>
            </div>
            <div class="flex items-center gap-3 text-gray-500 hover:text-white transition-colors duration-300 group">
                <i class="fab fa-node-js text-3xl text-green-500/50 group-hover:text-green-500 transition-colors"></i>
                <span class="text-lg font-bold">Node.js</span>
            </div>
            <div class="flex items-center gap-3 text-gray-500 hover:text-white transition-colors duration-300 group">
                <i class="fab fa-python text-3xl text-yellow-500/50 group-hover:text-yellow-500 transition-colors"></i>
                <span class="text-lg font-bold">Python</span>
            </div>
            <div class="flex items-center gap-3 text-gray-500 hover:text-white transition-colors duration-300 group">
                <i class="fab fa-vuejs text-3xl text-emerald-500/50 group-hover:text-emerald-500 transition-colors"></i>
                <span class="text-lg font-bold">Vue.js</span>
            </div>
            <div class="flex items-center gap-3 text-gray-500 hover:text-white transition-colors duration-300 group">
                <i class="fab fa-php text-3xl text-indigo-500/50 group-hover:text-indigo-500 transition-colors"></i>
                <span class="text-lg font-bold">PHP</span>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!--  TESTIMONIALS                                                      -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<section class="relative py-14 sm:py-20 border-t border-white/5 overflow-hidden">
    <div class="absolute top-1/2 left-0 w-[500px] h-[500px] bg-amber-600/5 rounded-full blur-[120px] -translate-y-1/2 -translate-x-1/2"></div>
    <div class="absolute top-1/2 right-0 w-[500px] h-[500px] bg-yellow-500/5 rounded-full blur-[120px] -translate-y-1/2 translate-x-1/2"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-3xl mx-auto mb-10">
            <h2 class="text-amber-500 font-bold tracking-widest uppercase text-sm mb-3">Trusted by Developers</h2>
            <p class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">Powering applications worldwide.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-6">
            <div class="bg-white/[0.04] backdrop-blur-xl rounded-2xl p-8 border border-white/5 hover:border-amber-500/20 transition-all duration-300">
                <div class="flex mb-4">
                    <i class="fas fa-star text-amber-500 text-sm"></i>
                    <i class="fas fa-star text-amber-500 text-sm"></i>
                    <i class="fas fa-star text-amber-500 text-sm"></i>
                    <i class="fas fa-star text-amber-500 text-sm"></i>
                    <i class="fas fa-star text-amber-500 text-sm"></i>
                </div>
                <p class="text-gray-300 text-sm leading-relaxed mb-6 font-medium italic">"SetuGeo's accuracy is unmatched. We switched from a competitor whose dataset was missing thousands of sub-regions, and we haven't looked back."</p>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-amber-600 rounded-full flex items-center justify-center text-white text-sm font-bold">JD</div>
                    <div>
                        <p class="text-white text-sm font-bold">James Dalton</p>
                        <p class="text-gray-600 text-xs">CTO, Global Commerce Inc.</p>
                    </div>
                </div>
            </div>
            <div class="bg-white/[0.04] backdrop-blur-xl rounded-2xl p-8 border border-white/5 hover:border-amber-500/20 transition-all duration-300">
                <div class="flex mb-4">
                    <i class="fas fa-star text-amber-500 text-sm"></i>
                    <i class="fas fa-star text-amber-500 text-sm"></i>
                    <i class="fas fa-star text-amber-500 text-sm"></i>
                    <i class="fas fa-star text-amber-500 text-sm"></i>
                    <i class="fas fa-star text-amber-500 text-sm"></i>
                </div>
                <p class="text-gray-300 text-sm leading-relaxed mb-6 font-medium italic">"Integrating the API was a breeze. The documentation is perfect, and the sub-50ms latency is exactly what we needed for our scale."</p>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-amber-600 rounded-full flex items-center justify-center text-white text-sm font-bold">SK</div>
                    <div>
                        <p class="text-white text-sm font-bold">Sarah Koenig</p>
                        <p class="text-gray-600 text-xs">Senior Developer, SaaS Travel</p>
                    </div>
                </div>
            </div>
            <div class="bg-white/[0.04] backdrop-blur-xl rounded-2xl p-8 border border-white/5 hover:border-amber-500/20 transition-all duration-300">
                <div class="flex mb-4">
                    <i class="fas fa-star text-amber-500 text-sm"></i>
                    <i class="fas fa-star text-amber-500 text-sm"></i>
                    <i class="fas fa-star text-amber-500 text-sm"></i>
                    <i class="fas fa-star text-amber-500 text-sm"></i>
                    <i class="fas fa-star text-amber-500 text-sm"></i>
                </div>
                <p class="text-gray-300 text-sm leading-relaxed mb-6 font-medium italic">"We power 3 million address lookups per month through SetuGeo. The pincode API alone saved us 200+ engineering hours."</p>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-amber-600 rounded-full flex items-center justify-center text-white text-sm font-bold">RM</div>
                    <div>
                        <p class="text-white text-sm font-bold">Ravi Mehta</p>
                        <p class="text-gray-600 text-xs">VP Eng, FinServ Platform</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!--  FAQ                                                               -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<section class="relative py-14 sm:py-20 border-t border-white/5" x-data="{ active: null }">
    <div class="absolute inset-0 bg-[radial-gradient(#ffffff08_1px,transparent_1px)] [background-size:20px_20px] opacity-30"></div>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center mb-10">
            <h2 class="text-amber-500 font-bold tracking-widest uppercase text-sm mb-3">FAQ</h2>
            <p class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">Questions? We've got answers.</p>
        </div>

        <div class="space-y-3">
            @forelse($faqs as $i => $item)
            <div class="bg-white/[0.03] rounded-2xl border border-white/5 overflow-hidden hover:border-amber-500/20 transition-all group">
                <button @click="active = (active === {{ $i }} ? null : {{ $i }})" class="w-full flex items-center justify-between px-6 sm:px-8 py-6 text-left">
                    <span class="text-base font-bold text-white group-hover:text-amber-400 transition-colors pr-4">{{ $item->question }}</span>
                    <i class="fas fa-plus text-amber-500 text-sm transition-transform duration-300 flex-shrink-0" :class="active === {{ $i }} ? 'rotate-45' : ''"></i>
                </button>
                <div x-show="active === {{ $i }}" x-collapse class="px-6 sm:px-8 pb-6 text-gray-400 font-medium leading-relaxed text-sm">
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
</section>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!--  FINAL CTA                                                         -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<section class="relative py-16 sm:py-24 border-t border-white/5 overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-b from-amber-500/[0.03] to-transparent"></div>
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[400px] bg-amber-500/5 rounded-full blur-[120px]"></div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <h2 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white tracking-tight mb-6">
            Ready to build with <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 to-yellow-400">SetuGeo</span>?
        </h2>
        <p class="text-lg text-gray-400 mb-8 font-medium max-w-2xl mx-auto">
            Join developers worldwide who trust SetuGeo for accurate, fast geographic data. Start with our free plan — no credit card required.
        </p>
        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <a href="{{ route('register') }}" class="inline-flex justify-center items-center px-10 py-4 text-lg font-bold rounded-2xl text-white bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-500 hover:to-amber-400 shadow-lg shadow-amber-600/20 hover:shadow-amber-500/30 transform hover:-translate-y-0.5 transition-all duration-300">
                Get Started Free
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
            <a href="{{ route('pricing') }}" class="inline-flex justify-center items-center px-10 py-4 text-lg font-bold rounded-2xl text-white bg-white/5 border border-white/10 hover:bg-white/10 transition-all duration-300">
                View Pricing
            </a>
        </div>
    </div>
</section>
@endsection
