# Cron schedule audit

## Midnight master update

`market:sync-daily-masters` starts daily at 00:00 Asia/Kolkata, in the background,
with overlap protection. It runs these steps sequentially and continues independent
steps after a failure, returning failure if any step failed:

1. `equities:sync-upstox-instruments`: stock/index master records and instrument keys.
2. `equities:sync-metadata`: stock symbols, classifications and exchange metadata.
3. `mf:sync-and-calculate`: AMFI fund master/scheme codes, NAVs, returns and nudges.
4. `mf:sync-upstox-daily`: match MF instrument metadata and latest NAVs by ISIN.
5. `market:sync-global-instruments`: global index/indicator master codes.

The former 23:30 MF, 06:10 MF provider, 06:15 stock instrument, 06:30 global
instrument and 08:00 stock metadata schedules are now consolidated here. Their
individual commands remain available for maintenance. Downloads import the latest
published source files; a midnight run cannot include data published later that day.
These public master-file downloads do not require an Upstox access token.

The host must continue invoking Laravel `schedule:run` every minute. This repository
change defines Laravel events; it does not install or verify the host task scheduler.

## Repetition and overlap findings

- MF sync still runs at 21:30 and again in the midnight workflow: intentional early
  availability followed by a final refresh. Both force imports and calculate returns;
  removing 21:30 would save work at the cost of later NAV availability.
- The midnight provider MF import overlaps AMFI NAV coverage, but also supplies
  instrument metadata and an independent source. AMFI can invoke this same provider
  as a failure fallback, so a source outage can cause a repeated provider download.
- `mf:backfill` scans the preceding 14 days at 06:45. This is historical gap repair,
  separate from the latest master/NAV import; review its cost if gaps become rare.
- Live quotes, final quote snapshots and exchange end-of-day imports overlap price
  fields but serve different purposes: live display, daily snapshots and detailed
  OHLCV/return enrichment. Keep them.
- Company profiles at 02:30 overlap the profile portion of the full company
  fundamentals batch at 02:00. The profile job selects missing profiles; the full
  batch rotates 25 companies, so the additional job accelerates initial coverage.
- `equities:sync-fundamentals` and `market:sync-company-fundamentals` overlap some
  financial metrics but use different sources and write different datasets.
- The hourly news schedule deliberately performs complete passes. It is potentially
  expensive, but detects new articles throughout the day. A duplicate dashboard
  entry claiming a separate 06:30 `--all` pass was stale and has been removed.
- `sync:mf-daily`, `mf:sync` and return-repair commands in the dashboard are manual
  or nested commands, not additional scheduled daily runs.
- Corrected the bond quote dashboard time from 18:00 to its actual 18:10 schedule.

Upstox authenticated jobs retain the active-token guards. No price, news or
fundamentals fetch was added to the midnight master workflow.
