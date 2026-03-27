@extends('layouts.public')
@section('title', 'GeoData API Documentation')

@section('content')
<div class="min-h-screen bg-[#020617] text-gray-300 antialiased font-inter">
    <!-- Hero Section -->
    <div class="border-b border-gray-800/50 bg-[#020617]">
        <div class="max-w-7xl mx-auto px-4 py-16 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-start md:items-center">
            <div>
                <h1 class="text-4xl font-extrabold text-white tracking-tight sm:text-5xl">API Documentation</h1>
                <p class="mt-4 text-xl text-gray-400 max-w-2xl">Comprehensive guide for integrating GeoData's geographic intelligence into your applications.</p>
            </div>
            <div class="mt-8 md:mt-0">
                <a href="{{ asset('GeoData.postman_collection.json') }}" download class="inline-flex items-center px-6 py-3 border border-amber-600/30 text-base font-bold rounded-xl text-white bg-amber-600/10 hover:bg-amber-600 hover:border-amber-600 transition-all shadow-lg group">
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
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Introduction</h3>
                        <ul class="mt-4 space-y-4">
                            <li><a href="#getting-started" class="text-sm hover:text-amber-500 transition-colors">Getting Started</a></li>
                            <li><a href="#authentication" class="text-sm hover:text-amber-500 transition-colors">Authentication</a></li>
                            <li><a href="#status-codes" class="text-sm hover:text-amber-500 transition-colors">Status Codes</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Authentication API</h3>
                        <ul class="mt-4 space-y-4">
                            <li><a href="#get-token" class="text-sm hover:text-amber-500 transition-colors">Generate Token</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">GeoData API</h3>
                        <ul class="mt-4 space-y-4">
                            <li><a href="#regions" class="text-sm hover:text-amber-500 transition-colors tracking-wide">Regions List</a></li>
                            <li><a href="#subregions" class="text-sm hover:text-amber-500 transition-colors tracking-wide">Sub-Regions List</a></li>
                            <li><a href="#timezones" class="text-sm hover:text-amber-500 transition-colors tracking-wide">Timezones List</a></li>
                            <li><a href="#countries" class="text-sm hover:text-amber-500 transition-colors tracking-wide">Countries List</a></li>
                            <li><a href="#states" class="text-sm hover:text-amber-500 transition-colors tracking-wide">States List</a></li>
                            <li><a href="#cities" class="text-sm hover:text-amber-500 transition-colors tracking-wide">Cities List</a></li>
                            <li><a href="#pincode-list" class="text-sm hover:text-amber-500 transition-colors tracking-wide">Pincodes List</a></li>
                            <li><a href="#pincode-search" class="text-sm hover:text-amber-500 transition-colors tracking-wide">Pincode Search (Deep)</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Account API</h3>
                        <ul class="mt-4 space-y-4">
                            <li><a href="#user-usage" class="text-sm hover:text-amber-500 transition-colors">Usage Monitoring</a></li>
                        </ul>
                    </div>
                </nav>
            </aside>

            <!-- Main Documentation Content -->
            <div class="lg:col-span-9 space-y-24 pb-24">
                
                <!-- Getting Started -->
                <section id="getting-started">
                    <h2 class="text-3xl font-bold text-white mb-6">Getting Started</h2>
                    <p class="text-lg leading-relaxed mb-6">
                        GeoData provides a comprehensive set of RESTful APIs to retrieve geographic information including Countries, States, Cities, and Pincodes. Our APIs are CORS-enabled and return JSON-formatted responses.
                    </p>
                    <div class="bg-gray-900/50 rounded-xl p-6 border border-gray-800">
                        <h4 class="text-sm font-semibold text-amber-500 uppercase mb-2">Base URL</h4>
                        <code class="text-white text-lg font-mono">{{ url('/api/v1') }}</code>
                    </div>
                </section>

                <!-- Authentication -->
                <section id="authentication">
                    <h2 class="text-3xl font-bold text-white mb-6">Authentication</h2>
                    <p class="mb-6">GeoData uses Bearer Token authentication. First, generate a token using your Client Key and Secret Key, which you can find in your <a href="{{ route('api-keys.index') }}" class="text-amber-500 hover:underline">Dashboard</a>.</p>
                    
                    <div class="space-y-8">
                        <div id="get-token" class="bg-gray-900 rounded-xl overflow-hidden border border-gray-800">
                            <div class="px-6 py-4 border-b border-gray-800 bg-gray-900/50 flex justify-between items-center">
                                <span class="text-sm font-bold text-green-400 uppercase">POST /auth/token</span>
                            </div>
                            <div class="p-6">
                                <h4 class="text-sm font-bold text-gray-400 uppercase mb-4">Request Body</h4>
                                <table class="w-full text-sm mb-6">
                                    <thead class="text-gray-500 text-left border-b border-gray-800">
                                        <tr>
                                            <th class="pb-2">Field</th>
                                            <th class="pb-2">Type</th>
                                            <th class="pb-2">Description</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        <tr><td class="py-3 font-mono text-white">client_key</td><td class="py-3 text-gray-500">string</td><td class="py-3 text-gray-400">Your unique Public API Key</td></tr>
                                        <tr><td class="py-3 font-mono text-white">client_secret</td><td class="py-3 text-gray-500">string</td><td class="py-3 text-gray-400">Your protected Secret API Key</td></tr>
                                    </tbody>
                                </table>

                                <!-- Code Tabs -->
                                <div x-data="{ tab: 'node' }" class="mt-8">
                                    <div class="flex space-x-4 border-b border-gray-800 mb-4">
                                        <button @click="tab = 'node'" :class="tab === 'node' ? 'text-amber-500 border-b-2 border-amber-500' : 'text-gray-500'" class="pb-2 text-sm font-bold">Node.js</button>
                                        <button @click="tab = 'react'" :class="tab === 'react' ? 'text-amber-500 border-b-2 border-amber-500' : 'text-gray-500'" class="pb-2 text-sm font-bold">ReactJS</button>
                                        <button @click="tab = 'python'" :class="tab === 'python' ? 'text-amber-500 border-b-2 border-amber-500' : 'text-gray-500'" class="pb-2 text-sm font-bold">Python</button>
                                        <button @click="tab = 'php'" :class="tab === 'php' ? 'text-amber-500 border-b-2 border-amber-500' : 'text-gray-500'" class="pb-2 text-sm font-bold">PHP (Laravel)</button>
                                    </div>
                                    <div x-show="tab === 'node'" class="bg-[#0f172a] rounded-lg p-5 font-mono text-sm overflow-x-auto">
