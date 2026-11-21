# Technology news operations

## Hourly bulk discovery

Set `TECH_NEWS_API_KEY` to a NewsAPI key that permits production use, refresh
Laravel's configuration cache, and run `php artisan news:install`. The existing
hourly `news:sync` job then includes **NewsAPI Technology Discovery**. The
development plan has a 24-hour delay and is only suitable for local testing.

Each run requests up to 10 pages of 100 results by default. Adjust
`TECH_NEWS_API_MAX_PAGES` to match the provider quota and observed volume.
The search overlaps the last successful run by 30 minutes, with a maximum
one-day recovery window. The first run looks back 90 minutes. Sync logs flag
runs that reach the page cap. Canonical URLs are deduplicated across runs and
providers. The daily count depends on available matches and repeated URLs.

Bulk records retain publisher, headline, author, publication time and link
for internal editorial discovery. Public reports continue through the existing
licensed full-text pipeline.

## AI releases and defensive cybersecurity

`php artisan news:install` installs four additional feeds and preserves existing
source settings. The regular hourly `news:sync` job fetches them.

| Source | What GeoData imports |
| --- | --- |
| [OpenAI Codex Releases](https://github.com/openai/codex/releases.atom) | Official release titles, timestamps and links |
| [Anthropic Claude Code Releases](https://github.com/anthropics/claude-code/releases.atom) | Official release titles, timestamps and links |
| [NIST Cybersecurity Insights](https://www.nist.gov/blogs/cybersecurity-insights/rss.xml) | Defensive cybersecurity headlines and links |
| [Wikimedia Diff Technology](https://diff.wikimedia.org/category/technology/feed/) | Open-source technology reporting for the verified rewrite pipeline under CC BY-SA 4.0 |

Codex, Claude Code and NIST entries are available for editorial monitoring in
admin. Their source text is excluded from rewriting and public full reports.
Atom feeds that supply only an update timestamp use that timestamp for sorting.

## Additional reviewed sources (17 November 2026)

Three independent publishers have been added to the default source installer, expanding the permitted defaults from two to five:

| Source | Coverage | Feed | Reuse basis | Full-text field |
| --- | --- | --- | --- | --- |
| Fedora Magazine | Linux, desktop software, development and infrastructure | https://fedoramagazine.org/feed/ | CC BY-SA 4.0, https://fedoramagazine.org/terms-and-conditions/ | content:encoded |
| Electronic Frontier Foundation | Security, privacy, AI policy and digital rights | https://www.eff.org/rss/updates.xml | CC BY 4.0 for original material, https://www.eff.org/copyright | description |
| The Document Foundation | LibreOffice, office software, interoperability and open source | https://blog.documentfoundation.org/feed/ | CC BY-SA 3.0, copyright section at https://blog.documentfoundation.org/ | content:encoded |

These are publisher-authored releases, analysis and project reporting, not fabricated records or a claim of comprehensive independent gadget/startup coverage. They are supplied free, without extra API keys. The policies exclude separately restricted third-party material; source licences, original authors, original links and adaptation notices are retained, and images/logos are not imported. Fedora's required “By [author] from Fedora Magazine” attribution is rendered on its reports. ShareAlike adaptations explicitly retain their source licence. Existing NASA/Mozilla source licences are also stored explicitly.

EFF places full text in RSS description; the new `feed_content_field` setting makes that explicit. Description is never automatically treated as a complete article for other feeds. New `license_name` / `license_url` fields support licence-specific attribution on local reports and in the public API. Administrators can adjust these fields under Sources without editing code.

Deploy the changes, then run:

```sh
php artisan migrate --path=database/migrations/2026_11_17_000005_add_feed_format_and_licensing_to_news_sources.php --force
php artisan news:install
php artisan config:cache
php artisan news:sync --force
```

New sources are installed active with verified full-feed adaptation permissions. Existing source settings are preserved, including deliberate deactivation. No new environment variables are required. `news:install` now reports how many new sources it created, making an unchanged/inactive installation easier to diagnose.

Verification: all three live feeds returned HTTP 200 and imported **50 real full-text articles** (10 Fedora, 30 EFF, 10 TDF). Repeating every source sync created **zero duplicates**. **94 tests / 552 assertions passed**, including explicit full-description parsing, permission gating, installer idempotence/preservation, author and licence attribution, and existing market-news regressions. These imported stories enter the existing AI verification/auto-publication pipeline; import is not a claim that all 50 have already been approved or published.

## Full reading on SetuGeo and simplified administration

All primary article links open the full verified report on SetuGeo; the reader route returns HTTP 200 and never redirects to the publisher. The article footer no longer contains a “Read original” call to action. Small source/licence references remain for attribution and open in a new tab; local related-story and browse links keep the main reading flow on SetuGeo. A report must have approved full source text, verified AI content and rewrite version 2 or later before it is public. Legacy summary-only drafts and sources without full-text permission are not published as full news.

The admin articles page now mirrors Market News: Fetch News / Process Pending / View Public News, six compact status counters, a search/status filter, and a source-title versus regenerated-title table with View Details, Generate/Regenerate and Unpublish/Publish actions. Source forms, full sync tables and duplicate review are removed from this daily-work screen and have separate links/pages:

* `/admin/technology-news/{id}` — original and generated full articles side by side, retry/errors, category and feature settings.
* `/admin/technology-news/sources` — source permissions and configuration.
* `/admin/technology-news/sync-logs` — paginated fetch diagnostics.
* `/admin/technology-news/duplicates` — potential duplicate review.

Per-story generation uses `POST /admin/technology-news/{id}/regenerate`, the same verified pipeline as the cron, with existing admin/CSRF protection and a three-per-minute limit. Processing locks prevent concurrent regeneration, provider quota cooldowns remain respected, and hidden stories stay hidden. Normal Fetch News and Process Pending actions reuse the existing cron-page execution/progress component, so administrators do not need to understand queue configuration for those buttons.

Latest verification: **91 tests, 516 assertions passed**. Tests explicitly check no Location redirect header on public article responses, summary-only exclusion, admin list/detail/source/log/duplicate rendering, status filters, generation authorization, auto-publication and unpublishing. PHP syntax, Blade compilation and diff checks passed; the UI detector returned no findings for the changed pages. Visual screenshot verification remains outstanding following the earlier computer-use policy stop.

## Market-news presentation parity

Technology News now follows the same SetuGeo Market News presentation: shared market CSS, the same responsive preview-card grid, category/date metadata, a short preview and **View details** link, followed by a local article page at `/technology-news/{slug}`. The detail page uses the market-news reading width, typography, paragraph layout and back navigation, while retaining original-source attribution and licence links. Unlicensed imagery is not imported; the listing uses the same news-icon fallback as Market News. Market pages retain their existing appearance because their unchanged CSS was extracted into `website/partials/market-styles.blade.php` and reused by both sections.

Rewrite version 2 produces three independent fields: `ai_title`, `ai_summary` (card preview), and `ai_content` (complete report). Longer permitted source texts produce multi-paragraph reports; short sources remain brief. The same numerical checks, model retries/quota cooldowns and independent factual verification gate automatic publication. RSS/Atom full article text is imported only when both AI adaptation permission and the new `news_sources.allow_feed_content` permission are enabled. Sources without permission continue using permitted metadata/excerpts; publisher pages are never scraped. Body changes participate in the source hash and requeue stale rewrites.

Generation uses Market News's numbered-paragraph structured-output pattern, joining the verified paragraphs into the stored article body. Correctable length/fact-check failures receive a bounded replacement-draft attempt using verifier feedback. Unverified drafts are never substituted for successful reports to fill the feed.

Parity verification: **87 tests, 474 assertions passed**, including existing market-news regressions. A live full-report generation produced a verified 472-word, six-paragraph technology report with a separate preview; both listing and local detail returned HTTP 200. The latest migration was applied, changed PHP syntax checks passed, and Blade templates compiled successfully. The UI detector reported only the pre-existing decorative grid helper moved unchanged into the shared market stylesheet; Technology News does not use that helper. Visual browser inspection remains unavailable because computer use was previously stopped by policy.

Migration `2026_11_17_000004_add_feed_content_permission_to_news_sources.php` adds this permission and enables it only for the reviewed NASA/Mozilla feed URLs. New/custom feeds default to false; admins must explicitly confirm suitable rights before enabling it. Source licence rules still apply to adaptations, including CC BY-SA for Mozilla text. Short detail pages remain `noindex, follow`. Public API metadata now includes `article_url` for the local report alongside `original_url`.

## Current AI publication workflow

Technology news now follows **fetch → pending AI rewrite → independent factual verification → automatic approval/publication**. The public page and API show `ai_title` and `ai_summary` only when editorial_status is published, rewritten_at is present, the rewrite_source_hash matches current source input, and source/category permissions and editorial visibility still allow publication. Raw pending/failed/stale drafts do not appear publicly. Admin-hidden stories stay hidden after auto-approval.

The Cron Jobs page exposes both jobs with Run controls and existing execution logs:

* `news:sync`: `0 * * * *` in UTC; fetches active permitted providers, then calls `news:rewrite` even if an individual provider fails.
* `news:rewrite`: `*/5 * * * *` in UTC; drains up to 20 pending/eligible failed stories, auto-publishing only verified results. Flags: `--limit=20`, `--source=ID`, `--id=ID` (repeatable). Per-article atomic claims protect overlapping cron/manual workers, and interrupted claims are recovered after 30 minutes.

Manual source sync jobs use the same fetch-then-rewrite flow. The public filter controls now have accessible focus, responsive category navigation, date disclosure, search progress, clear-filter and empty states. Admin shows generated copy, rewrite status, errors/retry times and buttons for both cron commands.

Apply `2026_11_17_000003_enable_technology_news_rewrite_workflow.php` after the first two migrations. It adds `news_sources.allow_ai_rewrite`, enabled only for the reviewed NASA and Mozilla default feed URLs. Additional sources require explicit adaptation permission and administrator confirmation. The importer retains a permitted short source excerpt for AI input when this permission is enabled; it never scrapes full publisher pages. The generated summary length is bounded by available facts. Attribution and source licensing links remain visible; Mozilla adaptations retain the applicable CC BY-SA licence.

AI uses the existing `services.nvidia` credentials/models/endpoint and the market-news implementation's shared model rotation, quota cooldowns, retries, structured JSON parser and numerical checks. A second model request verifies factual fidelity before auto-approval. Missing credentials, invalid output, unsupported numbers, failed verification or quota errors remain unpublished, with a persisted retry time (provider Retry-After for quota errors, otherwise 30 minutes). `NVIDIA_API_KEY` must already be configured. No credentials are returned in public APIs or logs. Source metadata changes requeue the article and invalidate its previous approval while preserving the draft for admin comparison.

Testing must use an isolated cache so a live provider cooldown cannot affect mocked market-news tests. In PowerShell set `$env:CACHE_DRIVER = 'array'` before the PHPUnit command; in a Unix shell prefix the command with `CACHE_DRIVER=array`. Live smoke tests successfully produced 13 verified, auto-approved stories; validation/quota failures stayed unpublished for retry. HTTP smoke checks returned 200 for the public page/API. Browser visual verification was stopped by the computer-use policy because the current browser URL could not be identified with sufficient confidence; no browser-policy workaround was attempted.

Workflow regression verification: **83 tests, 445 assertions passed**, covering technology ingestion/rewrite, the existing market-news editorial/API/admin flows and cron execution logging. Changed PHP files passed syntax checks, Blade views compiled successfully, and the UI detector returned no findings. The latest rewrite-specific rerun passed all 8 tests / 55 assertions.

Run `php artisan news:sync --force` for a full cycle or `php artisan news:rewrite --limit=2` for a small AI smoke test. Use `news:health` to monitor stalled rewrite backlog as well as fetch/queue health. **The application schedules are configured and verified, but the previously cancelled Windows SYSTEM tasks remain uninstalled; an active OS Laravel scheduler is still necessary for unattended execution.** No host task was enabled by this change.

## Architecture and scope

Laravel 9 / Blade / Eloquent, existing MySQL connection (SQLite in tests), existing public/admin layouts, Sanctum/session authentication, cache, Laravel scheduler, and CronExecutionLogger. The stock-specific MarketNews model transforms source branding and URLs and participates in an AI rewriting workflow; it is unsuitable for third-party attributed technology headlines. This module keeps that workflow unchanged. No new framework or paid API is used.

Storage: news_sources, news_categories, news_articles, news_article_references, news_duplicate_candidates, news_sync_logs, news_sync_locks, and news_jobs. All module timestamps are UTC even though the application timezone is Asia/Kolkata. Public dates display IST; date filters explicitly use UTC. Indexes cover publication, categories, canonical URL hashes, source IDs, and stable slugs.

Sync stages: fetch, validate, normalize, enforce display permission, canonical URL/provider-ID deduplication, rules classification, transactional storage. Equal/similar headlines within 24 hours become review candidates; they are never merged automatically. Provider metadata and local category/status/feature overrides remain separate. Full feed text is imported only with explicit source permission and is never exposed as raw public content; the AI-generated report is published after verification. Images are not imported. No scraping or browser-triggered synchronization occurs.

## Provider review (17 November 2026)

Enabled defaults:

* **NASA Technology** — https://www.nasa.gov/technology/feed/ — headline and direct link, NASA attribution, no implication of endorsement, no imagery or third-party article reproduction. Basis: https://www.nasa.gov/nasa-brand-center/images-and-media/ and NASA's offered RSS feeds at https://www.nasa.gov/rss-feeds/.
* **Mozilla Blog** — https://blog.mozilla.org/en/feed/ — attributed headline and original link; source text remains CC BY-SA 3.0 or later unless otherwise marked. No images, logos, or excerpts. Basis: https://www.mozilla.org/en-US/foundation/licensing/website-content/. Public cards link the licensing policy. Do not assume the licence covers separately marked third-party content.

Implemented but disabled defaults:

* Hacker News: top + new IDs, individual story metadata, publisher domain, source score. Never imports discussion text or treats submitters as article authors. https://github.com/HackerNews/API documents free public access; the repository licence alone is not evidence of publisher republication rights. Obtain a suitable metadata-use basis before enabling.
* DEV: first 30 recent articles, canonical links, authors, tags, publication dates. Public API docs: https://developers.forem.com/api/v1. https://dev.to/terms restricts public display; obtain explicit permission.
* BBC News: https://feeds.bbci.co.uk/news/technology/rss.xml. Business usage requires permission: https://www.bbc.com/usingthebbc/terms/.
* TechCrunch: https://techcrunch.com/feed/. No suitable syndication permission verified: https://techcrunch.com/terms-of-service/.
* The Verge: https://www.theverge.com/rss/index.xml. No suitable syndication permission verified: https://www.voxmedia.com/legal/terms-of-use.

Wikinews was investigated but its NewsFeed endpoint returned 404, so it is not an enabled/default source. ScienceDaily was considered and excluded because its RSS terms restrict database storage. A local disabled Wikinews source and failed diagnostic logs may remain as accurate operational history.

An admin must record permission evidence and confirm it when activating or changing a source's display mode. Free access does not imply republication rights. Review terms periodically and deactivate immediately if permission changes. A source's permissions apply to storage as well as display: disabled/unverified sources are never fetched. Changing excerpt mode to headline mode suppresses existing excerpts immediately in public responses; it does not grant new rights. Public APIs must remain free for these attributed metadata responses.

## Deployment

Requirements: PHP extensions curl, openssl, mbstring, SimpleXML, libxml, PDO; writable storage/bootstrap cache; HTTPS egress and DNS; PHP with a trusted CA bundle. Free feed access needs no news-provider API key, but AI rewriting and publication require the existing NVIDIA_API_KEY used for Market News.

### Production troubleshooting

Technology News reuses all existing NVIDIA_* settings from Market News. The only module-specific environment settings are NEWS_USER_AGENT (optional, has a default) and NEWS_CA_BUNDLE (optional when PHP's normal CA trust works). Example:

```dotenv
APP_URL=https://your-production-domain.example
NVIDIA_API_KEY=your-existing-nvidia-key
NEWS_USER_AGENT="SetuGeo-News/1.0 (+https://your-production-domain.example)"
NEWS_CA_BUNDLE=
```

Do not replace an existing NVIDIA_API_KEY with this placeholder. Do not deploy the local `D:/xampp/.../news-ca.pem` value to Linux. If a CA file is needed, set NEWS_CA_BUNDLE to a readable, trusted CA bundle on the production server. NVIDIA_CA_BUNDLE may also be set for the AI endpoint; its value must resolve on that server. Never disable certificate verification.

Apply all five technology-news migrations listed below, then run `php artisan news:install` and `php artisan config:cache`. Existing source rows are preserved by the installer; use the Sources screen to check active state, verified permissions, AI adaptations and full-feed-content permission. A source must actually return full article text for AI generation to proceed.

Run `php artisan news:sync --force` and `php artisan news:health` directly in production to distinguish a command failure from a scheduler failure. CLI sync performs rewriting inline; QUEUE_CONNECTION and a worker are not prerequisites for this command. The named technology-news queue worker is needed only for queued source-sync requests. The PHP CLI must have curl, openssl, mbstring, SimpleXML and the PDO driver, and direct outbound HTTPS/DNS/public IPv4 access. News fetching refuses redirects and proxies and caps responses at 2 MiB; environment variables cannot bypass these validation rules.

Output interpretation: `not_due` is the source interval check; `deferred` is a persisted provider cooldown; `success` with zero fetched records can be an unchanged-feed HTTP 304. `--force` bypasses source intervals but deliberately does not bypass provider Retry-After or AI retry windows. Successful fetching followed by failed/deferred rewrites usually requires checking NVIDIA configuration/quota or the article's rewrite_error in admin, not adding another news API key. Verification failures remain unpublished by design.

If manual sync works but automatic execution does not, check the OS Laravel scheduler separately. The application defines hourly fetching and five-minute rewrite retries; registration on the cron page alone does not start an OS scheduler. The local Windows SYSTEM task installation was cancelled, and no production scheduler state has been inspected from this workspace.

1. Back up the database. Deploy code and run the **specific** new migration (unrelated pending migrations need their own review):

   ```sh
   php artisan migrate --path=database/migrations/2026_11_17_000001_create_technology_news_tables.php --force
   php artisan migrate --path=database/migrations/2026_11_17_000002_add_ai_rewrite_fields_to_news_articles.php --force
   php artisan migrate --path=database/migrations/2026_11_17_000003_enable_technology_news_rewrite_workflow.php --force
   php artisan migrate --path=database/migrations/2026_11_17_000004_add_feed_content_permission_to_news_sources.php --force
   php artisan migrate --path=database/migrations/2026_11_17_000005_add_feed_format_and_licensing_to_news_sources.php --force
   php artisan news:install
   php artisan config:cache
   php artisan news:sync --force
   php artisan news:health
   php artisan schedule:list
   ```

2. Optional environment variables:

   ```dotenv
   NEWS_USER_AGENT="SetuGeo-News/1.0 (+https://your-domain.example/contact)"
   NEWS_CA_BUNDLE=/absolute/path/to/trusted-ca-bundle.pem
   ```

   NEWS_CA_BUNDLE is optional when PHP already trusts the system/public roots. Never set verification to false. This Windows checkout has a machine-root PEM export at storage/app/news-ca.pem for live verification; it is ignored by Git and should not be deployed as a universal trust bundle. Use your server's managed CA bundle in production. Existing DB_*, APP_URL, cache, session and auth settings are reused; the module does not alter the application's global timezone or default queue.

3. **One scheduling entry point per environment.** The Laravel event is `0 * * * *` in **UTC** (05:30, 06:30, etc. IST). Reuse an already supervised Laravel scheduler if present. Otherwise add this to one Linux scheduler host, as the application user:

   ```cron
   * * * * * cd /var/www/setugeo && /usr/bin/php artisan schedule:run >> storage/logs/scheduler.log 2>&1
   ```

   Do not add a separate hourly news cron alongside this. Other application events keep their existing timezones. Source intervals are multiples of 60 minutes, default 60; rounding to an hourly bucket prevents a few seconds' start drift from skipping the following hour. Manual sync ignores intervals but respects Retry-After. Database leases coordinate all entry points across servers. All app instances must use the same database, and distributed deployments must use a shared cache for coherent public cache invalidation and throttling. Set the existing CACHE_DRIVER to Redis/shared database in such deployments, following the existing application's setup.

4. **Persistent manual worker:** the named `technology-news` connection uses Laravel's database queue driver and the module-owned news_jobs table; retry_after=1900 seconds, worker timeout=1800, lease=3600. Scheduled CLI sync does not require this worker. Start and supervise:

   ```sh
   php artisan queue:work technology-news --queue=technology-news --sleep=3 --timeout=1800 --tries=1
   ```

   On Linux customize deploy/technology-news/setugeo-news-worker.service paths/user, install into /etc/systemd/system, then `systemctl daemon-reload` and `systemctl enable --now setugeo-news-worker`. On deployment restart the service gracefully. Global queue retry_after remains unchanged.

5. **Windows:** run deploy/technology-news/install-windows-tasks.ps1 from elevated PowerShell. It checks for an active Laravel scheduler before creating a dedicated hourly task. It registers an independent persistent database worker poll every minute, using SYSTEM so both tasks survive logout and restarts. The hourly start is derived from the next UTC hour and converted to the Windows local timezone; repeated invocations update the same task names. A previously disabled global scheduler is left disabled. If switching to a global scheduler later, disable/remove the dedicated news-hourly task. Check Task Scheduler history and LastTaskResult; use Start-ScheduledTask for a smoke run. The task execution limit is one hour and concurrent task instances are refused.

6. Verify before production acceptance: two sources have successful logs and real articles; repeat `news:sync --force` without article growth from unchanged feeds; inspect /technology-news, admin manual sync and queue drain; check schedule:list shows the hourly event in UTC; inspect the OS scheduler's next run, last result, and execution history across at least one real hourly boundary. Production host access is necessary to verify the last step; schedule:list alone does not prove cron is running.

## Routes and administration

### AI rewrite storage

Migration `2026_11_17_000002_add_ai_rewrite_fields_to_news_articles.php` adds nullable `ai_title`, `ai_summary`, `ai_content`, `ai_tags` (JSON), `ai_meta_title`, and `ai_meta_description`. Original source fields remain `title`, `description`, `content`, and `tags`; provider synchronization never overwrites generated fields.

Workflow metadata follows MarketNews naming: `editorial_status` (pending by default; processing/ready/published/failed constants), `rewrite_model`, `rewrite_version`, `rewrite_error`, `rewrite_retry_at`, and `rewritten_at`. `rewrite_source_hash` records the source `content_hash` used for generation, allowing a future worker to detect source changes. Retry/completion timestamps are UTC. Existing `status` continues to control public visibility independently of rewrite progress.

Apply with `php artisan migrate --path=database/migrations/2026_11_17_000002_add_ai_rewrite_fields_to_news_articles.php --force`, then the workflow migration described above. AI drafts and rewrite internals remain hidden from default model serialization; the public API explicitly returns approved AI title/summary/content and attribution, never processing errors or unapproved drafts.

* GET /technology-news: filters q, category ID, source ID, from/to UTC dates; server pagination. Public desktop/mobile nav links and sitemap entry included. Filtered pages are noindex, pagination has distinct canonical URLs. Shared layout supplies Open Graph and metadata. No NewsArticle schema or thin local article pages.
* GET /technology-news/{slug}: SetuGeo's verified rewritten article, with original publisher and licence links. Short reports remain noindex.
* GET /api/news, /api/news/latest, /api/news/search, /api/news/trending, /api/news/categories, /api/news/sources, /api/news/{slug}. Article metadata uses an explicit allowlist. Search/list return data plus pagination meta. Trending is labelled Hacker News score, and remains empty while HN is disabled.
* Admin UI: /admin/technology-news. Existing admin session is required; controller authorization returns 403 for non-admins. Source configuration, queueing, source health, publication/category/feature edits, duplicate decisions and paginated logs are available.
* Sanctum admin API: GET /api/admin/news, GET/POST /api/admin/news/sources, PATCH /api/admin/news/sources/{id}, POST /api/admin/news/sync, POST /api/admin/news/sources/{id}/sync, PATCH /api/admin/news/articles/{id}, GET /api/admin/news/sync-logs. JSON bodies use the same validated fields as UI forms. Source PATCH currently requires the complete source configuration, including a new permission confirmation if active/displayed.
* Public limits: 60/minute plus existing API limiter; admin reads/writes 30/minute and manual sync 3/minute, independently keyed. Web mutations retain CSRF. New news API routes bypass stock-provider branding sanitization to preserve exact headlines and attribution.

## Reliability and monitoring

`php artisan news:health` exits 1 for stale/failing/unpermitted active sources, runs marked running over an hour, or manual jobs waiting over an hour. Integrate with the existing host monitoring. Watch structured technology_news.* application events, the admin sync logs, and existing CronExecutionLogger history. Log duration and fetched/inserted/updated/duplicate/skipped/failed counts. Alert when last_success_at exceeds two source intervals (minimum three hours). Running logs left by killed processes are retained for investigation; leases expire after an hour. Failed source records and old articles are never deleted on provider outages.

HTTP has 5-second connection and 15-second response timeouts, up to three attempts for transport/5xx failures and bounded exponential delay; 429/503 Retry-After defers the source using a persisted timestamp, even for manual sync. Requests cap downloaded/decompressed body size at 2 MiB, refuse redirects/proxies, validate public HTTPS DNS and pin the selected public IPv4 address via cURL, preventing DNS rebinding and private-network fetches. IPv6-only origins are conservatively unsupported. ETags/Last-Modified reduce RSS requests. No publisher pages are fetched, so the module does not need a page scraper or robots bypass. Feed administrators must also verify feed endpoint automation conditions.

Caches use the existing backend with version invalidation on sync/admin writes: lists/featured 5 minutes, categories 60 minutes, HN ranking 15 minutes. Listing is paginated and relations eagerly loaded; source bodies and candidate comparisons are bounded. Search uses indexed filters plus SQL LIKE; large-scale full-text indexing can be added later without changing API semantics. At most 100 nearby stories per inserted record are checked for potential duplicate review; exact provider and URL uniqueness applies across the entire archive.

## Tests and verification

```sh
php -d error_reporting=22527 vendor/phpunit/phpunit/phpunit --filter='TechnologyNewsTest|AdminMarketNewsTest|MarketNewsEditorialWorkflowTest|UpstoxNewsSyncTest'
```

Mocked tests cover API/RSS/Atom ingestion, malformed feeds, HTTP failures/timeouts/429, provider metadata changes, duplicate references and review, hourly due checks, database leases, classification, missing dates/images, authorization, filters/pagination, source activation, transactional rollback, cache invalidation, unsafe DNS/URLs/XML, HN attribution and schedule/installer idempotence. `error_reporting=22527` suppresses legacy vendor PHP 8.4 deprecation output; no vendor code was changed.

Local live tests imported real NASA and Mozilla metadata using verified HTTPS. UI route tests and local HTTP smoke checks passed. Impeccable's mechanical UI detector returned no findings. Browser automation was unavailable, so desktop/mobile screenshot QA is still required before production sign-off.

Latest verification: **64 tests, 328 assertions passed**, including the existing market-news regression tests; 20 new PHP files passed syntax checks; Windows installer parsed successfully; `git diff --check` passed. A real persisted manual job was processed successfully and the queue drained to zero. Repeat live synchronization retained exactly 30 real articles (10 NASA, 20 Mozilla) and used conditional HTTP requests.

**Scheduling is inactive: installation was cancelled by the user.** The pending Windows UAC elevation was cancelled, and a follow-up check confirmed that neither dedicated news task nor an installer process remained. The existing `GeoData Laravel Scheduler` task remains disabled. The prepared installer requires an elevated PowerShell session; `-StartTasks` additionally smoke-runs registered tasks. Do not install or start tasks without renewed user authorization. The Laravel schedule definition is verified as `0 * * * *` UTC, but automatic execution is not enabled.

Changed integration files: .env.example, README.md, app/Console/Kernel.php, app/Http/Controllers/SitemapController.php, config/cron_jobs.php, config/queue.php, resources/views/layouts/{app,public}.blade.php, routes/{api,web}.php. New implementation files: app/Console/Commands/{InstallTechnologyNews,SyncTechnologyNews,TechnologyNewsHealth}.php; public/admin TechnologyNewsController.php; app/Jobs/SyncTechnologyNews.php; app/Models/{NewsArticle,NewsCategory,NewsSource,NewsSyncLog}.php and Concerns/UsesUtcTimestamps.php; app/Services/TechnologyNews/{SafeHttp,RateLimited,Providers,Normalizer,Synchronizer,NewsCache}.php; config/technology_news.php; the migration above; routes/technology-news.php; website/technology-news.blade.php and admin/technology-news/{index,source-fields}.blade.php; tests/Feature/TechnologyNewsTest.php; deploy/technology-news/*; this guide. Local .env additionally points to the ignored machine-trust PEM bundle; no secrets were added to tracked files.

## Rollback

Stop/disable the news worker and remove/disable the dedicated news scheduler tasks (or remove the news event in the deployed code). Keep the database archive for recovery, revert the application changes, rebuild config/routes/views caches, and restart existing workers. To remove all module data after a backup, rollback only the specific migration with `php artisan migrate:rollback --path=database/migrations/2026_11_17_000001_create_technology_news_tables.php --step=1`; confirm it is the latest migration batch first. Do not run migrate:fresh or rollback unrelated batches. Dropping these tables destroys the news archive and queue.
