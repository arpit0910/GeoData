@include('website.partials.market-styles')
<div class="border-b border-white/10 bg-[#090d14]">
    <div class="market-wrap pt-5 sm:pt-7">
        <header>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">Technology news</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 market-muted">The latest verified technology stories, with concise previews and full article pages.</p>
        </header>
        <nav class="market-nav-scroll mt-5 sm:mt-6 border-t border-white/[.07] flex items-center gap-6 sm:gap-8 overflow-x-auto" aria-label="News sections">
            <a href="{{ route('technology-news.index') }}" aria-current="page" class="shrink-0 whitespace-nowrap border-b-2 border-amber-400 py-3.5 text-sm font-semibold text-amber-400">Technology news</a>
            <a href="{{ route('market.news') }}" class="shrink-0 whitespace-nowrap border-b-2 border-transparent py-3.5 text-sm font-semibold text-slate-400 hover:text-white">Market news</a>
        </nav>
    </div>
</div>