<pre class="text-gray-300"><span class="text-blue-400">const</span> axios = <span class="text-yellow-400">require</span>(<span class="text-green-400">'axios'</span>);

axios.<span class="text-yellow-400">post</span>(<span class="text-green-400">'{{ url('/api/v1/auth/token') }}'</span>, {
    client_key: <span class="text-green-400">'YOUR_KEY'</span>,
    client_secret: <span class="text-green-400">'YOUR_SECRET'</span>
})
.then(<span class="text-blue-400">res</span> => console.<span class="text-yellow-400">log</span>(res.data))
.catch(<span class="text-blue-400">err</span> => console.<span class="text-yellow-400">error</span>(err));</pre>
                                    </div>
                                    <div x-show="tab === 'react'" class="bg-[#0f172a] rounded-lg p-5 font-mono text-sm overflow-x-auto">
<pre class="text-gray-300"><span class="text-blue-400">import</span> { useEffect } <span class="text-blue-400">from</span> <span class="text-green-400">'react'</span>;
<span class="text-blue-400">import</span> axios <span class="text-blue-400">from</span> <span class="text-green-400">'axios'</span>;

<span class="text-blue-400">const</span> GeoDataApp = () => {
  <span class="text-yellow-400">useEffect</span>(() => {
    axios.<span class="text-yellow-400">post</span>(<span class="text-green-400">'{{ url('/api/v1/auth/token') }}'</span>, {
      client_key: <span class="text-green-400">'YOUR_KEY'</span>,
      client_secret: <span class="text-green-400">'YOUR_SECRET'</span>
    })
    .then(<span class="text-blue-400">res</span> => console.<span class="text-yellow-400">log</span>(res.data));
  }, []);

  <span class="text-blue-400">return</span> <span class="text-gray-500">&lt;div&gt;</span>Explore GeoData API<span class="text-gray-500">&lt;/div&gt;</span>;
};</pre>
                                    </div>
                                    <div x-show="tab === 'python'" class="bg-[#0f172a] rounded-lg p-5 font-mono text-sm overflow-x-auto">
<pre class="text-gray-300"><span class="text-blue-400">import</span> requests

payload = {
    <span class="text-green-400">'client_key'</span>: <span class="text-green-400">'YOUR_KEY'</span>,
    <span class="text-green-400">'client_secret'</span>: <span class="text-green-400">'YOUR_SECRET'</span>
}

