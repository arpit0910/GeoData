@extends('layouts.public')
@section('title', ($technologyNews->ai_meta_title ?: $technologyNews->ai_title).' - SetuGeo Technology')
@section('meta_description', \Illuminate\Support\Str::limit($technologyNews->ai_meta_description ?: $technologyNews->ai_summary, 155))
@section('canonical_url', route('technology-news.show', $technologyNews->slug))
@section('robots', str_word_count((string) $technologyNews->ai_content) < 180 ? 'noindex, follow' : 'index, follow')
@section('content')
@include('website.partials.market-styles')
<div class="market-shell">
<div class="market-news-detail-wrap py-5 sm:py-8 lg:py-10">
    <a href="{{ route('technology-news.index') }}" class="mb-4 sm:mb-6 inline-flex items-center text-sm font-bold text-amber-400 hover:text-amber-300"><i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>Back to technology news</a>
    <article class="market-panel overflow-hidden">
        <div class="px-5 py-6 sm:px-8 sm:py-8 lg:px-10 lg:py-10">
            <div class="mx-auto max-w-3xl">
                <div class="mb-4 flex flex-wrap items-center gap-x-3 gap-y-2 sm:mb-5">
                    <span class="text-xs font-semibold text-amber-300">{{ $technologyNews->category?->name ?? 'Technology' }}</span>
                    <time class="text-xs market-muted" datetime="{{ $technologyNews->published_at->toIso8601String() }}">{{ $technologyNews->published_at->copy()->timezone('Asia/Kolkata')->format('d M Y') }} &middot; {{ $technologyNews->published_at->copy()->timezone('Asia/Kolkata')->format('h:i A') }} IST</time>
                </div>
                <h1 class="break-words text-2xl font-black leading-tight text-white sm:text-3xl lg:text-4xl">{{ $technologyNews->ai_title }}</h1>
                @php
                    $articleParagraphs = array_values(array_filter(
                        preg_split('/(?:\r?\n){2,}/', trim(strip_tags((string) $technologyNews->ai_content))) ?: [],
                        static fn ($paragraph) => trim($paragraph) !== ''
                    ));
                @endphp
                <div class="mt-6 space-y-5 text-[15px] leading-7 text-slate-300 sm:mt-8 sm:space-y-6 sm:text-base sm:leading-8">
                    @foreach($articleParagraphs as $paragraph)<p class="break-words">{{ preg_replace('/\s+/u', ' ', trim($paragraph)) }}</p>@endforeach
                </div>
                <details class="mt-8 border-t border-white/10 pt-2 text-sm leading-6 market-muted">
                    <summary class="min-h-[44px] w-fit cursor-pointer py-3 font-semibold text-slate-300">Source credits</summary>
                    <p>
                        @if($technologyNews->author_name)By {{ $technologyNews->author_name }} from @endif<a href="{{ $technologyNews->original_url }}" target="_blank" rel="noopener noreferrer" class="underline underline-offset-4">{{ $technologyNews->source->name }}</a>.
                        Edited by SetuGeo.
                    </p>
                    @if($technologyNews->source->license_name && $technologyNews->source->license_url)
                        <a href="{{ $technologyNews->source->license_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-[44px] items-center underline underline-offset-4">{{ $technologyNews->source->license_name }}</a>
                    @endif
                </details>
            </div>
        </div>
    </article>
    <section class="mt-8" aria-labelledby="more-technology-news">
        <h2 id="more-technology-news" class="text-xl font-bold text-white">More technology news</h2>
        @if($related->isNotEmpty())
            <ul class="mt-3 divide-y divide-white/10 border-y border-white/10">
                @foreach($related as $story)
                    <li><a href="{{ route('technology-news.show', $story->slug) }}" class="block break-words py-3 font-semibold text-slate-200 hover:text-amber-300">{{ $story->ai_title }}</a></li>
                @endforeach
            </ul>
        @endif
        <a href="{{ route('technology-news.index') }}" class="mt-4 inline-flex min-h-[44px] items-center text-sm font-semibold text-amber-400 hover:underline">Browse all technology news</a>
    </section>
</div></div>
@endsection
