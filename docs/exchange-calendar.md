# NSE/BSE exchange calendar

This module stores equity-market holidays and Muhurat trading sessions from the
official NSE and BSE calendar pages. Matching NSE/BSE dates are stored once as a
combined calendar record. Every Saturday and Sunday is also persisted, so the
database contains the complete non-trading calendar rather than calculating
weekends only at request time.

## Setup and synchronization

```sh
php artisan migrate --force
php artisan exchange-calendar:sync
```

By default, the command syncs NSE and BSE for the current and next calendar
years. It is safe to run repeatedly. A successful provider response updates
changed events and removes dates that the same provider has withdrawn. A failed
fetch leaves existing data untouched. An empty next-year result is treated as
not-yet-published and also leaves any stored records untouched.

Target individual calendars when required:

```sh
php artisan exchange-calendar:sync --exchange=NSE --year=2026
php artisan exchange-calendar:sync --exchange=BSE --year=2026
```

The scheduler runs the full command every day at 06:00 Asia/Kolkata. The URLs
can be overridden with `NSE_HOLIDAY_URL` and `BSE_HOLIDAY_URL`. TLS verification
is enabled by default; configure the host trust store instead of disabling it.

## API

These endpoints use the same authentication, subscription and credit middleware
as the existing stocks/mutual-funds APIs.

```text
GET /api/v1/market-calendar/holidays?exchange=nse&year=2026
GET /api/v1/market-calendar/holidays?exchange=all&from=2026-10-01&to=2026-12-31&type=holiday
GET /api/v1/market-calendar/holidays?exchange=all&year=2026&type=weekend
GET /api/v1/market-calendar/check?date=2026-11-08&exchange=all
```

`exchange` accepts `nse`, `bse`, or `all`; combined rows are returned for either
individual exchange filter. The check endpoint reports weekend,
official holiday, special-session, trading-day, and status fields independently.
Muhurat dates return `is_exchange_holiday=true`, `status=muhurat`, and
`is_trading_day=true`; session times
remain null until an exchange publishes them in its calendar source.
For a weekday in a year that has not been synchronized, the endpoint returns
`calendar_available=false`, `status=unknown`, and `is_trading_day=null` rather
than incorrectly assuming that the exchange is open.

## Admin CRUD

Authenticated administrators can manage records at `/admin/exchange-calendar`.
The screen supports search and filters, individual detail views, creation,
editing, and deletion. Creating or editing a record marks it as a manual
override, which prevents scheduled provider synchronization from overwriting
that record. Deleting a provider-controlled record removes it immediately, but
a later successful provider sync can recreate it if it remains in the official
exchange calendar.
