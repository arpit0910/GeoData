# Upstox capability audit

Reviewed on 17 October 2026 against the official Upstox developer documentation.

## Already integrated

- V3 LTP and full quotes for equities, bonds, NSE indices, and BSE indices.
- Daily equity/index/mutual-fund/global instrument masters.
- Mutual-fund NAV fallback.
- Company profile, balance sheet, cash flow, income statement, share holdings,
  key ratios, corporate actions, and competitors.
- Market/stock news and corporate actions.
- IPO listings/details and FII/DII institutional activity.

## Index constituents and allocation

Upstox's index instruments provide an instrument key and support quotes and
historical candles, but Upstox does not document an index-constituents endpoint.
Its `portfolio/long-term-holdings` endpoint returns the authenticated user's
Demat holdings; it is not the list of companies inside an index.

The application therefore sources index constituents from official NSE and BSE
constituent downloads during `indices:sync`. The 12 October 2026 validation run
stored 301 index price rows and constituent lists for 43 indices, including all
50 Nifty 50 constituents. The exchange constituent downloads used by the app do
not contain actual free-float weights. `weightage_percentage` is consequently an
equal-weight estimate and is explicitly marked with `weightage_is_estimate: true`;
the holdings API also returns `weightage_basis: equal_weight_estimate`. Do not
present these estimates as the amount actually invested in each constituent.

Official references:

- https://upstox.com/developer/api-documentation/instruments/
- https://upstox.com/developer/api-documentation/get-holdings/

## Useful Upstox capabilities not yet integrated

1. **V3 historical and intraday candles** — highest priority. These can replace
   or validate parts of the daily history pipeline and enable intraday charts.
   Upstox documents minute/hour history from 2022 and day/week/month history
   from 2000, subject to API limits.
2. **Market Data Feed V3 (WebSocket)** — useful when second-level updates are
   required. The one-minute REST scheduler remains simpler and sufficient for
   the current requirement.
3. **Option chain and Greeks** — useful for an F&O analytics module; not needed
   by the current stock/index pages.
4. **Remaining market-information datasets** — open interest, change in OI,
   max pain, and put-call ratio are useful additions for derivatives pages.
5. **Expired futures/options and their candles** — useful for derivatives
   backtesting and requires the Upstox Plus entitlement.
6. **Instrument Search API** — useful for on-demand lookup. The daily complete
   instrument master remains preferable for this application's bulk sync.
7. **User portfolio/trading APIs** — Demat holdings, positions, orders, funds,
   trade P&L, charges, and mutual-fund holdings/SIPs. These are account-specific
   and should only be added if SetuGeo is intended to connect end-user brokerage
   accounts; they are not public market datasets.

Official references for the integrated additions and remaining capabilities:

- https://upstox.com/developer/api-documentation/get-ipos/
- https://upstox.com/developer/api-documentation/get-ipo-details/
- https://upstox.com/developer/api-documentation/get-fii-data/
- https://upstox.com/developer/api-documentation/get-dii-data/
- https://upstox.com/developer/api-documentation/v3/get-historical-candle-data/
- https://upstox.com/developer/api-documentation/v3/get-intra-day-candle-data/
- https://upstox.com/developer/api-documentation/get-market-data-feed/
- https://upstox.com/developer/api-documentation/get-pc-option-chain/
- https://upstox.com/developer/api-documentation/expired-instruments/
- https://upstox.com/developer/api-documentation/instrument-search/

