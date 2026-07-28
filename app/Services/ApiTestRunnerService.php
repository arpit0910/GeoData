<?php

namespace App\Services;

use App\Models\ApiTestReport;
use App\Models\Bank;
use App\Models\BankBranch;
use App\Models\City;
use App\Models\Country;
use App\Models\CurrencyConversion;
use App\Models\Equity;
use App\Models\Index;
use App\Models\MfMaster;
use App\Models\Pincode;
use App\Models\Plan;
use App\Models\Region;
use App\Models\State;
use App\Models\SubRegion;
use App\Models\Subscription;
use App\Models\SubscriptionFeature;
use App\Models\Timezone;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

class ApiTestRunnerService
{
    public function buildEndpointCatalog(?User $targetUser = null): Collection
    {
        $sampleData = $this->sampleData($targetUser);

        $routes = collect(Route::getRoutes())->filter(function ($route) {
            $uri = $route->uri();

            return str_starts_with($uri, 'api/v1')
                && !in_array('generated::fallback', $route->middleware(), true)
                && $route->getActionName() !== 'Closure';
        });

        return $routes->map(function ($route) use ($sampleData) {
            $uri = $route->uri();
            $methods = array_values(array_diff($route->methods(), ['HEAD']));
            $method = $methods[0] ?? 'GET';
            $middleware = $route->gatherMiddleware();

            $sample = $this->buildSampleRequest($uri, $sampleData);
            $supported = in_array($method, ['GET', 'POST'], true);
            $unsupportedReason = null;

            if (! $sample['resolvable']) {
                $supported = false;
                $unsupportedReason = $sample['reason'];
            } elseif (! $supported) {
                $unsupportedReason = 'Only GET and POST endpoints are currently supported in this runner.';
            }

            return [
                'key' => $method . ' ' . $uri,
                'method' => $method,
                'uri' => $uri,
                'category' => $this->categoryFromUri($uri),
                'middleware' => $middleware,
                'requires_auth' => in_array('auth:sanctum', $middleware, true),
                'supported' => $supported,
                'unsupported_reason' => $unsupportedReason,
                'sample_path' => $sample['path'],
                'sample_query' => $sample['query'],
            ];
        })->sortBy(['category', 'uri'])->values();
    }

    public function runAndStore(
        User $actor,
        User $targetUser,
        array $requestedKeys = [],
        string $mode = 'demo',
        array $overrides = []
    ): ApiTestReport
    {
        $mode = $mode === 'production' ? 'production' : 'demo';
        $catalog = $this->buildEndpointCatalog($targetUser)->keyBy('key');
        $requested = collect($requestedKeys)->filter()->values();
        $selected = $requested->isEmpty()
            ? $catalog
            : $requested->map(fn (string $key) => $catalog->get($key))->filter();

        if ($selected->isEmpty()) {
            throw new \InvalidArgumentException('No API endpoints selected.');
        }

        if ($mode === 'demo') {
            $this->ensureAdminTestingAccess($targetUser);
        }

        $bearerToken = '';

        $startedAt = now();
        $results = [];

        foreach ($selected as $endpoint) {
            if (! ($endpoint['supported'] ?? false)) {
                $results[] = $this->buildSkippedResult($endpoint, $overrides[$endpoint['key']] ?? []);
                continue;
            }

            $results[] = $this->executeEndpoint(
                $endpoint,
                $actor,
                $targetUser,
                $bearerToken,
                $mode,
                $overrides[$endpoint['key']] ?? []
            );
        }

        $resultsCollection = collect($results);
        $summary = [
            'mode' => $mode,
            'company' => [
                'id' => $targetUser->id,
                'name' => $targetUser->company_name ?: $targetUser->name,
                'email' => $targetUser->email,
            ],
            'total' => $resultsCollection->count(),
            'passed' => $resultsCollection->where('outcome', 'passed')->count(),
            'failed' => $resultsCollection->where('outcome', 'failed')->count(),
            'skipped' => $resultsCollection->where('outcome', 'skipped')->count(),
            'executed' => $resultsCollection->whereIn('outcome', ['passed', 'failed'])->count(),
            'average_duration_ms' => round((float) ($resultsCollection->whereIn('outcome', ['passed', 'failed'])->avg('duration_ms') ?? 0), 2),
            'generated_at' => now()->toDateTimeString(),
        ];

        $reportAttributes = [
            'generated_by_user_id' => $actor->id,
            'target_user_id' => $targetUser->id,
            'mode' => $mode,
            'status' => 'completed',
            'report_name' => $this->buildReportName($targetUser, $mode, $startedAt),
            'total_endpoints' => $summary['total'],
            'passed_endpoints' => $summary['passed'],
            'failed_endpoints' => $summary['failed'],
            'average_duration_ms' => $summary['average_duration_ms'],
            'selected_endpoints' => $selected->pluck('key')->values()->all(),
            'summary' => $summary,
            'results' => $results,
            'started_at' => $startedAt,
            'completed_at' => now(),
        ];

        if (Schema::hasColumn('api_test_reports', 'skipped_endpoints')) {
            $reportAttributes['skipped_endpoints'] = $summary['skipped'];
        }

        return ApiTestReport::create($reportAttributes);
    }