response = requests.<span class="text-yellow-400">post</span>(<span class="text-green-400">'{{ url('/api/v1/auth/token') }}'</span>, <span class="text-blue-400">data</span>=payload)
<span class="text-yellow-400">print</span>(response.<span class="text-yellow-400">json</span>())</pre>
                                    </div>
                                    <div x-show="tab === 'php'" class="bg-[#0f172a] rounded-lg p-5 font-mono text-sm overflow-x-auto">
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

                <!-- GeoData Endpoints -->
                <section id="geo-endpoints">
                    <h2 class="text-3xl font-bold text-white mb-12">GeoData Endpoints</h2>
                    
                    <div class="space-y-12">
                        <!-- Regions List -->
                        <div id="regions" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /region/list</h3>
                            </div>
                            <div class="p-6">
                                <p class="mb-4">Get a list of global political or geographic regions (e.g., Asia, Europe, Africa).</p>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                <table class="w-full text-sm mb-8">
                                    <thead class="text-gray-500 text-left border-b border-gray-800">
                                        <tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        <tr><td class="py-3 font-mono text-amber-500">name</td><td class="py-3 text-gray-400">Partial match for region name (e.g. "Americas")</td></tr>
                                        <tr><td class="py-3 font-mono text-amber-500">limit</td><td class="py-3 text-gray-400">Pagination limit (default: 100)</td></tr>
                                    </tbody>
                                </table>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                <div class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto">
