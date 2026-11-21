@include('website.partials.market-styles')
<div class="border-b border-white/10 bg-[#090d14]">
    <div class="market-wrap">
        <header class="mx-auto max-w-5xl pt-5 sm:pt-7">
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">Technology news</h1>
        </header>
        <nav class="market-nav-scroll mx-auto mt-4 flex max-w-5xl items-center gap-6 overflow-x-auto sm:gap-8" aria-label="News sections">
            <a href="{{ route('technology-news.index') }}" aria-current="page" class="shrink-0 whitespace-nowrap border-b-2 border-amber-400 py-3.5 text-sm font-semibold text-amber-400">Technology news</a>
            <a href="{{ route('market.news') }}" class="shrink-0 whitespace-nowrap border-b-2 border-transparent py-3.5 text-sm font-semibold text-slate-400 hover:text-white">Market news</a>
        </nav>
    </div>
</div>
