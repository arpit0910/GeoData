@extends('layouts.public')
@section('title', ($technologyNews->ai_meta_title ?: $technologyNews->ai_title).' - SetuGeo Technology')
@section('meta_description', \Illuminate\Support\Str::limit($technologyNews->ai_meta_description ?: $technologyNews->ai_summary, 155))
@section('canonical_url', route('technology-news.show', $technologyNews->slug))
@section('robots', str_word_count((string) $technologyNews->ai_content) < 180 ? 'noindex, follow' : 'index, follow')
@section('content')
<div class="market-shell">@include('website.partials.technology-news-nav')
<div class="market-news-detail-wrap py-5 sm:py-8 lg:py-10">
    <a href="{{ route('technology-news.index') }}" class="mb-4 sm:mb-6 inline-flex items-center text-sm font-bold text-amber-400 hover:text-amber-300"><i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>Back to technology news</a>
    <article class="market-panel overflow-hidden">
        <div class="px-5 py-6 sm:px-8 sm:py-8 lg:px-10 lg:py-10">
            <div class="mx-auto max-w-3xl">
                <div class="mb-4 flex flex-wrap items-center gap-x-3 gap-y-2 sm:mb-5">
                    <span class="market-chip">{{ $technologyNews->category->name }}</span>
                    <time class="text-xs market-muted" datetime="{{ $technologyNews->published_at->toIso8601String() }}">{{ $technologyNews->published_at->copy()->timezone('Asia/Kolkata')->format('d M Y') }} &middot; {{ $technologyNews->published_at->copy()->timezone('Asia/Kolkata')->format('h:i A') }} IST</time>
                </div>
                <h1 class="break-words text-2xl font-black leading-tight text-white sm:text-3xl lg:text-4xl">{{ $technologyNews->ai_title }}</h1>
                <p class="mt-4 text-sm leading-6 market-muted">Adapted from {{ $technologyNews->publisher_name }}@if($technologyNews->author_name) &middot; Original author: {{ $technologyNews->author_name }}@endif</p>
                @php
                    $articleParagraphs = array_values(array_filter(
                        preg_split('/(?:\r?\n){2,}/', trim(strip_tags((string) $technologyNews->ai_content))) ?: [],
                        static fn ($paragraph) => trim($paragraph) !== ''
                    ));
                @endphp
                <div class="mt-6 space-y-5 text-[15px] leading-7 text-slate-300 sm:mt-8 sm:space-y-6 sm:text-base sm:leading-8">
                    @foreach($articleParagraphs as $paragraph)<p class="break-words">{{ preg_replace('/\s+/u', ' ', trim($paragraph)) }}</p>@endforeach
                </div>
                <footer class="mt-8 border-t border-white/10 pt-5 text-sm leading-6 market-muted">
                    <p>AI-assisted report checked against the available source material. The publisher retains rights to its original reporting.</p>
                    <p class="mt-3 text-xs">Source: <a href="{{ $technologyNews->original_url }}" target="_blank" rel="noopener noreferrer" class="underline underline-offset-4">{{ $technologyNews->source->name }}</a> &middot; <a href="{{ $technologyNews->source->permission_reference }}" target="_blank" rel="noopener noreferrer" class="underline underline-offset-4">Source licence & attribution</a></p>
                    @if($technologyNews->author_name)<p class="mt-3 text-xs">By {{ $technologyNews->author_name }} from {{ $technologyNews->source->name }}. Adaptation by SetuGeo.</p>@endif
                    @if($technologyNews->source->license_name && $technologyNews->source->license_url)<p class="mt-3 text-xs">Adapted text is available under <a href="{{ $technologyNews->source->license_url }}" target="_blank" rel="noopener noreferrer" class="underline underline-offset-4">{{ $technologyNews->source->license_name }}</a>. Changes: AI-assisted paraphrasing and editing.</p>@endif
                </footer>
            </div>
        </div>
    </article>
    <section class="mt-8" aria-labelledby="more-technology-news"><h2 id="more-technology-news" class="text-xl font-extrabold text-white">More technology news</h2><div class="mt-4 space-y-3">@foreach($related as $story)<a href="{{ route('technology-news.show', $story->slug) }}" class="market-panel block p-5 font-bold text-slate-200 hover:text-amber-300">{{ $story->ai_title }} <i class="fas fa-arrow-right ml-2 text-xs" aria-hidden="true"></i></a>@endforeach</div><a href="{{ route('technology-news.index') }}" class="market-button mt-5">Browse all technology news</a></section>
</div></div>
@endsection
