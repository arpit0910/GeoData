@extends('layouts.public')
@section('title', 'API Documentation - SetuGeo Geographic Data API Integration Guide')
@section('meta_description',
    'Comprehensive API documentation for SetuGeo. Learn how to integrate geographic data APIs
    for countries, states, cities, pincodes, and banking details into your apps.')
@section('meta_keywords',
    'api documentation, setugeo api guide, geographic api integration, pincode api docs, bank ifsc
    api documentation')

@section('content')
    <div class="min-h-screen bg-[#020617] text-gray-300 antialiased font-inter">
        <!-- Hero Section -->
        <div class="border-b border-gray-800/50 bg-[#020617]">
            <div
                class="max-w-7xl mx-auto px-4 py-16 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-start md:items-center">
                <div>
                    <h1 class="text-2xl font-extrabold text-white tracking-tight sm:text-5xl">API Documentation</h1>
                    <p class="mt-4 text-xl text-gray-400 max-w-2xl">Comprehensive guide for integrating SetuGeo's geographic
                        intelligence into your applications.</p>
                </div>
                <div class="mt-8 md:mt-0">
                    <a href="{{ asset('SetuGeo.postman_collection.json') }}" download
                        class="inline-flex items-center px-6 py-3 border border-amber-600/30 text-base font-bold rounded-xl text-white bg-amber-600/10 hover:bg-amber-600 hover:border-amber-600 transition-all shadow-lg group">
                        <i class="fas fa-rocket mr-3 text-amber-500 group-hover:text-white"></i>
                        Postman Collection
                    </a>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="lg:grid lg:grid-cols-12 lg:gap-12">
                <!-- Sidebar Navigation -->
                <aside class="hidden lg:block lg:col-span-3">
                    <nav class="sticky top-24 space-y-8">
                        <div>
                            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Navigation</h3>
                            <ul class="mt-4 space-y-4">
                                <li><a href="#getting-started"
                                        class="text-sm font-medium hover:text-amber-500 transition-colors">Getting
                                        Started</a></li>
                                <li><a href="#authentication"
                                        class="text-sm font-medium hover:text-amber-500 transition-colors">Authentication</a>
                                </li>
                                <li><a href="#account-api"
                                        class="text-sm font-medium hover:text-amber-500 transition-colors">Account & Usage
                                        API</a></li>
                                <li><a href="#core-api"
                                        class="text-sm font-medium hover:text-amber-500 transition-colors">Core Geographic
                                        Data</a></li>
                                <li><a href="#utilities-api"
                                        class="text-sm font-medium hover:text-amber-500 transition-colors">Intelligent
                                        Utilities</a></li>
                                <li><a href="#banking-api"
                                        class="text-sm font-medium hover:text-amber-500 transition-colors">Banking &
                                        Finance</a></li>
                                <li><a href="#equity-api"
                                        class="text-sm font-medium hover:text-amber-500 transition-colors">Equity
                                        Intelligence</a></li>
                                <li><a href="#index-api"
                                        class="text-sm font-medium hover:text-amber-500 transition-colors">Index
                                        Intelligence</a></li>
                                <li><a href="#mf-api"
                                        class="text-sm font-medium hover:text-amber-500 transition-colors">Mutual Fund
                                        Intelligence</a></li>
                                <li><a href="#equity-advanced-api"
                                        class="text-sm font-medium hover:text-amber-500 transition-colors">Equity Advanced
                                        Analytics</a></li>
                                <li><a href="#index-valuation-api"
                                        class="text-sm font-medium hover:text-amber-500 transition-colors">Index Valuation
                                        & OHLC</a></li>
                                <li><a href="#mf-advanced-api"
                                        class="text-sm font-medium hover:text-amber-500 transition-colors">MF Advanced
                                        Analytics</a></li>
                                <li><a href="#market-api"
                                        class="text-sm font-medium hover:text-amber-500 transition-colors">Market
                                        Overview</a></li>
                                <li><a href="#country-economic-api"
                                        class="text-sm font-medium hover:text-amber-500 transition-colors">Country
                                        Economic Data</a></li>
                                <li><a href="#banking-capability-api"
                                        class="text-sm font-medium hover:text-amber-500 transition-colors">Banking
                                        Capabilities</a></li>
                                <li><a href="#user-analytics-api"
                                        class="text-sm font-medium hover:text-amber-500 transition-colors">User
                                        Analytics</a></li>
                                <li><a href="#hierarchical-data"
                                        class="text-sm font-medium hover:text-amber-500 transition-colors">Hierarchical
                                        Drill-Downs</a></li>
                                <li><a href="#smart-addressing"
                                        class="text-sm font-medium hover:text-amber-500 transition-colors">Smart
                                        Addressing</a></li>
                                <li><a href="#geospatial-analysis"
                                        class="text-sm font-medium hover:text-amber-500 transition-colors">Geospatial
                                        Analysis</a></li>
                                <li><a href="#status-codes"
                                        class="text-sm font-medium hover:text-amber-500 transition-colors">Status Codes</a>
                                </li>
                            </ul>
                        </div>
                    </nav>
                </aside>

                <!-- Main Documentation Content -->
                <div class="lg:col-span-9 space-y-24 pb-24">

                    <!-- Getting Started -->
                    <section id="getting-started">
                        <h2 class="text-xl sm:text-3xl font-bold text-white mb-6">Getting Started</h2>
                        <p class="text-lg leading-relaxed mb-6">
                            SetuGeo provides a comprehensive set of RESTful APIs to retrieve geographic information
                            including Countries, States, Cities, and Pincodes. Our APIs are CORS-enabled and return
                            JSON-formatted responses.
                        </p>
                        <div class="bg-gray-900/50 rounded-xl p-6 border border-gray-800">
                            <h4 class="text-sm font-semibold text-amber-500 uppercase mb-2">Base URL</h4>
                            <code class="text-white text-lg font-mono">{{ url('/api/v1') }}</code>
                        </div>
                    </section>

                    <!-- Authentication -->
                    <section id="authentication">
                        <h2 class="text-xl sm:text-3xl font-bold text-white mb-6">Authentication</h2>
                        <div class="bg-amber-600/10 border border-amber-600/30 rounded-2xl p-8 mb-12">
                            <h3 class="text-lg sm:text-xl font-bold text-amber-500 mb-4 flex items-center">
                                <i class="fas fa-shield-alt mr-3"></i>
                                The Mandatory Two-Step Process
                            </h3>
                            <p class="text-gray-300 leading-relaxed mb-6">All SetuGeo APIs (except Auth) are protected. You
                                cannot use your Client Key or Secret Key directly in standard API calls. You <span
                                    class="text-white font-bold underline decoration-amber-500">must</span> follow this
                                sequence:</p>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="bg-black/20 p-4 rounded-xl border border-white/5 relative">
                                    <span
                                        class="absolute -top-3 -left-3 w-8 h-8 bg-amber-600 text-white rounded-full flex items-center justify-center font-bold text-sm shadow-lg">1</span>
                                    <h4 class="text-white font-bold text-sm mb-2">Get Your Keys</h4>
                                    <p class="text-[11px] text-gray-500 font-medium">Copy your Client Key and Secret Key
                                        from your dashboard.</p>
                                </div>
                                <div class="bg-black/20 p-4 rounded-xl border border-white/5 relative">
                                    <span
                                        class="absolute -top-3 -left-3 w-8 h-8 bg-amber-600 text-white rounded-full flex items-center justify-center font-bold text-sm shadow-lg">2</span>
                                    <h4 class="text-white font-bold text-sm mb-2">Request Token</h4>
                                    <p class="text-[11px] text-gray-500 font-medium">Call the <code
                                            class="text-amber-500">/auth/token</code> endpoint to get an <code
                                            class="text-white">access_token</code>.</p>
                                </div>
                                <div class="bg-black/20 p-4 rounded-xl border border-white/5 relative">
                                    <span
                                        class="absolute -top-3 -left-3 w-8 h-8 bg-amber-600 text-white rounded-full flex items-center justify-center font-bold text-sm shadow-lg">3</span>
                                    <h4 class="text-white font-bold text-sm mb-2">Access API</h4>
                                    <p class="text-[11px] text-gray-500 font-medium">Use the <code
                                            class="text-white">access_token</code> as a Bearer Token for all geographic
                                        APIs.</p>
                                </div>
                            </div>
                        </div>
                        <p class="mb-8 text-lg">To access geographic intelligence endpoints, ensure your requests include
                            the <code class="text-white">Authorization</code> header exactly as shown below.</p>

                        <!-- Auth Flow Explanation -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 mb-16">
                            <div class="space-y-6">
                                <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
                                    <span
                                        class="w-8 h-8 bg-amber-600/20 text-amber-500 rounded-full flex items-center justify-center mr-3 text-sm">1</span>
                                    Generate Token
                                </h3>
                                <p class="text-gray-400">Send your credentials to the <code
                                        class="text-amber-400">/auth/token</code> endpoint. You will receive an <code
                                        class="text-white">access_token</code> which is valid for 24 hours.</p>
                                <div class="bg-gray-900/50 p-4 rounded-xl border border-white/5 text-xs font-mono">
                                    <span class="text-blue-400">POST</span> /api/v1/auth/token
                                </div>
                            </div>
                            <div class="space-y-6">
                                <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
                                    <span
                                        class="w-8 h-8 bg-amber-600/20 text-amber-500 rounded-full flex items-center justify-center mr-3 text-sm">2</span>
                                    Use Bearer Token
                                </h3>
                                <p class="text-gray-400">Include the token in the <code
                                        class="text-white">Authorization</code> header for all subsequent API requests as a
                                    Bearer string.</p>
                                <div class="bg-gray-900/50 p-4 rounded-xl border border-white/5 text-xs font-mono">
                                    Authorization: Bearer <span class="text-amber-500">{your_access_token}</span>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-8">


                            <section id="account-api" class="pt-8">
                                <h2
                                    class="text-xl sm:text-3xl font-bold text-white mb-6 uppercase tracking-wider border-b border-gray-800 pb-4">
                                    Account & Usage API</h2>
                            </section>
                            <div id="user-usage" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                <div
                                    class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                    <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span>
                                        /user/usage</h3>
                                </div>
                                <div class="p-6">
                                    <p class="mb-4">Monitor your real-time credit balance and API consumption metrics.</p>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                    <div
                                        class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                        <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: {
    <span class="text-blue-400">"total_credits"</span>: <span class="text-blue-400">100000</span>,
    <span class="text-blue-400">"available_credits"</span>: <span class="text-blue-400">89540</span>,
    <span class="text-blue-400">"usage_this_month"</span>: <span class="text-blue-400">10460</span>
  }
}</pre>
                                    </div>
                                </div>
                            </div>
                            <div id="get-token" class="bg-gray-900 rounded-xl overflow-hidden border border-gray-800">
                                <div
                                    class="px-6 py-4 border-b border-gray-800 bg-gray-900/50 flex justify-between items-center">
                                    <span class="text-sm font-bold text-green-400 uppercase">POST /auth/token</span>
                                </div>
                                <div class="p-6">
                                    <h4 class="text-sm font-bold text-gray-400 uppercase mb-4">Request Body</h4>
                                    <table class="w-full text-sm mb-6">
                                        <thead class="text-gray-500 text-left border-b border-gray-800">
                                            <tr>
                                                <th class="pb-2">Field</th>
                                                <th class="pb-2">Type</th>
                                                <th class="pb-2">Required</th>
                                                <th class="pb-2">Description</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-800">
                                            <tr>
                                                <td class="py-3 font-mono text-white">client_key</td>
                                                <td class="py-3 text-gray-500">string</td>
                                                <td class="py-3 text-amber-500 font-bold">Yes</td>
                                                <td class="py-3 text-gray-400">Your unique Public API Key (Access via
                                                    Dashboard)</td>
                                            </tr>
                                            <tr>
                                                <td class="py-3 font-mono text-white">client_secret</td>
                                                <td class="py-3 text-gray-500">string</td>
                                                <td class="py-3 text-amber-500 font-bold">Yes</td>
                                                <td class="py-3 text-gray-400">Your protected Secret API Key</td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                    <div
                                        class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                        <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"access_token"</span>: <span class="text-green-400">"eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsIm..."</span>,
  <span class="text-blue-400">"token_type"</span>: <span class="text-green-400">"Bearer"</span>,
  <span class="text-blue-400">"expires_in"</span>: <span class="text-blue-400">86400</span>
}</pre>
                                    </div>

                                    <!-- Code Tabs -->
                                    <div x-data="{ tab: 'node' }" class="mt-8">
                                        <div class="flex space-x-4 border-b border-gray-800 mb-4">
                                            <button @click="tab = 'node'"
                                                :class="tab === 'node' ? 'text-amber-500 border-b-2 border-amber-500' :
                                                    'text-gray-500'"
                                                class="pb-2 text-sm font-bold">Node.js</button>
                                            <button @click="tab = 'react'"
                                                :class="tab === 'react' ? 'text-amber-500 border-b-2 border-amber-500' :
                                                    'text-gray-500'"
                                                class="pb-2 text-sm font-bold">ReactJS</button>
                                            <button @click="tab = 'python'"
                                                :class="tab === 'python' ? 'text-amber-500 border-b-2 border-amber-500' :
                                                    'text-gray-500'"
                                                class="pb-2 text-sm font-bold">Python</button>
                                            <button @click="tab = 'php'"
                                                :class="tab === 'php' ? 'text-amber-500 border-b-2 border-amber-500' :
                                                    'text-gray-500'"
                                                class="pb-2 text-sm font-bold">PHP (Laravel)</button>
                                        </div>
                                        <div x-show="tab === 'node'"
                                            class="bg-[#0f172a] rounded-lg p-5 font-mono text-sm overflow-x-auto">
                                            <pre class="text-gray-300"><span class="text-blue-400">const</span> axios = <span class="text-yellow-400">require</span>(<span class="text-green-400">'axios'</span>);

axios.<span class="text-yellow-400">post</span>(<span class="text-green-400">'{{ url('/api/v1/auth/token') }}'</span>, {
    client_key: <span class="text-green-400">'YOUR_KEY'</span>,
    client_secret: <span class="text-green-400">'YOUR_SECRET'</span>
})
.then(<span class="text-blue-400">res</span> => console.<span class="text-yellow-400">log</span>(res.data))
.catch(<span class="text-blue-400">err</span> => console.<span class="text-yellow-400">error</span>(err));</pre>
                                        </div>
                                        <div x-show="tab === 'react'"
                                            class="bg-[#0f172a] rounded-lg p-5 font-mono text-sm overflow-x-auto">
                                            <pre class="text-gray-300"><span class="text-blue-400">import</span> { useEffect } <span class="text-blue-400">from</span> <span class="text-green-400">'react'</span>;
<span class="text-blue-400">import</span> axios <span class="text-blue-400">from</span> <span class="text-green-400">'axios'</span>;

<span class="text-blue-400">const</span> SetuGeoApp = () => {
  <span class="text-yellow-400">useEffect</span>(() => {
    axios.<span class="text-yellow-400">post</span>(<span class="text-green-400">'{{ url('/api/v1/auth/token') }}'</span>, {
      client_key: <span class="text-green-400">'YOUR_KEY'</span>,
      client_secret: <span class="text-green-400">'YOUR_SECRET'</span>
    })
    .then(<span class="text-blue-400">res</span> => console.<span class="text-yellow-400">log</span>(res.data));
  }, []);

  <span class="text-blue-400">return</span> <span class="text-gray-500">&lt;div&gt;</span>Explore SetuGeo API<span class="text-gray-500">&lt;/div&gt;</span>;
};</pre>
                                        </div>
                                        <div x-show="tab === 'python'"
                                            class="bg-[#0f172a] rounded-lg p-5 font-mono text-sm overflow-x-auto">
                                            <pre class="text-gray-300"><span class="text-blue-400">import</span> requests

payload = {
    <span class="text-green-400">'client_key'</span>: <span class="text-green-400">'YOUR_KEY'</span>,
    <span class="text-green-400">'client_secret'</span>: <span class="text-green-400">'YOUR_SECRET'</span>
}

response = requests.<span class="text-yellow-400">post</span>(<span class="text-green-400">'{{ url('/api/v1/auth/token') }}'</span>, <span class="text-blue-400">data</span>=payload)
<span class="text-yellow-400">print</span>(response.<span class="text-yellow-400">json</span>())</pre>
                                        </div>
                                        <div x-show="tab === 'php'"
                                            class="bg-[#0f172a] rounded-lg p-5 font-mono text-sm overflow-x-auto">
                                            <pre class="text-gray-300"><span class="text-blue-400">use</span> Illuminate\Support\Facades\Http;

$response = Http::<span class="text-yellow-400">post</span>(<span class="text-green-400">'{{ url('/api/v1/auth/token') }}'</span>, [
    <span class="text-green-400">'client_key'</span> => <span class="text-green-400">'YOUR_KEY'</span>,
    <span class="text-green-400">'client_secret'</span> => <span class="text-green-400">'YOUR_SECRET'</span>,
]);

