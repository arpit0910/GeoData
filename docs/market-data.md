# Latest equity quotes

`market:fetch-live` reads active records from `equities`, maps `nse_symbol` to
Yahoo `.NS` symbols and `bse_symbol` to `.BO` symbols, and saves one latest
snapshot per ISIN and exchange in `equity_quotes`. Verify that the master uses
Yahoo-compatible exchange symbols. Missing/incorrect mappings fail visibly.
The existing daily `equity_prices` history is separate.

## Setup

If PHP reports cURL error 60, set `MARKET_DATA_CA_BUNDLE` to a trusted CA
bundle and run `php artisan config:clear`. On this XAMPP installation the
local `.env` uses `D:/xampp/apache/bin/curl-ca-bundle.crt`. TLS certificate
verification stays enabled.

```sh
php artisan migrate --force
php artisan equities:sync-metadata
php artisan market:fetch-live --isin=INE002A01018
```

The equity master must contain active equities and exchange symbols before
fetching. To fetch all active equities, run `php artisan market:fetch-live`.
Optional Yahoo symbols must map to the master, for example:
`php artisan market:fetch-live RELIANCE.NS`.

## Scheduler

The Laravel schedule runs every 15 minutes on weekdays from 09:15 through
16:00 Asia/Kolkata. It prevents overlapping runs, runs in the background,
and records success/failure in the existing cron logs. Holidays may return
the previous session's quote. Large universes are fetched in provider batches;
overlapping cycles are skipped. Bulk scheduled/UI runs allow partial provider
coverage and report saved/failed counts. Targeted command runs remain strict.

On Linux, add this crontab entry (replace the deployment path):

```cron
* * * * * cd /path/to/GeoData && php artisan schedule:run >> storage/logs/scheduler.log 2>&1
```

On this Windows/XAMPP workstation, create a Task Scheduler task repeating
every minute, with program `D:\xampp\php\php.exe`, arguments
`artisan schedule:run`, and Start in `D:\xampp\htdocs\GeoData`.
Use an account with access to the project/database and select “Do not start
a new instance” if already running. The Laravel scheduler starts all the
project's scheduled jobs. Do not add a duplicate task if already configured.

## API

`GET /api/v1/market/equity/INE002A01018` returns the saved NSE/BSE quotes
without calling Yahoo. Invalid ISIN format returns 422; no saved quotes
returns 404. Each quote includes `isin`, `exchange`, `symbol`, `price`,
`previous_close`, `d`, `dp`, `currency`, `quoted_at`, `fetched_at`,
`age_seconds`, and `is_stale` (quote older than 900 seconds).

Times are stored in UTC. Provider failures retain the previous snapshot;
older provider timestamps cannot overwrite newer saved quotes. Broadcast
failures do not undo database saves. The command returns a failure exit code
for failed quotes, unmatched requested symbols/ISINs, or an empty universe.

Yahoo is the existing data source; this integration does not guarantee a
maximum 15-minute delay or provider availability. Quote time and fetch time
are distinct. The original symbol/index API endpoints remain available.

## Verification

```sh
php vendor/phpunit/phpunit/phpunit tests/Feature/Api/MarketDataTest.php
php artisan schedule:list
```
