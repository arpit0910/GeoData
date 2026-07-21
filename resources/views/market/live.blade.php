<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Stock and Indices Tracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pusher/8.4.0/pusher.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/laravel-echo/1.16.1/echo.iife.js"></script>
    <style>
        body {
            background:
                radial-gradient(circle at top left, rgba(59, 130, 246, 0.18), transparent 34%),
                radial-gradient(circle at top right, rgba(16, 185, 129, 0.14), transparent 28%),
                linear-gradient(180deg, #07111f 0%, #0f172a 50%, #111827 100%);
        }

        .ticker-card {
            transition: transform 180ms ease, border-color 180ms ease, background-color 180ms ease;
        }

        .ticker-card.flash-up {
            border-color: rgba(34, 197, 94, 0.7);
            background-color: rgba(20, 83, 45, 0.5);
            transform: translateY(-2px);
        }

        .ticker-card.flash-down {
            border-color: rgba(239, 68, 68, 0.7);
            background-color: rgba(127, 29, 29, 0.4);
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="min-h-screen text-slate-100">
    @php
        $symbols = ['AAPL', 'MSFT', 'TSLA', 'AMZN', 'GOOGL', '^GSPC', '^DJI', '^IXIC'];
        $reverbHost = env('REVERB_HOST', parse_url(config('app.url'), PHP_URL_HOST) ?: '127.0.0.1');
        $reverbScheme = env('REVERB_SCHEME', 'http');
        $reverbPort = (int) env('REVERB_PORT', 8080);
    @endphp

    <div class="mx-auto max-w-7xl px-6 py-10">
        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm uppercase tracking-[0.35em] text-cyan-300/80">Realtime Market Feed</p>
                <h1 class="mt-3 text-4xl font-black tracking-tight">Live Stock and Indices Tracker</h1>
                <p class="mt-3 max-w-3xl text-sm text-slate-300">
                    Yahoo Finance powers the latest price snapshots while Laravel broadcasting pushes live updates into this screen.
                </p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-200 shadow-2xl backdrop-blur">
                <div class="font-semibold">WebSocket Channel</div>
                <div class="mt-1 text-slate-400">Public channel: <span class="text-white">market-data</span></div>
                <div id="connection-status" class="mt-2 text-amber-300">Connecting...</div>
            </div>
        </div>

        <div class="mb-6 rounded-3xl border border-cyan-400/20 bg-slate-900/60 p-5 shadow-2xl backdrop-blur">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-400">Tracked Symbols</div>
                    <div class="mt-2 text-sm text-slate-300">{{ implode(', ', $symbols) }}</div>
                </div>
                <button
                    id="refresh-all"
                    class="inline-flex items-center justify-center rounded-2xl border border-cyan-400/40 bg-cyan-400/10 px-4 py-2 text-sm font-semibold text-cyan-100 transition hover:bg-cyan-400/20"
                >
                    Refresh Snapshot
                </button>
            </div>
        </div>

        <div id="ticker-grid" class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($symbols as $symbol)
                <article
                    id="card-{{ md5($symbol) }}"
                    data-symbol="{{ $symbol }}"
                    class="ticker-card rounded-3xl border border-white/10 bg-slate-900/70 p-5 shadow-2xl backdrop-blur"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-400">Symbol</div>
                            <h2 class="mt-2 text-2xl font-black text-white">{{ $symbol }}</h2>
                        </div>
                        <span class="rounded-full border border-white/10 bg-white/5 px-2.5 py-1 text-[11px] font-semibold text-slate-300">
                            Waiting
                        </span>
                    </div>

                    <div class="mt-6">
                        <div class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Last Price</div>
                        <div class="mt-2 text-3xl font-black text-white" data-role="price">--</div>
                    </div>

                    <div class="mt-6 grid grid-cols-2 gap-4">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
                            <div class="text-[11px] uppercase tracking-[0.2em] text-slate-500">Change</div>
                            <div class="mt-2 text-lg font-bold text-slate-200" data-role="change">--</div>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
                            <div class="text-[11px] uppercase tracking-[0.2em] text-slate-500">% Change</div>
                            <div class="mt-2 text-lg font-bold text-slate-200" data-role="percent">--</div>
                        </div>
                    </div>

                    <div class="mt-5 text-xs text-slate-400">
                        <span data-role="meta">Waiting for first update...</span>
                    </div>
                </article>
            @endforeach
        </div>
    </div>

    <script>
        window.marketTrackerConfig = {
            symbols: @json($symbols),
            snapshotUrl: @json(url('/api/v1/market/stocks?symbols='.implode(',', $symbols))),
            reverb: {
                key: @json(env('REVERB_APP_KEY', env('PUSHER_APP_KEY', 'app-key'))),
                host: @json($reverbHost),
                port: @json($reverbPort),
                scheme: @json($reverbScheme),
            },
        };

        const connectionStatus = document.getElementById('connection-status');
        const refreshButton = document.getElementById('refresh-all');

        function formatSignedNumber(value, suffix = '') {
            if (value === null || value === undefined || value === '') {
                return '--';
            }

            const numericValue = Number(value);
            const sign = numericValue > 0 ? '+' : '';

            return `${sign}${numericValue.toFixed(2)}${suffix}`;
        }

        function updateCard(symbol, payload) {
            const card = document.querySelector(`[data-symbol="${CSS.escape(symbol)}"]`);

            if (!card) {
                return;
            }

            const priceEl = card.querySelector('[data-role="price"]');
            const changeEl = card.querySelector('[data-role="change"]');
            const percentEl = card.querySelector('[data-role="percent"]');
            const metaEl = card.querySelector('[data-role="meta"]');
            const badgeEl = card.querySelector('span');
            const change = payload.d;
            const isPositive = Number(change) > 0;
            const isNegative = Number(change) < 0;

            priceEl.textContent = payload.price !== null ? Number(payload.price).toFixed(2) : '--';
            changeEl.textContent = formatSignedNumber(payload.d);
            percentEl.textContent = formatSignedNumber(payload.dp, '%');
            metaEl.textContent = `${payload.exchange || 'Unknown exchange'} - ${payload.source || 'unknown'} - ${payload.fetched_at || ''}`;
            badgeEl.textContent = payload.currency || 'LIVE';

            [changeEl, percentEl].forEach((element) => {
                element.classList.remove('text-emerald-400', 'text-rose-400', 'text-slate-200');

                if (isPositive) {
                    element.classList.add('text-emerald-400');
                } else if (isNegative) {
                    element.classList.add('text-rose-400');
                } else {
                    element.classList.add('text-slate-200');
                }
            });

            card.classList.remove('flash-up', 'flash-down');

            if (isPositive) {
                card.classList.add('flash-up');
            } else if (isNegative) {
                card.classList.add('flash-down');
            }

            window.setTimeout(() => {
                card.classList.remove('flash-up', 'flash-down');
            }, 1200);
        }

        async function loadInitialSnapshot() {
            connectionStatus.textContent = 'Loading initial snapshot...';

            try {
                const response = await axios.get(window.marketTrackerConfig.snapshotUrl);
                const data = response.data?.data || {};

                Object.entries(data).forEach(([symbol, payload]) => updateCard(symbol, payload));

                connectionStatus.textContent = 'Snapshot loaded. Listening for live updates...';
                connectionStatus.className = 'mt-2 text-emerald-300';
            } catch (error) {
                connectionStatus.textContent = 'Initial snapshot failed. Waiting for live updates...';
                connectionStatus.className = 'mt-2 text-rose-300';
                console.error('Market snapshot fetch failed:', error);
            }
        }

        function ensureEcho() {
            if (window.__marketEchoInstance && typeof window.__marketEchoInstance.channel === 'function') {
                return window.__marketEchoInstance;
            }

            const EchoClass = typeof window.Echo === 'function'
                ? window.Echo
                : (window.EchoConstructor || window.LaravelEcho || null);

            const PusherClass = window.Pusher || window.pusher || null;

            if (! EchoClass || ! PusherClass) {
                return null;
            }

            window.Pusher = PusherClass;
            window.__marketEchoInstance = new EchoClass({
                broadcaster: 'pusher',
                key: window.marketTrackerConfig.reverb.key,
                wsHost: window.marketTrackerConfig.reverb.host,
                wsPort: window.marketTrackerConfig.reverb.port,
                wssPort: window.marketTrackerConfig.reverb.port,
                forceTLS: window.marketTrackerConfig.reverb.scheme === 'https',
                enabledTransports: ['ws', 'wss'],
                disableStats: true,
                cluster: 'mt1',
            });

            return window.__marketEchoInstance;
        }

        function connectRealtimeFeed() {
            const echo = ensureEcho();

            if (! echo) {
                connectionStatus.textContent = 'Echo/Reverb is not bootstrapped yet.';
                connectionStatus.className = 'mt-2 text-amber-300';
                return;
            }

            connectionStatus.textContent = 'Connected to live market-data channel.';
            connectionStatus.className = 'mt-2 text-emerald-300';

            echo.channel('market-data')
                .listen('.StockPriceUpdated', (event) => {
                    updateCard(event.symbol, event.data || {});
                });
        }

        refreshButton.addEventListener('click', loadInitialSnapshot);

        loadInitialSnapshot().finally(connectRealtimeFeed);
    </script>
</body>
</html>