    public function executeEndpoint(
        array $endpoint,
        User $actor,
        User $targetUser,
        string $bearerToken,
        string $mode,
        array $override = []
    ): array
    {
        $payload = $this->normalizeOverrideParams($override['params'] ?? $endpoint['sample_query']);
        $path = $this->normalizeOverridePath($override['path'] ?? $endpoint['sample_path']);
        $files = [];

        if ($endpoint['uri'] === 'api/v1/auth/token') {
            $payload = [
                'client_key' => $targetUser->client_key,
                'client_secret' => $targetUser->client_secret,
            ];
            $path = $endpoint['sample_path'];
        } elseif ($endpoint['uri'] === 'api/v1/webhooks/razorpay') {
            $payload = [
                'event' => 'tester.ping',
                'payload' => [
                    'tester' => [
                        'entity' => [
                            'source' => 'admin-api-tester',
                            'company_id' => $targetUser->id,
                        ],
                    ],
                ],
            ];
        } elseif ($endpoint['uri'] === 'api/v1/ocr/extract') {
            $payload['document_type'] = $payload['document_type'] ?? 'pan';
            $files['image'] = $this->buildOcrTestImage();
        }

        $queryString = $endpoint['method'] === 'GET' && ! empty($payload)
            ? '?' . http_build_query($payload)
            : '';

        $uri = '/' . ltrim($path, '/') . $queryString;
        $start = microtime(true);

        $transactionStarted = false;

        try {
            if (in_array($mode, ['demo', 'production'], true)) {
                DB::beginTransaction();
                $transactionStarted = true;
            }

            $internalRequest = Request::create(
                $uri,
                $endpoint['method'],
                $endpoint['method'] === 'GET' ? [] : $payload,
                [],
                $files
            );
            $internalRequest->headers->set('Accept', 'application/json');
            $internalRequest->headers->set('X-Requested-With', 'XMLHttpRequest');
            $internalRequest->server->set('REMOTE_ADDR', '127.0.0.1');

            if ($endpoint['requires_auth']) {
                Sanctum::actingAs($targetUser);
                $internalRequest->setUserResolver(fn () => $targetUser);
            }

            $internalRequest->headers->set('X-Admin-Api-Tester', '1');
            $internalRequest->headers->set('X-Admin-Api-Tester-Actor', (string) $actor->id);

            $response = app()->handle($internalRequest);
            $durationMs = round((microtime(true) - $start) * 1000, 2);
            $content = $response->getContent();
            app('auth')->forgetGuards();

            if ($transactionStarted && DB::transactionLevel() > 0) {
                DB::rollBack();
                $transactionStarted = false;
            }
        } catch (\Throwable $e) {
            if ($transactionStarted && DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            app('auth')->forgetGuards();

            return [
                'key' => $endpoint['key'],
                'method' => $endpoint['method'],
                'uri' => $endpoint['uri'],
                'category' => $endpoint['category'],
                'tested_uri' => $uri,
                'request_path' => '/' . ltrim($path, '/'),
                'request_params' => $payload,
                'ok' => false,
                'outcome' => 'failed',
                'status' => 500,
                'duration_ms' => round((microtime(true) - $start) * 1000, 2),
                'response_preview' => $e->getMessage(),
            ];
        }

        return [
            'key' => $endpoint['key'],
            'method' => $endpoint['method'],
            'uri' => $endpoint['uri'],
            'category' => $endpoint['category'],
            'tested_uri' => $uri,
            'request_path' => '/' . ltrim($path, '/'),
            'request_params' => $payload,
            'ok' => $response->getStatusCode() >= 200 && $response->getStatusCode() < 300,
            'outcome' => $response->getStatusCode() >= 200 && $response->getStatusCode() < 300 ? 'passed' : 'failed',
            'status' => $response->getStatusCode(),
            'duration_ms' => $durationMs,
            'response_preview' => $this->truncateResponse($content),
        ];
    }

    private function buildSkippedResult(array $endpoint, array $override = [], ?string $reason = null): array
    {
        $payload = $this->normalizeOverrideParams($override['params'] ?? $endpoint['sample_query']);
        $path = $this->normalizeOverridePath($override['path'] ?? $endpoint['sample_path']);
        $queryString = $endpoint['method'] === 'GET' && ! empty($payload)
            ? '?' . http_build_query($payload)
            : '';

        return [
            'key' => $endpoint['key'],
            'method' => $endpoint['method'],
            'uri' => $endpoint['uri'],
            'category' => $endpoint['category'],
            'tested_uri' => '/' . ltrim($path, '/') . $queryString,
            'request_path' => '/' . ltrim($path, '/'),
            'request_params' => $payload,
            'ok' => null,
            'outcome' => 'skipped',
            'status' => 'skipped',
            'duration_ms' => 0,
            'response_preview' => $reason ?? $endpoint['unsupported_reason'] ?? 'Skipped because this route is not runnable in the current tester context.',
        ];
    }

    private function buildOcrTestImage(): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9sX6lz4AAAAASUVORK5CYII=');
        $path = tempnam(sys_get_temp_dir(), 'ocr-api-tester-');

        if ($path === false || $png === false) {
            throw new \RuntimeException('Unable to prepare OCR test image.');
        }

        file_put_contents($path, $png);

        return new UploadedFile($path, 'ocr-test.png', 'image/png', null, true);
    }

