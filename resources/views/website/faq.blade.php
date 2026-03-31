@extends('layouts.public')

@section('title', 'Frequently Asked Questions - SetuGeo API')

@section('content')
<!-- Hero Section -->
<div class="relative pt-20 pb-32 overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
        <h1 class="text-5xl md:text-7xl font-black mb-6 tracking-tight">
            How Can We <span class="text-gradient">Help?</span>
        </h1>
        <p class="text-xl text-gray-400 max-w-2xl mx-auto font-medium">
            Find answers to common questions about our geographic data APIs, pricing, and technical implementation.
        </p>
    </div>
</div>

<!-- FAQ Content -->
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pb-32">
    <div class="space-y-6" x-data="{ selectedFaq: null }">
        @forelse($faqs as $faq)
            <div class="glass-card rounded-[2rem] overflow-hidden transition-all duration-500 hover:border-amber-500/30 group">
                <button @click="selectedFaq = selectedFaq === {{ $faq->id }} ? null : {{ $faq->id }}" 
                    class="w-full px-8 py-7 flex items-center justify-between text-left focus:outline-none">
                    <span class="text-lg md:text-xl font-bold text-white group-hover:text-amber-500 transition-colors duration-300">
                        {{ $faq->question }}
                    </span>
                    <div class="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center transition-all duration-500"
                        :class="selectedFaq === {{ $faq->id }} ? 'bg-amber-500 rotate-180' : 'group-hover:bg-white/10'">
                        <i class="fas fa-chevron-down text-sm" :class="selectedFaq === {{ $faq->id }} ? 'text-black' : 'text-gray-400'"></i>
                    </div>
                </button>
                
                <div x-show="selectedFaq === {{ $faq->id }}" 
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 -translate-y-4"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-cloak
                    class="px-8 pb-8">
                    <div class="h-px w-full bg-gradient-to-r from-transparent via-white/10 to-transparent mb-8"></div>
                    <div class="text-gray-400 text-lg leading-relaxed font-medium">
                        {!! nl2br(e($faq->answer)) !!}
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-24 glass-card rounded-[3rem]">
                <div class="w-20 h-20 bg-white/5 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-search text-3xl text-gray-600"></i>
                </div>
                <h3 class="text-2xl font-bold text-white mb-2">No FAQs Yet</h3>
                <p class="text-gray-500 font-medium">We're currently updating our knowledge base. Check back soon!</p>
            </div>
        @endforelse
    </div>

    <!-- Contact CTA -->
    <div class="mt-20 glass-card p-12 rounded-[3rem] text-center border-amber-500/20 bg-gradient-to-br from-amber-500/5 to-transparent">
        <h3 class="text-3xl font-bold text-white mb-4 tracking-tight">Still have questions?</h3>
        <p class="text-gray-400 mb-10 text-lg font-medium max-w-xl mx-auto">
            Our support team is available 24/7 to help you with any technical or billing inquiries.
        </p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-6">
            <a href="{{ route('contact') }}" class="bg-amber-600 hover:bg-amber-700 text-black font-black px-10 py-5 rounded-2xl transition-all shadow-xl hover:shadow-amber-500/20 transform hover:-translate-y-1">
                Contact Support
            </a>
            <a href="{{ route('docs') }}" class="text-white font-bold hover:text-amber-500 transition-colors flex items-center gap-2">
                Read Documentation <i class="fas fa-arrow-right text-xs"></i>
            </a>
        </div>
    </div>
</div>
@endsection
