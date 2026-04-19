@extends('layouts.public')

@section('title', 'SetuGeo - The Ultimate Geographic Data API for Developers')
@section('meta_description', 'SetuGeo is a powerful geographic data API platform providing instant access to global countries, states, cities, pincodes (ZIP codes), timezones, and bank IFSC details with sub-50ms latency.')
@section('meta_keywords', 'geographic data api, pincode lookup api, indian pincode api, global cities database, state list api, country list api, bank ifsc api, local currency converter api, developer location api')

@section('structured_data')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "SoftwareApplication",
  "name": "SetuGeo API",
  "operatingSystem": "All",
  "applicationCategory": "DeveloperApplication",
  "offers": {
    "@type": "Offer",
    "price": "0.00",
    "priceCurrency": "INR"
  },
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "4.9",
    "ratingCount": "1240"
  },
  "description": "SetuGeo provides high-speed, accurate geographic data APIs for developers including country, state, city, pincode, and currency data."
}
</script>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "SetuGeo",
  "url": "{{ route('home') }}",
  "logo": "{{ asset('assets/img/logo.png') }}",
  "sameAs": [
    "https://twitter.com/setugeo",
    "https://github.com/setugeo",
    "https://linkedin.com/company/setugeo"
  ]
}
</script>
@endsection

