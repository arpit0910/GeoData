<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\BankBranch;
use App\Models\City;
use App\Models\Country;
use App\Models\CurrencyConversion;
use App\Models\Equity;
use App\Models\Index;
use App\Models\MfMaster;
use App\Models\Plan;
use App\Models\Pincode;
use App\Models\Region;
use App\Models\State;
use App\Models\SubRegion;
use App\Models\Subscription;
use App\Models\SubscriptionFeature;
use App\Models\Timezone;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ApiTesterController extends Controller
{
    public function index()
    {
        $sampleData = $this->sampleData();
        $endpoints = $this->buildEndpointCatalog($sampleData);
        $adminUser = Auth::user();

        return view('admin.api-tester.index', compact('endpoints', 'adminUser'));
    }

    public function run(Request $request)
    {
        $validated = $request->validate([
            'endpoints' => ['nullable', 'array'],
            'endpoints.*' => ['string'],
        ]);

        $user = Auth::user();

        if (!$user || !$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'API Tester can only be run as an authenticated admin user.',
            ], 403);
        }

        $this->ensureAdminTestingAccess($user);

        $sampleData = $this->sampleData();
        $catalog = $this->buildEndpointCatalog($sampleData)->keyBy('key');

        $requestedKeys = collect($validated['endpoints'] ?? [])
            ->filter()
            ->values();

        $selected = $requestedKeys->isEmpty()
            ? $catalog->filter(fn ($endpoint) => $endpoint['supported'])
            : $requestedKeys->map(fn ($key) => $catalog->get($key))->filter();

        if ($selected->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No supported API endpoints selected.',
            ], 422);
        }

        $user->tokens()->where('name', 'admin-api-tester')->delete();
        $bearerToken = $user->createToken('admin-api-tester')->plainTextToken;

        $results = [];
        foreach ($selected as $endpoint) {
            $results[] = $this->executeEndpoint($endpoint, $user, $bearerToken);
        }

        $summary = [
            'total' => count($results),
            'passed' => collect($results)->where('ok', true)->count(),
            'failed' => collect($results)->where('ok', false)->count(),
        ];

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'results' => $results,
            'warning' => 'Admin-mode tests bypass subscription and credit checks only inside the internal API tester.',
        ]);
    }

    private function buildEndpointCatalog(array $sampleData): Collection
    {
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
            $supported = $method === 'GET' || ($method === 'POST' && $uri === 'api/v1/auth/token');
            $unsupportedReason = null;

            if ($uri === 'api/v1/webhooks/razorpay') {
                $supported = false;
                $unsupportedReason = 'Webhook endpoint should be tested from Razorpay callback payloads.';
            } elseif ($uri === 'api/v1/ocr/extract') {
                $supported = false;
                $unsupportedReason = 'Requires file upload input and is better tested manually.';
            } elseif (!$sample['resolvable']) {
                $supported = false;
                $unsupportedReason = $sample['reason'];
            } elseif (!$supported) {
                $unsupportedReason = 'Only GET endpoints and token generation are supported in this tester.';
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

    private function executeEndpoint(array $endpoint, User $user, string $bearerToken): array
    {
        $server = [
            'HTTP_ACCEPT' => 'application/json',
            'REMOTE_ADDR' => '127.0.0.1',
        ];

        $payload = $endpoint['sample_query'];
        if ($endpoint['requires_auth']) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer ' . $bearerToken;
        }

        if ($user->is_admin) {
            $server['HTTP_X_ADMIN_API_TESTER'] = '1';
        }

        if ($endpoint['uri'] === 'api/v1/auth/token') {
            if (blank($user->client_key) || blank($user->client_secret)) {
                return [
                    'key' => $endpoint['key'],
                    'method' => $endpoint['method'],
                    'uri' => $endpoint['uri'],
                    'tested_uri' => '/' . ltrim($endpoint['sample_path'], '/'),
                    'ok' => false,
                    'status' => 422,
                    'duration_ms' => 0,
                    'response_preview' => 'The signed-in admin user does not have API client credentials for token generation.',
                ];
            }

            $payload = [
                'client_key' => $user->client_key,
                'client_secret' => $user->client_secret,
            ];
        }

        $queryString = $endpoint['method'] === 'GET' && !empty($payload)
            ? '?' . http_build_query($payload)
            : '';

        $uri = '/' . ltrim($endpoint['sample_path'], '/') . $queryString;

        $start = microtime(true);
        try {
            $internalRequest = Request::create($uri, $endpoint['method'], $endpoint['method'] === 'GET' ? [] : $payload);
            foreach ($server as $key => $value) {
                $internalRequest->server->set($key, $value);
            }

            $response = app()->handle($internalRequest);
            $durationMs = round((microtime(true) - $start) * 1000, 2);
            $content = $response->getContent();
        } catch (\Throwable $e) {
            return [
                'key' => $endpoint['key'],
                'method' => $endpoint['method'],
                'uri' => $endpoint['uri'],
                'tested_uri' => $uri,
                'ok' => false,
                'status' => 500,
                'duration_ms' => round((microtime(true) - $start) * 1000, 2),
                'response_preview' => $e->getMessage(),
            ];
        }

        return [
            'key' => $endpoint['key'],
            'method' => $endpoint['method'],
            'uri' => $endpoint['uri'],
            'tested_uri' => $uri,
            'ok' => $response->getStatusCode() >= 200 && $response->getStatusCode() < 300,
            'status' => $response->getStatusCode(),
            'duration_ms' => $durationMs,
            'response_preview' => $this->truncateResponse($content),
        ];
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
                $query = ['country_ids' => implode(',', array_filter([$sampleData['country_id'], $sampleData['country2_id']]))];
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
                    'country_id' => $sampleData['country_id'],
                    'type' => 'city',
                    'precision' => 2,
                ];
                break;
            case 'api/v1/countries/economic-profile':
            case 'api/v1/countries/tax-data':
                $query = ['country_id' => $sampleData['country_id']];
                break;
            case 'api/v1/countries/analysis/regional-gdp':
                $query = ['region_id' => $sampleData['region_id']];
                break;
            case 'api/v1/currency/exchange':
                $query = ['currency' => $sampleData['currency_code']];
                break;
            case 'api/v1/currency/convert':
                $query = ['from' => 'USD', 'to' => 'INR', 'amount' => 10];
                break;
            case 'api/v1/banks':
                $query = ['limit' => 3, 'name' => $sampleData['bank_name']];
                break;
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
            'query' => array_filter($query, fn ($value) => !blank($value)),
        ];
    }

    private function sampleData(): array
    {
        $country = Country::query()->orderBy('id')->first();
        $country2 = Country::query()->where('id', '!=', $country?->id)->orderBy('id')->first();
        $state = State::query()->when($country, fn ($q) => $q->where('country_id', $country->id))->orderBy('id')->first()
            ?? State::query()->orderBy('id')->first();
        $city = City::query()->when($state, fn ($q) => $q->where('state_id', $state->id))->orderBy('id')->first()
            ?? City::query()->orderBy('id')->first();
        $pincode = Pincode::query()->whereNotNull('postal_code')->orderBy('id')->first();
        $bank = Bank::query()->orderBy('id')->first();
        $branch = BankBranch::query()->whereNotNull('ifsc')->orderBy('id')->first();
        $region = Region::query()->orderBy('id')->first();
        $subRegion = SubRegion::query()->orderBy('id')->first();
        $timezone = Timezone::query()->orderBy('id')->first();
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
            'timezone_name' => $timezone?->zone_name,
            'currency_code' => $currency?->currency ?? 'USD',
            'bank_name' => $bank?->name,
            'branch_name' => $branch?->branch,
            'country_name' => $country?->name,
            'state_name' => $state?->name,
            'city_name' => $city?->name,
            'equity_isin' => $equity?->isin,
            'equity_symbol' => $equity?->nse_symbol ?? $equity?->bse_symbol ?? $equity?->isin,
            'equity2_isin' => $equityTwo?->isin,
            'index_code' => $index?->index_code,
            'index_search' => $index?->index_name ?? $index?->index_code,
            'mf_isin' => $mf?->isin,
            'mf2_isin' => $mfTwo?->isin,
            'mf_search' => $mf?->scheme_name ? Str::words($mf->scheme_name, 2, '') : null,
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
                'status' => 'active',
                'billing_cycle' => 'monthly',
                'terms' => 'Internal plan provisioned automatically for API tester access.',
                'benefits' => ['Unlimited internal admin API testing'],
            ]
        );

        $plan->forceFill([
            'api_hits_limit' => null,
            'amount' => 999999,
            'discount_amount' => 0,
            'status' => 'active',
            'billing_cycle' => 'monthly',
        ])->save();

        if (Schema::hasTable('subscription_features') && Schema::hasTable('plan_subscription_feature')) {
            $allApiFeature = SubscriptionFeature::query()
                ->where('key', SubscriptionFeature::MODULE_ALL_API)
                ->where('is_active', true)
                ->first();

            if ($allApiFeature && !$plan->features()->whereKey($allApiFeature->id)->exists()) {
                $plan->features()->attach($allApiFeature->id);
            }
        }

        $subscription = Subscription::query()
            ->where('user_id', $user->id)
            ->where('plan_id', $plan->id)
            ->latest()
            ->first();

        if (!$subscription) {
            $subscription = new Subscription([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
            ]);
        }

        $subscription->forceFill([
            'razorpay_order_id' => $subscription->razorpay_order_id ?: 'internal-admin-tester-order',
            'razorpay_payment_id' => $subscription->razorpay_payment_id ?: null,
            'razorpay_signature' => $subscription->razorpay_signature ?: null,
            'razorpay_subscription_id' => $subscription->razorpay_subscription_id ?: 'internal-admin-tester-subscription',
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
        $content ??= '';
        if (Str::length($content) <= 2000) {
            return $content;
        }

        return Str::limit($content, 2000, ' ...[truncated]');
    }
}
