@extends('layouts.public')
@section('title', 'API Documentation - SetuGeo Geographic Data API Integration Guide')
@section('meta_description', 'Comprehensive API documentation for SetuGeo. Learn how to integrate geographic data APIs for countries, states, cities, pincodes, and banking details into your apps.')
@section('meta_keywords', 'api documentation, setugeo api guide, geographic api integration, pincode api docs, bank ifsc api documentation')

@section('content')
<div class="min-h-screen bg-[#020617] text-gray-300 antialiased font-inter">
    <!-- Hero Section -->
    <div class="border-b border-gray-800/50 bg-[#020617]">
        <div class="max-w-7xl mx-auto px-4 py-16 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-start md:items-center">
            <div>
                <h1 class="text-4xl font-extrabold text-white tracking-tight sm:text-5xl">API Documentation</h1>
                <p class="mt-4 text-xl text-gray-400 max-w-2xl">Comprehensive guide for integrating SetuGeo's geographic intelligence into your applications.</p>
            </div>
            <div class="mt-8 md:mt-0">
                <a href="{{ asset('SetuGeo.postman_collection.json') }}" download class="inline-flex items-center px-6 py-3 border border-amber-600/30 text-base font-bold rounded-xl text-white bg-amber-600/10 hover:bg-amber-600 hover:border-amber-600 transition-all shadow-lg group">
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
                            <li><a href="#getting-started" class="text-sm font-medium hover:text-amber-500 transition-colors">Getting Started</a></li>
                            <li><a href="#authentication" class="text-sm font-medium hover:text-amber-500 transition-colors">Authentication</a></li>
                            <li><a href="#account-api" class="text-sm font-medium hover:text-amber-500 transition-colors">Account & Usage API</a></li>
                            <li><a href="#core-api" class="text-sm font-medium hover:text-amber-500 transition-colors">Core Geographic Data</a></li>
                            <li><a href="#utilities-api" class="text-sm font-medium hover:text-amber-500 transition-colors">Intelligent Utilities</a></li>
                            <li><a href="#banking-api" class="text-sm font-medium hover:text-amber-500 transition-colors">Banking & Finance</a></li>
                            <li><a href="#equity-api" class="text-sm font-medium hover:text-amber-500 transition-colors">Equity Intelligence</a></li>
                            <li><a href="#hierarchical-data" class="text-sm font-medium hover:text-amber-500 transition-colors">Hierarchical Drill-Downs</a></li>
                            <li><a href="#smart-addressing" class="text-sm font-medium hover:text-amber-500 transition-colors">Smart Addressing</a></li>
                            <li><a href="#geospatial-analysis" class="text-sm font-medium hover:text-amber-500 transition-colors">Geospatial Analysis</a></li>
                            <li><a href="#status-codes" class="text-sm font-medium hover:text-amber-500 transition-colors">Status Codes</a></li>
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
                        SetuGeo provides a comprehensive set of RESTful APIs to retrieve geographic information including Countries, States, Cities, and Pincodes. Our APIs are CORS-enabled and return JSON-formatted responses.
                    </p>
                    <div class="bg-gray-900/50 rounded-xl p-6 border border-gray-800">
                        <h4 class="text-sm font-semibold text-amber-500 uppercase mb-2">Base URL</h4>
                        <code class="text-white text-lg font-mono">{{ url('/api/v1') }}</code>
                    </div>
                </section>

                <!-- Authentication -->
                <section id="authentication">
                    <h2 class="text-3xl font-bold text-white mb-6">Authentication</h2>
                    <div class="bg-amber-600/10 border border-amber-600/30 rounded-2xl p-8 mb-12">
                        <h3 class="text-xl font-bold text-amber-500 mb-4 flex items-center">
                            <i class="fas fa-shield-alt mr-3"></i>
                            The Mandatory Two-Step Process
                        </h3>
                        <p class="text-gray-300 leading-relaxed mb-6">All SetuGeo APIs (except Auth) are protected. You cannot use your Client Key or Secret Key directly in standard API calls. You <span class="text-white font-bold underline decoration-amber-500">must</span> follow this sequence:</p>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="bg-black/20 p-4 rounded-xl border border-white/5 relative">
                                <span class="absolute -top-3 -left-3 w-8 h-8 bg-amber-600 text-white rounded-full flex items-center justify-center font-bold text-sm shadow-lg">1</span>
                                <h4 class="text-white font-bold text-sm mb-2">Get Your Keys</h4>
                                <p class="text-[11px] text-gray-500 font-medium">Copy your Client Key and Secret Key from your dashboard.</p>
                            </div>
                            <div class="bg-black/20 p-4 rounded-xl border border-white/5 relative">
                                <span class="absolute -top-3 -left-3 w-8 h-8 bg-amber-600 text-white rounded-full flex items-center justify-center font-bold text-sm shadow-lg">2</span>
                                <h4 class="text-white font-bold text-sm mb-2">Request Token</h4>
                                <p class="text-[11px] text-gray-500 font-medium">Call the <code class="text-amber-500">/auth/token</code> endpoint to get an <code class="text-white">access_token</code>.</p>
                            </div>
                            <div class="bg-black/20 p-4 rounded-xl border border-white/5 relative">
                                <span class="absolute -top-3 -left-3 w-8 h-8 bg-amber-600 text-white rounded-full flex items-center justify-center font-bold text-sm shadow-lg">3</span>
                                <h4 class="text-white font-bold text-sm mb-2">Access API</h4>
                                <p class="text-[11px] text-gray-500 font-medium">Use the <code class="text-white">access_token</code> as a Bearer Token for all geographic APIs.</p>
                            </div>
                        </div>
                    </div>
                    <p class="mb-8 text-lg">To access geographic intelligence endpoints, ensure your requests include the <code class="text-white">Authorization</code> header exactly as shown below.</p>
                    
                    <!-- Auth Flow Explanation -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-12 mb-16">
                        <div class="space-y-6">
                            <h3 class="text-xl font-bold text-white flex items-center">
                                <span class="w-8 h-8 bg-amber-600/20 text-amber-500 rounded-full flex items-center justify-center mr-3 text-sm">1</span>
                                Generate Token
                            </h3>
                            <p class="text-gray-400">Send your credentials to the <code class="text-amber-400">/auth/token</code> endpoint. You will receive an <code class="text-white">access_token</code> which is valid for 24 hours.</p>
                            <div class="bg-gray-900/50 p-4 rounded-xl border border-white/5 text-xs font-mono">
                                <span class="text-blue-400">POST</span> /api/v1/auth/token
                            </div>
                        </div>
                        <div class="space-y-6">
                            <h3 class="text-xl font-bold text-white flex items-center">
                                <span class="w-8 h-8 bg-amber-600/20 text-amber-500 rounded-full flex items-center justify-center mr-3 text-sm">2</span>
                                Use Bearer Token
                            </h3>
                            <p class="text-gray-400">Include the token in the <code class="text-white">Authorization</code> header for all subsequent API requests as a Bearer string.</p>
                            <div class="bg-gray-900/50 p-4 rounded-xl border border-white/5 text-xs font-mono">
                                Authorization: Bearer <span class="text-amber-500">{your_access_token}</span>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-8">
                        

                <section id="account-api" class="pt-8">
                    <h2 class="text-3xl font-bold text-white mb-6 uppercase tracking-wider border-b border-gray-800 pb-4">Account & Usage API</h2>
                </section>
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
                                            <th class="pb-2">Required</th>
                                            <th class="pb-2">Description</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        <tr>
                                            <td class="py-3 font-mono text-white">client_key</td>
                                            <td class="py-3 text-gray-500">string</td>
                                            <td class="py-3 text-amber-500 font-bold">Yes</td>
                                            <td class="py-3 text-gray-400">Your unique Public API Key (Access via Dashboard)</td>
                                        </tr>
                                        <tr>
                                            <td class="py-3 font-mono text-white">client_secret</td>
                                            <td class="py-3 text-gray-500">string</td>
                                            <td class="py-3 text-amber-500 font-bold">Yes</td>
                                            <td class="py-3 text-gray-400">Your protected Secret API Key</td>
                                        </tr>
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

                <!-- Request Patterns -->
                <section id="request-patterns">
                    <h2 class="text-3xl font-bold text-white mb-6">Request Patterns</h2>
                    <p class="mb-8">All SetuGeo API endpoints follow a consistent pattern. Use <span class="text-white">GET</span> for retrieving data and <span class="text-white">POST</span> for authentication or complex analysis.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="bg-gray-900/40 rounded-xl p-6 border border-gray-800">
                            <h4 class="text-sm font-bold text-amber-500 uppercase mb-4">Content Type</h4>
                            <p class="text-sm text-gray-400 mb-4">Always send and expect JSON. Ensure your headers include:</p>
                            <div class="space-y-2 text-xs font-mono">
                                <div class="bg-black/30 p-2 rounded text-gray-500">Content-Type: <span class="text-green-400">application/json</span></div>
                                <div class="bg-black/30 p-2 rounded text-gray-500">Accept: <span class="text-green-400">application/json</span></div>
                            </div>
                        </div>
                        <div class="bg-gray-900/40 rounded-xl p-6 border border-gray-800">
                            <h4 class="text-sm font-bold text-amber-500 uppercase mb-4">Standard Response</h4>
                            <p class="text-sm text-gray-400 mb-4">Every successful response includes a <code class="text-white">success</code> boolean and a <code class="text-white">data</code> object/array.</p>
                            <div class="bg-[#0f172a] p-3 rounded text-[10px] font-mono text-gray-500">
                                { "success": true, "data": [...] }
                            </div>
                        </div>
                    </div>
                </section>

                <!-- SetuGeo Endpoints -->
                <section id="geo-endpoints">
                    <h2 class="text-3xl font-bold text-white mb-12">SetuGeo Endpoints</h2>
                    
                    <div class="space-y-12">
                        <!-- Regions -->
                        

                <section id="core-api" class="pt-8">
                    <h2 class="text-3xl font-bold text-white mb-6 uppercase tracking-wider border-b border-gray-800 pb-4">Core Geographic Data</h2>
                </section>
                <div id="regions" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /regions</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
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

                        <!-- Sub-Regions -->
                        <div id="sub-regions" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /sub-regions</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
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

                        <!-- Timezones -->
                        <div id="timezones" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /timezones</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
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

                        <!-- Countries -->
                        <div id="countries" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /countries</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
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

                        <!-- States -->
                        <div id="states" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2">GET</span> /states</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
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

                        <!-- Cities -->
                        <div id="cities" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2">GET</span> /cities</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
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
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /pincodes/search</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
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

                        <!-- Pincodes -->
                        <div id="pincodes" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /pincodes</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
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

                        <!-- Currency Exchange -->
                        

                <section id="utilities-api" class="pt-8">
                    <h2 class="text-3xl font-bold text-white mb-6 uppercase tracking-wider border-b border-gray-800 pb-4">Intelligent Utilities</h2>
                </section>
                <div id="currency-exchange" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /currency/exchange</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
                            </div>
                            <div class="p-6">
                                <p class="mb-4">Get real-time currency exchange rates against USD and INR. We provide high-fidelity rates for 30+ major currencies, synchronized daily.</p>
                                
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                <table class="w-full text-sm mb-8">
                                    <thead class="text-gray-500 text-left border-b border-gray-800">
                                        <tr><th class="pb-2">Field</th><th class="pb-2">Type</th><th class="pb-2">Description</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        <tr><td class="py-3 font-mono text-amber-500">currency</td><td class="py-3 text-gray-500">string</td><td class="py-3 text-gray-400">The 3-letter currency code (e.g. <code class="text-amber-400">EUR</code>, <code class="text-amber-400">GBP</code>, <code class="text-amber-400">JPY</code>)</td></tr>
                                    </tbody>
                                </table>

                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                <div class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto">
