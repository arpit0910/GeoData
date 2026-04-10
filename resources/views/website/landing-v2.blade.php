@extends('layouts.public')

@section('title', 'SetuGeo - Location Intelligence APIs for Every Developer')

@section('content')
<style>
    @keyframes gradient-x { 0%,100% { background-position:0% 50%; } 50% { background-position:100% 50%; } }
    @keyframes slideInLeft { from { opacity:0; transform:translateX(-60px); } to { opacity:1; transform:translateX(0); } }
    @keyframes slideInRight { from { opacity:0; transform:translateX(60px); } to { opacity:1; transform:translateX(0); } }
    @keyframes scaleIn { from { opacity:0; transform:scale(0.9); } to { opacity:1; transform:scale(1); } }
    @keyframes orbit { from { transform:rotate(0deg) translateX(180px) rotate(0deg); } to { transform:rotate(360deg) translateX(180px) rotate(-360deg); } }
    @keyframes shimmer { 0% { background-position:-200% 0; } 100% { background-position:200% 0; } }
    .gradient-animate { background-size:200% 200%; animation: gradient-x 8s ease infinite; }
    .anim-left { animation: slideInLeft 0.8s ease-out both; }
    .anim-left-d1 { animation: slideInLeft 0.8s 0.15s ease-out both; }
    .anim-left-d2 { animation: slideInLeft 0.8s 0.3s ease-out both; }
    .anim-right { animation: slideInRight 0.8s 0.4s ease-out both; }
    .feature-bento:hover .feature-bento-icon { transform:rotate(-8deg) scale(1.1); }
    .use-case-card { transition: all 0.4s cubic-bezier(.4,0,.2,1); }
    .use-case-card:hover { transform: translateY(-8px) scale(1.02); }
    .shimmer-border { position:relative; overflow:hidden; }
    .shimmer-border::before { content:''; position:absolute; top:-50%; left:-50%; width:200%; height:200%; background:linear-gradient(45deg,transparent 45%,rgba(245,158,11,0.08) 50%,transparent 55%); animation:shimmer 4s ease-in-out infinite; }
    .code-tab.active { background:rgba(245,158,11,0.15); color:#f59e0b; border-color:rgba(245,158,11,0.4); }
</style>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!--  HERO — Centered Dramatic with Orbiting Elements                   -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<section class="relative overflow-hidden min-h-[80vh] flex items-center justify-center">
    <!-- Animated gradient sphere -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] rounded-full bg-gradient-to-br from-amber-500/10 via-transparent to-yellow-500/5 blur-[80px] gradient-animate"></div>

    <!-- Grid overlay -->
    <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.02)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.02)_1px,transparent_1px)] bg-[size:80px_80px]"></div>

    <!-- Orbiting node decorations (desktop only) -->
    <div class="hidden lg:block absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[320px] h-[320px]">
        <div class="absolute" style="animation: orbit 20s linear infinite;">
            <div class="bg-amber-500/20 backdrop-blur-md border border-amber-500/30 rounded-xl px-3 py-2 text-[10px] font-mono text-amber-400 shadow-lg">
                <span class="text-green-400">●</span> Mumbai
            </div>
        </div>
        <div class="absolute" style="animation: orbit 20s linear infinite; animation-delay: -6.67s;">
            <div class="bg-sky-500/20 backdrop-blur-md border border-sky-500/30 rounded-xl px-3 py-2 text-[10px] font-mono text-sky-400 shadow-lg">
                <span class="text-green-400">●</span> London
            </div>
        </div>
        <div class="absolute" style="animation: orbit 20s linear infinite; animation-delay: -13.33s;">
            <div class="bg-purple-500/20 backdrop-blur-md border border-purple-500/30 rounded-xl px-3 py-2 text-[10px] font-mono text-purple-400 shadow-lg">
                <span class="text-green-400">●</span> Tokyo
            </div>
        </div>
    </div>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-20 py-14">
        <!-- Badge -->
        <div class="anim-left inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-gradient-to-r from-amber-500/10 to-yellow-500/10 border border-amber-500/20 mb-6">
            <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z"/></svg>
            <span class="text-xs font-bold tracking-widest uppercase text-amber-400">Trusted by 500+ Developers</span>
        </div>

        <h1 class="anim-left-d1 text-4xl sm:text-5xl md:text-6xl lg:text-7xl xl:text-8xl font-extrabold text-white leading-[1.05] tracking-tight mb-6">
            Turn Location into<br>
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 via-yellow-300 to-amber-500 gradient-animate">Intelligence</span>
        </h1>

        <p class="anim-left-d2 text-lg lg:text-xl text-gray-400 max-w-2xl mx-auto mb-8 font-medium leading-relaxed">
            SetuGeo provides structured, real-time geographic data across 200+ countries. Countries, states, cities, pincodes, timezones, and currencies — all from a single, lightning-fast API.
        </p>

        <div class="anim-right flex flex-col sm:flex-row justify-center gap-4 mb-10">
            <a href="{{ route('register') }}" class="group inline-flex justify-center items-center px-10 py-4 text-base font-bold rounded-2xl text-black bg-gradient-to-r from-amber-400 to-yellow-400 hover:from-amber-300 hover:to-yellow-300 shadow-lg shadow-amber-500/20 transform hover:-translate-y-0.5 transition-all duration-300">
                Start for Free
                <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
            <a href="{{ route('docs') }}" class="inline-flex justify-center items-center px-10 py-4 text-base font-bold rounded-2xl text-gray-300 bg-white/5 border border-white/10 hover:bg-white/10 hover:text-white transition-all duration-300">
                <i class="fas fa-play-circle mr-2 text-amber-500"></i>
                Explore API Docs
            </a>
        </div>

        <!-- Mini stats row -->
        <div class="flex flex-wrap justify-center gap-8 text-center">
            <div>
                <p class="text-2xl sm:text-3xl font-black text-white">200<span class="text-amber-500">+</span></p>
                <p class="text-xs font-bold text-gray-600 uppercase tracking-wider mt-1">Countries</p>
            </div>
            <div class="w-px h-12 bg-white/10 self-center hidden sm:block"></div>
            <div>
                <p class="text-2xl sm:text-3xl font-black text-white">4K<span class="text-amber-500">+</span></p>
                <p class="text-xs font-bold text-gray-600 uppercase tracking-wider mt-1">States</p>
            </div>
            <div class="w-px h-12 bg-white/10 self-center hidden sm:block"></div>
            <div>
                <p class="text-2xl sm:text-3xl font-black text-white">150K<span class="text-amber-500">+</span></p>
                <p class="text-xs font-bold text-gray-600 uppercase tracking-wider mt-1">Cities</p>
            </div>
            <div class="w-px h-12 bg-white/10 self-center hidden sm:block"></div>
            <div>
                <p class="text-2xl sm:text-3xl font-black text-white">&lt;50<span class="text-amber-500">ms</span></p>
                <p class="text-xs font-bold text-gray-600 uppercase tracking-wider mt-1">Latency</p>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!--  MULTI-LANGUAGE CODE SAMPLE — Tabbed                               -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<section class="relative py-14 sm:py-20 border-t border-white/5" x-data="{ tab: 'curl' }">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-3xl mx-auto mb-8">
            <h2 class="text-amber-500 font-bold tracking-widest uppercase text-sm mb-3">Developer First</h2>
            <p class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">Integrate in minutes, not days.</p>
            <p class="mt-4 text-gray-400 font-medium">Pick your language. Make a request. Get structured JSON back.</p>
        </div>

        <!-- Tabs -->
        <div class="flex justify-center gap-2 mb-6">
            <button @click="tab='curl'" :class="tab==='curl' ? 'active' : ''" class="code-tab px-4 py-2 rounded-xl text-sm font-bold text-gray-400 border border-white/5 hover:border-white/20 transition-all">cURL</button>
            <button @click="tab='js'" :class="tab==='js' ? 'active' : ''" class="code-tab px-4 py-2 rounded-xl text-sm font-bold text-gray-400 border border-white/5 hover:border-white/20 transition-all">JavaScript</button>
            <button @click="tab='python'" :class="tab==='python' ? 'active' : ''" class="code-tab px-4 py-2 rounded-xl text-sm font-bold text-gray-400 border border-white/5 hover:border-white/20 transition-all">Python</button>
            <button @click="tab='php'" :class="tab==='php' ? 'active' : ''" class="code-tab px-4 py-2 rounded-xl text-sm font-bold text-gray-400 border border-white/5 hover:border-white/20 transition-all">PHP</button>
        </div>

        <!-- Code blocks -->
        <div class="bg-gray-950/80 backdrop-blur-xl rounded-2xl border border-white/10 overflow-hidden shadow-2xl">
            <div class="flex items-center px-5 py-3 bg-white/[0.02] border-b border-white/5">
                <div class="flex space-x-2">
                    <div class="w-3 h-3 rounded-full bg-red-500/60"></div>
                    <div class="w-3 h-3 rounded-full bg-yellow-500/60"></div>
                    <div class="w-3 h-3 rounded-full bg-green-500/60"></div>
                </div>
                <span class="ml-4 text-[11px] font-mono text-gray-600" x-text="tab === 'curl' ? 'terminal' : tab === 'js' ? 'app.js' : tab === 'python' ? 'main.py' : 'index.php'"></span>
            </div>
            <div class="p-6 font-mono text-sm leading-relaxed overflow-x-auto">
                <!-- cURL -->
                <div x-show="tab==='curl'" class="text-gray-300 space-y-1">
                    <div><span class="text-green-400">$</span> <span class="text-amber-300">curl</span> -s -X GET \</div>
                    <div class="pl-4"><span class="text-sky-300">"https://api.setugeo.com/v1/countries/IN/states"</span> \</div>
                    <div class="pl-4">-H <span class="text-emerald-300">"Authorization: Bearer YOUR_API_TOKEN"</span> \</div>
                    <div class="pl-4">-H <span class="text-emerald-300">"Accept: application/json"</span> | <span class="text-amber-300">jq</span> <span class="text-emerald-300">'.'</span></div>
                </div>
                <!-- JavaScript -->
                <div x-show="tab==='js'" x-cloak class="text-gray-300 space-y-1">
                    <div><span class="text-purple-400">const</span> <span class="text-sky-300">response</span> = <span class="text-purple-400">await</span> <span class="text-amber-300">fetch</span>(</div>
                    <div class="pl-4"><span class="text-emerald-300">'https://api.setugeo.com/v1/countries/IN/states'</span>,</div>
                    <div class="pl-4">{</div>
                    <div class="pl-8"><span class="text-amber-300">headers</span>: {</div>
                    <div class="pl-12"><span class="text-emerald-300">'Authorization'</span>: <span class="text-emerald-300">`Bearer ${<span class="text-sky-300">API_TOKEN</span>}`</span>,</div>
                    <div class="pl-12"><span class="text-emerald-300">'Accept'</span>: <span class="text-emerald-300">'application/json'</span></div>
                    <div class="pl-8">}</div>
                    <div class="pl-4">}</div>
                    <div>);</div>
                    <div class="mt-2"><span class="text-purple-400">const</span> <span class="text-sky-300">states</span> = <span class="text-purple-400">await</span> response.<span class="text-amber-300">json</span>();</div>
                    <div><span class="text-sky-300">console</span>.<span class="text-amber-300">log</span>(states.<span class="text-sky-300">data</span>); <span class="text-gray-600">// Array of 36 states</span></div>
                </div>
                <!-- Python -->
                <div x-show="tab==='python'" x-cloak class="text-gray-300 space-y-1">
                    <div><span class="text-purple-400">import</span> <span class="text-sky-300">requests</span></div>
                    <div class="mt-2"><span class="text-sky-300">response</span> = requests.<span class="text-amber-300">get</span>(</div>
                    <div class="pl-4"><span class="text-emerald-300">"https://api.setugeo.com/v1/countries/IN/states"</span>,</div>
                    <div class="pl-4">headers={</div>
                    <div class="pl-8"><span class="text-emerald-300">"Authorization"</span>: <span class="text-emerald-300">f"Bearer {API_TOKEN}"</span>,</div>
                    <div class="pl-8"><span class="text-emerald-300">"Accept"</span>: <span class="text-emerald-300">"application/json"</span></div>
                    <div class="pl-4">}</div>
                    <div>)</div>
                    <div class="mt-2"><span class="text-sky-300">states</span> = response.<span class="text-amber-300">json</span>()[<span class="text-emerald-300">"data"</span>]</div>
                    <div><span class="text-amber-300">print</span>(<span class="text-emerald-300">f"Found {<span class="text-amber-300">len</span>(states)} states"</span>)</div>
                </div>
                <!-- PHP -->
                <div x-show="tab==='php'" x-cloak class="text-gray-300 space-y-1">
                    <div><span class="text-sky-300">$response</span> = <span class="text-amber-300">Http</span>::<span class="text-amber-300">withHeaders</span>([</div>
                    <div class="pl-4"><span class="text-emerald-300">'Authorization'</span> => <span class="text-emerald-300">'Bearer '</span> . <span class="text-sky-300">$apiToken</span>,</div>
                    <div class="pl-4"><span class="text-emerald-300">'Accept'</span> => <span class="text-emerald-300">'application/json'</span>,</div>
                    <div>])-><span class="text-amber-300">get</span>(</div>
                    <div class="pl-4"><span class="text-emerald-300">'https://api.setugeo.com/v1/countries/IN/states'</span></div>
                    <div>);</div>
                    <div class="mt-2"><span class="text-sky-300">$states</span> = <span class="text-sky-300">$response</span>-><span class="text-amber-300">json</span>(<span class="text-emerald-300">'data'</span>);</div>
                    <div><span class="text-gray-600">// Returns array of 36 Indian states</span></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!--  BENTO FEATURES GRID                                               -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<section class="relative py-14 sm:py-20 border-t border-white/5 overflow-hidden">
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(245,158,11,0.04)_0%,transparent_50%)]"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-3xl mx-auto mb-10">
            <h2 class="text-amber-500 font-bold tracking-widest uppercase text-sm mb-3">Platform Features</h2>
            <p class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white tracking-tight">Everything a location-aware app needs.</p>
        </div>

        <!-- Bento Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Large Feature Card -->
            <div class="feature-bento lg:col-span-2 bg-gradient-to-br from-white/[0.06] to-white/[0.02] rounded-3xl p-10 border border-white/5 hover:border-amber-500/20 transition-all duration-300 group">
                <div class="flex flex-col lg:flex-row gap-8 items-start">
                    <div class="feature-bento-icon w-16 h-16 bg-amber-500/10 rounded-2xl flex items-center justify-center flex-shrink-0 transition-transform duration-300">
                        <i class="fas fa-database text-2xl text-amber-500"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-white mb-3">Comprehensive Geographic Database</h3>
                        <p class="text-gray-400 leading-relaxed font-medium">
                            Access the world's most complete geographic dataset — 200+ countries, 4,000+ states, 150K+ cities, millions of pincodes, timezones, and weekly-refreshed currency rates. All validated, structured, and ready for production.
                        </p>
                        <div class="flex flex-wrap gap-2 mt-5">
                            <span class="px-3 py-1 rounded-lg bg-white/5 text-xs font-bold text-gray-400 border border-white/5">Countries</span>
                            <span class="px-3 py-1 rounded-lg bg-white/5 text-xs font-bold text-gray-400 border border-white/5">States</span>
                            <span class="px-3 py-1 rounded-lg bg-white/5 text-xs font-bold text-gray-400 border border-white/5">Cities</span>
                            <span class="px-3 py-1 rounded-lg bg-white/5 text-xs font-bold text-gray-400 border border-white/5">Pincodes</span>
                            <span class="px-3 py-1 rounded-lg bg-white/5 text-xs font-bold text-gray-400 border border-white/5">Timezones</span>
                            <span class="px-3 py-1 rounded-lg bg-white/5 text-xs font-bold text-gray-400 border border-white/5">Currencies</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Small Card -->
            <div class="feature-bento bg-white/[0.04] rounded-3xl p-8 border border-white/5 hover:border-sky-500/20 transition-all duration-300 group">
                <div class="feature-bento-icon w-14 h-14 bg-sky-500/10 rounded-2xl flex items-center justify-center mb-5 transition-transform duration-300">
                    <i class="fas fa-bolt text-xl text-sky-500"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">Sub-50ms</h3>
                <p class="text-gray-500 text-sm leading-relaxed font-medium">Every response delivered at edge speed through our global CDN infrastructure.</p>
            </div>

            <!-- Small Card -->
            <div class="feature-bento bg-white/[0.04] rounded-3xl p-8 border border-white/5 hover:border-emerald-500/20 transition-all duration-300 group">
                <div class="feature-bento-icon w-14 h-14 bg-emerald-500/10 rounded-2xl flex items-center justify-center mb-5 transition-transform duration-300">
                    <i class="fas fa-shield-alt text-xl text-emerald-500"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">Secure by Default</h3>
                <p class="text-gray-500 text-sm leading-relaxed font-medium">Bearer token auth, rate limiting, IP whitelisting, and 256-bit SSL on every request.</p>
            </div>

            <!-- Small Card -->
            <div class="feature-bento bg-white/[0.04] rounded-3xl p-8 border border-white/5 hover:border-purple-500/20 transition-all duration-300 group">
                <div class="feature-bento-icon w-14 h-14 bg-purple-500/10 rounded-2xl flex items-center justify-center mb-5 transition-transform duration-300">
                    <i class="fas fa-chart-bar text-xl text-purple-500"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">Real-Time Dashboard</h3>
                <p class="text-gray-500 text-sm leading-relaxed font-medium">Monitor API usage, response times, errors, and credit consumption in a rich admin panel.</p>
            </div>

            <!-- Large Feature Card -->
            <div class="feature-bento lg:col-span-3 bg-gradient-to-r from-amber-500/[0.06] via-transparent to-yellow-500/[0.04] rounded-3xl p-10 border border-white/5 hover:border-amber-500/20 transition-all duration-300">
                <div class="flex flex-col lg:flex-row items-center justify-between gap-8">
                    <div class="max-w-xl">
                        <h3 class="text-2xl font-bold text-white mb-3">99.9% Uptime Guarantee</h3>
                        <p class="text-gray-400 leading-relaxed font-medium">
                            We back our infrastructure with an industry-leading SLA. Redundant systems, automatic failover, and 24/7 monitoring ensure your applications never lose access to geographic data.
                        </p>
                    </div>
                    <div class="flex items-center gap-4 flex-shrink-0">
                        <div class="text-center px-6 py-4 bg-white/5 rounded-2xl border border-white/5">
                            <p class="text-3xl font-black text-emerald-500">99.9%</p>
                            <p class="text-xs font-bold text-gray-500 mt-1 uppercase">Uptime</p>
                        </div>
                        <div class="text-center px-6 py-4 bg-white/5 rounded-2xl border border-white/5">
                            <p class="text-3xl font-black text-amber-500">&lt;1s</p>
                            <p class="text-xs font-bold text-gray-500 mt-1 uppercase">Recovery</p>
                        </div>
                        <div class="text-center px-6 py-4 bg-white/5 rounded-2xl border border-white/5">
                            <p class="text-3xl font-black text-sky-500">24/7</p>
                            <p class="text-xs font-bold text-gray-500 mt-1 uppercase">Monitored</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!--  USE CASES                                                         -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<section class="relative py-14 sm:py-20 border-t border-white/5">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-3xl mx-auto mb-10">
            <h2 class="text-amber-500 font-bold tracking-widest uppercase text-sm mb-3">Use Cases</h2>
            <p class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white tracking-tight">Built for every industry.</p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-5">
            <div class="use-case-card shimmer-border bg-white/[0.03] rounded-2xl p-7 border border-white/5 hover:border-amber-500/20 text-center group">
                <div class="w-14 h-14 mx-auto bg-amber-500/10 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-amber-500/20 transition-colors">
                    <i class="fas fa-shopping-cart text-xl text-amber-500"></i>
                </div>
                <h3 class="text-base font-bold text-white mb-2">E-Commerce</h3>
                <p class="text-gray-500 text-xs leading-relaxed font-medium">Auto-fill addresses, validate pincodes, calculate geographic zones globally.</p>
            </div>
            <div class="use-case-card shimmer-border bg-white/[0.03] rounded-2xl p-7 border border-white/5 hover:border-sky-500/20 text-center group">
                <div class="w-14 h-14 mx-auto bg-sky-500/10 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-sky-500/20 transition-colors">
                    <i class="fas fa-truck text-xl text-sky-500"></i>
                </div>
                <h3 class="text-base font-bold text-white mb-2">Commerce</h3>
                <p class="text-gray-500 text-xs leading-relaxed font-medium">Route planning, delivery zone mapping, and warehouse coverage analysis.</p>
            </div>
            <div class="use-case-card shimmer-border bg-white/[0.03] rounded-2xl p-7 border border-white/5 hover:border-emerald-500/20 text-center group">
                <div class="w-14 h-14 mx-auto bg-emerald-500/10 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-emerald-500/20 transition-colors">
                    <i class="fas fa-university text-xl text-emerald-500"></i>
                </div>
                <h3 class="text-base font-bold text-white mb-2">FinTech</h3>
                <p class="text-gray-500 text-xs leading-relaxed font-medium">KYC verification, bank branch lookup, currency conversions at scale.</p>
            </div>
            <div class="use-case-card shimmer-border bg-white/[0.03] rounded-2xl p-7 border border-white/5 hover:border-purple-500/20 text-center group">
                <div class="w-14 h-14 mx-auto bg-purple-500/10 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-purple-500/20 transition-colors">
                    <i class="fas fa-plane text-xl text-purple-500"></i>
                </div>
                <h3 class="text-base font-bold text-white mb-2">Travel</h3>
                <p class="text-gray-500 text-xs leading-relaxed font-medium">Destination search, timezone management, and location-based recommendations.</p>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!--  TESTIMONIALS — Card Carousel                                      -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<section class="relative py-14 sm:py-20 border-t border-white/5 overflow-hidden">
    <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-amber-500/30 to-transparent"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-3xl mx-auto mb-10">
            <h2 class="text-amber-500 font-bold tracking-widest uppercase text-sm mb-3">What Developers Say</h2>
            <p class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">Loved by teams around the world.</p>
        </div>

        <div class="grid md:grid-cols-2 gap-6 max-w-5xl mx-auto">
            <div class="bg-white/[0.04] backdrop-blur-xl rounded-2xl p-8 border border-white/5 hover:border-amber-500/20 transition-all duration-300 relative">
                <i class="fas fa-quote-left text-amber-500/20 text-4xl absolute top-6 right-6"></i>
                <p class="text-gray-300 leading-relaxed mb-8 font-medium relative z-10">
                    "SetuGeo's accuracy is unmatched. We switched from a competitor whose dataset was missing thousands of sub-regions, and we haven't looked back. The API is fast and the docs are crystal clear."
                </p>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-amber-600 rounded-full flex items-center justify-center text-white font-bold">JD</div>
                    <div>
                        <p class="text-white font-bold">James Dalton</p>
                        <p class="text-gray-500 text-sm">CTO, Global Commerce Inc.</p>
                    </div>
                </div>
            </div>
            <div class="bg-white/[0.04] backdrop-blur-xl rounded-2xl p-8 border border-white/5 hover:border-amber-500/20 transition-all duration-300 relative">
                <i class="fas fa-quote-left text-amber-500/20 text-4xl absolute top-6 right-6"></i>
                <p class="text-gray-300 leading-relaxed mb-8 font-medium relative z-10">
                    "Integrating the API was a breeze. The documentation is perfect, and the sub-50ms latency is exactly what we needed for our scale. Best geo data provider we've used."
                </p>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-amber-600 rounded-full flex items-center justify-center text-white font-bold">SK</div>
                    <div>
                        <p class="text-white font-bold">Sarah Koenig</p>
                        <p class="text-gray-500 text-sm">Senior Developer, SaaS Travel</p>
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
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center mb-10">
            <h2 class="text-amber-500 font-bold tracking-widest uppercase text-sm mb-3">FAQ</h2>
            <p class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">Frequently asked questions.</p>
        </div>

        <div class="space-y-3">
            @forelse($faqs as $i => $item)
            <div class="bg-white/[0.03] rounded-2xl border border-white/5 overflow-hidden hover:border-amber-500/20 transition-all group">
                <button @click="active = (active === {{ $i }} ? null : {{ $i }})" class="w-full flex items-center justify-between px-6 sm:px-8 py-6 text-left">
                    <span class="text-base font-bold text-white group-hover:text-amber-400 transition-colors pr-4">{{ $item->question }}</span>
                    <svg class="w-5 h-5 text-amber-500 transition-transform duration-300 flex-shrink-0" :class="active === {{ $i }} ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
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
<!--  CTA — Gradient Banner                                             -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<section class="relative py-16 sm:py-24 border-t border-white/5 overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-amber-500/[0.06] via-transparent to-yellow-500/[0.04]"></div>
    <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-amber-500/40 to-transparent"></div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="bg-gradient-to-br from-white/[0.06] to-white/[0.02] rounded-3xl p-12 sm:p-16 border border-white/10 text-center backdrop-blur-xl shadow-2xl">
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white tracking-tight mb-5">
                Start building with SetuGeo today.
            </h2>
            <p class="text-gray-400 mb-8 max-w-xl mx-auto font-medium text-lg">
                Free plan available. No credit card required. Get your API keys in under 60 seconds.
            </p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="{{ route('register') }}" class="inline-flex justify-center items-center px-10 py-4 text-base font-bold rounded-2xl text-black bg-gradient-to-r from-amber-400 to-yellow-400 hover:from-amber-300 hover:to-yellow-300 shadow-lg shadow-amber-500/20 transform hover:-translate-y-0.5 transition-all duration-300">
                    Create Free Account
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
                <a href="{{ route('pricing') }}" class="inline-flex justify-center items-center px-10 py-4 text-base font-bold rounded-2xl text-gray-300 bg-white/5 border border-white/10 hover:bg-white/10 hover:text-white transition-all duration-300">
                    Compare Plans
                </a>
            </div>
        </div>
    </div>
</section>
@endsection