    private function buildReportName(User $targetUser, string $mode, $startedAt): string
    {
        $company = $targetUser->company_name ?: $targetUser->name;

        return sprintf(
            '%s API %s Report - %s',
            $company,
            Str::title($mode),
            $startedAt->format('Y-m-d H:i')
        );
    }

    private function buildSampleRequest(string $uri, array $sampleData): array
    {
        $path = $uri;
        $query = [];
        $reason = null;

        $replacements = [
            '{country}' => $sampleData['country_id'],
            '{state}' => $sampleData['state_id'],
            '{city}' => $sampleData['city_id'],
            '{bank}' => $sampleData['bank_id'],
            '{ifsc}' => $sampleData['ifsc'],
            '{pincode}' => $sampleData['pincode'],
            '{isin}' => $sampleData['equity_isin'],
            '{cap}' => 'large-cap',
            '{index_code}' => $sampleData['index_code'],
        ];

        foreach ($replacements as $token => $value) {
            if (str_contains($path, $token)) {
                if (blank($value)) {
                    $reason = "Missing sample data for {$token}.";
                    break;
                }

                $path = str_replace($token, rawurlencode((string) $value), $path);
            }
        }

        switch ($uri) {
            case 'api/v1/regions':
                $query = ['limit' => 3, 'name' => $sampleData['region_name']];
                break;
            case 'api/v1/sub-regions':
                $query = ['limit' => 3, 'region_id' => $sampleData['region_id']];
                break;
            case 'api/v1/timezones':
                $query = ['limit' => 3, 'name' => $sampleData['timezone_name']];
                break;
            case 'api/v1/countries':
                $query = ['limit' => 3, 'iso2' => $sampleData['country_iso2']];
                break;
            case 'api/v1/states':
                $query = ['limit' => 3, 'country_id' => $sampleData['country_id']];
                break;
            case 'api/v1/cities':
                $query = ['limit' => 3, 'state_id' => $sampleData['state_id']];
                break;
            case 'api/v1/pincodes':
                $query = ['limit' => 3, 'city_id' => $sampleData['city_id']];
                break;
            case 'api/v1/pincodes/search':
                $query = ['pincode' => $sampleData['pincode']];
                break;
            case 'api/v1/countries/compare':
                $query = [
                    'c1_id' => $sampleData['country_id'],
                    'c2_id' => $sampleData['country2_id'],
                ];
                break;
            case 'api/v1/address/validate':
                $query = [
                    'country_id' => $sampleData['country_id'],
                    'state_id' => $sampleData['state_id'],
                    'city_id' => $sampleData['city_id'],
                    'pincode' => $sampleData['pincode'],
                ];
                break;
            case 'api/v1/address/autocomplete':
                $query = ['q' => $sampleData['city_name']];
                break;
            case 'api/v1/timezone/convert':
            case 'api/v1/timezones/convert':
                $query = [
                    'datetime' => now()->format('Y-m-d H:i:s'),
                    'from' => $sampleData['timezone_name'] ?: 'UTC',
                    'to' => 'Asia/Kolkata',
                ];
                break;
            case 'api/v1/geospatial/statistics':
                $query = ['country_id' => $sampleData['country_id'], 'state_id' => $sampleData['state_id']];
                break;
            case 'api/v1/geospatial/distance':
                $query = [
                    'lat1' => $sampleData['lat1'],
                    'lng1' => $sampleData['lng1'],
                    'lat2' => $sampleData['lat2'],
                    'lng2' => $sampleData['lng2'],
                    'unit' => 'km',
                ];
                break;
            case 'api/v1/geospatial/nearby':
                $query = [
                    'lat' => $sampleData['lat1'],
                    'lng' => $sampleData['lng1'],
                    'radius' => 25,
                    'type' => 'city',
                    'limit' => 3,
                ];
                break;
            case 'api/v1/geospatial/geocode':
                $query = ['lat' => $sampleData['lat1'], 'lng' => $sampleData['lng1']];
                break;
            case 'api/v1/geospatial/boundary':
                $query = [
                    'min_lat' => min($sampleData['lat1'], $sampleData['lat2']) - 0.5,
                    'max_lat' => max($sampleData['lat1'], $sampleData['lat2']) + 0.5,
                    'min_lng' => min($sampleData['lng1'], $sampleData['lng2']) - 0.5,
                    'max_lng' => max($sampleData['lng1'], $sampleData['lng2']) + 0.5,
                    'type' => 'city',
                    'limit' => 3,
                ];
                break;
            case 'api/v1/geospatial/cluster':
                $query = [
                    'lat' => $sampleData['lat1'],
                    'lng' => $sampleData['lng1'],
                    'radius' => 25,
                    'grid_size' => 0.5,
                ];
                break;
            case 'api/v1/countries/economic-profile':
                $query = ['region_id' => $sampleData['region_id'], 'sort_by' => 'gdp'];
                break;
            case 'api/v1/countries/tax-data':
                $query = ['region_id' => $sampleData['region_id']];
                break;
            case 'api/v1/countries/analysis/regional-gdp':
                $query = ['region_id' => $sampleData['region_id']];
                break;
            case 'api/v1/currency/exchange':
                $query = ['currency' => $sampleData['currency_code']];
                break;
            case 'api/v1/currency/convert':
                $query = ['from' => $sampleData['convert_from'], 'to' => $sampleData['convert_to'], 'amount' => 10];
                break;
            case 'api/v1/banks':
                $query = ['limit' => 3, 'name' => $sampleData['bank_name']];
                break;
            case 'api/v1/branch/search':
            case 'api/v1/bank/branches/search':
                $query = ['q' => $sampleData['branch_name'], 'limit' => 3];
                break;
            case 'api/v1/banks/digital-coverage':
                $query = ['limit' => 3];
                break;
            case 'api/v1/equities':
                $query = ['symbol' => $sampleData['equity_symbol']];
                break;
            case 'api/v1/equities/search':
                $query = ['q' => $sampleData['equity_symbol']];
                break;
            case 'api/v1/equities/analysis/top-gainers':
            case 'api/v1/equities/analysis/top-losers':
            case 'api/v1/equities/analysis/top-turnover':
            case 'api/v1/equities/analysis/high-volume':
                $query = ['exchange' => 'nse'];
                break;
            case 'api/v1/equities/analysis/gap-movers':
                $query = ['exchange' => 'nse', 'direction' => 'up', 'min_pct' => 1, 'limit' => 5];
                break;
            case 'api/v1/equities/analysis/intraday-movers':
            case 'api/v1/equities/analysis/wide-range-stocks':
            case 'api/v1/equities/analysis/high-activity':
            case 'api/v1/equities/analysis/nse-bse-spread':
                $query = ['exchange' => 'nse', 'limit' => 5];
                break;
            case 'api/v1/equities/analysis/consistent-performers':
                $query = ['period' => '1m', 'min_return' => 1, 'limit' => 5];
                break;
            case 'api/v1/equities/analysis/52-week-extremes':
                $query = ['type' => 'high', 'limit' => 5];
                break;
            case 'api/v1/equities/analysis/sector-heatmap':
                $query = ['period' => '1m'];
                break;
            case 'api/v1/equity/{isin}/activity-metrics':
                $query = ['exchange' => 'nse'];
                break;
            case 'api/v1/indices/snapshot':
            case 'api/v1/indices/analysis/valuation-comparison':
            case 'api/v1/indices/analysis/ohlc-summary':
                $query = ['exchange' => 'NSE'];
                break;
            case 'api/v1/indices/search':
                $query = ['q' => $sampleData['index_search']];
                break;
            case 'api/v1/indices/holdings':
                $query = ['index_code' => $sampleData['index_code']];
                break;
            case 'api/v1/indices/analysis/top-gainers':
            case 'api/v1/indices/analysis/top-losers':
                $query = ['period' => '1d', 'limit' => 5];
                break;
            case 'api/v1/indices/{index_code}/history':
                $query = ['start_date' => now()->subDays(30)->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')];
                break;
            case 'api/v1/indices/{index_code}/valuation-history':
                $query = ['months' => 3];
                break;
            case 'api/v1/mf/list':
                $query = ['per_page' => 5];
                break;
            case 'api/v1/mf/search':
                $query = ['q' => $sampleData['mf_search']];
                break;
            case 'api/v1/mf/compare':
                $query = ['isins' => implode(',', array_filter([$sampleData['mf_isin'], $sampleData['mf2_isin']]))];
                break;
            case 'api/v1/mf/analysis/top-gainers':
            case 'api/v1/mf/analysis/top-losers':
                $query = ['period' => '1y', 'limit' => 5];
                break;
            case 'api/v1/mf/analysis/category-returns':
            case 'api/v1/mf/analysis/amc-performance':
                $query = ['period' => '1y', 'limit' => 5];
                break;
            case 'api/v1/mf/analysis/consistent-performers':
                $query = ['periods' => '1m,3m,1y', 'limit' => 5];
                break;
            case 'api/v1/mf/history/{isin}':
                $query = ['months' => 6];
                break;
            case 'api/v1/mf/{isin}/similar-funds':
                $query = ['limit' => 5];
                break;
            case 'api/v1/market/heatmap':
                $query = ['period' => '1m'];
                break;
            case 'api/v1/market/breadth':
                $query = ['exchange' => 'nse', 'period' => '1d'];
                break;
        }

        return [
            'resolvable' => $reason === null,
            'reason' => $reason,
            'path' => $path,
            'query' => array_filter($query, fn ($value) => ! blank($value)),
        ];
    }

    private function sampleData(?User $targetUser = null): array
    {
        $userCountry = $targetUser?->country_id ? Country::find($targetUser->country_id) : null;
        $userState = $targetUser?->state_id ? State::find($targetUser->state_id) : null;
        $userCity = $targetUser?->city_id ? City::find($targetUser->city_id) : null;
        $userPincode = $targetUser?->pincode ? Pincode::query()->where('postal_code', $targetUser->pincode)->first() : null;

        $country = $userCountry ?? Country::query()->orderBy('id')->first();
        $country2 = Country::query()->where('id', '!=', $country?->id)->orderBy('id')->first();
        $state = $userState
            ?? State::query()->when($country, fn ($q) => $q->where('country_id', $country->id))->orderBy('id')->first()
            ?? State::query()->orderBy('id')->first();
        $city = $userCity
            ?? City::query()->when($state, fn ($q) => $q->where('state_id', $state->id))->orderBy('id')->first()
            ?? City::query()->orderBy('id')->first();
        $pincode = $userPincode
            ?? Pincode::query()->whereNotNull('postal_code')->orderBy('id')->first();
        $bank = Bank::query()->orderBy('id')->first();
        $branch = BankBranch::query()->whereNotNull('ifsc')->orderBy('id')->first();
        $region = Region::query()->orderBy('id')->first();
        $subRegion = SubRegion::query()->orderBy('id')->first();
        $timezone = Timezone::query()->when($country, fn ($q) => $q->where('country_id', $country->id))->orderBy('id')->first()
            ?? Timezone::query()->orderBy('id')->first();
        $currency = CurrencyConversion::query()->whereNotNull('currency')->orderBy('currency')->first();
        $equity = Equity::query()->where('is_active', true)->orderBy('id')->first();
        $equityTwo = Equity::query()->where('is_active', true)->where('id', '!=', $equity?->id)->orderBy('id')->first();
        $index = Index::query()->orderBy('index_code')->first();
        $mf = MfMaster::query()->where('is_active', true)->orderBy('scheme_name')->first();
        $mfTwo = MfMaster::query()->where('is_active', true)->where('isin', '!=', $mf?->isin)->orderBy('scheme_name')->first();

        $lat1 = $city?->latitude ?? $pincode?->latitude ?? 28.6139;
        $lng1 = $city?->longitude ?? $pincode?->longitude ?? 77.2090;
        $lat2 = $pincode?->latitude ?? ($lat1 + 0.1);
        $lng2 = $pincode?->longitude ?? ($lng1 + 0.1);

        return [
            'country_id' => $country?->id,
            'country2_id' => $country2?->id,
            'country_iso2' => $country?->iso2,
            'state_id' => $state?->id,
            'city_id' => $city?->id,
            'pincode' => $pincode?->postal_code,
            'bank_id' => $bank?->id,
            'ifsc' => $branch?->ifsc,
            'region_id' => $region?->id,
            'region_name' => $region?->name,
            'subregion_id' => $subRegion?->id,
            'timezone_name' => $targetUser?->timezone ?: $timezone?->zone_name,
            'currency_code' => $currency?->currency ?? 'USD',
            'convert_from' => $currency?->currency ?? 'USD',
            'convert_to' => 'USD',
            'bank_name' => $bank?->name,
            'branch_name' => $branch?->branch ?? 'main',
            'country_name' => $country?->name,
            'state_name' => $state?->name,
            'city_name' => $city?->name ?? 'delhi',
            'equity_isin' => $equity?->isin ?? 'INE000000000',
            'equity_symbol' => $equity?->nse_symbol ?? $equity?->bse_symbol ?? $equity?->isin ?? 'RELIANCE',
            'equity2_isin' => $equityTwo?->isin ?? 'INE000000001',
            'index_code' => $index?->index_code ?? 'NIFTY 50',
            'index_search' => $index?->index_name ?? $index?->index_code ?? 'NIFTY',
            'mf_isin' => $mf?->isin ?? 'INF000000001',
            'mf2_isin' => $mfTwo?->isin ?? 'INF000000002',
            'mf_search' => $mf?->scheme_name ? Str::words($mf->scheme_name, 2, '') : 'SBI',
            'lat1' => $lat1,
            'lng1' => $lng1,
            'lat2' => $lat2,
            'lng2' => $lng2,
        ];
    }

    private function categoryFromUri(string $uri): string
    {
        $path = str_replace('api/v1/', '', $uri);

        return ucfirst(Str::before($path, '/'));
    }

    private function ensureAdminTestingAccess(User $user): void
    {
        $plan = Plan::query()->firstOrCreate(
            ['name' => 'Internal Admin Tester'],
            [
                'api_hits_limit' => null,
                'amount' => 999999,
                'discount_amount' => 0,
                'status' => 1,
                'billing_cycle' => 'monthly',
                'terms' => 'Internal plan provisioned automatically for API tester access.',
                'benefits' => ['Unlimited internal admin API testing'],
            ]
        );

        $plan->forceFill([
            'api_hits_limit' => null,
            'amount' => 999999,
            'discount_amount' => 0,
            'status' => 1,
            'billing_cycle' => 'monthly',
        ])->save();

        if (Schema::hasTable('subscription_features') && Schema::hasTable('plan_subscription_feature')) {
            $allApiFeature = SubscriptionFeature::query()
                ->where('key', SubscriptionFeature::MODULE_ALL_API)
                ->where('is_active', true)
                ->first();

            if ($allApiFeature && ! $plan->features()->whereKey($allApiFeature->id)->exists()) {
                $plan->features()->attach($allApiFeature->id);
            }
        }

        $subscription = Subscription::query()
            ->where('user_id', $user->id)
            ->where('plan_id', $plan->id)
            ->latest()
            ->first();

        if (! $subscription) {
            $subscription = new Subscription([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
            ]);
        }

        $internalOrderId = 'internal-admin-tester-order-' . $user->id;
        $internalSubscriptionId = 'internal-admin-tester-subscription-' . $user->id;

        $subscription->forceFill([
            'razorpay_order_id' => $subscription->razorpay_order_id ?: $internalOrderId,
            'razorpay_payment_id' => $subscription->razorpay_payment_id ?: null,
            'razorpay_signature' => $subscription->razorpay_signature ?: null,
            'razorpay_subscription_id' => $subscription->razorpay_subscription_id ?: $internalSubscriptionId,
            'amount_paid' => 0,
            'status' => 'active',
            'expires_at' => now()->addYears(50),
            'total_credits' => 999999999,
            'used_credits' => 0,
            'available_credits' => 999999999,
            'discount_amount' => 0,
            'remaining_discount_cycles' => 0,
            'last_credit_refresh' => now(),
        ])->save();

        $user->forceFill([
            'plan_id' => $plan->id,
            'available_credits' => 999999999,
        ])->save();
    }

    private function truncateResponse(?string $content): string
    {
        return $content ?? '';
    }

    private function normalizeOverridePath(mixed $path): string
    {
        $normalized = trim((string) $path);

        return ltrim($normalized, '/') ?: 'api/v1';
    }

    private function normalizeOverrideParams(mixed $params): array
    {
        if (! is_array($params)) {
            return [];
        }

        return collect($params)
            ->mapWithKeys(function ($value, $key) {
                if (is_array($value)) {
                    $value = implode(',', array_filter($value, fn ($item) => ! blank($item)));
                }

                return [(string) $key => is_string($value) ? trim($value) : $value];
            })
            ->filter(fn ($value) => ! blank($value))
            ->all();
    }
}
