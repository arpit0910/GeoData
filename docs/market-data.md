# Latest equity quotes

`market:sync-upstox-quotes` reads every active mapped record from `equities`,
selects the NSE Upstox instrument key when available (otherwise BSE), and saves
one latest snapshot per ISIN in `equity_quotes`. Every run processes the full
eligible stock universe. It splits that universe into requests of up to 500
instruments because 500 is the Upstox full-quote API per-request limit. The
existing daily `equity_prices` history is separate.

## Setup

Set the current daily Upstox access token in the deployment environment. Never
commit the token. If configuration is cached, rebuild it after updating the
token. If PHP reports cURL error 60, set `MARKET_DATA_CA_BUNDLE` to a trusted
CA bundle. TLS certificate verification stays enabled.

```sh
php artisan migrate --force
php artisan equities:sync-upstox-instruments /secure/path/complete.json
php artisan market:sync-upstox-quotes --isin=INE002A01018
```

The equity master must contain active equities and Upstox instrument keys
before fetching. A normal run processes all mapped equities:
`php artisan market:sync-upstox-quotes`. Use `--batch-size=100` only when a
smaller per-request batch is needed; it does not limit the total stocks synced.

## Scheduler

The Laravel schedule runs every 5 minutes on weekdays from 09:15 through 16:00
Asia/Kolkata. Each scheduled run processes all mapped stocks in batches of 500,
prevents overlapping runs, runs in the background, and records success/failure
in the existing cron logs. If one provider batch fails, later batches are still
attempted and the command returns a failure status for the partial cycle.
Holidays may return the previous session's quote.

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

`GET /api/v1/market/equity/INE002A01018` returns the saved preferred-exchange
quote without calling Upstox. Invalid ISIN format returns 422; no saved quotes
returns 404. Each quote includes `isin`, `exchange`, `symbol`, `price`,
`previous_close`, `d`, `dp`, `currency`, `quoted_at`, `fetched_at`,
`age_seconds`, and `is_stale` (quote older than 900 seconds).

Times are stored in UTC. Provider failures retain the previous snapshot;
older provider timestamps cannot overwrite newer saved quotes. Broadcast
failures do not undo database saves. The command returns a failure exit code
for failed quotes, unmatched requested symbols/ISINs, or an empty universe.

Upstox V3 full market quotes are the scheduled equity source. Quote time and
fetch time are distinct. Provider instrument tokens are removed before quote
payloads are stored or broadcast and are not returned by public APIs.

## Verification

```sh
php vendor/phpunit/phpunit/phpunit tests/Feature/Api/MarketDataTest.php
php vendor/phpunit/phpunit/phpunit tests/Feature/Api/UpstoxQuoteSyncTest.php
php artisan schedule:list
```
