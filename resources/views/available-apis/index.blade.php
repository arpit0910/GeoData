@extends('layouts.app')

@section('header', 'Available APIs')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <p class="text-xs font-black uppercase tracking-[0.25em] text-amber-500">Developer Access</p>
            <h1 class="mt-2 text-3xl md:text-4xl font-black text-white">Available APIs</h1>
            <p class="mt-2 text-gray-400">Free APIs available for onboarding. Other APIs require a paid subscription.</p>
        </div>
        <a href="{{ route('docs') }}" class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-sm font-bold transition-colors">
            Open Complete Docs <i class="fas fa-arrow-up-right-from-square ml-2 text-xs"></i>
        </a>
    </div>

    @if(count($availableApiDocs))
        <div class="grid grid-cols-1 gap-5">
            @foreach($availableApiDocs as $group)
                <section class="bg-gray-900/40 rounded-xl border border-gray-800 overflow-hidden shadow-sm">
                    <div class="px-6 py-5 bg-gray-900/60 border-b border-gray-800 flex items-center gap-3">
                        <span class="w-10 h-10 rounded-xl bg-{{ $group['color'] }}-500/10 text-{{ $group['color'] }}-400 flex items-center justify-center"><i class="fas {{ $group['icon'] }}"></i></span>
                        <div>
                            <h2 class="text-lg font-black text-white">{{ $group['category'] }}</h2>
                            <p class="text-[10px] font-black uppercase tracking-widest text-emerald-400 mt-1">Free tier included</p>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-800">
                        @foreach($group['items'] as $api)
                            <div class="px-6 py-5">
                                <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
                                    <span class="text-[10px] font-black text-blue-400 uppercase">{{ $api['method'] }}</span>
                                    <code class="text-sm text-white break-all">{{ $api['path'] }}</code>
                                </div>
                                <p class="mt-2 text-sm text-gray-400">{{ $api['description'] }}</p>
                                <div class="mt-5 grid grid-cols-1 lg:grid-cols-2 gap-5">
                                    <div>
                                        <h3 class="text-xs font-black uppercase tracking-widest text-gray-500">How to use</h3>
                                        <p class="mt-2 text-xs text-gray-400">Send a GET request with your API token in the Authorization header.</p>
                                        <div class="mt-3 rounded-xl bg-gray-900 p-4 overflow-x-auto"><pre class="text-xs text-green-300 whitespace-pre-wrap">{{ $api['example'] }}</pre></div>
                                    </div>
                                    <div>
                                        <h3 class="text-xs font-black uppercase tracking-widest text-gray-500">Parameters</h3>
                                        <div class="mt-3 overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
                                            @foreach($api['parameters'] as $parameter)
                                                <div class="p-3 border-b last:border-b-0 border-gray-800 text-xs">
                                                    <div class="flex items-center gap-2"><code class="font-bold text-amber-400">{{ $parameter['name'] }}</code><span class="text-gray-400">({{ $parameter['location'] }})</span><span class="text-red-400">{{ $parameter['required'] ? 'Required' : 'Optional' }}</span></div>
                                                    <p class="mt-1 text-gray-400">{{ $parameter['description'] }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-5">
                                    <h3 class="text-xs font-black uppercase tracking-widest text-gray-500">Response</h3>
                                    <div class="mt-3 rounded-xl bg-gray-900 p-4 overflow-x-auto"><pre class="text-xs text-blue-200 whitespace-pre-wrap">{{ json_encode($api['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></div>
                                </div>
                                <a href="{{ route('docs') }}#{{ $api['anchor'] }}" class="inline-flex mt-4 text-xs font-bold text-amber-600 dark:text-amber-400 hover:underline">View full documentation <i class="fas fa-arrow-right ml-2"></i></a>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @else
        <div class="bg-gray-900/40 rounded-xl border border-dashed border-gray-700 p-12 text-center">
            <i class="fas fa-lock text-3xl text-gray-400 mb-4"></i>
            <h2 class="text-xl font-black text-white">No active free plan found</h2>
            <p class="mt-2 text-gray-400">Contact an administrator to assign a free onboarding plan.</p>
        </div>
    @endif
</div>
@endsection