@section('content')
<style>
    @keyframes reveal { from { opacity:0; transform:translateY(30px) scale(0.98); } to { opacity:1; transform:translateY(0) scale(1); } }
    @keyframes draw-line { from { stroke-dashoffset: 1000; } to { stroke-dashoffset: 0; } }
    @keyframes ticker { 0% { transform:translateX(0); } 100% { transform:translateX(-50%); } }
    @keyframes ping-slow { 0%,100% { transform:scale(1); opacity:0.6; } 50% { transform:scale(1.5); opacity:0; } }
    .anim-reveal { animation: reveal 0.7s ease-out both; }
    .anim-reveal-d1 { animation: reveal 0.7s 0.1s ease-out both; }
    .anim-reveal-d2 { animation: reveal 0.7s 0.2s ease-out both; }
    .anim-reveal-d3 { animation: reveal 0.7s 0.3s ease-out both; }
    .anim-reveal-d4 { animation: reveal 0.7s 0.4s ease-out both; }
    .comparison-row:hover { background: rgba(255,255,255,0.02); }
    .pricing-card-glow { position:relative; }
    .pricing-card-glow::before { content:''; position:absolute; inset:-1px; border-radius:1.5rem; padding:1px; background:linear-gradient(135deg,rgba(245,158,11,0.3),transparent 50%,rgba(245,158,11,0.15)); -webkit-mask:linear-gradient(#fff 0 0) content-box,linear-gradient(#fff 0 0); -webkit-mask-composite:xor; mask-composite:exclude; pointer-events:none; }
    .ticker-track { display:flex; width:fit-content; animation:ticker 30s linear infinite; }
    .ticker-track:hover { animation-play-state:paused; }
    .api-endpoint-row { transition: all 0.2s ease; }
    .api-endpoint-row:hover { background: rgba(245,158,11,0.04); transform:translateX(4px); }
    .hero-gradient-text { background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 40%, #fcd34d 60%, #f59e0b 100%); background-size:200% auto; -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
</style>

<section class="relative overflow-hidden min-h-[90vh] lg:min-h-screen flex items-center pt-10 lg:pt-0">
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute top-[10%] left-[5%] w-[500px] h-[500px] bg-amber-500/[0.07] rounded-full blur-[100px]"></div>
        <div class="absolute bottom-[10%] right-[10%] w-[400px] h-[400px] bg-yellow-500/[0.05] rounded-full blur-[100px]"></div>
        <div class="absolute inset-0 bg-[radial-gradient(#ffffff03_1px,transparent_1px)] [background-size:32px_32px]"></div>
    </div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-20 w-full pt-6 sm:pt-20 lg:pt-24 pb-14">
        <div class="grid lg:grid-cols-12 gap-10 lg:gap-8 items-center">
            <div class="lg:col-span-7">
                <h1 class="anim-reveal-d1 text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white leading-[1.1] sm:leading-[1.06] tracking-tight mb-8">
                    Power your apps with the world's
                    <span class="hero-gradient-text break-words">most precise</span>
                    location data
                </h1>

                <p class="anim-reveal-d2 text-lg lg:text-xl text-gray-400 max-w-xl mb-8 font-medium leading-relaxed">
                    One unified API for countries, states, cities, pincodes, timezones, bank branches, and currency conversions. Trusted infrastructure for production workloads.
                </p>

                <div class="anim-reveal-d3 flex flex-wrap gap-4">
                    <a href="{{ route('register') }}" class="group inline-flex items-center px-8 py-4 text-base font-bold rounded-2xl text-white bg-amber-600 hover:bg-amber-500 shadow-lg shadow-amber-600/20 hover:shadow-amber-500/30 transform hover:-translate-y-0.5 transition-all duration-300">
                        Get Your API Key
                        <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                    <a href="{{ route('pricing') }}" class="inline-flex items-center px-8 py-4 text-base font-bold rounded-2xl text-gray-300 border border-white/10 hover:bg-white/5 hover:border-white/20 transition-all duration-300">
                        View Plans & Pricing
                    </a>
                </div>

                <div class="anim-reveal-d4 mt-10 pt-6 border-t border-white/5">
                    <p class="text-xs font-bold text-gray-600 uppercase tracking-widest mb-4">Trusted by developers at</p>
                    <div class="flex flex-wrap items-center gap-6 text-gray-600">
                        <span class="text-lg font-black tracking-tight">LogiFlow</span>
                        <span class="text-lg font-black tracking-tight">FinServ</span>
                        <span class="text-lg font-black tracking-tight">CloudScale</span>
                        <span class="text-lg font-black tracking-tight">TravelStack</span>
                        <span class="text-lg font-black tracking-tight">DataBridge</span>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-5 anim-reveal-d3 hidden lg:block">
                <div class="space-y-4">
                    <div class="bg-gray-900/60 backdrop-blur-2xl rounded-2xl p-6 border border-white/10 hover:border-amber-500/30 transition-all duration-300 shadow-xl group">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-amber-500/10 rounded-xl flex items-center justify-center">
                                    <i class="fas fa-globe text-amber-500"></i>
                                </div>
                                <div>
                                    <p class="text-white text-sm font-bold">Countries API</p>
                                    <p class="text-gray-400 text-xs font-mono">/v1/countries</p>
                                </div>
                            </div>
                            <span class="text-[10px] font-bold text-emerald-400 bg-emerald-500/20 px-2 py-1 rounded-lg">LIVE</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-300 font-medium">India → 36 States → 150K+ Cities</span>
                            <span class="text-amber-500 font-bold">18ms</span>
                        </div>
                    </div>

                    <div class="bg-gray-900/60 backdrop-blur-2xl rounded-2xl p-6 border border-white/10 hover:border-sky-500/30 transition-all duration-300 shadow-xl group">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-sky-500/10 rounded-xl flex items-center justify-center">
                                    <i class="fas fa-map-pin text-sky-500"></i>
                                </div>
                                <div>
                                    <p class="text-white text-sm font-bold">Pincode Lookup</p>
                                    <p class="text-gray-400 text-xs font-mono">/v1/pincodes/400001</p>
                                </div>
                            </div>
                            <span class="text-[10px] font-bold text-emerald-400 bg-emerald-500/20 px-2 py-1 rounded-lg">LIVE</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-300 font-medium">Mumbai, Maharashtra, India</span>
                            <span class="text-sky-500 font-bold">12ms</span>
                        </div>
                    </div>

                    <div class="bg-gray-900/60 backdrop-blur-2xl rounded-2xl p-6 border border-white/10 hover:border-purple-500/30 transition-all duration-300 shadow-xl group">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-purple-500/10 rounded-xl flex items-center justify-center">
                                    <i class="fas fa-exchange-alt text-purple-500"></i>
                                </div>
                                <div>
                                    <p class="text-white text-sm font-bold">Currency Rates</p>
                                    <p class="text-gray-400 text-xs font-mono">/v1/currencies/USD</p>
                                </div>
                            </div>
                            <span class="text-[10px] font-bold text-emerald-400 bg-emerald-500/20 px-2 py-1 rounded-lg">LIVE</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-300 font-medium">1 USD = ₹83.25 INR</span>
                            <span class="text-purple-500 font-bold">22ms</span>
                        </div>
                    </div>

                    <div class="bg-gray-900/60 backdrop-blur-2xl rounded-2xl p-6 border border-white/10 hover:border-emerald-500/30 transition-all duration-300 shadow-xl group">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-emerald-500/10 rounded-xl flex items-center justify-center">
                                    <i class="fas fa-chart-line text-emerald-500"></i>
                                </div>
                                <div>
                                    <p class="text-white text-sm font-bold">Equity Intelligence</p>
                                    <p class="text-gray-400 text-xs font-mono">/v1/equities</p>
                                </div>
                            </div>
                            <span class="text-[10px] font-bold text-emerald-400 bg-emerald-500/20 px-2 py-1 rounded-lg">LIVE</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-300 font-medium font-inter tracking-tight flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> 
                                Data by Market Cap & Industry
                            </span>
                            <span class="text-emerald-500 font-bold">14ms</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="py-10 border-y border-white/5 overflow-hidden">
    <div class="ticker-track">
        @for($t = 0; $t < 2; $t++)
        <div class="flex items-center gap-14 px-7 whitespace-nowrap">
            <span class="flex items-center gap-2 text-gray-600 font-bold text-lg"><i class="fab fa-laravel text-2xl text-red-500/50"></i> Laravel</span>
            <span class="flex items-center gap-2 text-gray-600 font-bold text-lg"><i class="fab fa-react text-2xl text-sky-500/50"></i> React</span>
            <span class="flex items-center gap-2 text-gray-600 font-bold text-lg"><i class="fab fa-node-js text-2xl text-green-500/50"></i> Node.js</span>
            <span class="flex items-center gap-2 text-gray-600 font-bold text-lg"><i class="fab fa-python text-2xl text-yellow-500/50"></i> Python</span>
            <span class="flex items-center gap-2 text-gray-600 font-bold text-lg"><i class="fab fa-vuejs text-2xl text-emerald-500/50"></i> Vue</span>
            <span class="flex items-center gap-2 text-gray-600 font-bold text-lg"><i class="fab fa-angular text-2xl text-red-500/50"></i> Angular</span>
            <span class="flex items-center gap-2 text-gray-600 font-bold text-lg"><i class="fab fa-golang text-2xl text-sky-500/50"></i> Go</span>
            <span class="flex items-center gap-2 text-gray-600 font-bold text-lg"><i class="fab fa-java text-2xl text-orange-500/50"></i> Java</span>
            <span class="flex items-center gap-2 text-gray-600 font-bold text-lg"><i class="fab fa-swift text-2xl text-orange-500/50"></i> Swift</span>
            <span class="flex items-center gap-2 text-gray-600 font-bold text-lg"><i class="fab fa-php text-2xl text-indigo-500/50"></i> PHP</span>
        </div>
        @endfor
    </div>
</section>

<section class="relative py-14 sm:py-20 overflow-hidden">
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_bottom_left,rgba(245,158,11,0.04)_0%,transparent_50%)]"></div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid lg:grid-cols-5 gap-12 items-start">
            <div class="lg:col-span-2 lg:sticky lg:top-28">
                <h2 class="text-amber-500 font-bold tracking-widest uppercase text-sm mb-3">API Reference</h2>
                <p class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight mb-4 break-words">Complete endpoint directory.</p>
                <p class="text-gray-400 font-medium leading-relaxed mb-8">
                    Well-documented RESTful endpoints returning clean JSON. Integrate with any language or framework using standard HTTP calls.
                </p>
                <a href="{{ route('docs') }}" class="inline-flex items-center text-amber-500 font-bold hover:text-amber-400 transition-colors group">
                    View Full Documentation
                    <svg class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
            </div>

            <div class="lg:col-span-3 space-y-3">
                <div class="api-endpoint-row flex items-start sm:items-center gap-3 sm:gap-4 bg-white/[0.03] rounded-xl p-4 sm:p-5 border border-white/5 cursor-default">
                    <span class="px-2 sm:px-3 py-1 rounded-lg bg-emerald-500/10 text-emerald-400 text-[10px] sm:text-xs font-bold tracking-wider flex-shrink-0 w-12 sm:w-16 text-center mt-0.5 sm:mt-0">GET</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-white font-bold text-sm break-all leading-tight mb-0.5">/v1/countries</p>
                        <p class="text-gray-600 text-xs whitespace-normal leading-relaxed">List all countries with ISO codes, dial codes, regions, flags</p>
                    </div>
                    <span class="text-amber-500/50 text-[10px] sm:text-xs font-mono flex-shrink-0 mt-1 sm:mt-0">~15ms</span>
                </div>

                <div class="api-endpoint-row flex items-start sm:items-center gap-3 sm:gap-4 bg-white/[0.03] rounded-xl p-4 sm:p-5 border border-white/5 cursor-default">
                    <span class="px-2 sm:px-3 py-1 rounded-lg bg-emerald-500/10 text-emerald-400 text-[10px] sm:text-xs font-bold tracking-wider flex-shrink-0 w-12 sm:w-16 text-center mt-0.5 sm:mt-0">GET</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-white font-bold text-sm break-all leading-tight mb-0.5">/v1/countries/{iso}/states</p>
                        <p class="text-gray-600 text-xs whitespace-normal leading-relaxed">States/provinces by country with coordinates</p>
                    </div>
                    <span class="text-amber-500/50 text-[10px] sm:text-xs font-mono flex-shrink-0 mt-1 sm:mt-0">~18ms</span>
                </div>

                <div class="api-endpoint-row flex items-start sm:items-center gap-3 sm:gap-4 bg-white/[0.03] rounded-xl p-4 sm:p-5 border border-white/5 cursor-default">
                    <span class="px-2 sm:px-3 py-1 rounded-lg bg-emerald-500/10 text-emerald-400 text-[10px] sm:text-xs font-bold tracking-wider flex-shrink-0 w-12 sm:w-16 text-center mt-0.5 sm:mt-0">GET</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-white font-bold text-sm break-all leading-tight mb-0.5">/v1/states/{id}/cities</p>
                        <p class="text-gray-600 text-xs whitespace-normal leading-relaxed">Cities by state with lat/lng coordinates</p>
                    </div>
                    <span class="text-amber-500/50 text-[10px] sm:text-xs font-mono flex-shrink-0 mt-1 sm:mt-0">~25ms</span>
                </div>

                <div class="api-endpoint-row flex items-start sm:items-center gap-3 sm:gap-4 bg-white/[0.03] rounded-xl p-4 sm:p-5 border border-white/5 cursor-default">
                    <span class="px-2 sm:px-3 py-1 rounded-lg bg-emerald-500/10 text-emerald-400 text-[10px] sm:text-xs font-bold tracking-wider flex-shrink-0 w-12 sm:w-16 text-center mt-0.5 sm:mt-0">GET</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-white font-bold text-sm break-all leading-tight mb-0.5">/v1/pincodes/{code}</p>
                        <p class="text-gray-600 text-xs whitespace-normal leading-relaxed">Pincode lookup with city, state, area, coordinates</p>
                    </div>
                    <span class="text-amber-500/50 text-[10px] sm:text-xs font-mono flex-shrink-0 mt-1 sm:mt-0">~12ms</span>
                </div>

                <div class="api-endpoint-row flex items-start sm:items-center gap-3 sm:gap-4 bg-white/[0.03] rounded-xl p-4 sm:p-5 border border-white/5 cursor-default">
                    <span class="px-2 sm:px-3 py-1 rounded-lg bg-emerald-500/10 text-emerald-400 text-[10px] sm:text-xs font-bold tracking-wider flex-shrink-0 w-12 sm:w-16 text-center mt-0.5 sm:mt-0">GET</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-white font-bold text-sm break-all leading-tight mb-0.5">/v1/timezones/{country}</p>
                        <p class="text-gray-600 text-xs whitespace-normal leading-relaxed">Timezone data with GMT offset, abbreviation, DST</p>
                    </div>
                    <span class="text-amber-500/50 text-[10px] sm:text-xs font-mono flex-shrink-0 mt-1 sm:mt-0">~8ms</span>
                </div>

                <div class="api-endpoint-row flex items-start sm:items-center gap-3 sm:gap-4 bg-white/[0.03] rounded-xl p-4 sm:p-5 border border-white/5 cursor-default">
                    <span class="px-2 sm:px-3 py-1 rounded-lg bg-emerald-500/10 text-emerald-400 text-[10px] sm:text-xs font-bold tracking-wider flex-shrink-0 w-12 sm:w-16 text-center mt-0.5 sm:mt-0">GET</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-white font-bold text-sm break-all leading-tight mb-0.5">/v1/currencies/{code}</p>
                        <p class="text-gray-600 text-xs whitespace-normal leading-relaxed">Real-time exchange rates vs USD & INR</p>
                    </div>
                    <span class="text-amber-500/50 text-[10px] sm:text-xs font-mono flex-shrink-0 mt-1 sm:mt-0">~22ms</span>
                </div>

                <div class="api-endpoint-row flex items-start sm:items-center gap-3 sm:gap-4 bg-white/[0.03] rounded-xl p-4 sm:p-5 border border-white/5 cursor-default group/api">
                    <span class="px-2 sm:px-3 py-1 rounded-lg bg-emerald-500/10 text-emerald-400 text-[10px] sm:text-xs font-bold tracking-wider flex-shrink-0 w-12 sm:w-16 text-center mt-0.5 sm:mt-0">GET</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-white font-bold text-sm break-all leading-tight mb-0.5 group-hover/api:text-amber-500 transition-colors">/v1/equities</p>
                        <p class="text-gray-600 text-xs whitespace-normal leading-relaxed">Full directory with Industry & Market Cap categorization</p>
                    </div>
                    <span class="text-amber-500/50 text-[10px] sm:text-xs font-mono flex-shrink-0 mt-1 sm:mt-0">~18ms</span>
                </div>

                <div class="api-endpoint-row flex items-start sm:items-center gap-3 sm:gap-4 bg-white/[0.03] rounded-xl p-4 sm:p-5 border border-white/5 cursor-default group/api">
                    <span class="px-2 sm:px-3 py-1 rounded-lg bg-pink-500/10 text-pink-400 text-[10px] sm:text-xs font-bold tracking-wider flex-shrink-0 w-12 sm:w-16 text-center mt-0.5 sm:mt-0 text-center">GET</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-white font-bold text-sm break-all leading-tight mb-0.5 group-hover/api:text-amber-500 transition-colors">/top-gainers/metrics</p>
                        <p class="text-gray-600 text-xs whitespace-normal leading-relaxed">Analytical performance series & daily leaders</p>
                    </div>
                    <span class="text-amber-500/50 text-[10px] sm:text-xs font-mono flex-shrink-0 mt-1 sm:mt-0">~14ms</span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="relative py-14 sm:py-20 border-t border-white/5">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-3xl mx-auto mb-10">
            <h2 class="text-amber-500 font-bold tracking-widest uppercase text-sm mb-3">Why SetuGeo</h2>
            <p class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">The unfair advantage for your stack.</p>
        </div>
        <div class="grid gap-4 md:hidden">
            @php
                $features = [
                    ['title' => 'Response Time', 'sg' => '<50ms', 'others' => '200-500ms'],
                    ['title' => 'Data Freshness', 'sg' => 'Weekly Updates', 'others' => 'Monthly/Manual'],
                    ['title' => 'Coverage', 'sg' => '200+ Countries', 'others' => '50-100 Countries'],
                    ['title' => 'Pincode Data', 'sg' => 'Full Coverage', 'others' => 'Limited', 'sg_icon' => 'fa-check text-emerald-500', 'o_icon' => 'fa-times text-red-500/50'],
                    ['title' => 'Currency Rates', 'sg' => 'Built-in', 'others' => 'Separate API', 'sg_icon' => 'fa-check text-emerald-500', 'o_icon' => 'fa-times text-red-500/50'],
                    ['title' => 'Equity Metrics', 'sg' => 'Real-time & Cap-wise', 'others' => 'Manual Scraping', 'sg_icon' => 'fa-check text-emerald-500', 'o_icon' => 'fa-times text-red-500/50'],
                    ['title' => 'Free Tier', 'sg' => 'Yes', 'others' => 'Paid Only', 'sg_icon' => 'fa-check text-emerald-500', 'o_icon' => '']
                ];
            @endphp

            @foreach($features as $f)
            <div class="bg-white/[0.03] border border-white/5 rounded-3xl p-5 hover:border-amber-500/20 transition-all duration-300">
                <h3 class="text-gray-500 text-[10px] font-black uppercase tracking-widest mb-4 flex items-center gap-2">
                    <span class="w-1 h-1 bg-amber-500 rounded-full"></span>
                    {{ $f['title'] }}
                </h3>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-amber-500/[0.07] rounded-2xl p-4 border border-amber-500/10">
                        <p class="text-amber-500 text-[9px] font-black uppercase tracking-tight mb-2">SetuGeo</p>
                        <p class="text-white font-bold text-sm flex items-center gap-2">
                            @if(isset($f['sg_icon'])) <i class="fas {{ $f['sg_icon'] }} text-[10px]"></i> @endif
                            {{ $f['sg'] }}
                        </p>
                    </div>
                    <div class="bg-white/[0.02] rounded-2xl p-4 border border-white/5">
                        <p class="text-gray-600 text-[9px] font-black uppercase tracking-tight mb-2">Others</p>
                        <p class="text-gray-400 font-bold text-sm flex items-center gap-2">
                            @if(isset($f['o_icon'])) <i class="fas {{ $f['o_icon'] }} text-[10px]"></i> @endif
                            {{ $f['others'] }}
                        </p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="hidden md:block bg-white/[0.02] rounded-3xl border border-white/5 overflow-hidden">
            <div class="grid grid-cols-3 text-center border-b border-white/5 bg-white/[0.02]">
                <div class="py-5 px-4 text-sm font-bold text-gray-500 uppercase tracking-wider">Feature</div>
                <div class="py-5 px-4 text-sm font-bold text-amber-500 uppercase tracking-wider border-x border-white/5">SetuGeo</div>
                <div class="py-5 px-4 text-sm font-bold text-gray-600 uppercase tracking-wider">Others</div>
            </div>
            <div class="comparison-row grid grid-cols-3 text-center border-b border-white/5 transition-colors">
                <div class="py-4 px-4 text-sm text-gray-400 font-medium">Response Time</div>
                <div class="py-4 px-4 text-sm text-white font-bold border-x border-white/5">&lt;50ms</div>
                <div class="py-4 px-4 text-sm text-gray-600 font-medium">200-500ms</div>
            </div>
            <div class="comparison-row grid grid-cols-3 text-center border-b border-white/5 transition-colors">
                <div class="py-4 px-4 text-sm text-gray-400 font-medium">Data Freshness</div>
                <div class="py-4 px-4 text-sm text-white font-bold border-x border-white/5">Weekly Updates</div>
                <div class="py-4 px-4 text-sm text-gray-600 font-medium">Monthly/Manual</div>
            </div>
            <div class="comparison-row grid grid-cols-3 text-center border-b border-white/5 transition-colors">
                <div class="py-4 px-4 text-sm text-gray-400 font-medium">Coverage</div>
                <div class="py-4 px-4 text-sm text-white font-bold border-x border-white/5">200+ Countries</div>
                <div class="py-4 px-4 text-sm text-gray-600 font-medium">50-100 Countries</div>
            </div>
            <div class="comparison-row grid grid-cols-3 text-center border-b border-white/5 transition-colors">
                <div class="py-4 px-4 text-sm text-gray-400 font-medium">Pincode Data</div>
                <div class="py-4 px-4 text-sm text-white font-bold border-x border-white/5"><i class="fas fa-check text-emerald-500"></i> Full Coverage</div>
                <div class="py-4 px-4 text-sm text-gray-600 font-medium"><i class="fas fa-times text-red-500/50"></i> Limited</div>
            </div>
            <div class="comparison-row grid grid-cols-3 text-center border-b border-white/5 transition-colors">
                <div class="py-4 px-4 text-sm text-gray-400 font-medium">Currency Rates</div>
                <div class="py-4 px-4 text-sm text-white font-bold border-x border-white/5"><i class="fas fa-check text-emerald-500"></i> Built-in</div>
                <div class="py-4 px-4 text-sm text-gray-600 font-medium"><i class="fas fa-times text-red-500/50"></i> Separate API</div>
            </div>
            <div class="comparison-row grid grid-cols-3 text-center transition-colors">
                <div class="py-4 px-4 text-sm text-gray-400 font-medium">Free Tier</div>
                <div class="py-4 px-4 text-sm text-white font-bold border-x border-white/5"><i class="fas fa-check text-emerald-500"></i> Yes</div>
                <div class="py-4 px-4 text-sm text-gray-600 font-medium">Paid Only</div>
            </div>
        </div>
    </div>
</section>

<section class="relative py-14 sm:py-20 border-t border-white/5">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-3xl mx-auto mb-10">
            <h2 class="text-amber-500 font-bold tracking-widest uppercase text-sm mb-3">Pricing</h2>
            <p class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight mb-4">Transparent, developer-friendly pricing.</p>
            <p class="text-gray-400 font-medium">Start free. Scale when you're ready. No hidden charges, ever.</p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-6xl mx-auto">
            @foreach($plans->take(3) as $index => $plan)
            @php
                $isPopular = ($index === 1);
                $isFree = ($plan->amount == 0);
            @endphp
            <div class="{{ $isPopular ? 'pricing-card-glow bg-white/[0.05] border-amber-500/20 hover:border-amber-500/40 transform lg:-translate-y-4 shadow-lg shadow-amber-500/5' : 'bg-white/[0.03] border-white/5 hover:border-white/10' }} rounded-3xl p-8 border transition-all duration-300 flex flex-col relative">
                @if($isPopular)
                <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-amber-500 text-black text-[10px] font-black px-4 py-1 rounded-full uppercase tracking-widest">Most Popular</div>
                @endif
                <h3 class="text-xl font-bold text-white mb-2">{{ $plan->name }}</h3>
                <p class="text-gray-500 text-sm mb-6">{{ $isFree ? 'Perfect for prototyping.' : 'For production applications.' }}</p>
                <div class="flex items-baseline gap-1 mb-1">
                    <span class="text-4xl font-black text-white">₹{{ number_format($plan->amount - ($plan->discount_amount ?? 0), 0) }}</span>
                    @if($plan->discount_amount > 0)
                    <span class="text-gray-600 line-through text-sm">₹{{ number_format($plan->amount, 0) }}</span>
                    @endif
                </div>
                <p class="text-gray-600 text-sm font-medium mb-8">/{{ $plan->billing_cycle }}</p>
                
                <ul class="space-y-3 text-sm text-gray-400 font-medium mb-8 flex-1">
                    <li class="flex items-center gap-3">
                        <i class="fas fa-check {{ $isPopular ? 'text-amber-500' : 'text-emerald-500' }} text-xs"></i> 
                        {{ $plan->api_hits_limit ? number_format($plan->api_hits_limit) : 'Unlimited' }} API requests
                    </li>
                    @if($plan->benefits)
                        @foreach($plan->benefits as $benefit)
                        <li class="flex items-center gap-3 text-balance">
                            <i class="fas fa-check {{ $isPopular ? 'text-amber-500' : 'text-emerald-500' }} text-xs"></i> 
                            {{ $benefit }}
                        </li>
                        @endforeach
                    @else
                        <li class="flex items-center gap-3 text-balance"><i class="fas fa-check {{ $isPopular ? 'text-amber-500' : 'text-emerald-500' }} text-xs"></i> All endpoints access</li>
                        <li class="flex items-center gap-3 text-balance"><i class="fas fa-check {{ $isPopular ? 'text-amber-500' : 'text-emerald-500' }} text-xs"></i> Standard support</li>
                    @endif
                </ul>

                <a href="{{ route('register') }}" class="block text-center py-3.5 rounded-xl font-bold transition-all {{ $isPopular ? 'bg-amber-600 hover:bg-amber-500 text-white shadow-md' : 'bg-white/5 border border-white/10 text-white hover:bg-white/10' }}">
                    {{ $isFree ? 'Get Started' : 'Choose Plan' }}
                </a>
            </div>
            @endforeach

            @if($plans->count() < 3)
            <div class="bg-white/[0.03] rounded-3xl p-8 border border-white/5 hover:border-white/10 transition-all duration-300 flex flex-col">
                <h3 class="text-xl font-bold text-white mb-2">Enterprise</h3>
                <p class="text-gray-500 text-sm mb-6">For high-volume, mission-critical apps.</p>
                <p class="text-4xl font-black text-white mb-1">Custom</p>
                <p class="text-gray-600 text-sm font-medium mb-8">tailored for you</p>
                <ul class="space-y-3 text-sm text-gray-400 font-medium mb-8 flex-1">
                    <li class="flex items-center gap-3"><i class="fas fa-check text-emerald-500 text-xs"></i> Unlimited API requests</li>
                    <li class="flex items-center gap-3"><i class="fas fa-check text-emerald-500 text-xs"></i> Dedicated support</li>
                    <li class="flex items-center gap-3"><i class="fas fa-check text-emerald-500 text-xs"></i> Custom SLA</li>
                    <li class="flex items-center gap-3"><i class="fas fa-check text-emerald-500 text-xs"></i> On-premise option</li>
                </ul>
                <a href="{{ route('contact') }}" class="block text-center py-3.5 rounded-xl bg-white/5 border border-white/10 text-white font-bold hover:bg-white/10 transition-all">
                    Contact Sales
                </a>
            </div>
            @endif
        </div>

        <div class="text-center mt-8">
            <a href="{{ route('pricing') }}" class="text-amber-500 font-bold hover:text-amber-400 transition-colors text-sm">
                View all plans and compare features →
            </a>
        </div>
    </div>
</section>

<section class="relative py-14 sm:py-20 border-t border-white/5 overflow-hidden">
    <div class="absolute top-1/2 left-0 w-[400px] h-[400px] bg-amber-600/5 rounded-full blur-[100px] -translate-y-1/2 -translate-x-1/2"></div>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-3xl mx-auto mb-10">
            <h2 class="text-amber-500 font-bold tracking-widest uppercase text-sm mb-3">Developer Love</h2>
            <p class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">What our users are saying.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-6">
            <div class="bg-white/[0.04] backdrop-blur-xl rounded-2xl p-7 border border-white/5 hover:border-amber-500/20 transition-all duration-300">
                <div class="flex mb-3 gap-0.5">
                    @for($s=0; $s<5; $s++)<i class="fas fa-star text-amber-500 text-xs"></i>@endfor
                </div>
                <p class="text-gray-300 text-sm leading-relaxed mb-6 font-medium">"SetuGeo's accuracy is unmatched. We switched from a competitor and haven't looked back."</p>
                <div class="flex items-center gap-3 border-t border-white/5 pt-4">
                    <div class="w-9 h-9 bg-gradient-to-br from-amber-500 to-amber-600 rounded-full flex items-center justify-center text-white text-xs font-bold">JD</div>
                    <div>
                        <p class="text-white text-xs font-bold">James Dalton</p>
                        <p class="text-gray-600 text-[11px]">CTO, Global Commerce</p>
                    </div>
                </div>
            </div>
            <div class="bg-white/[0.04] backdrop-blur-xl rounded-2xl p-7 border border-white/5 hover:border-amber-500/20 transition-all duration-300">
                <div class="flex mb-3 gap-0.5">
                    @for($s=0; $s<5; $s++)<i class="fas fa-star text-amber-500 text-xs"></i>@endfor
                </div>
                <p class="text-gray-300 text-sm leading-relaxed mb-6 font-medium">"Integration took 15 minutes. The docs are clear and the sub-50ms latency is real."</p>
                <div class="flex items-center gap-3 border-t border-white/5 pt-4">
                    <div class="w-9 h-9 bg-gradient-to-br from-amber-500 to-amber-600 rounded-full flex items-center justify-center text-white text-xs font-bold">SK</div>
                    <div>
                        <p class="text-white text-xs font-bold">Sarah Koenig</p>
                        <p class="text-gray-600 text-[11px]">Sr. Dev, SaaS Travel</p>
                    </div>
                </div>
            </div>
            <div class="bg-white/[0.04] backdrop-blur-xl rounded-2xl p-7 border border-white/5 hover:border-amber-500/20 transition-all duration-300">
                <div class="flex mb-3 gap-0.5">
                    @for($s=0; $s<5; $s++)<i class="fas fa-star text-amber-500 text-xs"></i>@endfor
                </div>
                <p class="text-gray-300 text-sm leading-relaxed mb-6 font-medium">"The pincode API alone saved us 200+ engineering hours. 3M lookups/month, zero issues."</p>
                <div class="flex items-center gap-3 border-t border-white/5 pt-4">
                    <div class="w-9 h-9 bg-gradient-to-br from-amber-500 to-amber-600 rounded-full flex items-center justify-center text-white text-xs font-bold">RM</div>
                    <div>
                        <p class="text-white text-xs font-bold">Ravi Mehta</p>
                        <p class="text-gray-600 text-[11px]">VP Eng, FinServ</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="relative py-14 sm:py-20 border-t border-white/5" x-data="{ active: null }">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center mb-10">
            <h2 class="text-amber-500 font-bold tracking-widest uppercase text-sm mb-3">FAQ</h2>
            <p class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">Common questions.</p>
        </div>

        <div class="space-y-3">
            @forelse($faqs as $i => $item)
            <div class="bg-white/[0.03] rounded-2xl border border-white/5 overflow-hidden hover:border-amber-500/20 transition-all group">
                <button @click="active = (active === {{ $i }} ? null : {{ $i }})" class="w-full flex items-center justify-between px-6 sm:px-8 py-5 text-left">
                    <span class="text-sm font-bold text-white group-hover:text-amber-400 transition-colors pr-4">{{ $item->question }}</span>
                    <div class="w-6 h-6 rounded-full bg-white/5 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-chevron-down text-amber-500 text-[10px] transition-transform duration-300" :class="active === {{ $i }} ? 'rotate-180' : ''"></i>
                    </div>
                </button>
                <div x-show="active === {{ $i }}" x-collapse class="px-6 sm:px-8 pb-5 text-gray-400 font-medium leading-relaxed text-sm">
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

<section class="relative py-16 sm:py-24 border-t border-white/5">
    <div class="absolute inset-0 bg-gradient-to-t from-amber-500/[0.03] to-transparent"></div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <div class="mb-8">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-500/10 border border-emerald-500/20 mb-6">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span class="text-xs font-bold tracking-widest uppercase text-emerald-400">All systems operational</span>
            </div>
        </div>

        <h2 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white tracking-tight mb-6">
            Build faster with <span class="hero-gradient-text">SetuGeo</span>.
        </h2>
        <p class="text-lg text-gray-400 mb-8 font-medium max-w-2xl mx-auto">
            Get your API keys in 60 seconds. Start with a generous free tier — upgrade when your product takes off.
        </p>
        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <a href="{{ route('register') }}" class="group inline-flex justify-center items-center px-10 py-4 text-base font-bold rounded-2xl text-white bg-amber-600 hover:bg-amber-500 shadow-lg shadow-amber-600/20 transform hover:-translate-y-0.5 transition-all duration-300">
                Create Free Account
                <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
            <a href="{{ route('docs') }}" class="inline-flex justify-center items-center px-10 py-4 text-base font-bold rounded-2xl text-gray-300 bg-white/5 border border-white/10 hover:bg-white/10 hover:text-white transition-all duration-300">
                <i class="fas fa-book-open mr-2 text-amber-500"></i>
                Read Documentation
            </a>
        </div>
    </div>
</section>
@endsection
