@extends('layouts.public')
@section('title', 'Indian Market Data, News & Fundamentals - SetuGeo')
@section('meta_description', 'Explore Indian stocks, mutual funds, company fundamentals, corporate actions and financial news on fast, focused pages.')
@section('market_heading', 'Market overview')
@section('market_subheading', 'A current snapshot of Indian equities, mutual funds, company fundamentals, financial news and upcoming corporate actions.')

@section('content')
<div class="market-shell">
    @include('website.partials.market-nav')

    <main class="market-wrap py-4 sm:py-6 lg:py-8">
        <section class="market-panel market-grid-line relative overflow-hidden p-5 sm:p-6 lg:p-8 mb-4 sm:mb-6">
            <div class="absolute -right-20 -top-24 h-64 w-64 rounded-full bg-amber-500/10 blur-3xl pointer-events-none"></div>
            <div class="relative grid lg:grid-cols-[1fr_auto] gap-5 lg:gap-8 lg:items-center">
                <div class="max-w-3xl">
                    <div class="flex flex-wrap items-center gap-2 mb-3">
                        <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-bold {{ $marketStatus['is_open'] ? 'border-emerald-400/30 bg-emerald-400/10 text-emerald-300' : 'border-slate-500/30 bg-slate-500/10 text-slate-300' }}">
                            <span class="h-2 w-2 rounded-full {{ $marketStatus['is_open'] ? 'bg-emerald-400 animate-pulse' : 'bg-slate-400' }}"></span>
                            {{ $marketStatus['status'] }}
                        </span>
                        <span class="text-xs market-muted">NSE &amp; BSE &middot; {{ $marketStatus['trading_hours'] }}</span>
                    </div>
                    <p class="market-kicker">Indian market intelligence</p>
                    <h2 class="mt-2 text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight text-white leading-[1.08]">Research the market without the clutter.</h2>
                    <p class="mt-3 text-sm sm:text-base leading-6 sm:leading-7 text-slate-400 max-w-2xl">Move quickly between listed companies, mutual funds, financial statements, corporate events and the latest business coverage.</p>
                </div>
                <div class="flex flex-col sm:flex-row lg:flex-col gap-2.5 lg:w-48">
                    <a href="{{ route('market.stocks') }}" class="market-button w-full"><i class="fas fa-chart-line mr-2"></i>Explore stocks</a>
                    <a href="{{ route('market.news') }}" class="w-full inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/[.04] px-4 py-3 text-sm font-bold text-white hover:bg-white/[.08] transition-colors"><i class="far fa-newspaper mr-2 text-amber-400"></i>Market news</a>
                </div>
            </div>
        </section>

        <section aria-label="Market coverage" class="grid grid-cols-2 lg:grid-cols-5 gap-2.5 sm:gap-3 mb-8 sm:mb-10">
            @foreach([
                ['Stocks', $stats['total_stocks'], 'market.stocks', 'fa-chart-line', 'Equities'],
                ['Live quotes', $stats['live_quotes'], 'market.stocks', 'fa-bolt', 'Latest prices'],
                ['Mutual funds', $stats['total_mfs'], 'market.mutual-funds', 'fa-layer-group', 'AMFI schemes'],
                ['News', $stats['total_news'], 'market.news', 'fa-newspaper', 'Market stories'],
                ['Actions', $stats['total_corporate_actions'], 'market.corporate-actions', 'fa-calendar-check', 'Company events'],
            ] as $index => [$label, $value, $routeName, $icon, $note])
                <a href="{{ route($routeName) }}" class="market-panel {{ $index === 4 ? 'col-span-2 lg:col-span-1' : '' }} p-4 group hover:-translate-y-0.5 hover:border-amber-400/40 transition-all">
                    <div class="flex items-center justify-between gap-2">
                        <span class="grid h-9 w-9 place-items-center rounded-xl bg-amber-400/10 text-amber-400"><i class="fas {{ $icon }} text-sm"></i></span>
                        <i class="fas fa-arrow-right text-xs text-slate-600 group-hover:text-amber-400 transition-colors"></i>
                    </div>
                    <div class="mt-3 text-2xl sm:text-3xl font-black tracking-tight text-white">{{ number_format($value) }}</div>
                    <div class="mt-1 text-sm font-bold text-slate-200">{{ $label }}</div>
                    <div class="hidden sm:block mt-1 text-xs market-muted">{{ $note }}</div>
                </a>
            @endforeach
        </section>

        <div class="space-y-9 sm:space-y-11">
            <section aria-labelledby="latest-news-heading" class="min-w-0 w-full">
                <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-5 sm:mb-6">
                    <div>
                        <p class="market-kicker">Latest coverage</p>
                        <h2 id="latest-news-heading" class="mt-1.5 text-2xl sm:text-3xl font-black tracking-tight text-white">What's moving the market</h2>
                        <p class="mt-2 text-sm leading-6 market-muted">Complete summaries from the latest company and market updates.</p>
                    </div>
                    <a href="{{ route('market.news') }}" class="shrink-0 text-sm font-bold text-amber-400 hover:text-amber-300">View news archive <i class="fas fa-arrow-right ml-1"></i></a>
                </header>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
                    @forelse($latestNews as $story)
                        <article class="market-panel overflow-hidden flex h-full flex-col min-w-0">
                            <div class="relative aspect-[16/9] overflow-hidden bg-slate-900 border-b border-white/[.07]">
                                @if($story->thumbnail && filter_var($story->thumbnail, FILTER_VALIDATE_URL))
                                    <img src="{{ $story->thumbnail }}" alt="News image for {{ $story->title }}" loading="lazy" decoding="async" class="h-full w-full object-cover transition-transform duration-500 hover:scale-[1.025]" onerror="this.parentElement.innerHTML='<div class=&quot;grid h-full place-items-center bg-gradient-to-br from-slate-900 to-slate-950 text-slate-700&quot;><i class=&quot;far fa-newspaper text-4xl&quot;></i></div>'">
                                    <div class="absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-[#0e131d] to-transparent pointer-events-none"></div>
                                @else
                                    <div class="grid h-full place-items-center bg-gradient-to-br from-slate-900 to-slate-950 text-slate-700"><i class="far fa-newspaper text-4xl"></i></div>
                                @endif
                            </div>
                            <div class="p-5 flex flex-col flex-1">
                                <div class="flex min-h-[28px] flex-wrap items-center gap-x-3 gap-y-2 mb-3">
                                    <span class="inline-flex items-center rounded-md bg-amber-400/10 px-2.5 py-1 text-[11px] font-extrabold tracking-wide text-amber-300 ring-1 ring-inset ring-amber-400/20">{{ $story->symbol ?: 'MARKET' }}</span>
                                    <time class="text-xs market-muted" datetime="{{ optional($story->published_at)->toIso8601String() }}"><i class="far fa-clock mr-1.5 text-slate-500"></i>{{ optional($story->published_at)->format('d M Y, h:i A') ?: 'Recently' }}</time>
                                </div>
                                <h3 class="market-clamp-3 text-lg font-extrabold leading-snug text-white break-words"><a href="{{ route('market.news.show', $story) }}" class="hover:text-amber-300 transition-colors">{{ $story->title }}</a></h3>
                                @if($story->summary)
                                    <p class="market-clamp-3 mt-3 text-sm leading-6 text-slate-300 break-words">{{ \Illuminate\Support\Str::limit(preg_replace('/\s+/u', ' ', trim(strip_tags($story->summary))), 190) }}</p>
                                @else
                                    <p class="market-clamp-3 mt-3 text-sm italic leading-6 market-muted">No additional description is available for this story.</p>
                                @endif
                                <div class="mt-auto flex items-center justify-between gap-3 border-t border-white/[.06] pt-5">
                                    <span class="text-xs market-muted">Read the full report</span>
                                    <a href="{{ route('market.news.show', $story) }}" class="inline-flex items-center text-xs font-extrabold text-amber-400 hover:text-amber-300">View details <i class="fas fa-arrow-right ml-2"></i></a>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="market-panel px-5 py-14 text-center md:col-span-2 lg:col-span-3"><span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-white/5 text-slate-500"><i class="far fa-newspaper text-xl"></i></span><h3 class="mt-4 font-bold text-white">No market news yet</h3><p class="mt-1 text-sm market-muted">New stories will appear here after the next data sync.</p></div>
                    @endforelse
                </div>
            </section>

            <section aria-labelledby="upcoming-actions-heading">
                <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-5 sm:mb-6">
                    <div>
                        <p class="market-kicker">Company calendar</p>
                        <h2 id="upcoming-actions-heading" class="mt-1.5 text-2xl sm:text-3xl font-black tracking-tight text-white">Upcoming actions</h2>
                        <p class="mt-2 text-sm leading-6 market-muted">Important dividends, splits, bonuses and company events in one place.</p>
                    </div>
                    <a href="{{ route('market.corporate-actions') }}" class="shrink-0 text-sm font-bold text-amber-400 hover:text-amber-300">View full calendar <i class="fas fa-arrow-right ml-1"></i></a>
                </header>

                <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3 sm:gap-4">
                    @forelse($upcomingActions as $action)
                        <article class="market-panel p-4 sm:p-5 flex flex-col min-w-0 hover:border-amber-400/30 transition-colors">
                            <div class="flex items-start justify-between gap-3">
                                <div class="grid h-14 w-14 shrink-0 place-items-center rounded-xl border border-white/10 bg-white/[.035] text-center">
                                    @if($action->expiry_date)
                                        <span><span class="block text-[10px] font-bold uppercase tracking-wider text-amber-400">{{ $action->expiry_date->format('M') }}</span><span class="block text-xl leading-5 font-black text-white">{{ $action->expiry_date->format('d') }}</span></span>
                                    @else
                                        <span class="text-xs font-extrabold text-slate-400">TBA</span>
                                    @endif
                                </div>
                                <span class="market-chip shrink-0">{{ $action->type }}</span>
                            </div>
                            <div class="mt-5 min-w-0">
                                <h3 class="font-extrabold text-white truncate">{{ $action->symbol ?: $action->company_name }}</h3>
                                <p class="text-xs market-muted truncate mt-1" title="{{ $action->company_name }}">{{ $action->company_name }}</p>
                                <p class="mt-3 text-sm leading-6 text-slate-300 break-words">{{ $action->name }}</p>
                            </div>
                            <a href="{{ route('market.corporate-actions', ['search' => $action->symbol]) }}" class="mt-auto pt-5 text-xs font-bold text-amber-400 hover:text-amber-300">View action details <i class="fas fa-chevron-right ml-1"></i></a>
                        </article>
                    @empty
                        <div class="market-panel p-10 text-center market-muted sm:col-span-2 lg:col-span-3 xl:col-span-5">No upcoming actions are currently scheduled.</div>
                    @endforelse
                </div>

                <div class="mt-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 rounded-xl border border-white/[.07] bg-white/[.025] px-4 py-3 text-xs market-muted">
                    <span><i class="fas fa-circle-info mr-2 text-amber-400"></i>Market data is informational and may be delayed.</span>
                    <span class="sm:text-right">Last quote sync: {{ $stats['last_sync_time'] }}</span>
                </div>
            </section>
        </div>
    </main>
</div>
@endsection
