@include('website.partials.market-styles')

@php
    $marketLinks = [
        ['market.index', 'Overview'],
        ['market.indices', 'Indices'],
        ['market.stocks', 'Stocks'],
        ['market.mutual-funds', 'Mutual funds'],
        ['market.news', 'News'],
        ['market.nudges', 'Nudges'],
        ['market.fundamentals', 'Fundamentals'],
        ['market.corporate-actions', 'Corporate actions'],
    ];
@endphp

<div class="border-b border-white/10 bg-[#090d14]">
    <div class="market-wrap pt-5 sm:pt-7">
        <header>
            <a href="{{ route('market.index') }}" class="text-[11px] font-bold uppercase tracking-[.16em] text-amber-400">Markets</a>
            <h1 class="mt-1.5 text-2xl sm:text-3xl font-extrabold tracking-tight text-white">@yield('market_heading', 'Market intelligence')</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 market-muted">@yield('market_subheading', 'Indian market data, organized for quick research.')</p>
        </header>

        <div class="mt-5 sm:mt-6 border-t border-white/[.07]">
            <nav class="market-nav-scroll flex items-center gap-6 sm:gap-8 overflow-x-auto" aria-label="Market sections">
                @foreach($marketLinks as [$routeName, $label])
                    <a href="{{ $routeName === 'market.indices' && !Route::has($routeName) ? url('/market/indices') : route($routeName) }}"
                       class="shrink-0 whitespace-nowrap border-b-2 py-3.5 text-sm font-semibold transition-colors {{ request()->routeIs($routeName) || request()->routeIs($routeName.'.*') ? 'border-amber-400 text-amber-400' : 'border-transparent text-slate-400 hover:border-slate-600 hover:text-white' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
        </div>
    </div>
</div>