<pre class="text-gray-400">{
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
                    <h2 class="text-3xl font-bold text-white mb-6 uppercase tracking-wider border-b border-gray-800 pb-4">Banking & Finance</h2>
                </section>
                <div id="banks" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /banks</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
                            </div>
                            <div class="p-6">
                                <p class="mb-4">Fetch list of all unique banks supported in the system.</p>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Optional Parameters</h4>
                                <table class="w-full text-sm mb-4">
                                    <thead class="text-gray-500 text-left border-b border-gray-800">
                                        <tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        <tr><td class="py-3 font-mono text-amber-500">name</td><td class="py-3 text-gray-400">Search by bank name</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Bank Branches -->
                        <div id="bank-branches" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /banks/{bank_id}/branches</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
                            </div>
                            <div class="p-6">
                                <p class="mb-4">Retrieve all available branches for a specific bank. Includes IFSC, MICR, and full address.</p>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Example Response</h4>
                                <div class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto">
<pre class="text-gray-400">{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: [
    {
      <span class="text-blue-400">"bank_id"</span>: 1,
      <span class="text-blue-400">"ifsc"</span>: <span class="text-green-400">"ABHY0065001"</span>,
      <span class="text-blue-400">"branch"</span>: <span class="text-green-400">"Abhyudaya Co-operative Bank IMPS"</span>,
      <span class="text-blue-400">"address"</span>: <span class="text-green-400">"Abhyudaya Bhavan, V.G. Market, Vevay Road, Mumbai"</span>,
      <span class="text-blue-400">"city"</span>: { <span class="text-blue-400">"name"</span>: "Mumbai" },
      <span class="text-blue-400">"state"</span>: { <span class="text-blue-400">"name"</span>: "Maharashtra" }
    }
  ]
}</pre>
                                </div>
                            </div>
                        </div>

                        <!-- Branch Info -->
                        <div id="branch-info" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /branch/{ifsc}</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
                            </div>
                        </div>

                        <!-- Banks List -->
                        

                <section id="banking-api" class="pt-8">
                    <h2 class="text-3xl font-bold text-white mb-6 uppercase tracking-wider border-b border-gray-800 pb-4">Banking & Finance</h2>
                </section>
                <div id="banks" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /banks</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
                            </div>
                            <div class="p-6">
                                <p class="mb-4">Fetch list of all unique banks supported in the system.</p>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Optional Parameters</h4>
                                <table class="w-full text-sm mb-4">
                                    <thead class="text-gray-500 text-left border-b border-gray-800">
                                        <tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        <tr><td class="py-3 font-mono text-amber-500">name</td><td class="py-3 text-gray-400">Search by bank name</td></tr>
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
                        <div id="bank-branches" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /banks/{bank_id}/branches</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
                            </div>
                            <div class="p-6">
                                <p class="mb-4">Retrieve all available branches for a specific bank. Includes IFSC, MICR, and full address.</p>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Route Parameters</h4>
                                <table class="w-full text-sm mb-8">
                                    <thead class="text-gray-500 text-left border-b border-gray-800">
                                        <tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        <tr><td class="py-3 font-mono text-amber-500">bank_id</td><td class="py-3 text-gray-400">The ID of the bank</td></tr>
                                    </tbody>
                                </table>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Example Response</h4>
                                <div class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto">
<pre class="text-gray-400">{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: [
    {
      <span class="text-blue-400">"bank_id"</span>: 1,
      <span class="text-blue-400">"ifsc"</span>: <span class="text-green-400">"ABHY0065001"</span>,
      <span class="text-blue-400">"branch"</span>: <span class="text-green-400">"Abhyudaya Co-operative Bank IMPS"</span>,
      <span class="text-blue-400">"address"</span>: <span class="text-green-400">"Abhyudaya Bhavan, V.G. Market, Vevay Road, Mumbai"</span>,
      <span class="text-blue-400">"city"</span>: { <span class="text-blue-400">"name"</span>: "Mumbai" },
      <span class="text-blue-400">"state"</span>: { <span class="text-blue-400">"name"</span>: "Maharashtra" }
    }
  ]
}</pre>
                                </div>
                            </div>
                        </div>

                        <!-- Branch Info -->
                        <div id="branch-info" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /branch/{ifsc}</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
                            </div>
                            <div class="p-6">
                                <p class="mb-4">Fetch comprehensive details of a single branch using its unique IFSC code.</p>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Route Parameters</h4>
                                <table class="w-full text-sm mb-8">
                                    <thead class="text-gray-500 text-left border-b border-gray-800">
                                        <tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        <tr><td class="py-3 font-mono text-amber-500">ifsc</td><td class="py-3 text-gray-400">The 11-character IFSC code (e.g., SBIN0000001)</td></tr>
                                    </tbody>
                                </table>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                <div class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto">
<pre class="text-gray-400">{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: {
    <span class="text-blue-400">"ifsc"</span>: <span class="text-green-400">"SBIN0000001"</span>,
    <span class="text-blue-400">"bank"</span>: { <span class="text-blue-400">"id"</span>: 1, <span class="text-blue-400">"name"</span>: <span class="text-green-400">"State Bank of India"</span> },
    <span class="text-blue-400">"branch"</span>: <span class="text-green-400">"Kolkata Main Branch"</span>,
    <span class="text-blue-400">"address"</span>: <span class="text-green-400">"Samriddhi Bhavan, 1, Strand Road, Kolkata"</span>,
    <span class="text-blue-400">"city"</span>: { <span class="text-blue-400">"name"</span>: <span class="text-green-400">"Kolkata"</span> },
    <span class="text-blue-400">"state"</span>: { <span class="text-blue-400">"name"</span>: <span class="text-green-400">"West Bengal"</span> },
    <span class="text-blue-400">"micr"</span>: <span class="text-green-400">"700002021"</span>
  }
}</pre>
                                </div>
                            </div>
                        </div>

                        <!-- Banks in City -->
                        <div id="banks-in-city" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /cities/{city_id}/banks</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
                            </div>
                            <div class="p-6">
                                <p class="mb-4">Get a collection of all banks that have active physical branches in a specified city.</p>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Route Parameters</h4>
                                <table class="w-full text-sm mb-8">
                                    <thead class="text-gray-500 text-left border-b border-gray-800">
                                        <tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        <tr><td class="py-3 font-mono text-amber-500">city_id</td><td class="py-3 text-gray-400">The ID of the city</td></tr>
                                    </tbody>
                                </table>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                <div class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto">
<pre class="text-gray-400">{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: [ { <span class="text-blue-400">"id"</span>: 1, <span class="text-blue-400">"name"</span>: <span class="text-green-400">"State Bank of India"</span> } ]
}</pre>
                                </div>
                            </div>
                        </div>

                        <!-- Banks in State -->
                        <div id="banks-in-state" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /states/{state_id}/banks</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
                            </div>
                            <div class="p-6">
                                <p class="mb-4">List all banks operating within a particular state.</p>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Route Parameters</h4>
                                <table class="w-full text-sm mb-8">
                                    <thead class="text-gray-500 text-left border-b border-gray-800">
                                        <tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        <tr><td class="py-3 font-mono text-amber-500">state_id</td><td class="py-3 text-gray-400">The ID of the state</td></tr>
                                    </tbody>
                                </table>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                <div class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto">
<pre class="text-gray-400">{
  <span class="text-blue-400">"success"</span>: <span class="text-blue-400">true</span>,
  <span class="text-blue-400">"data"</span>: [ { <span class="text-blue-400">"id"</span>: 1, <span class="text-blue-400">"name"</span>: <span class="text-green-400">"State Bank of India"</span> } ]
}</pre>
                                </div>
                            </div>
                        </div>

                                                <!-- Currency Convert -->
                        <div id="currency-convert" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /currency/convert</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
                            </div>
                            <div class="p-6">
                                <p class="mb-4">Convert a defined financial amount from a source currency to a target currency dynamically.</p>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                <table class="w-full text-sm mb-8">
                                    <thead class="text-gray-500 text-left border-b border-gray-800">
                                        <tr><th class="pb-2">Field</th><th class="pb-2">Type</th><th class="pb-2">Description</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        <tr><td class="py-3 font-mono text-amber-500">from</td><td class="py-3 text-gray-400">string</td><td class="py-3 text-gray-400">Source currency (e.g., <code class="text-amber-400">USD</code>)</td></tr>
                                        <tr><td class="py-3 font-mono text-amber-500">to</td><td class="py-3 text-gray-400">string</td><td class="py-3 text-gray-400">Target currency (e.g., <code class="text-amber-400">INR</code>)</td></tr>
                                        <tr><td class="py-3 font-mono text-amber-500">amount</td><td class="py-3 text-gray-400">float</td><td class="py-3 text-gray-400">The amount to convert (e.g., <code class="text-white">100</code>)</td></tr>
                                    </tbody>
                                </table>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                <div class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto">
<pre class="text-gray-400">{
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
                        <div id="timezones-convert" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                             <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /timezones/convert</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
                            </div>
                            <div class="p-6">
                                <p class="mb-4">Map a specific datatime string between any two global IANA timezones.</p>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                <table class="w-full text-sm mb-8">
                                    <thead class="text-gray-500 text-left border-b border-gray-800">
                                        <tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        <tr><td class="py-3 font-mono text-amber-500">from</td><td class="py-3 text-gray-400">Source timezone (e.g., <code class="text-amber-400">UTC</code>)</td></tr>
                                        <tr><td class="py-3 font-mono text-amber-500">to</td><td class="py-3 text-gray-400">Target timezone (e.g., <code class="text-amber-400">Asia/Kolkata</code>)</td></tr>
                                        <tr><td class="py-3 font-mono text-amber-500">time</td><td class="py-3 text-gray-400">Original datetime string (optional, defaults to current time)</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Branch Search -->
                        <div id="branch-search" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /branch/search</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
                            </div>
                            <div class="p-6">
                                <p class="mb-4">Perform a fuzzy search across the total banking directory using keywords, locations, or names.</p>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                <table class="w-full text-sm mb-8">
                                    <thead class="text-gray-500 text-left border-b border-gray-800">
                                        <tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        <tr><td class="py-3 font-mono text-amber-500">search_query</td><td class="py-3 text-gray-400">Your search keyword</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Bank Coverage Map -->
                        <div id="bank-coverage" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /banks/{bank_id}/coverage</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
                            </div>
                            <div class="p-6">
                                <p class="mb-4">Gain analytical insights into a bank's national footprint. Returns total aggregated branch counts grouped by physical state jurisdiction.</p>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Route Parameters</h4>
                                <table class="w-full text-sm mb-8">
                                    <thead class="text-gray-500 text-left border-b border-gray-800">
                                        <tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        <tr><td class="py-3 font-mono text-amber-500">bank_id</td><td class="py-3 text-gray-400">The ID of the bank</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Banks in Pincode -->
                        <div id="banks-in-pincode" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /pincodes/{pincode}/banks</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
                            </div>
                            <div class="p-6">
                                <p class="mb-4">Evaluate hyper-local banking infrastructure. List all financial institutions matching a precise postal coordinate.</p>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Route Parameters</h4>
                                <table class="w-full text-sm mb-8">
                                    <thead class="text-gray-500 text-left border-b border-gray-800">
                                        <tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        <tr><td class="py-3 font-mono text-amber-500">pincode</td><td class="py-3 text-gray-400">The postal code (e.g. 400001)</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>


                <section id="hierarchical-data" class="pt-8">
                    <h2 class="text-3xl font-bold text-white mb-6 uppercase tracking-wider border-b border-gray-800 pb-4">Hierarchical Drill-Downs</h2>
                </section>
                <div id="country-detail" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden mt-12 block">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /countries/{country_id}</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
                            </div>
                            <div class="p-6">
                                <p class="mb-4">Fetch deep, isolated intelligence for a single country, including surface area, population, and macroeconomic definitions.</p>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Route Parameters</h4>
                                <table class="w-full text-sm mb-8">
                                    <thead class="text-gray-500 text-left border-b border-gray-800">
                                        <tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        <tr><td class="py-3 font-mono text-amber-500">country_id</td><td class="py-3 text-gray-400">The ID of the country to fetch</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Country States -->
                        <div id="country-states" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /countries/{country_id}/states</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
                            </div>
                            <div class="p-6">
                                <p class="mb-4">Query all primary administrative divisions belonging directly to a country's geopolitical mapping.</p>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Route Parameters</h4>
                                <table class="w-full text-sm mb-8">
                                    <thead class="text-gray-500 text-left border-b border-gray-800">
                                        <tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        <tr><td class="py-3 font-mono text-amber-500">country_id</td><td class="py-3 text-gray-400">The ID of the country</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Country Cities -->
                        <div id="country-cities" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /countries/{country_id}/cities</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
                            </div>
                            <div class="p-6">
                                <p class="mb-4">Generate a bulk taxonomy of all mapped cities globally tied to the country.</p>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Route Parameters</h4>
                                <table class="w-full text-sm mb-8">
                                    <thead class="text-gray-500 text-left border-b border-gray-800">
                                        <tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        <tr><td class="py-3 font-mono text-amber-500">country_id</td><td class="py-3 text-gray-400">The ID of the country</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Country Timezones -->
                        <div id="country-timezones" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /countries/{country_id}/timezones</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
                            </div>
                            <div class="p-6">
                                <p class="mb-4">List all IANA standard timezones bridging the boundaries of a target nation.</p>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Route Parameters</h4>
                                <table class="w-full text-sm mb-8">
                                    <thead class="text-gray-500 text-left border-b border-gray-800">
                                        <tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        <tr><td class="py-3 font-mono text-amber-500">country_id</td><td class="py-3 text-gray-400">The ID of the country</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Country Banks -->
                        <div id="country-banks" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /countries/{country_id}/banks</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
                            </div>
                            <div class="p-6">
                                <p class="mb-4">Scan the entirety of a country's verified banking infrastructure array.</p>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Route Parameters</h4>
                                <table class="w-full text-sm mb-8">
                                    <thead class="text-gray-500 text-left border-b border-gray-800">
                                        <tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        <tr><td class="py-3 font-mono text-amber-500">country_id</td><td class="py-3 text-gray-400">The ID of the country</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Country Neighbors -->
                        <div id="country-neighbors" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /countries/{country_id}/neighbors</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
                            </div>
                            <div class="p-6">
                                <p class="mb-4">Topological mapping of physically adjoining, land-bordered partner nations.</p>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Route Parameters</h4>
                                <table class="w-full text-sm mb-8">
                                    <thead class="text-gray-500 text-left border-b border-gray-800">
                                        <tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        <tr><td class="py-3 font-mono text-amber-500">country_id</td><td class="py-3 text-gray-400">The ID of the country</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Compare Countries -->
                        <div id="countries-compare" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /countries/compare</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
                            </div>
                            <div class="p-6">
                                <p class="mb-4">Differential analysis yielding parallel statistical comparisons (economic, social) between two independent territories.</p>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                <table class="w-full text-sm mb-8">
                                    <thead class="text-gray-500 text-left border-b border-gray-800">
                                        <tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        <tr><td class="py-3 font-mono text-amber-500">c1_id</td><td class="py-3 text-gray-400">First Country ID</td></tr>
                                        <tr><td class="py-3 font-mono text-amber-500">c2_id</td><td class="py-3 text-gray-400">Second Country ID</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- State Detail -->
                        <div id="state-detail" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /states/{state_id}</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
                            </div>
                            <div class="p-6">
                                <p class="mb-4">Inspect high-fidelity parameters localized to a particular municipal region mapping.</p>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Route Parameters</h4>
                                <table class="w-full text-sm mb-8">
                                    <thead class="text-gray-500 text-left border-b border-gray-800">
                                        <tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        <tr><td class="py-3 font-mono text-amber-500">state_id</td><td class="py-3 text-gray-400">The ID of the state to fetch</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- State Cities -->
                        <div id="state-cities" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /states/{state_id}/cities</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
                            </div>
                            <div class="p-6">
                                <p class="mb-4">Batch isolate all cities governed natively inside a given state district.</p>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Route Parameters</h4>
                                <table class="w-full text-sm mb-8">
                                    <thead class="text-gray-500 text-left border-b border-gray-800">
                                        <tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        <tr><td class="py-3 font-mono text-amber-500">state_id</td><td class="py-3 text-gray-400">The ID of the state</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- City Detail -->
                        <div id="city-detail" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /cities/{city_id}</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
                            </div>
                            <div class="p-6">
                                <p class="mb-4">Extrapolate exact latitude/longitude coordinates and meta-flags bound to a solitary recognized city entity.</p>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Route Parameters</h4>
                                <table class="w-full text-sm mb-8">
                                    <thead class="text-gray-500 text-left border-b border-gray-800">
                                        <tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        <tr><td class="py-3 font-mono text-amber-500">city_id</td><td class="py-3 text-gray-400">The ID of the city</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                <section id="smart-addressing" class="pt-8">
                    <h2 class="text-3xl font-bold text-white mb-6 uppercase tracking-wider border-b border-gray-800 pb-4">Smart Addressing</h2>
                </section>
                <div id="address-autocomplete" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden mt-12 block">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /address/autocomplete</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
                            </div>
                            <div class="p-6">
                                <p class="mb-4">Ultra-fast search ideal for building intuitive autosuggest dropdowns based on partial keyword entry.</p>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                <table class="w-full text-sm mb-8">
                                    <thead class="text-gray-500 text-left border-b border-gray-800">
                                        <tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        <tr><td class="py-3 font-mono text-amber-500">search_query</td><td class="py-3 text-gray-400">Search text snippet</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Address Validate -->
                        <div id="address-validate" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /address/validate</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
                            </div>
                            <div class="p-6">
                                <p class="mb-4">Verification algorithm utilizing internal relations to validate combination authenticity between coordinates.</p>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                <table class="w-full text-sm mb-8">
                                    <thead class="text-gray-500 text-left border-b border-gray-800">
                                        <tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        <tr><td class="py-3 font-mono text-amber-500">pincode</td><td class="py-3 text-gray-400">Target postal code</td></tr>
                                        <tr><td class="py-3 font-mono text-amber-500">city_id</td><td class="py-3 text-gray-400">Target City ID</td></tr>
                                        <tr><td class="py-3 font-mono text-amber-500">state_id</td><td class="py-3 text-gray-400">Target State ID</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>


<!-- Geospatial Analysis -->
                <section id="geospatial-analysis">
                    <div class="flex items-center mb-12">
                        <h2 class="text-3xl font-bold text-white">Geospatial Analysis</h2>
                    </div>

                    <div class="space-y-12">
                        <!-- Geo Statistics -->
                        <div id="geo-stats" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden relative">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /geospatial/statistics</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
                            </div>
                            <div class="p-6">
                                <p class="mb-4 text-gray-400">Retrieve aggregate data counts for planning your integration. This is a non-chargeable API, perfect for calculating pagination and data volume.</p>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Optional Parameters</h4>
                                <table class="w-full text-sm mb-6">
                                    <thead class="text-gray-500 text-left border-b border-gray-800">
                                        <tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800 text-gray-400">
                                        <tr><td class="py-3 font-mono text-amber-500">country_id</td><td class="py-3">Filter counts by Country ID</td></tr>
                                        <tr><td class="py-3 font-mono text-amber-500">state_id</td><td class="py-3">Filter counts by State ID</td></tr>
                                    </tbody>
                                </table>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                <div class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto">
<pre class="text-gray-400">{
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
                        <div id="geo-distance" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /geospatial/distance</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
                            </div>
                            <div class="p-6">
                                <p class="mb-6 text-gray-400">Calculate the precision distance between two coordinate points using the Haversine formula. Supports multiple output units.</p>

                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
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
                                            <td class="py-3">Latitude of Point A &mdash; must be between <code class="text-white">-90</code> and <code class="text-white">90</code></td>
                                        </tr>
                                        <tr>
                                            <td class="py-3 pr-4 font-mono text-amber-500">lng1</td>
                                            <td class="py-3 pr-4 text-gray-500">float</td>
                                            <td class="py-3 pr-4 text-red-400 font-bold">Yes</td>
                                            <td class="py-3">Longitude of Point A &mdash; must be between <code class="text-white">-180</code> and <code class="text-white">180</code></td>
                                        </tr>
                                        <tr>
                                            <td class="py-3 pr-4 font-mono text-amber-500">lat2</td>
                                            <td class="py-3 pr-4 text-gray-500">float</td>
                                            <td class="py-3 pr-4 text-red-400 font-bold">Yes</td>
                                            <td class="py-3">Latitude of Point B &mdash; must be between <code class="text-white">-90</code> and <code class="text-white">90</code></td>
                                        </tr>
                                        <tr>
                                            <td class="py-3 pr-4 font-mono text-amber-500">lng2</td>
                                            <td class="py-3 pr-4 text-gray-500">float</td>
                                            <td class="py-3 pr-4 text-red-400 font-bold">Yes</td>
                                            <td class="py-3">Longitude of Point B &mdash; must be between <code class="text-white">-180</code> and <code class="text-white">180</code></td>
                                        </tr>
                                        <tr>
                                            <td class="py-3 pr-4 font-mono text-amber-500">unit</td>
                                            <td class="py-3 pr-4 text-gray-500">string</td>
                                            <td class="py-3 pr-4 text-gray-600 font-bold">No</td>
                                            <td class="py-3">
                                                Output unit for the distance. Defaults to <code class="text-white">km</code>.
                                                <div class="mt-2 flex flex-wrap gap-2">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-blue-500/10 text-blue-300 border border-blue-500/20 text-[10px] font-mono font-bold">km</span>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-purple-500/10 text-purple-300 border border-purple-500/20 text-[10px] font-mono font-bold">miles</span>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-teal-500/10 text-teal-300 border border-teal-500/20 text-[10px] font-mono font-bold">meters</span>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-orange-500/10 text-orange-300 border border-orange-500/20 text-[10px] font-mono font-bold">centimeters</span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Example Requests</h4>
                                <div class="bg-gray-900 rounded-lg p-4 font-mono text-xs text-gray-500 border border-gray-800 mb-6 space-y-1.5">
                                    <div><span class="text-gray-600"># Kilometers (default)</span></div>
                                    <div>/api/v1/geospatial/distance?lat1=28.6&lng1=77.2&lat2=19.0&lng2=72.8</div>
                                    <div class="mt-2"><span class="text-gray-600"># Miles</span></div>
                                    <div>/api/v1/geospatial/distance?lat1=28.6&lng1=77.2&lat2=19.0&lng2=72.8&unit=<span class="text-amber-500">miles</span></div>
                                    <div class="mt-2"><span class="text-gray-600"># Meters</span></div>
                                    <div>/api/v1/geospatial/distance?lat1=28.6&lng1=77.2&lat2=19.0&lng2=72.8&unit=<span class="text-amber-500">meters</span></div>
                                    <div class="mt-2"><span class="text-gray-600"># Centimeters</span></div>
                                    <div>/api/v1/geospatial/distance?lat1=28.6&lng1=77.2&lat2=19.0&lng2=72.8&unit=<span class="text-amber-500">centimeters</span></div>
                                </div>

                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                <div class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto text-gray-400">
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
                        <div id="geo-nearby" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /geospatial/nearby</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
                            </div>
                            <div class="p-6">
                                <p class="mb-6 text-gray-400">Search for Cities or Pincodes within a customized radius from any coordinate point.</p>
                                <div class="bg-gray-900 rounded-lg p-4 font-mono text-xs text-gray-500 border border-gray-800 mb-8">
                                    /api/v1/geospatial/nearby?lat=19.076&lng=72.877&radius=50&type=pincode
                                </div>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                <table class="w-full text-sm mb-8">
                                    <thead class="text-gray-500 text-left border-b border-gray-800">
                                        <tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        <tr><td class="py-3 font-mono text-amber-500">lat</td><td class="py-3 text-gray-400">Point latitude</td></tr>
                                        <tr><td class="py-3 font-mono text-amber-500">lng</td><td class="py-3 text-gray-400">Point longitude</td></tr>
                                        <tr><td class="py-3 font-mono text-amber-500">radius</td><td class="py-3 text-gray-400">Radius in km (default: 10)</td></tr>
                                        <tr><td class="py-3 font-mono text-amber-500">type</td><td class="py-3 text-gray-400">Search type: <code class="text-amber-400">city</code> or <code class="text-amber-400">pincode</code></td></tr>
                                    </tbody>
                                </table>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Response Example</h4>
                                <div class="bg-[#0f172a] rounded-lg p-4 font-mono text-xs overflow-x-auto">
<pre class="text-gray-400">{
  "success": true,
  "data": [
    { "id": 1024, "name": "Mumbai", "distance": 2.4 }
  ]
}</pre>
                                </div>
                            </div>
                        </div>

                        <!-- Reverse Geocode -->
                        <div id="geo-geocode" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /geospatial/geocode</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
                            </div>
                            <div class="p-6">
                                <p class="mb-4">Convert spatial coordinates back into localized human-readable geographical components reliably.</p>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                <table class="w-full text-sm mb-8">
                                    <thead class="text-gray-500 text-left border-b border-gray-800">
                                        <tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        <tr><td class="py-3 font-mono text-amber-500">lat</td><td class="py-3 text-gray-400">Target latitude</td></tr>
                                        <tr><td class="py-3 font-mono text-amber-500">lng</td><td class="py-3 text-gray-400">Target longitude</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Boundary Search -->
                        <div id="geo-boundary" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /geospatial/boundary</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
                            </div>
                            <div class="p-6">
                                <p class="mb-4">Fetch all coordinates enclosed within a rigid multi-point boundary or bounding box constraint.</p>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                <table class="w-full text-sm mb-8">
                                    <thead class="text-gray-500 text-left border-b border-gray-800">
                                        <tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        <tr><td class="py-3 font-mono text-amber-500">min_lat</td><td class="py-3 text-gray-400">Minimum latitude of the bounding box</td></tr>
                                        <tr><td class="py-3 font-mono text-amber-500">max_lat</td><td class="py-3 text-gray-400">Maximum latitude of the bounding box</td></tr>
                                        <tr><td class="py-3 font-mono text-amber-500">min_lng</td><td class="py-3 text-gray-400">Minimum longitude of the bounding box</td></tr>
                                        <tr><td class="py-3 font-mono text-amber-500">max_lng</td><td class="py-3 text-gray-400">Maximum longitude of the bounding box</td></tr>
                                        <tr><td class="py-3 font-mono text-amber-500">type</td><td class="py-3 text-gray-400">Data type: <code class="text-amber-400">city</code> or <code class="text-amber-400">pincode</code></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Grid Clustering -->
                        <div id="geo-cluster" class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-900/60 border-b border-gray-800 flex items-center justify-between">
                                <h3 class="font-bold text-white"><span class="text-blue-400 mr-2 uppercase">GET</span> /geospatial/cluster</h3>
                                <span class="bg-amber-600/20 text-amber-500 text-[10px] uppercase font-black px-2 sm:px-3 py-1 rounded-full border border-amber-600/30 ring-4 ring-amber-600/5">
                                    <i class="fas fa-coins sm:mr-2 text-amber-400"></i> <span class="hidden sm:inline">Credits Required</span>
                                </span>
                            </div>
                            <div class="p-6">
                                <p class="mb-4">Utilizes grouping algorithms to simplify heavy marker map deployments onto interactive map frontends.</p>
                                <h4 class="text-xs font-bold text-gray-500 uppercase mb-4">Query Parameters</h4>
                                <table class="w-full text-sm mb-8">
                                    <thead class="text-gray-500 text-left border-b border-gray-800">
                                        <tr><th class="pb-2">Field</th><th class="pb-2">Description</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        <tr><td class="py-3 font-mono text-amber-500">lat</td><td class="py-3 text-gray-400">Center latitude</td></tr>
                                        <tr><td class="py-3 font-mono text-amber-500">lng</td><td class="py-3 text-gray-400">Center longitude</td></tr>
                                        <tr><td class="py-3 font-mono text-amber-500">radius</td><td class="py-3 text-gray-400">Radius to search within (km)</td></tr>
                                        <tr><td class="py-3 font-mono text-amber-500">grid_size</td><td class="py-3 text-gray-400">Size of the grouping grid (default: 0.5)</td></tr>
                                    </tbody>
                                </table>
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
    html { scroll-behavior: smooth; }
    section[id] { scroll-margin-top: 120px; }
    #geo-docs a { scroll-behavior: smooth; }
    ::-webkit-scrollbar { width: 8px; }
    ::-webkit-scrollbar-track { background: #020617; }
    ::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: #334155; }

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