<span class="text-blue-400">return</span> $response-><span class="text-yellow-400">json</span>();</pre>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Request Patterns -->
                    <section id="request-patterns">
                        <h2 class="text-xl sm:text-3xl font-bold text-white mb-6">Request Patterns</h2>
                        <p class="mb-8">All SetuGeo API endpoints follow a consistent pattern. Use <span
                                class="text-white">GET</span> for retrieving data and <span class="text-white">POST</span>
                            for authentication or complex analysis.</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="bg-gray-900/40 rounded-xl p-6 border border-gray-800">
                                <h4 class="text-sm font-bold text-amber-500 uppercase mb-4">Content Type</h4>
                                <p class="text-sm text-gray-400 mb-4">Always send and expect JSON. Ensure your headers
                                    include:</p>
                                <div class="space-y-2 text-xs font-mono">
                                    <div class="bg-black/30 p-2 rounded text-gray-500">Content-Type: <span
                                            class="text-green-400">application/json</span></div>
                                    <div class="bg-black/30 p-2 rounded text-gray-500">Accept: <span
                                            class="text-green-400">application/json</span></div>
                                </div>
                            </div>
                            <div class="bg-gray-900/40 rounded-xl p-6 border border-gray-800">
                                <h4 class="text-sm font-bold text-amber-500 uppercase mb-4">Standard Response</h4>
                                <p class="text-sm text-gray-400 mb-4">Every successful response includes a <code
                                        class="text-white">success</code> boolean and a <code
                                        class="text-white">data</code> object/array.</p>
                                <div class="bg-[#0f172a] p-3 rounded text-[10px] font-mono text-gray-500">
                                    <pre>{
  "success": true,
  "data": [...]
}</pre>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- SetuGeo Endpoints -->
                    <section id="geo-endpoints">
                        <h2 class="text-xl sm:text-3xl font-bold text-white mb-12">SetuGeo Endpoints</h2>

                        <div class="space-y-12">
                            <!-- Regions -->


                            <section id="core-api" class="pt-8">
                                <h2
                                    class="text-xl sm:text-3xl font-bold text-white mb-6 uppercase tracking-wider border-b border-gray-800 pb-4">
                                    Core Geographic Data</h2>
                            </section>
                            <div id="regions" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                <div
                                    class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                    <h3 class="text-base sm:text-lg font-bold text-white"><span
                                            class="text-blue-400 mr-2 uppercase">GET</span> /regions</h3>
                                    <span
                                        class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                        <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                            class="hidden sm:inline">Credits Required</span>
                                    </span>
                                </div>
                                <div class="p-6">
                                    <p class="mb-4">Get a list of global political or geographic regions (e.g., Asia,
                                        Europe, Africa).</p>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                    <table class="w-full text-sm mb-8">
                                        <thead class="text-gray-500 text-left border-b border-gray-800">
                                            <tr>
                                                <th class="pb-2">Field</th>
                                                <th class="pb-2">Description</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-800">
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">name</td>
                                                <td class="py-3 text-gray-400">Partial match for region name (e.g.
                                                    "Americas")</td>
                                            </tr>
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">limit</td>
                                                <td class="py-3 text-gray-400">Pagination limit (default: 100)</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                    <div
                                        class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                        <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: [
    {
      <span class="text-blue-400">"id"</span>: <span class="text-blue-400">1</span>,
      <span class="text-blue-400">"name"</span>: <span class="text-green-400">"Asia"</span>,
      <span class="text-blue-400">"wikiDataId"</span>: <span class="text-green-400">"Q48"</span>
    },
    {
      <span class="text-blue-400">"id"</span>: <span class="text-blue-400">2</span>,
      <span class="text-blue-400">"name"</span>: <span class="text-green-400">"Europe"</span>,
      <span class="text-blue-400">"wikiDataId"</span>: <span class="text-green-400">"Q46"</span>
    }
  ]
}</pre>
                                    </div>
                                </div>
                            </div>

                            <!-- Sub-Regions -->
                            <div id="sub-regions"
                                class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                <div
                                    class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                    <h3 class="text-base sm:text-lg font-bold text-white"><span
                                            class="text-blue-400 mr-2 uppercase">GET</span> /sub-regions</h3>
                                    <span
                                        class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                        <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                            class="hidden sm:inline">Credits Required</span>
                                    </span>
                                </div>
                                <div class="p-6">
                                    <p class="mb-4">Get detailed sub-regions within a parent region.</p>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                    <table class="w-full text-sm mb-8">
                                        <thead class="text-gray-500 text-left border-b border-gray-800">
                                            <tr>
                                                <th class="pb-2">Field</th>
                                                <th class="pb-2">Description</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-800">
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">region_id</td>
                                                <td class="py-3 text-gray-400">Filter by Parent Region ID</td>
                                            </tr>
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">name</td>
                                                <td class="py-3 text-gray-400">Search by Sub-region name</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                    <div
                                        class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                        <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: [
    {
      <span class="text-blue-400">"id"</span>: <span class="text-blue-400">1</span>,
      <span class="text-blue-400">"name"</span>: <span class="text-green-400">"Southern Asia"</span>,
      <span class="text-blue-400">"region_id"</span>: <span class="text-blue-400">1</span>
    },
    {
      <span class="text-blue-400">"id"</span>: <span class="text-blue-400">2</span>,
      <span class="text-blue-400">"name"</span>: <span class="text-green-400">"Western Europe"</span>,
      <span class="text-blue-400">"region_id"</span>: <span class="text-blue-400">2</span>
    }
  ]
}</pre>
                                    </div>
                                </div>
                            </div>

                            <!-- Timezones -->
                            <div id="timezones" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                <div
                                    class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                    <h3 class="text-base sm:text-lg font-bold text-white"><span
                                            class="text-blue-400 mr-2 uppercase">GET</span> /timezones</h3>
                                    <span
                                        class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                        <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                            class="hidden sm:inline">Credits Required</span>
                                    </span>
                                </div>
                                <div class="p-6">
                                    <p class="mb-4">Retrieve standardized IANA timezones.</p>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                    <table class="w-full text-sm mb-8">
                                        <thead class="text-gray-500 text-left border-b border-gray-800">
                                            <tr>
                                                <th class="pb-2">Field</th>
                                                <th class="pb-2">Description</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-800">
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">name</td>
                                                <td class="py-3 text-gray-400">Filter by Zone name (e.g. "Asia/Kolkata")
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                    <div
                                        class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                        <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: [
    {
      <span class="text-blue-400">"id"</span>: <span class="text-blue-400">1</span>,
      <span class="text-blue-400">"name"</span>: <span class="text-green-400">"Asia/Kolkata"</span>,
      <span class="text-blue-400">"offset"</span>: <span class="text-green-400">"+05:30"</span>
    },
    {
      <span class="text-blue-400">"id"</span>: <span class="text-blue-400">2</span>,
      <span class="text-blue-400">"name"</span>: <span class="text-green-400">"America/New_York"</span>,
      <span class="text-blue-400">"offset"</span>: <span class="text-green-400">"-05:00"</span>
    }
  ]
}</pre>
                                    </div>
                                </div>
                            </div>

                            <!-- Countries -->
                            <div id="countries" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                <div
                                    class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                    <h3 class="text-base sm:text-lg font-bold text-white"><span
                                            class="text-blue-400 mr-2 uppercase">GET</span> /countries</h3>
                                    <span
                                        class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                        <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                            class="hidden sm:inline">Credits Required</span>
                                    </span>
                                </div>
                                <div class="p-6">
                                    <p class="mb-4">Retrieve a filterable list of countries with their ISO codes,
                                        currency, and capital.</p>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Request Headers</h4>
                                    <div
                                        class="bg-gray-900 rounded p-3 text-sm font-mono mb-6 text-gray-400 border border-gray-800">
                                        Authorization: Bearer <span class="text-amber-500">{your_token}</span>
                                    </div>

                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                    <table class="w-full text-sm mb-8">
                                        <thead class="text-gray-500 text-left border-b border-gray-800">
                                            <tr>
                                                <th class="pb-2">Field</th>
                                                <th class="pb-2">Description</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-800">
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">name</td>
                                                <td class="py-3 text-gray-400">Filter by country name (Partial match)</td>
                                            </tr>
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">iso2</td>
                                                <td class="py-3 text-gray-400">Filter by 2-letter ISO code (e.g. "IN")</td>
                                            </tr>
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">iso3</td>
                                                <td class="py-3 text-gray-400">Filter by 3-letter ISO code (e.g. "IND")
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">region_id</td>
                                                <td class="py-3 text-gray-400">Filter by Region ID</td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                    <div
                                        class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                        <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: [
    {
      <span class="text-blue-400">"name"</span>: <span class="text-green-400">"India"</span>,
      <span class="text-blue-400">"iso2"</span>: <span class="text-green-400">"IN"</span>,
      <span class="text-blue-400">"capital"</span>: <span class="text-green-400">"New Delhi"</span>,
      <span class="text-blue-400">"currency"</span>: <span class="text-green-400">"INR"</span>
    }
  ]
}</pre>
                                    </div>
                                </div>
                            </div>

                            <!-- States -->
                            <div id="states" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                <div
                                    class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                    <h3 class="text-base sm:text-lg font-bold text-white"><span
                                            class="text-blue-400 mr-2">GET</span> /states</h3>
                                    <span
                                        class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                        <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                            class="hidden sm:inline">Credits Required</span>
                                    </span>
                                </div>
                                <div class="p-6">
                                    <p class="mb-4">Retrieve states filtered by country. Perfect for dropdown menus.</p>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Query Parameters</h4>
                                    <ul class="text-sm space-y-2 mb-4 text-gray-400">
                                        <li><code class="text-amber-500">country_id</code>: <span
                                                class="italic text-gray-600">(Recommended)</span> Filter by Country ID.
                                        </li>
                                        <li><code class="text-amber-500">country_name</code>: Filter by Country Name.</li>
                                    </ul>

                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                    <div
                                        class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                        <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: [
    {
      <span class="text-blue-400">"id"</span>: <span class="text-blue-400">51</span>,
      <span class="text-blue-400">"name"</span>: <span class="text-green-400">"Maharashtra"</span>,
      <span class="text-blue-400">"iso2"</span>: <span class="text-green-400">"MH"</span>
    }
  ]
}</pre>
                                    </div>
                                </div>
                            </div>

                            <!-- Cities -->
                            <div id="cities" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                <div
                                    class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                    <h3 class="text-base sm:text-lg font-bold text-white"><span
                                            class="text-blue-400 mr-2">GET</span> /cities</h3>
                                    <span
                                        class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                        <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                            class="hidden sm:inline">Credits Required</span>
                                    </span>
                                </div>
                                <div class="p-6">
                                    <p class="mb-4">Retrieve cities filtered by state or country.</p>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Query Parameters</h4>
                                    <ul class="text-sm space-y-2 mb-4 text-gray-400">
                                        <li><code class="text-amber-500">state_id</code>: Filter by State ID.</li>
                                        <li><code class="text-amber-500">state_name</code>: Filter by State Name.</li>
                                    </ul>

                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                    <div
                                        class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                        <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: [
    {
      <span class="text-blue-400">"id"</span>: <span class="text-blue-400">1024</span>,
      <span class="text-blue-400">"name"</span>: <span class="text-green-400">"Mumbai"</span>,
      <span class="text-blue-400">"latitude"</span>: <span class="text-blue-400">19.0760</span>,
      <span class="text-blue-400">"longitude"</span>: <span class="text-blue-400">72.8777</span>
    }
  ]
}</pre>
                                    </div>
                                </div>
                            </div>

                            <!-- Pincode Search -->
                            <div id="pincode-search"
                                class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                <div
                                    class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                    <h3 class="text-base sm:text-lg font-bold text-white"><span
                                            class="text-blue-400 mr-2 uppercase">GET</span> /pincodes/search</h3>
                                    <span
                                        class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                        <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                            class="hidden sm:inline">Credits Required</span>
                                    </span>
                                </div>
                                <div class="p-6">
                                    <p class="mb-4">Search for detailed geographic data by Pincode. Returns associated
                                        City, State, and Country data.</p>

                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                    <table class="w-full text-sm mb-8">
                                        <thead class="text-gray-500 text-left border-b border-gray-800">
                                            <tr>
                                                <th class="pb-2">Field</th>
                                                <th class="pb-2">Description</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-800">
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">pincode</td>
                                                <td class="py-3 text-gray-400">The postal code to search for (Alternative:
                                                    <code class="text-amber-400">code</code>)
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                    <div
                                        class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                        <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: [
    {
      <span class="text-blue-400">"pincode"</span>: <span class="text-green-400">"400001"</span>,
      <span class="text-blue-400">"city"</span>: {
        <span class="text-blue-400">"id"</span>: <span class="text-blue-400">1024</span>,
        <span class="text-blue-400">"name"</span>: <span class="text-green-400">"Mumbai"</span>
      },
      <span class="text-blue-400">"state"</span>: {
        <span class="text-blue-400">"id"</span>: <span class="text-blue-400">51</span>,
        <span class="text-blue-400">"name"</span>: <span class="text-green-400">"Maharashtra"</span>
      },
      <span class="text-blue-400">"country"</span>: {
        <span class="text-blue-400">"id"</span>: <span class="text-blue-400">101</span>,
        <span class="text-blue-400">"name"</span>: <span class="text-green-400">"India"</span>
      },
      <span class="text-blue-400">"latitude"</span>: <span class="text-green-400">"18.922"</span>,
      <span class="text-blue-400">"longitude"</span>: <span class="text-green-400">"72.834"</span>
    }
  ]
}</pre>
                                    </div>
                                </div>
                            </div>

                            <!-- Pincodes -->
                            <div id="pincodes" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                <div
                                    class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                    <h3 class="text-base sm:text-lg font-bold text-white"><span
                                            class="text-blue-400 mr-2 uppercase">GET</span> /pincodes</h3>
                                    <span
                                        class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                        <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                            class="hidden sm:inline">Credits Required</span>
                                    </span>
                                </div>
                                <div class="p-6">
                                    <p class="mb-4">Batch retrieve pincodes by city, state or country filter.</p>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                    <table class="w-full text-sm mb-8">
                                        <thead class="text-gray-500 text-left border-b border-gray-800">
                                            <tr>
                                                <th class="pb-2">Field</th>
                                                <th class="pb-2">Description</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-800">
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">city_id</td>
                                                <td class="py-3 text-gray-400">Filter by City ID</td>
                                            </tr>
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">pincode</td>
                                                <td class="py-3 text-gray-400">Partial match for postal code</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                    <div
                                        class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                        <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: [
    {
      <span class="text-blue-400">"id"</span>: <span class="text-blue-400">101</span>,
      <span class="text-blue-400">"postal_code"</span>: <span class="text-green-400">"400001"</span>,
      <span class="text-blue-400">"city_id"</span>: <span class="text-blue-400">12</span>
    }
  ],
  <span class="text-blue-400">"meta"</span>: {
    <span class="text-blue-400">"total"</span>: <span class="text-blue-400">1200000</span>,
    <span class="text-blue-400">"current_page"</span>: <span class="text-blue-400">1</span>
  }
}</pre>
                                    </div>
                                </div>
                            </div>

                            <!-- Currency Exchange -->


                            <section id="utilities-api" class="pt-8">
                                <h2
                                    class="text-xl sm:text-3xl font-bold text-white mb-6 uppercase tracking-wider border-b border-gray-800 pb-4">
                                    Intelligent Utilities</h2>
                            </section>
                            <div id="currency-exchange"
                                class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                <div
                                    class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                    <h3 class="text-base sm:text-lg font-bold text-white"><span
                                            class="text-blue-400 mr-2 uppercase">GET</span> /currency/exchange</h3>
                                    <span
                                        class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                        <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                            class="hidden sm:inline">Credits Required</span>
                                    </span>
                                </div>
                                <div class="p-6">
                                    <p class="mb-4">Get real-time currency exchange rates against USD and INR. We provide
                                        high-fidelity rates for 30+ major currencies, synchronized daily.</p>

                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                    <table class="w-full text-sm mb-8">
                                        <thead class="text-gray-500 text-left border-b border-gray-800">
                                            <tr>
                                                <th class="pb-2">Field</th>
                                                <th class="pb-2">Type</th>
                                                <th class="pb-2">Description</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-800">
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">currency</td>
                                                <td class="py-3 text-gray-500">string</td>
                                                <td class="py-3 text-gray-400">The 3-letter currency code (e.g. <code
                                                        class="text-amber-400">EUR</code>, <code
                                                        class="text-amber-400">GBP</code>, <code
                                                        class="text-amber-400">JPY</code>)</td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                    <div
                                        class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                        <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: {
    <span class="text-blue-400">"base_currency"</span>: <span class="text-green-400">"EUR"</span>,
    <span class="text-blue-400">"exchange_rates"</span>: {
      <span class="text-blue-400">"USD"</span>: <span class="text-blue-400">1.0842</span>,
      <span class="text-blue-400">"INR"</span>: <span class="text-blue-400">91.350</span>
    },
    <span class="text-blue-400">"last_updated"</span>: <span class="text-green-400">"2026-04-05 20:30:00"</span>,
    <span class="text-blue-400">"provider"</span>: <span class="text-green-400">"SetuGeo Financial Engine"</span>
  }
}</pre>
                                    </div>
                                </div>
                            </div>

                            <!-- Banks List -->


                            <section id="banking-api" class="pt-8">
                                <h2
                                    class="text-xl sm:text-3xl font-bold text-white mb-6 uppercase tracking-wider border-b border-gray-800 pb-4">
                                    Banking & Finance</h2>
                            </section>
                            <div id="banks" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                <div
                                    class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                    <h3 class="text-base sm:text-lg font-bold text-white"><span
                                            class="text-blue-400 mr-2 uppercase">GET</span> /banks</h3>
                                    <span
                                        class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                        <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                            class="hidden sm:inline">Credits Required</span>
                                    </span>
                                </div>
                                <div class="p-6">
                                    <p class="mb-4">Fetch list of all unique banks supported in the system.</p>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Optional Parameters</h4>
                                    <table class="w-full text-sm mb-4">
                                        <thead class="text-gray-500 text-left border-b border-gray-800">
                                            <tr>
                                                <th class="pb-2">Field</th>
                                                <th class="pb-2">Description</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-800">
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">name</td>
                                                <td class="py-3 text-gray-400">Search by bank name</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                    <div
                                        class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                        <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: [
    {
      <span class="text-blue-400">"id"</span>: <span class="text-blue-400">1</span>,
      <span class="text-blue-400">"name"</span>: <span class="text-green-400">"State Bank of India"</span>
    }
  ]
}</pre>
                                    </div>
                                </div>
                            </div>

                            <!-- Bank Branches -->
                            <div id="bank-branches"
                                class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                <div
                                    class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                    <h3 class="text-base sm:text-lg font-bold text-white"><span
                                            class="text-blue-400 mr-2 uppercase">GET</span> /bank/{bank_id}/branches</h3>
                                    <span
                                        class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                        <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                            class="hidden sm:inline">Credits Required</span>
                                    </span>
                                </div>
                                <div class="p-6">
                                    <p class="mb-4">Retrieve all available branches for a specific bank. Includes IFSC,
                                        MICR, and full address.</p>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Example Response</h4>
                                    <div class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto">
                                        <pre class="text-gray-400">{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: [
    {
      <span class="text-blue-400">"bank_id"</span>: <span class="text-blue-400">1</span>,
      <span class="text-blue-400">"ifsc"</span>: <span class="text-green-400">"ABHY0065001"</span>,
      <span class="text-blue-400">"branch"</span>: <span class="text-green-400">"Abhyudaya Co-operative Bank IMPS"</span>,
      <span class="text-blue-400">"address"</span>: <span class="text-green-400">"Abhyudaya Bhavan, V.G. Market, Vevay Road, Mumbai"</span>,
      <span class="text-blue-400">"city"</span>: {
        <span class="text-blue-400">"name"</span>: <span class="text-green-400">"Mumbai"</span>
      },
      <span class="text-blue-400">"state"</span>: {
        <span class="text-blue-400">"name"</span>: <span class="text-green-400">"Maharashtra"</span>
      }
    }
  ]
}</pre>
                                    </div>
                                </div>
                            </div>

                            <!-- Branch Info -->
                            <div id="branch-info"
                                class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                <div
                                    class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                    <h3 class="text-base sm:text-lg font-bold text-white"><span
                                            class="text-blue-400 mr-2 uppercase">GET</span> /bank/ifsc/{ifsc}</h3>
                                    <span
                                        class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                        <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                            class="hidden sm:inline">Credits Required</span>
                                    </span>
                                </div>
                            </div>

                            <!-- Banks List -->


                            <section id="banking-api" class="pt-8">
                                <h2
                                    class="text-xl sm:text-3xl font-bold text-white mb-6 uppercase tracking-wider border-b border-gray-800 pb-4">
                                    Banking & Finance</h2>
                            </section>
                            <div id="banks" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                <div
                                    class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                    <h3 class="text-base sm:text-lg font-bold text-white"><span
                                            class="text-blue-400 mr-2 uppercase">GET</span> /banks</h3>
                                    <span
                                        class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                        <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                            class="hidden sm:inline">Credits Required</span>
                                    </span>
                                </div>
                                <div class="p-6">
                                    <p class="mb-4">Fetch list of all unique banks supported in the system.</p>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Optional Parameters</h4>
                                    <table class="w-full text-sm mb-4">
                                        <thead class="text-gray-500 text-left border-b border-gray-800">
                                            <tr>
                                                <th class="pb-2">Field</th>
                                                <th class="pb-2">Description</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-800">
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">name</td>
                                                <td class="py-3 text-gray-400">Search by bank name</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                    <div class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto">
                                        <pre class="text-gray-400">{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: [
    {
      <span class="text-blue-400">"id"</span>: 1,
      <span class="text-blue-400">"name"</span>: <span class="text-green-400">"State Bank of India"</span>
    }
  ]
}</pre>
                                    </div>
                                </div>
                            </div>

                            <!-- Bank Branches -->
                            <div id="bank-branches"
                                class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                <div
                                    class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                    <h3 class="text-base sm:text-lg font-bold text-white"><span
                                            class="text-blue-400 mr-2 uppercase">GET</span> /bank/{bank_id}/branches</h3>
                                    <span
                                        class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                        <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                            class="hidden sm:inline">Credits Required</span>
                                    </span>
                                </div>
                                <div class="p-6">
                                    <p class="mb-4">Retrieve all available branches for a specific bank. Includes IFSC,
                                        MICR, and full address.</p>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Route Parameters</h4>
                                    <table class="w-full text-sm mb-8">
                                        <thead class="text-gray-500 text-left border-b border-gray-800">
                                            <tr>
                                                <th class="pb-2">Field</th>
                                                <th class="pb-2">Description</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-800">
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">bank_id</td>
                                                <td class="py-3 text-gray-400">The ID of the bank</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                    <div
                                        class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                        <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: [
    {
      <span class="text-blue-400">"bank_id"</span>: <span class="text-blue-400">1</span>,
      <span class="text-blue-400">"ifsc"</span>: <span class="text-green-400">"ABHY0065001"</span>,
      <span class="text-blue-400">"branch"</span>: <span class="text-green-400">"Abhyudaya Co-operative Bank IMPS"</span>,
      <span class="text-blue-400">"address"</span>: <span class="text-green-400">"Abhyudaya Bhavan, V.G. Market, Vevay Road, Mumbai"</span>,
      <span class="text-blue-400">"city"</span>: {
        <span class="text-blue-400">"name"</span>: <span class="text-green-400">"Mumbai"</span>
      },
      <span class="text-blue-400">"state"</span>: {
        <span class="text-blue-400">"name"</span>: <span class="text-green-400">"Maharashtra"</span>
      }
    }
  ]
}</pre>
                                    </div>
                                </div>
                            </div>

                            <!-- Branch Info -->
                            <div id="branch-info"
                                class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                <div
                                    class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                    <h3 class="text-base sm:text-lg font-bold text-white"><span
                                            class="text-blue-400 mr-2 uppercase">GET</span> /bank/ifsc/{ifsc}</h3>
                                    <span
                                        class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                        <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                            class="hidden sm:inline">Credits Required</span>
                                    </span>
                                </div>
                                <div class="p-6">
                                    <p class="mb-4">Fetch comprehensive details of a single branch using its unique IFSC
                                        code.</p>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Route Parameters</h4>
                                    <table class="w-full text-sm mb-8">
                                        <thead class="text-gray-500 text-left border-b border-gray-800">
                                            <tr>
                                                <th class="pb-2">Field</th>
                                                <th class="pb-2">Description</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-800">
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">ifsc</td>
                                                <td class="py-3 text-gray-400">The 11-character IFSC code (e.g.,
                                                    SBIN0000001)</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                    <div
                                        class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                        <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: {
    <span class="text-blue-400">"ifsc"</span>: <span class="text-green-400">"SBIN0000001"</span>,
    <span class="text-blue-400">"bank"</span>: {
      <span class="text-blue-400">"id"</span>: <span class="text-blue-400">1</span>,
      <span class="text-blue-400">"name"</span>: <span class="text-green-400">"State Bank of India"</span>
    },
    <span class="text-blue-400">"branch"</span>: <span class="text-green-400">"Kolkata Main Branch"</span>,
    <span class="text-blue-400">"address"</span>: <span class="text-green-400">"Samriddhi Bhavan, 1, Strand Road, Kolkata"</span>,
    <span class="text-blue-400">"city"</span>: {
      <span class="text-blue-400">"name"</span>: <span class="text-green-400">"Kolkata"</span>
    },
    <span class="text-blue-400">"state"</span>: {
      <span class="text-blue-400">"name"</span>: <span class="text-green-400">"West Bengal"</span>
    },
    <span class="text-blue-400">"micr"</span>: <span class="text-green-400">"700002021"</span>
  }
}</pre>
                                    </div>
                                </div>
                            </div>

                            <!-- Banks in City -->
                            <div id="banks-in-city"
                                class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                <div
                                    class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                    <h3 class="text-base sm:text-lg font-bold text-white"><span
                                            class="text-blue-400 mr-2 uppercase">GET</span> /city/{city_id}/banks</h3>
                                    <span
                                        class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                        <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                            class="hidden sm:inline">Credits Required</span>
                                    </span>
                                </div>
                                <div class="p-6">
                                    <p class="mb-4">Get a collection of all banks that have active physical branches in a
                                        specified city.</p>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Route Parameters</h4>
                                    <table class="w-full text-sm mb-8">
                                        <thead class="text-gray-500 text-left border-b border-gray-800">
                                            <tr>
                                                <th class="pb-2">Field</th>
                                                <th class="pb-2">Description</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-800">
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">city_id</td>
                                                <td class="py-3 text-gray-400">The ID of the city</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                    <div
                                        class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                        <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: [
    {
      <span class="text-blue-400">"id"</span>: <span class="text-blue-400">1</span>,
      <span class="text-blue-400">"name"</span>: <span class="text-green-400">"State Bank of India"</span>
    }
  ]
}</pre>
                                    </div>
                                </div>
                            </div>

                            <!-- Banks in State -->
                            <div id="banks-in-state"
                                class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                <div
                                    class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                    <h3 class="text-base sm:text-lg font-bold text-white"><span
                                            class="text-blue-400 mr-2 uppercase">GET</span> /state/{state_id}/banks</h3>
                                    <span
                                        class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                        <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                            class="hidden sm:inline">Credits Required</span>
                                    </span>
                                </div>
                                <div class="p-6">
                                    <p class="mb-4">List all banks operating within a particular state.</p>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Route Parameters</h4>
                                    <table class="w-full text-sm mb-8">
                                        <thead class="text-gray-500 text-left border-b border-gray-800">
                                            <tr>
                                                <th class="pb-2">Field</th>
                                                <th class="pb-2">Description</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-800">
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">state_id</td>
                                                <td class="py-3 text-gray-400">The ID of the state</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                    <div
                                        class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                        <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: [
    {
      <span class="text-blue-400">"id"</span>: <span class="text-blue-400">1</span>,
      <span class="text-blue-400">"name"</span>: <span class="text-green-400">"State Bank of India"</span>
    }
  ]
}</pre>
                                    </div>
                                </div>
                            </div>

                            <!-- Currency Convert -->
                            <div id="currency-convert"
                                class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                <div
                                    class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                    <h3 class="text-base sm:text-lg font-bold text-white"><span
                                            class="text-blue-400 mr-2 uppercase">GET</span> /currency/convert</h3>
                                    <span
                                        class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                        <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                            class="hidden sm:inline">Credits Required</span>
                                    </span>
                                </div>
                                <div class="p-6">
                                    <p class="mb-4">Convert a defined financial amount from a source currency to a target
                                        currency dynamically.</p>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                    <table class="w-full text-sm mb-8">
                                        <thead class="text-gray-500 text-left border-b border-gray-800">
                                            <tr>
                                                <th class="pb-2">Field</th>
                                                <th class="pb-2">Type</th>
                                                <th class="pb-2">Description</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-800">
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">from</td>
                                                <td class="py-3 text-gray-400">string</td>
                                                <td class="py-3 text-gray-400">Source currency (e.g., <code
                                                        class="text-amber-400">USD</code>)</td>
                                            </tr>
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">to</td>
                                                <td class="py-3 text-gray-400">string</td>
                                                <td class="py-3 text-gray-400">Target currency (e.g., <code
                                                        class="text-amber-400">INR</code>)</td>
                                            </tr>
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">amount</td>
                                                <td class="py-3 text-gray-400">float</td>
                                                <td class="py-3 text-gray-400">The amount to convert (e.g., <code
                                                        class="text-white">100</code>)</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                    <div
                                        class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                        <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: {
    <span class="text-blue-400">"from"</span>: <span class="text-green-400">"USD"</span>,
    <span class="text-blue-400">"to"</span>: <span class="text-green-400">"INR"</span>,
    <span class="text-blue-400">"original_amount"</span>: <span class="text-blue-400">100</span>,
    <span class="text-blue-400">"converted_amount"</span>: <span class="text-blue-400">8345.50</span>,
    <span class="text-blue-400">"rate"</span>: <span class="text-blue-400">83.455</span>
  }
}</pre>
                                    </div>
                                </div>
                            </div>

                            <!-- Timezones Convert -->
                            <div id="timezones-convert"
                                class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                <div
                                    class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                    <h3 class="text-base sm:text-lg font-bold text-white"><span
                                            class="text-blue-400 mr-2 uppercase">GET</span> /timezone/convert</h3>
                                    <span
                                        class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                        <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                            class="hidden sm:inline">Credits Required</span>
                                    </span>
                                </div>
                                <div class="p-6">
                                    <p class="mb-4">Map a specific datatime string between any two global IANA timezones.
                                    </p>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                    <table class="w-full text-sm mb-8">
                                        <thead class="text-gray-500 text-left border-b border-gray-800">
                                            <tr>
                                                <th class="pb-2">Field</th>
                                                <th class="pb-2">Description</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-800">
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">from</td>
                                                <td class="py-3 text-gray-400">Source timezone (e.g., <code
                                                        class="text-amber-400">UTC</code>)</td>
                                            </tr>
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">to</td>
                                                <td class="py-3 text-gray-400">Target timezone (e.g., <code
                                                        class="text-amber-400">Asia/Kolkata</code>)</td>
                                            </tr>
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">time</td>
                                                <td class="py-3 text-gray-400">Original datetime string (optional, defaults
                                                    to current time)</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                    <div
                                        class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                        <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: {
    <span class="text-blue-400">"from"</span>: {
      <span class="text-blue-400">"zone"</span>: <span class="text-green-400">"UTC"</span>,
      <span class="text-blue-400">"time"</span>: <span class="text-green-400">"2026-04-13 12:00:00"</span>
    },
    <span class="text-blue-400">"to"</span>: {
      <span class="text-blue-400">"zone"</span>: <span class="text-green-400">"Asia/Kolkata"</span>,
      <span class="text-blue-400">"time"</span>: <span class="text-green-400">"2026-04-13 17:30:00"</span>
    }
  }
}</pre>
                                    </div>
                                </div>
                            </div>

                            <!-- Branch Search -->
                            <div id="branch-search"
                                class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                <div
                                    class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                    <h3 class="text-base sm:text-lg font-bold text-white"><span
                                            class="text-blue-400 mr-2 uppercase">GET</span> /bank/branches/search</h3>
                                    <span
                                        class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                        <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                            class="hidden sm:inline">Credits Required</span>
                                    </span>
                                </div>
                                <div class="p-6">
                                    <p class="mb-4">Perform a fuzzy search across the total banking directory using
                                        keywords, locations, or names.</p>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                    <table class="w-full text-sm mb-8">
                                        <thead class="text-gray-500 text-left border-b border-gray-800">
                                            <tr>
                                                <th class="pb-2">Field</th>
                                                <th class="pb-2">Description</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-800">
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">search_query</td>
                                                <td class="py-3 text-gray-400">Your search keyword</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                    <div
                                        class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                        <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: [
    {
      <span class="text-blue-400">"id"</span>: <span class="text-blue-400">45</span>,
      <span class="text-blue-400">"ifsc"</span>: <span class="text-green-400">"SBIN0000001"</span>,
      <span class="text-blue-400">"branch"</span>: <span class="text-green-400">"Mumbai Main"</span>
    }
  ]
}</pre>
                                    </div>
                                </div>
                            </div>

                            <!-- Bank Coverage Map -->
                            <div id="bank-coverage"
                                class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                <div
                                    class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                    <h3 class="text-base sm:text-lg font-bold text-white"><span
                                            class="text-blue-400 mr-2 uppercase">GET</span> /bank/{bank_id}/coverage</h3>
                                    <span
                                        class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                        <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                            class="hidden sm:inline">Credits Required</span>
                                    </span>
                                </div>
                                <div class="p-6">
                                    <p class="mb-4">Gain analytical insights into a bank's national footprint. Returns
                                        total aggregated branch counts grouped by physical state jurisdiction.</p>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Route Parameters</h4>
                                    <table class="w-full text-sm mb-8">
                                        <thead class="text-gray-500 text-left border-b border-gray-800">
                                            <tr>
                                                <th class="pb-2">Field</th>
                                                <th class="pb-2">Description</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-800">
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">bank_id</td>
                                                <td class="py-3 text-gray-400">The ID of the bank</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                    <div
                                        class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                        <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: {
    <span class="text-blue-400">"total_branches"</span>: <span class="text-blue-400">14000</span>,
    <span class="text-blue-400">"states"</span>: [
      {
        <span class="text-blue-400">"name"</span>: <span class="text-green-400">"Maharashtra"</span>,
        <span class="text-blue-400">"branches"</span>: <span class="text-blue-400">1200</span>
      }
    ]
  }
}</pre>
                                    </div>
                                </div>
                            </div>

                            <!-- Banks in Pincode -->
                            <div id="banks-in-pincode"
                                class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                <div
                                    class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                    <h3 class="text-base sm:text-lg font-bold text-white"><span
                                            class="text-blue-400 mr-2 uppercase">GET</span> /pincode/{pincode}/banks</h3>
                                    <span
                                        class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                        <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                            class="hidden sm:inline">Credits Required</span>
                                    </span>
                                </div>
                                <div class="p-6">
                                    <p class="mb-4">Evaluate hyper-local banking infrastructure. List all financial
                                        institutions matching a precise postal coordinate.</p>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Route Parameters</h4>
                                    <table class="w-full text-sm mb-8">
                                        <thead class="text-gray-500 text-left border-b border-gray-800">
                                            <tr>
                                                <th class="pb-2">Field</th>
                                                <th class="pb-2">Description</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-800">
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">pincode</td>
                                                <td class="py-3 text-gray-400">The postal code (e.g. 400001)</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                    <div
                                        class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                        <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: [
    {
      <span class="text-blue-400">"id"</span>: <span class="text-blue-400">1</span>,
      <span class="text-blue-400">"name"</span>: <span class="text-green-400">"State Bank of India"</span>
    }
  ]
}</pre>
                                    </div>
                                </div>
                            </div>


                            <section id="equity-api" class="pt-8">
                                <h2
                                    class="text-xl sm:text-3xl font-bold text-white mb-6 uppercase tracking-wider border-b border-gray-800 pb-4">
                                    Equity Intelligence</h2>

                                <div class="space-y-12">
                                    <!-- List Equities -->
                                    <div id="list-equities"
                                        class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div
                                            class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span
                                                    class="text-blue-400 mr-2 uppercase">GET</span> /equities</h3>
                                            <span
                                                class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                                <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                                    class="hidden sm:inline">Credits Required</span>
                                            </span>
                                        </div>
                                        <div class="p-6">
                                            <p class="mb-4">Get a paginated list of all active equities. Supports
                                                discovery by industry or specific symbols.</p>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters
                                            </h4>
                                            <table class="w-full text-sm mb-8">
                                                <thead class="text-gray-500 text-left border-b border-gray-800">
                                                    <tr>
                                                        <th class="pb-2">Field</th>
                                                        <th class="pb-2">Description</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-800">
                                                    <tr>
                                                        <td class="py-3 font-mono text-amber-500">industry</td>
                                                        <td class="py-3 text-gray-400">Filter by industry (e.g. "Banking",
                                                            "Automobiles")</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="py-3 font-mono text-amber-500">symbol</td>
                                                        <td class="py-3 text-gray-400">Search by symbol (Partial match)
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="py-3 font-mono text-amber-500">market_cap</td>
                                                        <td class="py-3 text-gray-400">Filter by market capitalization
                                                            (e.g. <code class="text-amber-400">Large Cap</code>, <code
                                                                class="text-amber-400">Mid Cap</code>)</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example
                                            </h4>
                                            <div
                                                class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                                <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: {
    <span class="text-blue-400">"current_page"</span>: <span class="text-blue-400">1</span>,
    <span class="text-blue-400">"data"</span>: [
      {
        <span class="text-blue-400">"isin"</span>: <span class="text-green-400">"INE002A01018"</span>,
        <span class="text-blue-400">"company_name"</span>: <span class="text-green-400">"RELIANCE INDUSTRIES LTD"</span>,
        <span class="text-blue-400">"nse_symbol"</span>: <span class="text-green-400">"RELIANCE"</span>,
        <span class="text-blue-400">"bse_symbol"</span>: <span class="text-green-400">"500325"</span>,
        <span class="text-blue-400">"industry"</span>: <span class="text-green-400">"Oil & Gas"</span>,
        <span class="text-blue-400">"market_cap"</span>: <span class="text-green-400">"Large Cap"</span>
      }
    ]
  }
}</pre>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Market Cap Filter -->
                                    <div id="equity-market-cap"
                                        class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div
                                            class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span
                                                    class="text-blue-400 mr-2 uppercase">GET</span>
                                                /equities/filter/market-cap/{cap}</h3>
                                            <span
                                                class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                                <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                                    class="hidden sm:inline">Credits Required</span>
                                            </span>
                                        </div>
                                        <div class="p-6">
                                            <p class="mb-4">Fetch complete segments of the market based on
                                                capitalization categorization. Our algorithm groups stocks into four
                                                distinct institutional levels.</p>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Route Parameters
                                            </h4>
                                            <table class="w-full text-sm mb-8">
                                                <thead class="text-gray-500 text-left border-b border-gray-800">
                                                    <tr>
                                                        <th class="pb-2">Field</th>
                                                        <th class="pb-2">Type</th>
                                                        <th class="pb-2">Description / Allowed Values</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-800">
                                                    <tr>
                                                        <td class="py-3 font-mono text-amber-500">cap</td>
                                                        <td class="py-3 text-gray-400 font-mono">string</td>
                                                        <td class="py-3 text-gray-400 leading-relaxed">
                                                            Accepted segments:
                                                            <ul class="mt-2 list-disc list-inside text-amber-500/80">
                                                                <li><span class="font-bold font-mono">Large Cap</span>
                                                                    (Top 100 Companies)</li>
                                                                <li><span class="font-bold font-mono">Mid Cap</span> (101
                                                                    - 250 Rank)</li>
                                                                <li><span class="font-bold font-mono">Small Cap</span>
                                                                    (251 Rank onwards)</li>
                                                                <li><span class="font-bold font-mono">Micro Cap</span>
                                                                    (Smallest market size)</li>
                                                            </ul>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example
                                                (Large Cap)</h4>
                                            <div
                                                class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                                <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"category"</span>: <span class="text-green-400">"Large Cap"</span>,
  <span class="text-blue-400">"data"</span>: [
    {
      <span class="text-blue-400">"isin"</span>: <span class="text-green-400">"INE002A01018"</span>,
      <span class="text-blue-400">"company_name"</span>: <span class="text-green-400">"RELIANCE"</span>,
      <span class="text-blue-400">"nse_symbol"</span>: <span class="text-green-400">"RELIANCE"</span>,
      <span class="text-blue-400">"industry"</span>: <span class="text-green-400">"Oil & Gas"</span>
    }
  ]
}</pre>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Search Equities -->
                                    <div id="equity-search"
                                        class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div
                                            class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span
                                                    class="text-blue-400 mr-2 uppercase">GET</span> /equities/search</h3>
                                            <span
                                                class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                                <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                                    class="hidden sm:inline">Credits Required</span>
                                            </span>
                                        </div>
                                        <div class="p-6">
                                            <p class="mb-4">Robust search engine for identifying equities. Supports
                                                partial matches of Company Names, NSE/BSE Symbols, or ISINs.</p>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters
                                            </h4>
                                            <table class="w-full text-sm mb-6">
                                                <thead class="text-gray-500 text-left border-b border-gray-800">
                                                    <tr>
                                                        <th class="pb-2">Field</th>
                                                        <th class="pb-2">Description</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-800 text-gray-400">
                                                    <tr>
                                                        <td class="py-3 font-mono text-amber-500">q</td>
                                                        <td class="py-3 text-gray-400">Search keyword (e.g. "Reliance",
                                                            "INFY", "INE002A01018")</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Top Gainers / Losers -->
                                    <div id="equity-rankings"
                                        class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div
                                            class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span
                                                    class="text-blue-400 mr-2 uppercase">GET</span>
                                                /equities/analysis/top-gainers</h3>
                                            <span
                                                class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                                <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                                    class="hidden sm:inline">Credits Required</span>
                                            </span>
                                        </div>
                                        <div class="p-6">
                                            <p class="mb-4">Fetch daily market performance leaders. Same pattern exists
                                                for:
                                                <code class="text-white">/equities/analysis/top-losers</code>,
                                                <code class="text-white">/equities/analysis/high-volume</code>, and
                                                <code class="text-white">/equities/analysis/top-turnover</code>.
                                            </p>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters
                                            </h4>
                                            <table class="w-full text-sm mb-6">
                                                <thead class="text-gray-500 text-left border-b border-gray-800">
                                                    <tr>
                                                        <th class="pb-2">Field</th>
                                                        <th class="pb-2">Description</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-800">
                                                    <tr>
                                                        <td class="py-3 font-mono text-amber-500">exchange</td>
                                                        <td class="py-3 text-gray-400">NSE or BSE ranking (default: <code
                                                                class="text-white">nse</code>)</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- New Listings & Stats -->
                                    <div id="equity-discovery"
                                        class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div
                                            class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span
                                                    class="text-blue-400 mr-2 uppercase">GET</span>
                                                /equities/analysis/new-listings</h3>
                                            <span
                                                class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                                <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                                    class="hidden sm:inline">Credits Required</span>
                                            </span>
                                        </div>
                                        <div class="p-6 border-b border-gray-800/50">
                                            <p class="text-gray-400">Discover recently listed equities (IPOs) sorted by
                                                listing date.</p>
                                        </div>
                                        <div
                                            class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span
                                                    class="text-blue-400 mr-2 uppercase">GET</span>
                                                /equities/analysis/market-cap-stats</h3>
                                        </div>
                                        <div class="p-6">
                                            <p class="text-gray-400">Retrieve global market distribution counts for Large,
                                                Mid, and Small Cap categories.</p>
                                        </div>
                                    </div>

                                    <!-- Equity Detail -->
                                    <div id="equity-detail"
                                        class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div
                                            class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span
                                                    class="text-blue-400 mr-2 uppercase">GET</span> /equity/{isin}</h3>
                                            <span
                                                class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                                <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                                    class="hidden sm:inline">Credits Required</span>
                                            </span>
                                        </div>
                                        <div class="p-6">
                                            <p class="mb-4">Fetch comprehensive profile details for a specific equity
                                                using its ISIN or Symbol.</p>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Route Parameters
                                            </h4>
                                            <table class="w-full text-sm mb-6">
                                                <thead class="text-gray-500 text-left border-b border-gray-800">
                                                    <tr>
                                                        <th class="pb-2">Field</th>
                                                        <th class="pb-2">Description</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-800">
                                                    <tr>
                                                        <td class="py-3 font-mono text-amber-500">isin</td>
                                                        <td class="py-3 text-gray-400">The unique identifier (e.g. <code
                                                                class="text-amber-400">INE002A01018</code>)</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example
                                            </h4>
                                            <div
                                                class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                                <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: {
    <span class="text-blue-400">"isin"</span>: <span class="text-green-400">"INE002A01018"</span>,
    <span class="text-blue-400">"company_name"</span>: <span class="text-green-400">"RELIANCE INDUSTRIES LTD"</span>,
    <span class="text-blue-400">"nse_symbol"</span>: <span class="text-green-400">"RELIANCE"</span>,
    <span class="text-blue-400">"bse_symbol"</span>: <span class="text-green-400">"500325"</span>,
    <span class="text-blue-400">"industry"</span>: <span class="text-green-400">"Oil & Gas"</span>,
    <span class="text-blue-400">"market_cap"</span>: <span class="text-green-400">"2098450.50"</span>,
    <span class="text-blue-400">"market_cap_category"</span>: <span class="text-green-400">"Large Cap"</span>,
    <span class="text-blue-400">"face_value"</span>: <span class="text-blue-400">10.00</span>,
    <span class="text-blue-400">"listing_date"</span>: <span class="text-green-400">"1977-01-01"</span>
  }
}</pre>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Price History -->
                                    <div id="equity-history"
                                        class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div
                                            class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span
                                                    class="text-blue-400 mr-2 uppercase">GET</span> /equity/{isin}/history
                                            </h3>
                                            <span
                                                class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                                <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                                    class="hidden sm:inline">Credits Required</span>
                                            </span>
                                        </div>
                                        <div class="p-6">
                                            <p class="mb-4 text-gray-400">Retrieve time-series pricing data for the last
                                                30 trading days. Perfect for building dynamic interactive charts.</p>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example
                                            </h4>
                                            <div
                                                class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                                <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"isin"</span>: <span class="text-green-400">"INE002A01018"</span>,
  <span class="text-blue-400">"data"</span>: [
    {
      <span class="text-blue-400">"traded_date"</span>: <span class="text-green-400">"2026-04-13"</span>,
      <span class="text-blue-400">"nse_close"</span>: <span class="text-blue-400">2950.45</span>,
      <span class="text-blue-400">"bse_close"</span>: <span class="text-blue-400">2950.10</span>,
      <span class="text-blue-400">"nse_volume"</span>: <span class="text-blue-400">1254890</span>
    }
  ]
}</pre>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Performance Metrics -->
                                    <div id="equity-metrics"
                                        class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div
                                            class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span
                                                    class="text-blue-400 mr-2 uppercase">GET</span> /equity/{isin}/metrics
                                            </h3>
                                            <span
                                                class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                                <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                                    class="hidden sm:inline">Credits Required</span>
                                            </span>
                                        </div>
                                        <div class="p-6 border-b border-gray-800/50">
                                            <p class="mb-4">Access pre-calculated performance analytics, including
                                                percentage changes for 1D, 7D, 1-Month, 1-Year, and 3-Year windows.</p>
                                        </div>
                                        <div
                                            class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span
                                                    class="text-blue-400 mr-2 uppercase">GET</span> /equity/{isin}/peers
                                            </h3>
                                        </div>
                                        <div class="p-6">
                                            <p class="text-gray-400">Retrieve top peers in the same industry based on
                                                market capitalization.</p>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section id="index-api" class="pt-8">
                                <h2
                                    class="text-xl sm:text-3xl font-bold text-white mb-6 uppercase tracking-wider border-b border-gray-800 pb-4">
                                    Index Intelligence</h2>

                                <div class="space-y-12">
                                    <!-- Index Snapshot -->
                                    <div id="index-snapshot" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /indices/snapshot</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                                <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                            </span>
                                        </div>
                                        <div class="p-6">
                                            <p class="mb-4">Get the latest market snapshot for all benchmark and sectoral indices. Supports filtering by exchange.</p>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                            <table class="w-full text-sm mb-8">
                                                <thead class="text-gray-500 text-left border-b border-gray-800">
                                                    <tr>
                                                        <th class="pb-2">Field</th>
                                                        <th class="pb-2">Description</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-800 text-gray-400">
                                                    <tr>
                                                        <td class="py-3 font-mono text-amber-500">exchange</td>
                                                        <td class="py-3">Filter by exchange (e.g. <code class="text-white">NSE</code>, <code class="text-white">BSE</code>)</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                            <div class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                                <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: [
    {
      <span class="text-blue-400">"code"</span>: <span class="text-green-400">"NIFTY 50"</span>,
      <span class="text-blue-400">"name"</span>: <span class="text-green-400">"NIFTY 50"</span>,
      <span class="text-blue-400">"exchange"</span>: <span class="text-green-400">"NSE"</span>,
      <span class="text-blue-400">"close"</span>: <span class="text-blue-400">22453.20</span>,
      <span class="text-blue-400">"change_percent"</span>: <span class="text-blue-400">0.85</span>,
      <span class="text-blue-400">"updated_at"</span>: <span class="text-green-400">"2026-04-13"</span>
    }
  ]
}</pre>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Index Search -->
                                    <div id="index-search" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /indices/search</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                                <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                            </span>
                                        </div>
                                        <div class="p-6">
                                            <p class="mb-4">Search for indices by name or code.</p>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                            <table class="w-full text-sm mb-6">
                                                <thead class="text-gray-500 text-left border-b border-gray-800">
                                                    <tr>
                                                        <th class="pb-2">Field</th>
                                                        <th class="pb-2">Description</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-800 text-gray-400">
                                                    <tr>
                                                        <td class="py-3 font-mono text-amber-500">q</td>
                                                        <td class="py-3">Search keyword (e.g. "Nifty", "Sensex")</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Index Rankings -->
                                    <div id="index-rankings" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /indices/analysis/top-gainers</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                                <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                            </span>
                                        </div>
                                        <div class="p-6">
                                            <p class="mb-4">Fetch top-performing indices based on returns for a specific period. Same pattern exists for <code class="text-white">/indices/analysis/top-losers</code>.</p>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                            <table class="w-full text-sm mb-6">
                                                <thead class="text-gray-500 text-left border-b border-gray-800">
                                                    <tr>
                                                        <th class="pb-2">Field</th>
                                                        <th class="pb-2">Description</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-800 text-gray-400">
                                                    <tr>
                                                        <td class="py-3 font-mono text-amber-500">period</td>
                                                        <td class="py-3">Period for returns: <code class="text-white">1d</code>, <code class="text-white">7d</code>, <code class="text-white">1m</code>, <code class="text-white">1y</code>, etc. (Default: 1d)</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="py-3 font-mono text-amber-500">limit</td>
                                                        <td class="py-3">Number of results (Default: 10)</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Index Metrics -->
                                    <div id="index-metrics" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /indices/{index_code}/metrics</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                                <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                            </span>
                                        </div>
                                        <div class="p-6">
                                            <p class="mb-4 text-gray-400">Access comprehensive performance analytics for a specific index, including multi-horizon returns and underlying historical reference values.</p>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                            <div class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                                <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: {
    <span class="text-blue-400">"code"</span>: <span class="text-green-400">"NIFTY 50"</span>,
    <span class="text-blue-400">"current_close"</span>: <span class="text-blue-400">22453.20</span>,
    <span class="text-blue-400">"returns"</span>: {
      <span class="text-blue-400">"1d"</span>: <span class="text-blue-400">0.85</span>,
      <span class="text-blue-400">"7d"</span>: <span class="text-blue-400">1.20</span>,
      <span class="text-blue-400">"1m"</span>: <span class="text-blue-400">3.45</span>,
      <span class="text-blue-400">"1y"</span>: <span class="text-blue-400">18.20</span>
    },
    <span class="text-blue-400">"historical_values"</span>: {
      <span class="text-blue-400">"1d"</span>: <span class="text-blue-400">22264.00</span>,
      <span class="text-blue-400">"1m"</span>: <span class="text-blue-400">21704.50</span>
    }
  }
}</pre>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Index History -->
                                    <div id="index-history" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /indices/{index_code}/history</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                                <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                            </span>
                                        </div>
                                        <div class="p-6">
                                            <p class="mb-4 text-gray-400">Retrieve time-series pricing data for an index. Ideal for charting and trend analysis.</p>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                            <table class="w-full text-sm mb-6">
                                                <thead class="text-gray-500 text-left border-b border-gray-800">
                                                    <tr>
                                                        <th class="pb-2">Field</th>
                                                        <th class="pb-2">Description</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-800 text-gray-400">
                                                    <tr>
                                                        <td class="py-3 font-mono text-amber-500">start_date</td>
                                                        <td class="py-3">Format: <code class="text-white">YYYY-MM-DD</code></td>
                                                    </tr>
                                                    <tr>
                                                        <td class="py-3 font-mono text-amber-500">end_date</td>
                                                        <td class="py-3">Format: <code class="text-white">YYYY-MM-DD</code></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section id="mf-api" class="pt-8">
                                <h2 class="text-xl sm:text-3xl font-bold text-white mb-6 uppercase tracking-wider border-b border-gray-800 pb-4">
                                    Mutual Fund Intelligence</h2>
                                <p class="text-gray-400 mb-8">Access India's complete AMFI mutual fund universe — 13,000+ schemes with pre-computed multi-period returns (1D to 3Y), NAV history, and analytical rankings. All endpoints are prefixed with <code class="text-amber-400">/mf</code>.</p>

                                <div class="space-y-12">

                                    <!-- MF List -->
                                    <div id="mf-list" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /mf/list</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                                <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                            </span>
                                        </div>
                                        <div class="p-6">
                                            <p class="mb-4 text-gray-400">Paginated list of all active AMFI mutual fund schemes with latest NAV and key return metrics. Supports filtering by category, AMC, and type.</p>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                            <table class="w-full text-sm mb-8">
                                                <thead class="text-gray-500 text-left border-b border-gray-800"><tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr></thead>
                                                <tbody class="divide-y divide-gray-800 text-gray-400">
                                                    <tr><td class="py-3 font-mono text-amber-500">search</td><td class="py-3">Filter by scheme name, AMC, or ISIN</td></tr>
                                                    <tr><td class="py-3 font-mono text-amber-500">category</td><td class="py-3">e.g. <code class="text-white">Equity</code>, <code class="text-white">Debt</code>, <code class="text-white">Hybrid</code>, <code class="text-white">ETF</code></td></tr>
                                                    <tr><td class="py-3 font-mono text-amber-500">amc_name</td><td class="py-3">Full AMC name (use <code class="text-white">/mf/filters</code> for valid values)</td></tr>
                                                    <tr><td class="py-3 font-mono text-amber-500">per_page</td><td class="py-3">Results per page (default: 20, max: 100)</td></tr>
                                                    <tr><td class="py-3 font-mono text-amber-500">page</td><td class="py-3">Page number</td></tr>
                                                </tbody>
                                            </table>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                            <div class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                                <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: [
    {
      <span class="text-blue-400">"isin"</span>: <span class="text-green-400">"INF109K01VQ0"</span>,
      <span class="text-blue-400">"scheme_name"</span>: <span class="text-green-400">"Axis Bluechip Fund - Growth"</span>,
      <span class="text-blue-400">"amc_name"</span>: <span class="text-green-400">"Axis Mutual Fund"</span>,
      <span class="text-blue-400">"category"</span>: <span class="text-green-400">"Equity"</span>,
      <span class="text-blue-400">"nav"</span>: <span class="text-blue-400">54.32</span>,
      <span class="text-blue-400">"nav_date"</span>: <span class="text-green-400">"2026-04-17"</span>,
      <span class="text-blue-400">"chg_1d"</span>: <span class="text-blue-400">0.42</span>,
      <span class="text-blue-400">"chg_1y"</span>: <span class="text-blue-400">18.74</span>
    }
  ],
  <span class="text-blue-400">"meta"</span>: { <span class="text-blue-400">"total"</span>: 13241, <span class="text-blue-400">"per_page"</span>: 20, <span class="text-blue-400">"current_page"</span>: 1, <span class="text-blue-400">"last_page"</span>: 663 }
}</pre>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- MF Search -->
                                    <div id="mf-search" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /mf/search</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                                <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                            </span>
                                        </div>
                                        <div class="p-6">
                                            <p class="mb-4 text-gray-400">Fast keyword search across scheme name, ISIN, AMC name, and scheme code. Returns up to 50 matches with latest NAV and 1D/1Y returns.</p>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                            <table class="w-full text-sm mb-6">
                                                <thead class="text-gray-500 text-left border-b border-gray-800"><tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr></thead>
                                                <tbody class="divide-y divide-gray-800 text-gray-400">
                                                    <tr><td class="py-3 font-mono text-amber-500">q <span class="text-rose-400">*</span></td><td class="py-3">Search keyword (required)</td></tr>
                                                    <tr><td class="py-3 font-mono text-amber-500">limit</td><td class="py-3">Max results (default: 20, max: 50)</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- MF Filters -->
                                    <div id="mf-filters" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /mf/filters</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                                <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                            </span>
                                        </div>
                                        <div class="p-6">
                                            <p class="mb-4 text-gray-400">Returns all distinct categories, types, and AMC names — use this to populate filter dropdowns in your app before calling <code class="text-white">/mf/list</code>.</p>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                            <div class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                                <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"categories"</span>: [<span class="text-green-400">"Debt"</span>, <span class="text-green-400">"ETF"</span>, <span class="text-green-400">"Equity"</span>, <span class="text-green-400">"FoF"</span>, <span class="text-green-400">"Hybrid"</span>, <span class="text-green-400">"Index"</span>],
  <span class="text-blue-400">"amcs"</span>: [<span class="text-green-400">"Axis Mutual Fund"</span>, <span class="text-green-400">"HDFC Mutual Fund"</span>, <span class="text-green-400">"...</span>"]
}</pre>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- MF Top Gainers / Losers -->
                                    <div id="mf-rankings" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /mf/analysis/top-gainers</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                                <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                            </span>
                                        </div>
                                        <div class="p-6">
                                            <p class="mb-4 text-gray-400">Top-performing mutual funds ranked by pre-computed returns for any period. Same pattern applies to <code class="text-white">/mf/analysis/top-losers</code>.</p>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                            <table class="w-full text-sm mb-8">
                                                <thead class="text-gray-500 text-left border-b border-gray-800"><tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr></thead>
                                                <tbody class="divide-y divide-gray-800 text-gray-400">
                                                    <tr><td class="py-3 font-mono text-amber-500">period</td><td class="py-3">Return period: <code class="text-white">1d</code>, <code class="text-white">3d</code>, <code class="text-white">7d</code>, <code class="text-white">1m</code>, <code class="text-white">3m</code>, <code class="text-white">6m</code>, <code class="text-white">9m</code>, <code class="text-white">1y</code>, <code class="text-white">3y</code> (default: <code class="text-white">1y</code>)</td></tr>
                                                    <tr><td class="py-3 font-mono text-amber-500">category</td><td class="py-3">Filter by category (e.g. <code class="text-white">Equity</code>)</td></tr>
                                                    <tr><td class="py-3 font-mono text-amber-500">amc_name</td><td class="py-3">Filter by AMC</td></tr>
                                                    <tr><td class="py-3 font-mono text-amber-500">limit</td><td class="py-3">Number of results (default: 10, max: 50)</td></tr>
                                                </tbody>
                                            </table>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                            <div class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                                <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"period"</span>: <span class="text-green-400">"1y"</span>,
  <span class="text-blue-400">"nav_date"</span>: <span class="text-green-400">"2026-04-17"</span>,
  <span class="text-blue-400">"data"</span>: [
    {
      <span class="text-blue-400">"isin"</span>: <span class="text-green-400">"INF204KB14I2"</span>,
      <span class="text-blue-400">"scheme_name"</span>: <span class="text-green-400">"Nippon India Small Cap Fund - Growth"</span>,
      <span class="text-blue-400">"category"</span>: <span class="text-green-400">"Equity"</span>,
      <span class="text-blue-400">"nav"</span>: <span class="text-blue-400">178.43</span>,
      <span class="text-blue-400">"return_pct"</span>: <span class="text-blue-400">54.82</span>,
      <span class="text-blue-400">"ref_nav"</span>: <span class="text-blue-400">115.26</span>
    }
  ]
}</pre>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- MF Details -->
                                    <div id="mf-details" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /mf/details/{isin}</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                                <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                            </span>
                                        </div>
                                        <div class="p-6">
                                            <p class="mb-4 text-gray-400">Full scheme profile with latest NAV and pre-computed returns for all 9 periods (1D → 3Y). Returns are read directly from the database — zero computation overhead.</p>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                            <div class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                                <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: {
    <span class="text-blue-400">"isin"</span>: <span class="text-green-400">"INF109K01VQ0"</span>,
    <span class="text-blue-400">"scheme_name"</span>: <span class="text-green-400">"Axis Bluechip Fund - Growth"</span>,
    <span class="text-blue-400">"amc_name"</span>: <span class="text-green-400">"Axis Mutual Fund"</span>,
    <span class="text-blue-400">"category"</span>: <span class="text-green-400">"Equity"</span>,
    <span class="text-blue-400">"latest_nav"</span>: <span class="text-blue-400">54.32</span>,
    <span class="text-blue-400">"latest_nav_date"</span>: <span class="text-green-400">"2026-04-17"</span>,
    <span class="text-blue-400">"returns"</span>: {
      <span class="text-blue-400">"1d"</span>: { <span class="text-blue-400">"return_pct"</span>: <span class="text-blue-400">0.42</span>, <span class="text-blue-400">"ref_nav"</span>: <span class="text-blue-400">54.09</span> },
      <span class="text-blue-400">"1m"</span>: { <span class="text-blue-400">"return_pct"</span>: <span class="text-blue-400">2.81</span>, <span class="text-blue-400">"ref_nav"</span>: <span class="text-blue-400">52.83</span> },
      <span class="text-blue-400">"1y"</span>: { <span class="text-blue-400">"return_pct"</span>: <span class="text-blue-400">18.74</span>, <span class="text-blue-400">"ref_nav"</span>: <span class="text-blue-400">45.75</span> },
      <span class="text-blue-400">"3y"</span>: { <span class="text-blue-400">"return_pct"</span>: <span class="text-blue-400">48.20</span>, <span class="text-blue-400">"ref_nav"</span>: <span class="text-blue-400">36.65</span> }
    }
  }
}</pre>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- MF History -->
                                    <div id="mf-history" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /mf/history/{isin}</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                                <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                            </span>
                                        </div>
                                        <div class="p-6">
                                            <p class="mb-4 text-gray-400">Time-series NAV data for a specific scheme — ideal for charting. Supports up to 10 years of data and optional return column inclusion.</p>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                            <table class="w-full text-sm mb-8">
                                                <thead class="text-gray-500 text-left border-b border-gray-800"><tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr></thead>
                                                <tbody class="divide-y divide-gray-800 text-gray-400">
                                                    <tr><td class="py-3 font-mono text-amber-500">months</td><td class="py-3">Months of history (default: 12, max: 120). Ignored if <code class="text-white">from</code>/<code class="text-white">to</code> are set.</td></tr>
                                                    <tr><td class="py-3 font-mono text-amber-500">from</td><td class="py-3">Start date <code class="text-white">YYYY-MM-DD</code></td></tr>
                                                    <tr><td class="py-3 font-mono text-amber-500">to</td><td class="py-3">End date <code class="text-white">YYYY-MM-DD</code></td></tr>
                                                    <tr><td class="py-3 font-mono text-amber-500">include_returns</td><td class="py-3">Set to <code class="text-white">true</code> to include chg_1d, chg_1m, chg_1y, etc. per row</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- MF Compare -->
                                    <div id="mf-compare" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /mf/compare</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                                <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                            </span>
                                        </div>
                                        <div class="p-6">
                                            <p class="mb-4 text-gray-400">Side-by-side comparison of 2–5 mutual funds across all return periods (1D to 3Y) in a single API call.</p>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                            <table class="w-full text-sm mb-8">
                                                <thead class="text-gray-500 text-left border-b border-gray-800"><tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr></thead>
                                                <tbody class="divide-y divide-gray-800 text-gray-400">
                                                    <tr><td class="py-3 font-mono text-amber-500">isins <span class="text-rose-400">*</span></td><td class="py-3">Comma-separated ISINs (2–5 required). e.g. <code class="text-white">INF109K01VQ0,INF204KB14I2</code></td></tr>
                                                </tbody>
                                            </table>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                            <div class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                                <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"nav_date"</span>: <span class="text-green-400">"2026-04-17"</span>,
  <span class="text-blue-400">"not_found"</span>: [],
  <span class="text-blue-400">"data"</span>: [
    {
      <span class="text-blue-400">"isin"</span>: <span class="text-green-400">"INF109K01VQ0"</span>,
      <span class="text-blue-400">"scheme_name"</span>: <span class="text-green-400">"Axis Bluechip Fund - Growth"</span>,
      <span class="text-blue-400">"chg_1d"</span>: <span class="text-blue-400">0.42</span>, <span class="text-blue-400">"chg_1m"</span>: <span class="text-blue-400">2.81</span>,
      <span class="text-blue-400">"chg_1y"</span>: <span class="text-blue-400">18.74</span>, <span class="text-blue-400">"chg_3y"</span>: <span class="text-blue-400">48.20</span>
    }
  ]
}</pre>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </section>

                            <!-- ═══════════════════════════════════════════════════════ -->
                            <!-- EQUITY ADVANCED ANALYTICS                              -->
                            <!-- ═══════════════════════════════════════════════════════ -->
                            <section id="equity-advanced-api" class="pt-8">
                                <h2 class="text-xl sm:text-3xl font-bold text-white mb-6 uppercase tracking-wider border-b border-gray-800 pb-4">Equity Advanced Analytics</h2>
                                <p class="text-gray-400 mb-8">Deep market microstructure data derived from existing price records — gap analysis, intraday moves, arbitrage signals, sector rotation, and per-stock dual-exchange comparison.</p>
                                <div class="space-y-12">

                                    <div class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2">GET</span> /equities/analysis/gap-movers</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-3 py-1 rounded-full border border-amber-600/30"><i class="fas fa-coins mr-1 text-amber-400"></i>Credits</span>
                                        </div>
                                        <div class="p-6 text-gray-400">
                                            <p class="mb-4">Stocks that gapped up or down at market open vs previous close. Essential signal for gap traders. <code class="text-white">direction=up|down</code>, <code class="text-white">min_pct</code> (default 1%), <code class="text-white">exchange=nse|bse</code>, <code class="text-white">limit</code> (max 50).</p>
                                        </div>
                                    </div>

                                    <div class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2">GET</span> /equities/analysis/intraday-movers</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-3 py-1 rounded-full border border-amber-600/30"><i class="fas fa-coins mr-1 text-amber-400"></i>Credits</span>
                                        </div>
                                        <div class="p-6 text-gray-400">
                                            <p class="mb-4">Stocks with the strongest intraday move from open to close (not day-over-day return). Useful for identifying momentum continuation vs reversal. <code class="text-white">direction=up|down</code>, <code class="text-white">exchange</code>, <code class="text-white">limit</code>.</p>
                                        </div>
                                    </div>

                                    <div class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2">GET</span> /equities/analysis/wide-range-stocks</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-3 py-1 rounded-full border border-amber-600/30"><i class="fas fa-coins mr-1 text-amber-400"></i>Credits</span>
                                        </div>
                                        <div class="p-6 text-gray-400">
                                            <p class="mb-4">Stocks with the widest high-to-low intraday range as a percentage of previous close. High-range = high volatility = trading opportunity. <code class="text-white">exchange</code>, <code class="text-white">limit</code>.</p>
                                        </div>
                                    </div>

                                    <div class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2">GET</span> /equities/analysis/high-activity</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-3 py-1 rounded-full border border-amber-600/30"><i class="fas fa-coins mr-1 text-amber-400"></i>Credits</span>
                                        </div>
                                        <div class="p-6 text-gray-400">
                                            <p class="mb-4">Stocks with the most number of trades — more meaningful than raw volume for detecting retail or institutional attention. Returns trades, volume, turnover, and avg price per stock. <code class="text-white">exchange</code>, <code class="text-white">limit</code>.</p>
                                        </div>
                                    </div>

                                    <div class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2">GET</span> /equities/analysis/nse-bse-spread</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-3 py-1 rounded-full border border-amber-600/30"><i class="fas fa-coins mr-1 text-amber-400"></i>Credits</span>
                                        </div>
                                        <div class="p-6 text-gray-400">
                                            <p class="mb-4">Stocks with a notable price difference between NSE and BSE on the same day. Unique arbitrage signal only available in dual-listed Indian markets. <code class="text-white">min_spread</code> (default ₹0.5), <code class="text-white">limit</code>. Returns absolute spread and spread as % of NSE close.</p>
                                        </div>
                                    </div>

                                    <div class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2">GET</span> /equities/analysis/consistent-performers</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-3 py-1 rounded-full border border-amber-600/30"><i class="fas fa-coins mr-1 text-amber-400"></i>Credits</span>
                                        </div>
                                        <div class="p-6 text-gray-400">
                                            <p class="mb-4">Stocks that are positive across all requested return periods simultaneously — the steady compounders of the market. <code class="text-white">periods=1m,3m,6m,1y</code> (comma-separated), <code class="text-white">exchange</code>, <code class="text-white">limit</code>.</p>
                                        </div>
                                    </div>

                                    <div class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2">GET</span> /equities/analysis/52-week-extremes</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-3 py-1 rounded-full border border-amber-600/30"><i class="fas fa-coins mr-1 text-amber-400"></i>Credits</span>
                                        </div>
                                        <div class="p-6 text-gray-400">
                                            <p class="mb-4">Stocks near their 52-week high or low. <code class="text-white">position=near_high|near_low</code>, <code class="text-white">threshold</code> (% from extreme, default 5), <code class="text-white">exchange</code>, <code class="text-white">limit</code>.</p>
                                        </div>
                                    </div>

                                    <div class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2">GET</span> /equities/analysis/sector-heatmap</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-3 py-1 rounded-full border border-amber-600/30"><i class="fas fa-coins mr-1 text-amber-400"></i>Credits</span>
                                        </div>
                                        <div class="p-6 text-gray-400">
                                            <p class="mb-4">Average return by industry/sector for any period — the classic sector rotation signal. Returns avg return, stock count, gainers, losers, best and worst performer per sector. <code class="text-white">period=1d|1m|3m|1y</code>, <code class="text-white">exchange</code>.</p>
                                        </div>
                                    </div>

                                    <div class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2">GET</span> /equity/{isin}/ohlc</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-3 py-1 rounded-full border border-amber-600/30"><i class="fas fa-coins mr-1 text-amber-400"></i>Credits</span>
                                        </div>
                                        <div class="p-6 text-gray-400">
                                            <p class="mb-4">Full OHLC data for a specific stock — both NSE and BSE — for a date or date range. Includes spread, gap %, intraday %, and range %. <code class="text-white">date=YYYY-MM-DD</code> or <code class="text-white">from</code>/<code class="text-white">to</code>.</p>
                                        </div>
                                    </div>

                                    <div class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2">GET</span> /equity/{isin}/dual-exchange</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-3 py-1 rounded-full border border-amber-600/30"><i class="fas fa-coins mr-1 text-amber-400"></i>Credits</span>
                                        </div>
                                        <div class="p-6 text-gray-400">
                                            <p class="mb-4">NSE vs BSE side-by-side price, volume, and spread for the same stock over time. Shows which exchange leads price discovery on each day. <code class="text-white">days</code> (default 30, max 365).</p>
                                        </div>
                                    </div>

                                    <div class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2">GET</span> /equity/{isin}/activity-metrics</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-3 py-1 rounded-full border border-amber-600/30"><i class="fas fa-coins mr-1 text-amber-400"></i>Credits</span>
                                        </div>
                                        <div class="p-6 text-gray-400">
                                            <p class="mb-4">Trades, volume, turnover, and average ticket size trend over time. High avg ticket = institutional activity. Low avg ticket = retail-driven. <code class="text-white">days</code> (default 30, max 365).</p>
                                        </div>
                                    </div>

                                </div>
                            </section>

                            <!-- ═══════════════════════════════════════════════════════ -->
                            <!-- INDEX VALUATION & OHLC                                 -->
                            <!-- ═══════════════════════════════════════════════════════ -->
                            <section id="index-valuation-api" class="pt-8">
                                <h2 class="text-xl sm:text-3xl font-bold text-white mb-6 uppercase tracking-wider border-b border-gray-800 pb-4">Index Valuation & OHLC</h2>
                                <p class="text-gray-400 mb-8">PE ratio, PB ratio, dividend yield, and full OHLC data for market indices. This is premium valuation data used by professional investors for market timing and risk assessment.</p>
                                <div class="space-y-12">

                                    <div class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2">GET</span> /indices/{index_code}/valuation</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-3 py-1 rounded-full border border-amber-600/30"><i class="fas fa-coins mr-1 text-amber-400"></i>Credits</span>
                                        </div>
                                        <div class="p-6 text-gray-400">
                                            <p class="mb-4">PE ratio, PB ratio, and dividend yield for a specific index on the latest date or a historical date. Use this to determine if the market is overvalued or undervalued. <code class="text-white">date=YYYY-MM-DD</code> (optional, defaults to latest).</p>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                            <div class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400"><pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"index_code"</span>: <span class="text-green-400">"NIFTY 50"</span>,
  <span class="text-blue-400">"data"</span>: {
    <span class="text-blue-400">"traded_date"</span>: <span class="text-green-400">"2026-04-17"</span>,
    <span class="text-blue-400">"close"</span>: <span class="text-blue-400">22453.20</span>,
    <span class="text-blue-400">"pe_ratio"</span>: <span class="text-blue-400">21.4</span>,
    <span class="text-blue-400">"pb_ratio"</span>: <span class="text-blue-400">3.8</span>,
    <span class="text-blue-400">"div_yield"</span>: <span class="text-blue-400">1.32</span>
  }
}</pre></div>
                                        </div>
                                    </div>

                                    <div class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2">GET</span> /indices/{index_code}/valuation-history</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-3 py-1 rounded-full border border-amber-600/30"><i class="fas fa-coins mr-1 text-amber-400"></i>Credits</span>
                                        </div>
                                        <div class="p-6 text-gray-400">
                                            <p class="mb-4">PE/PB/yield trend over time — the key tool for mean-reversion investors to identify valuation extremes. <code class="text-white">months</code> (default 12, max 120) or <code class="text-white">from</code>/<code class="text-white">to</code>.</p>
                                        </div>
                                    </div>

                                    <div class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2">GET</span> /indices/analysis/valuation-comparison</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-3 py-1 rounded-full border border-amber-600/30"><i class="fas fa-coins mr-1 text-amber-400"></i>Credits</span>
                                        </div>
                                        <div class="p-6 text-gray-400">
                                            <p class="mb-4">PE/PB/yield for all indices side by side — sorted from cheapest to most expensive. One call to answer "which index is cheap?". <code class="text-white">exchange=NSE|BSE</code>.</p>
                                        </div>
                                    </div>

                                    <div class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2">GET</span> /indices/analysis/ohlc-summary</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-3 py-1 rounded-full border border-amber-600/30"><i class="fas fa-coins mr-1 text-amber-400"></i>Credits</span>
                                        </div>
                                        <div class="p-6 text-gray-400">
                                            <p class="mb-4">OHLC, gap %, intraday %, and range % for all indices in a single call. Perfect for building a market overview dashboard. <code class="text-white">exchange=NSE|BSE</code>, <code class="text-white">date=YYYY-MM-DD</code>.</p>
                                        </div>
                                    </div>

                                </div>
                            </section>

                            <!-- ═══════════════════════════════════════════════════════ -->
                            <!-- MF ADVANCED ANALYTICS                                  -->
                            <!-- ═══════════════════════════════════════════════════════ -->
                            <section id="mf-advanced-api" class="pt-8">
                                <h2 class="text-xl sm:text-3xl font-bold text-white mb-6 uppercase tracking-wider border-b border-gray-800 pb-4">MF Advanced Analytics</h2>
                                <p class="text-gray-400 mb-8">Aggregated analytics across the 13,000+ AMFI scheme universe — category-level benchmarking, AMC ranking, multi-period consistent performers, and fund discovery.</p>
                                <div class="space-y-12">

                                    <div class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2">GET</span> /mf/analysis/category-returns</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-3 py-1 rounded-full border border-amber-600/30"><i class="fas fa-coins mr-1 text-amber-400"></i>Credits</span>
                                        </div>
                                        <div class="p-6 text-gray-400">
                                            <p class="mb-4">Average returns across all 9 periods (1D–3Y) grouped by fund category. One call to see if Equity is beating Debt, Hybrid, ETF, FoF, etc. No parameters required.</p>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                            <div class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400"><pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: [
    {
      <span class="text-blue-400">"category"</span>: <span class="text-green-400">"Equity"</span>,
      <span class="text-blue-400">"fund_count"</span>: <span class="text-blue-400">4821</span>,
      <span class="text-blue-400">"avg_return_1m"</span>: <span class="text-blue-400">3.42</span>,
      <span class="text-blue-400">"avg_return_1y"</span>: <span class="text-blue-400">22.18</span>,
      <span class="text-blue-400">"best_1y"</span>: <span class="text-blue-400">87.43</span>,
      <span class="text-blue-400">"worst_1y"</span>: <span class="text-blue-400">-12.60</span>
    }
  ]
}</pre></div>
                                        </div>
                                    </div>

                                    <div class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2">GET</span> /mf/analysis/amc-performance</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-3 py-1 rounded-full border border-amber-600/30"><i class="fas fa-coins mr-1 text-amber-400"></i>Credits</span>
                                        </div>
                                        <div class="p-6 text-gray-400">
                                            <p class="mb-4">AMC (fund house) ranking by average returns. Tells users which fund house is consistently delivering results. <code class="text-white">period=1y</code>, <code class="text-white">category=Equity</code>, <code class="text-white">limit=20</code>.</p>
                                        </div>
                                    </div>

                                    <div class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2">GET</span> /mf/analysis/consistent-performers</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-3 py-1 rounded-full border border-amber-600/30"><i class="fas fa-coins mr-1 text-amber-400"></i>Credits</span>
                                        </div>
                                        <div class="p-6 text-gray-400">
                                            <p class="mb-4">Mutual funds positive across all requested periods simultaneously — the most reliable filter for steady compounders. <code class="text-white">periods=1m,3m,6m,1y</code>, <code class="text-white">category</code>, <code class="text-white">amc_name</code>, <code class="text-white">limit</code>.</p>
                                        </div>
                                    </div>

                                    <div class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2">GET</span> /mf/{isin}/similar-funds</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-3 py-1 rounded-full border border-amber-600/30"><i class="fas fa-coins mr-1 text-amber-400"></i>Credits</span>
                                        </div>
                                        <div class="p-6 text-gray-400">
                                            <p class="mb-4">Funds in the same category with a similar 1-year return profile (±10% range), sorted by closest match. Fund discovery for users evaluating alternatives. <code class="text-white">limit</code> (max 30).</p>
                                        </div>
                                    </div>

                                </div>
                            </section>

                            <!-- ═══════════════════════════════════════════════════════ -->
                            <!-- MARKET OVERVIEW (CROSS-ASSET)                          -->
                            <!-- ═══════════════════════════════════════════════════════ -->
                            <section id="market-api" class="pt-8">
                                <h2 class="text-xl sm:text-3xl font-bold text-white mb-6 uppercase tracking-wider border-b border-gray-800 pb-4">Market Overview</h2>
                                <p class="text-gray-400 mb-8">Cross-asset endpoints that combine indices, equities, and mutual funds in a single response. Designed for dashboards, home screens, and portfolio widgets.</p>
                                <div class="space-y-12">

                                    <div class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2">GET</span> /market/snapshot</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-3 py-1 rounded-full border border-amber-600/30"><i class="fas fa-coins mr-1 text-amber-400"></i>Credits</span>
                                        </div>
                                        <div class="p-6 text-gray-400">
                                            <p class="mb-4">One-call market dashboard: major index levels (Nifty 50, Sensex, Bank Nifty, IT), top 5 equity gainers/losers (NSE), and top 5 MF performers for the day. No parameters required.</p>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Structure</h4>
                                            <div class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400"><pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"indices"</span>: [ ... ],
  <span class="text-blue-400">"equity_gainers"</span>: [ ... ],
  <span class="text-blue-400">"equity_losers"</span>: [ ... ],
  <span class="text-blue-400">"mf_top_gainers"</span>: [ ... ]
}</pre></div>
                                        </div>
                                    </div>

                                    <div class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2">GET</span> /market/heatmap</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-3 py-1 rounded-full border border-amber-600/30"><i class="fas fa-coins mr-1 text-amber-400"></i>Credits</span>
                                        </div>
                                        <div class="p-6 text-gray-400">
                                            <p class="mb-4">Equity sector returns + MF category returns for any period in one response. The definitive sector rotation view — see which parts of the market are leading and lagging. <code class="text-white">period=1d|1m|3m|1y</code> (default 1m).</p>
                                        </div>
                                    </div>

                                    <div class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2">GET</span> /market/breadth</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-3 py-1 rounded-full border border-amber-600/30"><i class="fas fa-coins mr-1 text-amber-400"></i>Credits</span>
                                        </div>
                                        <div class="p-6 text-gray-400">
                                            <p class="mb-4">Market breadth — total advancers, decliners, unchanged, advance/decline ratio, and a sentiment label (strongly_bullish → strongly_bearish). <code class="text-white">exchange=NSE|BSE</code>, <code class="text-white">period=1d|1m</code>.</p>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                            <div class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400"><pre>{
  <span class="text-blue-400">"advancers"</span>: <span class="text-blue-400">3842</span>,
  <span class="text-blue-400">"decliners"</span>: <span class="text-blue-400">1204</span>,
  <span class="text-blue-400">"advance_decline_ratio"</span>: <span class="text-blue-400">3.19</span>,
  <span class="text-blue-400">"sentiment"</span>: <span class="text-green-400">"strongly_bullish"</span>
}</pre></div>
                                        </div>
                                    </div>

                                </div>
                            </section>

                            <!-- ═══════════════════════════════════════════════════════ -->
                            <!-- COUNTRY ECONOMIC INTELLIGENCE                          -->
                            <!-- ═══════════════════════════════════════════════════════ -->
                            <section id="country-economic-api" class="pt-8">
                                <h2 class="text-xl sm:text-3xl font-bold text-white mb-6 uppercase tracking-wider border-b border-gray-800 pb-4">Country Economic Data</h2>
                                <p class="text-gray-400 mb-8">Query countries by GDP, income level, OECD/EU membership, tax systems, and driving side. Rich economic data for fintech, compliance, and market research applications.</p>
                                <div class="space-y-12">

                                    <div class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2">GET</span> /countries/economic-profile</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-3 py-1 rounded-full border border-amber-600/30"><i class="fas fa-coins mr-1 text-amber-400"></i>Credits</span>
                                        </div>
                                        <div class="p-6 text-gray-400">
                                            <p class="mb-4">Filter countries by economic indicators. Returns GDP, population, income level, OECD/EU membership, and area.</p>
                                            <table class="w-full text-sm mb-6"><thead class="text-gray-500 text-left border-b border-gray-800"><tr><th class="pb-2">Parameter</th><th class="pb-2">Description</th></tr></thead>
                                            <tbody class="divide-y divide-gray-800">
                                                <tr><td class="py-2 font-mono text-amber-500">income_level</td><td class="py-2"><code class="text-white">High</code>, <code class="text-white">Upper-middle</code>, <code class="text-white">Lower-middle</code>, <code class="text-white">Low</code></td></tr>
                                                <tr><td class="py-2 font-mono text-amber-500">is_oecd</td><td class="py-2"><code class="text-white">true</code> — filter to OECD member countries only</td></tr>
                                                <tr><td class="py-2 font-mono text-amber-500">is_eu</td><td class="py-2"><code class="text-white">true</code> — filter to European Union members only</td></tr>
                                                <tr><td class="py-2 font-mono text-amber-500">gdp_min / gdp_max</td><td class="py-2">GDP range in USD billions</td></tr>
                                                <tr><td class="py-2 font-mono text-amber-500">sort_by</td><td class="py-2"><code class="text-white">gdp</code>, <code class="text-white">population</code>, <code class="text-white">area_sq_km</code>, <code class="text-white">name</code></td></tr>
                                            </tbody></table>
                                        </div>
                                    </div>

                                    <div class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2">GET</span> /countries/tax-data</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-3 py-1 rounded-full border border-amber-600/30"><i class="fas fa-coins mr-1 text-amber-400"></i>Credits</span>
                                        </div>
                                        <div class="p-6 text-gray-400">
                                            <p class="mb-4">Tax system and standard tax rate for all countries. Returns a summary (total countries, system breakdown, avg rate) plus per-country detail. Filter by <code class="text-white">tax_system=Territorial|Worldwide</code> or <code class="text-white">region_id</code>.</p>
                                        </div>
                                    </div>

                                    <div class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2">GET</span> /countries/analysis/regional-gdp</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-3 py-1 rounded-full border border-amber-600/30"><i class="fas fa-coins mr-1 text-amber-400"></i>Credits</span>
                                        </div>
                                        <div class="p-6 text-gray-400">
                                            <p class="mb-4">Total GDP, average GDP, and total population grouped by geographic region or sub-region. <code class="text-white">group_by=region|subregion</code>.</p>
                                        </div>
                                    </div>

                                    <div class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2">GET</span> /country/{country}/economic-summary</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-3 py-1 rounded-full border border-amber-600/30"><i class="fas fa-coins mr-1 text-amber-400"></i>Credits</span>
                                        </div>
                                        <div class="p-6 text-gray-400">
                                            <p class="mb-4">Complete economic profile for one country — GDP, population, currency, tax system, OECD/EU status, area, driving side, measurement system, and nationality.</p>
                                        </div>
                                    </div>

                                </div>
                            </section>

                            <!-- ═══════════════════════════════════════════════════════ -->
                            <!-- BANKING CAPABILITY INTELLIGENCE                        -->
                            <!-- ═══════════════════════════════════════════════════════ -->
                            <section id="banking-capability-api" class="pt-8">
                                <h2 class="text-xl sm:text-3xl font-bold text-white mb-6 uppercase tracking-wider border-b border-gray-800 pb-4">Banking Capabilities</h2>
                                <p class="text-gray-400 mb-8">Query bank branches by digital payment capabilities — UPI, NEFT, RTGS, IMPS, and SWIFT. Rank banks by coverage and find SWIFT-enabled branches for international transfers.</p>
                                <div class="space-y-12">

                                    <div class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2">GET</span> /banks/digital-coverage</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-3 py-1 rounded-full border border-amber-600/30"><i class="fas fa-coins mr-1 text-amber-400"></i>Credits</span>
                                        </div>
                                        <div class="p-6 text-gray-400">
                                            <p class="mb-4">All banks ranked by the percentage of their branches supporting a specific digital payment method. <code class="text-white">capability=upi|neft|rtgs|imps|swift</code> (default: upi). Returns branch count per method plus coverage percentage.</p>
                                        </div>
                                    </div>

                                    <div class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2">GET</span> /bank/{bank}/swift-branches</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-3 py-1 rounded-full border border-amber-600/30"><i class="fas fa-coins mr-1 text-amber-400"></i>Credits</span>
                                        </div>
                                        <div class="p-6 text-gray-400">
                                            <p class="mb-4">All branches of a specific bank that support SWIFT (international wire transfers). Filter by <code class="text-white">state_id</code>. Useful for cross-border payment routing and correspondent banking lookups.</p>
                                        </div>
                                    </div>

                                </div>
                            </section>

                            <!-- ═══════════════════════════════════════════════════════ -->
                            <!-- USER ANALYTICS                                         -->
                            <!-- ═══════════════════════════════════════════════════════ -->
                            <section id="user-analytics-api" class="pt-8">
                                <h2 class="text-xl sm:text-3xl font-bold text-white mb-6 uppercase tracking-wider border-b border-gray-800 pb-4">User Analytics</h2>
                                <p class="text-gray-400 mb-8">Understand your API credit consumption — which categories consume the most and how your usage trends over time.</p>
                                <div class="space-y-12">

                                    <div class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2">GET</span> /user/usage-breakdown</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-3 py-1 rounded-full border border-amber-600/30"><i class="fas fa-coins mr-1 text-amber-400"></i>Credits</span>
                                        </div>
                                        <div class="p-6 text-gray-400">
                                            <p class="mb-4">API calls grouped by category (Geo, Banking, Equities, MF, Market, Currency, Geospatial, Address) for the last N days. <code class="text-white">days</code> (default 30, max 90).</p>
                                        </div>
                                    </div>

                                    <div class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span class="text-blue-400 mr-2">GET</span> /user/usage-history</h3>
                                            <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-3 py-1 rounded-full border border-amber-600/30"><i class="fas fa-coins mr-1 text-amber-400"></i>Credits</span>
                                        </div>
                                        <div class="p-6 text-gray-400">
                                            <p class="mb-4">Daily API call count trend — total calls and credit-consuming calls per day. Useful for quota planning and anomaly detection. <code class="text-white">days</code> (default 30, max 90).</p>
                                        </div>
                                    </div>

                                </div>
                            </section>

                            <section id="hierarchical-data" class="pt-8">
                                <h2
                                    class="text-xl sm:text-3xl font-bold text-white mb-6 uppercase tracking-wider border-b border-gray-800 pb-4">
                                    Hierarchical Drill-Downs</h2>
                            </section>
                            <div id="country-detail"
                                class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden mt-12 block">
                                <div
                                    class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                    <h3 class="text-base sm:text-lg font-bold text-white"><span
                                            class="text-blue-400 mr-2 uppercase">GET</span> /country/{country_id}</h3>
                                    <span
                                        class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                        <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                            class="hidden sm:inline">Credits Required</span>
                                    </span>
                                </div>
                                <div class="p-6">
                                    <p class="mb-4">Fetch deep, isolated intelligence for a single country, including
                                        surface area, population, and macroeconomic definitions.</p>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Route Parameters</h4>
                                    <table class="w-full text-sm mb-8">
                                        <thead class="text-gray-500 text-left border-b border-gray-800">
                                            <tr>
                                                <th class="pb-2">Field</th>
                                                <th class="pb-2">Description</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-800">
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">country_id</td>
                                                <td class="py-3 text-gray-400">The ID of the country to fetch</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                    <div
                                        class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                        <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: {
    <span class="text-blue-400">"id"</span>: <span class="text-blue-400">101</span>,
    <span class="text-blue-400">"name"</span>: <span class="text-green-400">"India"</span>,
    <span class="text-blue-400">"iso2"</span>: <span class="text-green-400">"IN"</span>,
    <span class="text-blue-400">"capital"</span>: <span class="text-green-400">"New Delhi"</span>
  }
}</pre>
                                    </div>
                                </div>
                            </div>

                            <!-- Country States -->
                            <div id="country-states"
                                class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                <div
                                    class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                    <h3 class="text-base sm:text-lg font-bold text-white"><span
                                            class="text-blue-400 mr-2 uppercase">GET</span> /country/{country_id}/states
                                    </h3>
                                    <span
                                        class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                        <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                            class="hidden sm:inline">Credits Required</span>
                                    </span>
                                </div>
                                <div class="p-6">
                                    <p class="mb-4">Query all primary administrative divisions belonging directly to a
                                        country's geopolitical mapping.</p>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Route Parameters</h4>
                                    <table class="w-full text-sm mb-8">
                                        <thead class="text-gray-500 text-left border-b border-gray-800">
                                            <tr>
                                                <th class="pb-2">Field</th>
                                                <th class="pb-2">Description</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-800">
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">country_id</td>
                                                <td class="py-3 text-gray-400">The ID of the country</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                    <div
                                        class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                        <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: [
    {
      <span class="text-blue-400">"id"</span>: <span class="text-blue-400">1</span>,
      <span class="text-blue-400">"name"</span>: <span class="text-green-400">"Maharashtra"</span>,
      <span class="text-blue-400">"state_code"</span>: <span class="text-green-400">"MH"</span>
    }
  ]
}</pre>
                                    </div>
                                </div>
                            </div>

                            <!-- Country Cities -->
                            <div id="country-cities"
                                class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                <div
                                    class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                    <h3 class="text-base sm:text-lg font-bold text-white"><span
                                            class="text-blue-400 mr-2 uppercase">GET</span> /country/{country_id}/cities
                                    </h3>
                                    <span
                                        class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                        <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                            class="hidden sm:inline">Credits Required</span>
                                    </span>
                                </div>
                                <div class="p-6">
                                    <p class="mb-4">Generate a bulk taxonomy of all mapped cities globally tied to the
                                        country.</p>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Route Parameters</h4>
                                    <table class="w-full text-sm mb-8">
                                        <thead class="text-gray-500 text-left border-b border-gray-800">
                                            <tr>
                                                <th class="pb-2">Field</th>
                                                <th class="pb-2">Description</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-800">
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">country_id</td>
                                                <td class="py-3 text-gray-400">The ID of the country</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                    <div
                                        class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                        <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: [ { <span class="text-blue-400">"id"</span>: 1024, <span class="text-blue-400">"name"</span>: <span class="text-green-400">"Mumbai"</span> } ]
}</pre>
                                    </div>
                                </div>
                            </div>

                            <!-- Country Timezones -->
                            <div id="country-timezones"
                                class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                <div
                                    class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                    <h3 class="text-base sm:text-lg font-bold text-white"><span
                                            class="text-blue-400 mr-2 uppercase">GET</span>
                                        /country/{country_id}/timezones</h3>
                                    <span
                                        class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                        <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                            class="hidden sm:inline">Credits Required</span>
                                    </span>
                                </div>
                                <div class="p-6">
                                    <p class="mb-4">List all IANA standard timezones bridging the boundaries of a target
                                        nation.</p>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Route Parameters</h4>
                                    <table class="w-full text-sm mb-8">
                                        <thead class="text-gray-500 text-left border-b border-gray-800">
                                            <tr>
                                                <th class="pb-2">Field</th>
                                                <th class="pb-2">Description</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-800">
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">country_id</td>
                                                <td class="py-3 text-gray-400">The ID of the country</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                    <div
                                        class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                        <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: [
    {
      <span class="text-blue-400">"zone_name"</span>: <span class="text-green-400">"Asia/Kolkata"</span>,
      <span class="text-blue-400">"gmt_offset"</span>: <span class="text-blue-400">19800</span>
    }
  ]
}</pre>
                                    </div>
                                </div>
                            </div>

                            <!-- Country Banks -->
                            <div id="country-banks"
                                class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                <div
                                    class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                    <h3 class="text-base sm:text-lg font-bold text-white"><span
                                            class="text-blue-400 mr-2 uppercase">GET</span> /country/{country_id}/banks
                                    </h3>
                                    <span
                                        class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                        <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                            class="hidden sm:inline">Credits Required</span>
                                    </span>
                                </div>
                                <div class="p-6">
                                    <p class="mb-4">Scan the entirety of a country's verified banking infrastructure
                                        array.</p>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Route Parameters</h4>
                                    <table class="w-full text-sm mb-8">
                                        <thead class="text-gray-500 text-left border-b border-gray-800">
                                            <tr>
                                                <th class="pb-2">Field</th>
                                                <th class="pb-2">Description</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-800">
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">country_id</td>
                                                <td class="py-3 text-gray-400">The ID of the country</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                    <div
                                        class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                        <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: [
    {
      <span class="text-blue-400">"id"</span>: <span class="text-blue-400">1</span>,
      <span class="text-blue-400">"name"</span>: <span class="text-green-400">"State Bank of India"</span>
    }
  ]
}</pre>
                                    </div>
                                </div>
                            </div>

                            <!-- Country Neighbors -->
                            <div id="country-neighbors"
                                class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                <div
                                    class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                    <h3 class="text-base sm:text-lg font-bold text-white"><span
                                            class="text-blue-400 mr-2 uppercase">GET</span>
                                        /country/{country_id}/neighbors</h3>
                                    <span
                                        class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                        <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                            class="hidden sm:inline">Credits Required</span>
                                    </span>
                                </div>
                                <div class="p-6">
                                    <p class="mb-4">Topological mapping of physically adjoining, land-bordered partner
                                        nations.</p>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Route Parameters</h4>
                                    <table class="w-full text-sm mb-8">
                                        <thead class="text-gray-500 text-left border-b border-gray-800">
                                            <tr>
                                                <th class="pb-2">Field</th>
                                                <th class="pb-2">Description</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-800">
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">country_id</td>
                                                <td class="py-3 text-gray-400">The ID of the country</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                    <div
                                        class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                        <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: [
    {
      <span class="text-blue-400">"name"</span>: <span class="text-green-400">"Nepal"</span>,
      <span class="text-blue-400">"distance_km"</span>: <span class="text-blue-400">645.2</span>
    }
  ]
}</pre>
                                    </div>
                                </div>
                            </div>

                            <!-- Compare Countries -->
                            <div id="countries-compare"
                                class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                <div
                                    class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                    <h3 class="text-base sm:text-lg font-bold text-white"><span
                                            class="text-blue-400 mr-2 uppercase">GET</span> /countries/compare</h3>
                                    <span
                                        class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                        <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                            class="hidden sm:inline">Credits Required</span>
                                    </span>
                                </div>
                                <div class="p-6">
                                    <p class="mb-4">Differential analysis yielding parallel statistical comparisons
                                        (economic, social) between two independent territories.</p>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                    <table class="w-full text-sm mb-8">
                                        <thead class="text-gray-500 text-left border-b border-gray-800">
                                            <tr>
                                                <th class="pb-2">Field</th>
                                                <th class="pb-2">Description</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-800">
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">c1_id</td>
                                                <td class="py-3 text-gray-400">First Country ID</td>
                                            </tr>
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">c2_id</td>
                                                <td class="py-3 text-gray-400">Second Country ID</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                    <div
                                        class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                        <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: {
    <span class="text-blue-400">"countries"</span>: [<span class="text-green-400">"India"</span>, <span class="text-green-400">"USA"</span>],
    <span class="text-blue-400">"comparison"</span>: {
      <span class="text-blue-400">"population"</span>: {
        <span class="text-blue-400">"val1"</span>: <span class="text-blue-400">1400000000</span>,
        <span class="text-blue-400">"val2"</span>: <span class="text-blue-400">331000000</span>
      }
    }
  }
}</pre>
                                    </div>
                                </div>
                            </div>

                            <!-- State Detail -->
                            <div id="state-detail"
                                class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                <div
                                    class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                    <h3 class="text-base sm:text-lg font-bold text-white"><span
                                            class="text-blue-400 mr-2 uppercase">GET</span> /state/{state_id}</h3>
                                    <span
                                        class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                        <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                            class="hidden sm:inline">Credits Required</span>
                                    </span>
                                </div>
                                <div class="p-6">
                                    <p class="mb-4">Inspect high-fidelity parameters localized to a particular municipal
                                        region mapping.</p>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Route Parameters</h4>
                                    <table class="w-full text-sm mb-8">
                                        <thead class="text-gray-500 text-left border-b border-gray-800">
                                            <tr>
                                                <th class="pb-2">Field</th>
                                                <th class="pb-2">Description</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-800">
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">state_id</td>
                                                <td class="py-3 text-gray-400">The ID of the state to fetch</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                    <div
                                        class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                        <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: {
    <span class="text-blue-400">"id"</span>: <span class="text-blue-400">1</span>,
    <span class="text-blue-400">"name"</span>: <span class="text-green-400">"Maharashtra"</span>,
    <span class="text-blue-400">"latitude"</span>: <span class="text-blue-400">19.75</span>,
    <span class="text-blue-400">"longitude"</span>: <span class="text-blue-400">75.71</span>
  }
}</pre>
                                    </div>
                                </div>
                            </div>

                            <!-- State Cities -->
                            <div id="state-cities"
                                class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                <div
                                    class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                    <h3 class="text-base sm:text-lg font-bold text-white"><span
                                            class="text-blue-400 mr-2 uppercase">GET</span> /state/{state_id}/cities</h3>
                                    <span
                                        class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                        <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                            class="hidden sm:inline">Credits Required</span>
                                    </span>
                                </div>
                                <div class="p-6">
                                    <p class="mb-4">Batch isolate all cities governed natively inside a given state
                                        district.</p>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Route Parameters</h4>
                                    <table class="w-full text-sm mb-8">
                                        <thead class="text-gray-500 text-left border-b border-gray-800">
                                            <tr>
                                                <th class="pb-2">Field</th>
                                                <th class="pb-2">Description</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-800">
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">state_id</td>
                                                <td class="py-3 text-gray-400">The ID of the state</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                    <div
                                        class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                        <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: [
    {
      <span class="text-blue-400">"id"</span>: <span class="text-blue-400">1024</span>,
      <span class="text-blue-400">"name"</span>: <span class="text-green-400">"Mumbai"</span>
    }
  ]
}</pre>
                                    </div>
                                </div>
                            </div>

                            <!-- City Detail -->
                            <div id="city-detail"
                                class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                <div
                                    class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                    <h3 class="text-base sm:text-lg font-bold text-white"><span
                                            class="text-blue-400 mr-2 uppercase">GET</span> /city/{city_id}</h3>
                                    <span
                                        class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                        <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                            class="hidden sm:inline">Credits Required</span>
                                    </span>
                                </div>
                                <div class="p-6">
                                    <p class="mb-4">Extrapolate exact latitude/longitude coordinates and meta-flags
                                        bound to a solitary recognized city entity.</p>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Route Parameters</h4>
                                    <table class="w-full text-sm mb-8">
                                        <thead class="text-gray-500 text-left border-b border-gray-800">
                                            <tr>
                                                <th class="pb-2">Field</th>
                                                <th class="pb-2">Description</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-800">
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">city_id</td>
                                                <td class="py-3 text-gray-400">The ID of the city</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                    <div
                                        class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                        <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: {
    <span class="text-blue-400">"id"</span>: <span class="text-blue-400">1024</span>,
    <span class="text-blue-400">"name"</span>: <span class="text-green-400">"Mumbai"</span>,
    <span class="text-blue-400">"latitude"</span>: <span class="text-blue-400">19.07</span>,
    <span class="text-blue-400">"longitude"</span>: <span class="text-blue-400">72.87</span>
  }
}</pre>
                                    </div>
                                </div>
                            </div>

                            <section id="smart-addressing" class="pt-8">
                                <h2
                                    class="text-xl sm:text-3xl font-bold text-white mb-6 uppercase tracking-wider border-b border-gray-800 pb-4">
                                    Smart Addressing</h2>
                            </section>
                            <div id="address-autocomplete"
                                class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden mt-12 block">
                                <div
                                    class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                    <h3 class="text-base sm:text-lg font-bold text-white"><span
                                            class="text-blue-400 mr-2 uppercase">GET</span> /address/autocomplete</h3>
                                    <span
                                        class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                        <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                            class="hidden sm:inline">Credits Required</span>
                                    </span>
                                </div>
                                <div class="p-6">
                                    <p class="mb-4">Ultra-fast search ideal for building intuitive autosuggest dropdowns
                                        based on partial keyword entry.</p>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                    <table class="w-full text-sm mb-8">
                                        <thead class="text-gray-500 text-left border-b border-gray-800">
                                            <tr>
                                                <th class="pb-2">Field</th>
                                                <th class="pb-2">Description</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-800">
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">search_query</td>
                                                <td class="py-3 text-gray-400">Search text snippet</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                    <div
                                        class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                        <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: [
    {
      <span class="text-blue-400">"type"</span>: <span class="text-green-400">"city"</span>,
      <span class="text-blue-400">"text"</span>: <span class="text-green-400">"City: Mumbai"</span>
    }
  ]
}</pre>
                                    </div>
                                </div>
                            </div>

                            <!-- Address Validate -->
                            <div id="address-validate"
                                class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                <div
                                    class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                    <h3 class="text-base sm:text-lg font-bold text-white"><span
                                            class="text-blue-400 mr-2 uppercase">GET</span> /address/validate</h3>
                                    <span
                                        class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                        <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                            class="hidden sm:inline">Credits Required</span>
                                    </span>
                                </div>
                                <div class="p-6">
                                    <p class="mb-4">Verification algorithm utilizing internal relations to validate
                                        combination authenticity between coordinates.</p>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                    <table class="w-full text-sm mb-8">
                                        <thead class="text-gray-500 text-left border-b border-gray-800">
                                            <tr>
                                                <th class="pb-2">Field</th>
                                                <th class="pb-2">Description</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-800">
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">pincode</td>
                                                <td class="py-3 text-gray-400">Target postal code</td>
                                            </tr>
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">city_id</td>
                                                <td class="py-3 text-gray-400">Target City ID</td>
                                            </tr>
                                            <tr>
                                                <td class="py-3 font-mono text-amber-500">state_id</td>
                                                <td class="py-3 text-gray-400">Target State ID</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                    <div
                                        class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                        <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: {
    <span class="text-blue-400">"is_valid"</span>: <span class="text-blue-400">true</span>,
    <span class="text-blue-400">"matches"</span>: [
      {
        <span class="text-blue-400">"city"</span>: <span class="text-green-400">"Mumbai"</span>,
        <span class="text-blue-400">"state"</span>: <span class="text-green-400">"Maharashtra"</span>
      }
    ]
  }
}</pre>
                                    </div>
                                </div>
                            </div>


                            <!-- Geospatial Analysis -->
                            <section id="geospatial-analysis">
                                <div class="flex items-center mb-12">
                                    <h2 class="text-xl sm:text-3xl font-bold text-white">Geospatial Analysis</h2>
                                </div>

                                <div class="space-y-12">
                                    <!-- Geo Statistics -->
                                    <div id="geo-stats"
                                        class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden relative">
                                        <div
                                            class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span
                                                    class="text-blue-400 mr-2 uppercase">GET</span> /geospatial/statistics
                                            </h3>
                                            <span
                                                class="bg-green-600/20 text-green-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-green-600/30 ring-4 ring-green-600/5">
                                                <i class="fas fa-check-circle sm:mr-2 text-green-400"></i> <span
                                                    class="hidden sm:inline">Free API</span>
                                            </span>
                                        </div>
                                        <div class="p-6">
                                            <p class="mb-4 text-gray-400">Retrieve aggregate data counts for planning your
                                                integration. This is a non-chargeable API, perfect for calculating
                                                pagination and data volume.</p>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Optional Parameters
                                            </h4>
                                            <table class="w-full text-sm mb-6">
                                                <thead class="text-gray-500 text-left border-b border-gray-800">
                                                    <tr>
                                                        <th class="pb-2">Field</th>
                                                        <th class="pb-2">Description</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-800 text-gray-400">
                                                    <tr>
                                                        <td class="py-3 font-mono text-amber-500">country_id</td>
                                                        <td class="py-3">Filter counts by Country ID</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="py-3 font-mono text-amber-500">state_id</td>
                                                        <td class="py-3">Filter counts by State ID</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example
                                            </h4>
                                            <div
                                                class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                                <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: {
    <span class="text-blue-400">"total_countries"</span>: <span class="text-blue-400">240</span>,
    <span class="text-blue-400">"total_states"</span>: <span class="text-blue-400">4120</span>,
    <span class="text-blue-400">"total_cities"</span>: <span class="text-blue-400">48000</span>,
    <span class="text-blue-400">"total_pincodes"</span>: <span class="text-blue-400">1200000</span>
  }
}</pre>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Distance Calculator -->
                                    <div id="geo-distance"
                                        class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div
                                            class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span
                                                    class="text-blue-400 mr-2 uppercase">GET</span> /geospatial/distance
                                            </h3>
                                            <span
                                                class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                                <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                                    class="hidden sm:inline">Credits Required</span>
                                            </span>
                                        </div>
                                        <div class="p-6">
                                            <p class="mb-6 text-gray-400">Calculate the precision distance between two
                                                coordinate points using the Haversine formula. Supports multiple output
                                                units.</p>

                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters
                                            </h4>
                                            <table class="w-full text-sm mb-8">
                                                <thead class="text-gray-500 text-left border-b border-gray-800">
                                                    <tr>
                                                        <th class="pb-2 pr-4">Field</th>
                                                        <th class="pb-2 pr-4">Type</th>
                                                        <th class="pb-2 pr-4">Required</th>
                                                        <th class="pb-2">Description</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-800 text-gray-400">
                                                    <tr>
                                                        <td class="py-3 pr-4 font-mono text-amber-500">lat1</td>
                                                        <td class="py-3 pr-4 text-gray-500">float</td>
                                                        <td class="py-3 pr-4 text-red-400 font-bold">Yes</td>
                                                        <td class="py-3">Latitude of Point A &mdash; must be between
                                                            <code class="text-white">-90</code> and <code
                                                                class="text-white">90</code>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="py-3 pr-4 font-mono text-amber-500">lng1</td>
                                                        <td class="py-3 pr-4 text-gray-500">float</td>
                                                        <td class="py-3 pr-4 text-red-400 font-bold">Yes</td>
                                                        <td class="py-3">Longitude of Point A &mdash; must be between
                                                            <code class="text-white">-180</code> and <code
                                                                class="text-white">180</code>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="py-3 pr-4 font-mono text-amber-500">lat2</td>
                                                        <td class="py-3 pr-4 text-gray-500">float</td>
                                                        <td class="py-3 pr-4 text-red-400 font-bold">Yes</td>
                                                        <td class="py-3">Latitude of Point B &mdash; must be between
                                                            <code class="text-white">-90</code> and <code
                                                                class="text-white">90</code>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="py-3 pr-4 font-mono text-amber-500">lng2</td>
                                                        <td class="py-3 pr-4 text-gray-500">float</td>
                                                        <td class="py-3 pr-4 text-red-400 font-bold">Yes</td>
                                                        <td class="py-3">Longitude of Point B &mdash; must be between
                                                            <code class="text-white">-180</code> and <code
                                                                class="text-white">180</code>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="py-3 pr-4 font-mono text-amber-500">unit</td>
                                                        <td class="py-3 pr-4 text-gray-500">string</td>
                                                        <td class="py-3 pr-4 text-gray-600 font-bold">No</td>
                                                        <td class="py-3">
                                                            Output unit for the distance. Defaults to <code
                                                                class="text-white">km</code>.
                                                            <div class="mt-2 flex flex-wrap gap-2">
                                                                <span
                                                                    class="inline-flex items-center px-2 py-0.5 rounded bg-blue-500/10 text-blue-300 border border-blue-500/20 text-[10px] font-mono font-bold">km</span>
                                                                <span
                                                                    class="inline-flex items-center px-2 py-0.5 rounded bg-purple-500/10 text-purple-300 border border-purple-500/20 text-[10px] font-mono font-bold">miles</span>
                                                                <span
                                                                    class="inline-flex items-center px-2 py-0.5 rounded bg-teal-500/10 text-teal-300 border border-teal-500/20 text-[10px] font-mono font-bold">meters</span>
                                                                <span
                                                                    class="inline-flex items-center px-2 py-0.5 rounded bg-orange-500/10 text-orange-300 border border-orange-500/20 text-[10px] font-mono font-bold">centimeters</span>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>

                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Example Requests
                                            </h4>
                                            <div
                                                class="bg-gray-900 rounded-lg p-4 font-mono text-xs text-gray-500 border border-gray-800 mb-6 space-y-1.5">
                                                <div><span class="text-gray-600"># Kilometers (default)</span></div>
                                                <div>/api/v1/geospatial/distance?lat1=28.6&lng1=77.2&lat2=19.0&lng2=72.8
                                                </div>
                                                <div class="mt-2"><span class="text-gray-600"># Miles</span></div>
                                                <div>
                                                    /api/v1/geospatial/distance?lat1=28.6&lng1=77.2&lat2=19.0&lng2=72.8&unit=<span
                                                        class="text-amber-500">miles</span></div>
                                                <div class="mt-2"><span class="text-gray-600"># Meters</span></div>
                                                <div>
                                                    /api/v1/geospatial/distance?lat1=28.6&lng1=77.2&lat2=19.0&lng2=72.8&unit=<span
                                                        class="text-amber-500">meters</span></div>
                                                <div class="mt-2"><span class="text-gray-600"># Centimeters</span>
                                                </div>
                                                <div>
                                                    /api/v1/geospatial/distance?lat1=28.6&lng1=77.2&lat2=19.0&lng2=72.8&unit=<span
                                                        class="text-amber-500">centimeters</span></div>
                                            </div>

                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example
                                            </h4>
                                            <div
                                                class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                                <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: {
    <span class="text-blue-400">"distance"</span>: <span class="text-blue-400">1146423.8</span>,       <span class="text-gray-600">// value in requested unit</span>
    <span class="text-blue-400">"unit"</span>: <span class="text-green-400">"meters"</span>,            <span class="text-gray-600">// the unit param you sent</span>
    <span class="text-blue-400">"unit_label"</span>: <span class="text-green-400">"m"</span>,         <span class="text-gray-600">// short display label</span>
    <span class="text-blue-400">"distance_km"</span>: <span class="text-blue-400">1146.4238</span>   <span class="text-gray-600">// always present for reference</span>
  }
}</pre>
                                            </div>
                                        </div>
                                    </div>


                                    <!-- Nearby Radius Search -->
                                    <div id="geo-nearby"
                                        class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div
                                            class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span
                                                    class="text-blue-400 mr-2 uppercase">GET</span> /geospatial/nearby
                                            </h3>
                                            <span
                                                class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                                <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                                    class="hidden sm:inline">Credits Required</span>
                                            </span>
                                        </div>
                                        <div class="p-6">
                                            <p class="mb-6 text-gray-400">Search for Cities or Pincodes within a
                                                customized radius from any coordinate point.</p>
                                            <div
                                                class="bg-gray-900 rounded-lg p-4 font-mono text-xs text-gray-500 border border-gray-800 mb-8">
                                                /api/v1/geospatial/nearby?lat=19.076&lng=72.877&radius=50&type=pincode
                                            </div>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters
                                            </h4>
                                            <table class="w-full text-sm mb-8">
                                                <thead class="text-gray-500 text-left border-b border-gray-800">
                                                    <tr>
                                                        <th class="pb-2">Field</th>
                                                        <th class="pb-2">Description</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-800">
                                                    <tr>
                                                        <td class="py-3 font-mono text-amber-500">lat</td>
                                                        <td class="py-3 text-gray-400">Point latitude</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="py-3 font-mono text-amber-500">lng</td>
                                                        <td class="py-3 text-gray-400">Point longitude</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="py-3 font-mono text-amber-500">radius</td>
                                                        <td class="py-3 text-gray-400">Radius in km (default: 10)</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="py-3 font-mono text-amber-500">type</td>
                                                        <td class="py-3 text-gray-400">Search type: <code
                                                                class="text-amber-400">city</code> or <code
                                                                class="text-amber-400">pincode</code></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example
                                            </h4>
                                            <div
                                                class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                                <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: [
    {
      <span class="text-blue-400">"id"</span>: <span class="text-blue-400">1024</span>,
      <span class="text-blue-400">"name"</span>: <span class="text-green-400">"Mumbai"</span>,
      <span class="text-blue-400">"distance"</span>: <span class="text-blue-400">2.4</span>
    }
  ]
}</pre>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Reverse Geocode -->
                                    <div id="geo-geocode"
                                        class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div
                                            class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span
                                                    class="text-blue-400 mr-2 uppercase">GET</span> /geospatial/geocode
                                            </h3>
                                            <span
                                                class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                                <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                                    class="hidden sm:inline">Credits Required</span>
                                            </span>
                                        </div>
                                        <div class="p-6">
                                            <p class="mb-4">Convert spatial coordinates back into localized
                                                human-readable geographical components reliably.</p>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters
                                            </h4>
                                            <table class="w-full text-sm mb-8">
                                                <thead class="text-gray-500 text-left border-b border-gray-800">
                                                    <tr>
                                                        <th class="pb-2">Field</th>
                                                        <th class="pb-2">Description</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-800">
                                                    <tr>
                                                        <td class="py-3 font-mono text-amber-500">lat</td>
                                                        <td class="py-3 text-gray-400">Target latitude</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="py-3 font-mono text-amber-500">lng</td>
                                                        <td class="py-3 text-gray-400">Target longitude</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example
                                            </h4>
                                            <div
                                                class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                                <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: {
    <span class="text-blue-400">"city"</span>: { <span class="text-blue-400">"name"</span>: <span class="text-green-400">"Mumbai"</span>, <span class="text-blue-400">"distance_km"</span>: 0.5 },
    <span class="text-blue-400">"state"</span>: { <span class="text-blue-400">"name"</span>: <span class="text-green-400">"Maharashtra"</span> },
    <span class="text-blue-400">"formatted_address"</span>: <span class="text-green-400">"Mumbai, Maharashtra, India"</span>
  }
}</pre>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Boundary Search -->
                                    <div id="geo-boundary"
                                        class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div
                                            class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span
                                                    class="text-blue-400 mr-2 uppercase">GET</span> /geospatial/boundary
                                            </h3>
                                            <span
                                                class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                                <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                                    class="hidden sm:inline">Credits Required</span>
                                            </span>
                                        </div>
                                        <div class="p-6">
                                            <p class="mb-4">Fetch all coordinates enclosed within a rigid multi-point
                                                boundary or bounding box constraint.</p>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters
                                            </h4>
                                            <table class="w-full text-sm mb-8">
                                                <thead class="text-gray-500 text-left border-b border-gray-800">
                                                    <tr>
                                                        <th class="pb-2">Field</th>
                                                        <th class="pb-2">Description</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-800">
                                                    <tr>
                                                        <td class="py-3 font-mono text-amber-500">min_lat</td>
                                                        <td class="py-3 text-gray-400">Minimum latitude of the bounding
                                                            box</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="py-3 font-mono text-amber-500">max_lat</td>
                                                        <td class="py-3 text-gray-400">Maximum latitude of the bounding
                                                            box</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="py-3 font-mono text-amber-500">min_lng</td>
                                                        <td class="py-3 text-gray-400">Minimum longitude of the bounding
                                                            box</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="py-3 font-mono text-amber-500">max_lng</td>
                                                        <td class="py-3 text-gray-400">Maximum longitude of the bounding
                                                            box</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="py-3 font-mono text-amber-500">type</td>
                                                        <td class="py-3 text-gray-400">Data type: <code
                                                                class="text-amber-400">city</code> or <code
                                                                class="text-amber-400">pincode</code></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example
                                            </h4>
                                            <div
                                                class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                                <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: [
    {
      <span class="text-blue-400">"id"</span>: <span class="text-blue-400">1024</span>,
      <span class="text-blue-400">"name"</span>: <span class="text-green-400">"Mumbai"</span>,
      <span class="text-blue-400">"latitude"</span>: <span class="text-blue-400">19.07</span>
    }
  ],
  <span class="text-blue-400">"meta"</span>: {
    <span class="text-blue-400">"count"</span>: <span class="text-blue-400">1</span>,
    <span class="text-blue-400">"type"</span>: <span class="text-green-400">"city"</span>
  }
}</pre>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Grid Clustering -->
                                    <div id="geo-cluster"
                                        class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                                        <div
                                            class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                            <h3 class="text-base sm:text-lg font-bold text-white"><span
                                                    class="text-blue-400 mr-2 uppercase">GET</span> /geospatial/cluster
                                            </h3>
                                            <span
                                                class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                                <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span
                                                    class="hidden sm:inline">Credits Required</span>
                                            </span>
                                        </div>
                                        <div class="p-6">
                                            <p class="mb-4">Utilizes grouping algorithms to simplify heavy marker map
                                                deployments onto interactive map frontends.</p>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters
                                            </h4>
                                            <table class="w-full text-sm mb-8">
                                                <thead class="text-gray-500 text-left border-b border-gray-800">
                                                    <tr>
                                                        <th class="pb-2">Field</th>
                                                        <th class="pb-2">Description</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-800">
                                                    <tr>
                                                        <td class="py-3 font-mono text-amber-500">lat</td>
                                                        <td class="py-3 text-gray-400">Center latitude</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="py-3 font-mono text-amber-500">lng</td>
                                                        <td class="py-3 text-gray-400">Center longitude</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="py-3 font-mono text-amber-500">radius</td>
                                                        <td class="py-3 text-gray-400">Radius to search within (km)</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="py-3 font-mono text-amber-500">grid_size</td>
                                                        <td class="py-3 text-gray-400">Size of the grouping grid (default:
                                                            0.5)</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example
                                            </h4>
                                            <div
                                                class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
                                                <pre>{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: [
    {
      <span class="text-blue-400">"grid_lat"</span>: <span class="text-blue-400">19.0</span>,
      <span class="text-blue-400">"grid_lng"</span>: <span class="text-blue-400">72.5</span>,
      <span class="text-blue-400">"count"</span>: <span class="text-blue-400">120</span>
    }
  ]
}</pre>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </section>

                            <!-- Status Codes -->
                            <section id="status-codes">
                                <h2 class="text-xl sm:text-3xl font-bold text-white mb-6">Status Codes</h2>
                                <table class="w-full text-sm">
                                    <thead class="text-gray-500 text-left border-b border-gray-800">
                                        <tr>
                                            <th class="pb-2">Code</th>
                                            <th class="pb-2">Message</th>
                                            <th class="pb-2">Description</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        <tr>
                                            <td class="py-4 text-green-400 font-bold">200</td>
                                            <td class="py-4">OK</td>
                                            <td class="py-4">Success. Credits will be debited.</td>
                                        </tr>
                                        <tr>
                                            <td class="py-4 text-amber-400 font-bold">401</td>
                                            <td class="py-4">Unauthorized</td>
                                            <td class="py-4">Invalid token or missing Authorization header.</td>
                                        </tr>
                                        <tr>
                                            <td class="py-4 text-red-500 font-bold">402</td>
                                            <td class="py-4">Payment Required</td>
                                            <td class="py-4">Insufficient credits or expired subscription.</td>
                                        </tr>
                                        <tr>
                                            <td class="py-4 text-gray-500 font-bold">404</td>
                                            <td class="py-4">Not Found</td>
                                            <td class="py-4">Resource not found. Credits NOT debited.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </section>

                        </div>
                </div>
            </div>
        </div>



        <style>
            html {
                scroll-behavior: smooth;
            }

            section[id] {
                scroll-margin-top: 120px;
            }

            #geo-docs a {
                scroll-behavior: smooth;
            }

            ::-webkit-scrollbar {
                width: 8px;
            }

            ::-webkit-scrollbar-track {
                background: #020617;
            }

            ::-webkit-scrollbar-thumb {
                background: #1e293b;
                border-radius: 4px;
            }

            ::-webkit-scrollbar-thumb:hover {
                background: #334155;
            }

            /* Mobile Responsive Tables */
            @media (max-width: 1024px) {
                .lg\:col-span-9 div[class*="p-"] {
                    overflow-x: auto !important;
                    -webkit-overflow-scrolling: touch;
                }

                .lg\:col-span-9 table {
                    min-width: 600px;
                }
            }
        </style>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const sections = document.querySelectorAll('section[id]');
                const navLinks = document.querySelectorAll('aside nav ul li a');

                const observerOptions = {
                    root: null,
                    rootMargin: '0px 0px -70% 0px',
                    threshold: 0
                };

                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const currentId = entry.target.getAttribute('id');

                            navLinks.forEach(link => {
                                link.classList.remove('text-amber-500', 'font-bold');
                                if (link.getAttribute('href') === '#' + currentId) {
                                    link.classList.add('text-amber-500', 'font-bold');
                                }
                            });
                        }
                    });
                }, observerOptions);

                sections.forEach(section => {
                    observer.observe(section);
                });

                // Click listener for immediate visual feedback
                navLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        navLinks.forEach(l => l.classList.remove('text-amber-500', 'font-bold'));
                        this.classList.add('text-amber-500', 'font-bold');
                    });
                });
            });
        </script>
    @endsection
