@once
<style>
    .market-shell{background:radial-gradient(circle at 85% 0,rgba(245,158,11,.08),transparent 28rem),#070a10;color:#e5e7eb;min-height:calc(100vh - 80px)}
    .market-wrap{max-width:1280px;margin:0 auto;padding:0 1rem}
    .market-panel{background:#0e131d;border:1px solid #202938;border-radius:18px}
    .market-muted{color:#94a3b8}.market-accent{color:#fbbf24}
    .market-input{background:#0a0f17!important;border:1px solid #293445!important;border-radius:12px!important;color:#fff!important;padding:.72rem .9rem!important}
    .market-input:focus{outline:none!important;border-color:#d97706!important;box-shadow:0 0 0 3px rgba(217,119,6,.12)}
    .market-table{width:100%;border-collapse:collapse;min-width:760px}.market-table th{color:#94a3b8;font-size:.69rem;letter-spacing:.08em;text-transform:uppercase;text-align:left;background:#0a0f17}.market-table th,.market-table td{padding:.85rem 1rem;border-bottom:1px solid #1d2633}.market-table tr:last-child td{border-bottom:0}.market-table tbody tr:hover{background:#121925}
    .market-chip{display:inline-flex;align-items:center;padding:.25rem .55rem;border-radius:999px;background:#192231;color:#cbd5e1;font-size:.7rem;font-weight:700}
    .market-button{display:inline-flex;align-items:center;justify-content:center;border-radius:11px;background:#f59e0b;color:#111827;font-weight:800;padding:.72rem 1rem;transition:.15s}.market-button:hover{background:#fbbf24}
    .market-kicker{font-size:.7rem;line-height:1rem;text-transform:uppercase;letter-spacing:.16em;color:#fbbf24;font-weight:800}
    .market-grid-line{background-image:linear-gradient(rgba(255,255,255,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.025) 1px,transparent 1px);background-size:32px 32px}
    .market-nav-scroll{scrollbar-width:none;-ms-overflow-style:none}.market-nav-scroll::-webkit-scrollbar{display:none}
    .market-pagination nav>div:first-child{display:none}.market-pagination nav>div:last-child{display:flex!important;align-items:center;justify-content:space-between;gap:1rem}.market-pagination nav span,.market-pagination nav a{border-color:#293445!important;background:#0e131d!important;color:#cbd5e1!important}.market-pagination nav a:hover{background:#192231!important;color:#fff!important}
    @media(max-width:640px){.market-wrap{padding:0 .85rem}.market-pagination nav>div:last-child>div:first-child{display:none}.market-table th,.market-table td{padding:.75rem}}
</style>
@endonce

@php
    $marketLinks = [
        ['market.index', 'Overview'],
        ['market.stocks', 'Stocks'],
        ['market.mutual-funds', 'Mutual funds'],
        ['market.news', 'News'],
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
                    <a href="{{ route($routeName) }}"
                       class="shrink-0 whitespace-nowrap border-b-2 py-3.5 text-sm font-semibold transition-colors {{ request()->routeIs($routeName) || ($routeName === 'market.fundamentals' && request()->routeIs('market.fundamentals.*')) ? 'border-amber-400 text-amber-400' : 'border-transparent text-slate-400 hover:border-slate-600 hover:text-white' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
        </div>
    </div>
</div>