<pre class="text-gray-400">{
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

                        <!-- Sub-Regions List -->
                        <div id="subregions" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /subregion/list</h3>
                            </div>
                            <div class="p-6">
                                <p class="mb-4">Get detailed sub-regions within a parent region.</p>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                <table class="w-full text-sm mb-8">
                                    <thead class="text-gray-500 text-left border-b border-gray-800">
                                        <tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        <tr><td class="py-3 font-mono text-amber-500">region_id</td><td class="py-3 text-gray-400">Filter by Parent Region ID</td></tr>
                                        <tr><td class="py-3 font-mono text-amber-500">name</td><td class="py-3 text-gray-400">Search by Sub-region name</td></tr>
                                    </tbody>
                                </table>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                <div class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto">
<pre class="text-gray-400">{
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

                        <!-- Timezones List -->
                        <div id="timezones" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /timezone/list</h3>
                            </div>
                            <div class="p-6">
                                <p class="mb-4">Retrieve standardized IANA timezones.</p>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                <table class="w-full text-sm mb-8">
                                    <thead class="text-gray-500 text-left border-b border-gray-800">
                                        <tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        <tr><td class="py-3 font-mono text-amber-500">name</td><td class="py-3 text-gray-400">Filter by Zone name (e.g. "Asia/Kolkata")</td></tr>
                                    </tbody>
                                </table>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                <div class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto">
<pre class="text-gray-400">{
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

                        <!-- Country List -->
                        <div id="countries" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /country/list</h3>
                            </div>
                            <div class="p-6">
                                <p class="mb-4">Retrieve a filterable list of countries with their ISO codes, currency, and capital.</p>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Request Headers</h4>
                                <div class="bg-gray-900 rounded p-3 text-sm font-mono mb-6 text-gray-400 border border-gray-800">
                                    Authorization: Bearer <span class="text-amber-500">{your_token}</span>
                                </div>

                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                <table class="w-full text-sm mb-8">
                                    <thead class="text-gray-500 text-left border-b border-gray-800">
                                        <tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        <tr><td class="py-3 font-mono text-amber-500">name</td><td class="py-3 text-gray-400">Filter by country name (Partial match)</td></tr>
                                        <tr><td class="py-3 font-mono text-amber-500">iso2</td><td class="py-3 text-gray-400">Filter by 2-letter ISO code (e.g. "IN")</td></tr>
                                        <tr><td class="py-3 font-mono text-amber-500">iso3</td><td class="py-3 text-gray-400">Filter by 3-letter ISO code (e.g. "IND")</td></tr>
                                        <tr><td class="py-3 font-mono text-amber-500">region_id</td><td class="py-3 text-gray-400">Filter by Region ID</td></tr>
                                    </tbody>
                                </table>

                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                <div class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto">
<pre class="text-gray-400">{
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

                        <!-- State List -->
                        <div id="states" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2">GET</span> /state/list</h3>
                            </div>
                            <div class="p-6">
                                <p class="mb-4">Retrieve states filtered by country. Perfect for dropdown menus.</p>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Query Parameters</h4>
                                <ul class="text-sm space-y-2 mb-4 text-gray-400">
                                    <li><code class="text-amber-500">country_id</code>: <span class="italic text-gray-600">(Recommended)</span> Filter by Country ID.</li>
                                    <li><code class="text-amber-500">country_name</code>: Filter by Country Name.</li>
                                </ul>

                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                <div class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto">
<pre class="text-gray-400">{
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

                        <!-- City List -->
                        <div id="cities" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2">GET</span> /city/list</h3>
                            </div>
                            <div class="p-6">
                                <p class="mb-4">Retrieve cities filtered by state or country.</p>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Query Parameters</h4>
                                <ul class="text-sm space-y-2 mb-4 text-gray-400">
                                    <li><code class="text-amber-500">state_id</code>: Filter by State ID.</li>
                                    <li><code class="text-amber-500">state_name</code>: Filter by State Name.</li>
                                </ul>

                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                <div class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto">
<pre class="text-gray-400">{
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
                        <div id="pincode-search" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /pincode/search</h3>
                            </div>
                            <div class="p-6">
                                <p class="mb-4">Search for detailed geographic data by Pincode. Returns associated City, State, and Country data.</p>
                                
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                <table class="w-full text-sm mb-8">
                                    <thead class="text-gray-500 text-left border-b border-gray-800">
                                        <tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        <tr><td class="py-3 font-mono text-amber-500">pincode</td><td class="py-3 text-gray-400">The postal code to search for (Alternative: <code class="text-amber-400">code</code>)</td></tr>
                                    </tbody>
                                </table>

                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                <div class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto">
<pre class="text-gray-400">{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: [
    {
      <span class="text-blue-400">"pincode"</span>: <span class="text-green-400">"400001"</span>,
      <span class="text-blue-400">"city"</span>: { <span class="text-blue-400">"id"</span>: 1024, <span class="text-blue-400">"name"</span>: <span class="text-green-400">"Mumbai"</span> },
      <span class="text-blue-400">"state"</span>: { <span class="text-blue-400">"id"</span>: 51, <span class="text-blue-400">"name"</span>: <span class="text-green-400">"Maharashtra"</span> },
      <span class="text-blue-400">"country"</span>: { <span class="text-blue-400">"id"</span>: 101, <span class="text-blue-400">"name"</span>: <span class="text-green-400">"India"</span> },
      <span class="text-blue-400">"latitude"</span>: <span class="text-green-400">"18.922"</span>,
      <span class="text-blue-400">"longitude"</span>: <span class="text-green-400">"72.834"</span>
    }
  ]
}</pre>
                                </div>
                            </div>
                        </div>

                        <!-- Pincode List (Legacy/Batch) -->
                        <div id="pincode-list" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /pincode/list</h3>
                            </div>
                            <div class="p-6">
                                <p class="mb-4">Batch retrieve pincodes by city, state or country filter.</p>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                <table class="w-full text-sm mb-8">
                                    <thead class="text-gray-500 text-left border-b border-gray-800">
                                        <tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        <tr><td class="py-3 font-mono text-amber-500">city_id</td><td class="py-3 text-gray-400">Filter by City ID</td></tr>
                                        <tr><td class="py-3 font-mono text-amber-500">pincode</td><td class="py-3 text-gray-400">Partial match for postal code</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Usage API -->
                        <div id="user-usage" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2">GET</span> /user/usage</h3>
                            </div>
                            <div class="p-6">
                                <p class="mb-4">Monitor your remaining credits and recent API hits.</p>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                <div class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto">
<pre class="text-gray-400">{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: {
    <span class="text-blue-400">"total_credits"</span>: <span class="text-blue-400">1000</span>,
    <span class="text-blue-400">"available_credits"</span>: <span class="text-blue-400">850</span>,
    <span class="text-blue-400">"recent_logs"</span>: [ ... ]
  }
}</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Status Codes -->
                <section id="status-codes">
                    <h2 class="text-3xl font-bold text-white mb-6">Status Codes</h2>
                    <table class="w-full text-sm">
                        <thead class="text-gray-500 text-left border-b border-gray-800">
                            <tr>
                                <th class="pb-2">Code</th>
                                <th class="pb-2">Message</th>
                                <th class="pb-2">Description</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800">
                            <tr><td class="py-4 text-green-400 font-bold">200</td><td class="py-4">OK</td><td class="py-4">Success. Credits will be debited.</td></tr>
                            <tr><td class="py-4 text-amber-400 font-bold">401</td><td class="py-4">Unauthorized</td><td class="py-4">Invalid token or missing Authorization header.</td></tr>
                            <tr><td class="py-4 text-red-500 font-bold">402</td><td class="py-4">Payment Required</td><td class="py-4">Insufficient credits or expired subscription.</td></tr>
                            <tr><td class="py-4 text-gray-500 font-bold">404</td><td class="py-4">Not Found</td><td class="py-4">Resource not found. Credits NOT debited.</td></tr>
                        </tbody>
                    </table>
                </section>

            </div>
        </div>
    </div>
</div>

<style>
    #geo-docs a { scroll-behavior: smooth; }
    ::-webkit-scrollbar { width: 8px; }
    ::-webkit-scrollbar-track { background: #020617; }
    ::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: #334155; }
</style>
@endsection
