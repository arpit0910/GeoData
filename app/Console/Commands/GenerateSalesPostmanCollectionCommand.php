<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ApiTestRunnerService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateSalesPostmanCollectionCommand extends Command
{
    protected $signature = 'postman:generate-sales-collection {--user= : Target company user ID or email for sample requests}';

    protected $description = 'Generate the public Postman collection used for demos, docs, and sales handoffs.';

    public function handle(ApiTestRunnerService $runner): int
    {
        $targetUser = $this->resolveTargetUser();
        $catalog = $runner->buildEndpointCatalog($targetUser);
        $supported = $catalog->where('supported', true)->values();

        $collection = [
            'info' => [
                '_postman_id' => (string) Str::uuid(),
                'name' => 'SetuGeo API - Demo & Sales Collection',
                'description' => 'Generated on ' . now()->toDateTimeString() . " from the current Laravel route catalog.\n\nUse this collection for product demos, sales walkthroughs, and production smoke checks. The token request saves the bearer token automatically for the rest of the collection.",
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'auth' => [
                'type' => 'bearer',
                'bearer' => [
                    [
                        'key' => 'token',
                        'value' => '{{bearerToken}}',
                        'type' => 'string',
                    ],
                ],
            ],
            'event' => [[
                'listen' => 'test',
                'script' => [
                    'type' => 'text/javascript',
                    'exec' => [
                        'pm.collectionVariables.set("lastStatusCode", String(pm.response.code));',
                    ],
                ],
            ]],
            'variable' => [
                ['key' => 'baseUrl', 'value' => 'https://setugeo.com/api/v1', 'type' => 'string'],
                ['key' => 'localBaseUrl', 'value' => 'http://127.0.0.1:8000/api/v1', 'type' => 'string'],
                ['key' => 'clientKey', 'value' => 'YOUR_CLIENT_KEY', 'type' => 'string'],
                ['key' => 'clientSecret', 'value' => 'YOUR_CLIENT_SECRET', 'type' => 'string'],
                ['key' => 'bearerToken', 'value' => 'YOUR_TOKEN_HERE', 'type' => 'string'],
                ['key' => 'lastStatusCode', 'value' => '', 'type' => 'string'],
            ],
            'item' => array_values(array_filter([
                $this->buildGettingStartedFolder(),
                ...$this->buildCategoryFolders($supported),
                $this->buildManualTestingFolder($catalog->where('supported', false)->values()),
            ])),
        ];

        $json = json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        File::put(public_path('postman_collection.json'), $json);
        File::put(public_path('SetuGeo.postman_collection.json'), $json);

        $this->info('Generated Postman collection for docs and demos.');
        $this->line('Source user: ' . ($targetUser?->email ?: 'default sample data'));
        $this->line('Supported requests: ' . $supported->count());

        return self::SUCCESS;
    }

    private function resolveTargetUser(): ?User
    {
        $value = $this->option('user');

        if (! $value) {
            return User::query()
                ->where('is_admin', false)
                ->where('account_type', 'client')
                ->orderBy('id')
                ->first();
        }

        return User::query()
            ->where('email', $value)
            ->orWhere('id', $value)
            ->first();
    }

    private function buildGettingStartedFolder(): array
    {
        return [
            'name' => '00. Getting Started',
            'description' => 'Authenticate first, then use the bearer token automatically across the collection.',
            'item' => [
                [
                    'name' => 'Generate Bearer Token',
                    'request' => [
                        'method' => 'POST',
                        'auth' => ['type' => 'noauth'],
                        'header' => [
                            ['key' => 'Accept', 'value' => 'application/json'],
                        ],
                        'body' => [
                            'mode' => 'urlencoded',
                            'urlencoded' => [
                                ['key' => 'client_key', 'value' => '{{clientKey}}', 'type' => 'text'],
                                ['key' => 'client_secret', 'value' => '{{clientSecret}}', 'type' => 'text'],
                            ],
                        ],
                        'url' => [
                            'raw' => '{{baseUrl}}/auth/token',
                            'host' => ['{{baseUrl}}'],
                            'path' => ['auth', 'token'],
                        ],
                        'description' => 'Exchange client credentials for a bearer token. The test script stores the token automatically.',
                    ],
                    'event' => [[
                        'listen' => 'test',
                        'script' => [
                            'type' => 'text/javascript',
                            'exec' => [
                                'const jsonData = pm.response.json();',
                                'const token = jsonData?.data?.access_token;',
                                'if (token) {',
                                '    pm.collectionVariables.set("bearerToken", token);',
                                '    pm.environment.set("bearerToken", token);',
                                '}',
                            ],
                        ],
                    ]],
                    'response' => $this->buildTokenResponses(),
                ],
                [
                    'name' => 'Check OCR Health',
                    'request' => [
                        'method' => 'GET',
                        'header' => [
                            ['key' => 'Accept', 'value' => 'application/json'],
                        ],
                        'url' => [
                            'raw' => '{{baseUrl}}/ocr/health',
                            'host' => ['{{baseUrl}}'],
                            'path' => ['ocr', 'health'],
                        ],
                        'description' => 'Quick unauthenticated health check for the OCR service.',
                    ],
                    'response' => $this->buildOcrHealthResponses(),
                ],
            ],
        ];
    }

    private function buildCategoryFolders(Collection $supported): array
    {
        $grouped = $supported
            ->reject(fn (array $endpoint) => $this->shouldExcludeFromSalesCollection($endpoint['uri']))
            ->groupBy(fn (array $endpoint) => $this->categoryFamilyKey($endpoint['category']))
            ->map(function (Collection $familyItems, string $familyKey) {
                $config = $this->categoryFamilyConfig($familyKey);

                $subfolders = $familyItems
                    ->groupBy(fn (array $endpoint) => $this->normalizedCategory($endpoint['category']))
                    ->sortKeys()
                    ->map(function (Collection $items, string $subcategory) {
                        $sorted = $items->sortBy([
                            fn (array $endpoint) => $this->requestSortWeight($endpoint),
                            'uri',
                        ])->values();

                        return [
                            'name' => $subcategory,
                            'item' => $sorted->map(fn (array $endpoint) => $this->buildRequestItem($endpoint))->values()->all(),
                        ];
                    })
                    ->values()
                    ->all();

                return [
                    'order' => $config['order'],
                    'name' => sprintf('%02d. %s', $config['order'], $config['label']),
                    'description' => $config['description'],
                    'item' => $subfolders,
                ];
            })
            ->sortBy('order')
            ->values();

        return $grouped->map(function (array $folder) {
            unset($folder['order']);

            return $folder;
        })->all();
    }

    private function buildRequestItem(array $endpoint): array
    {
        $query = collect($endpoint['sample_query'] ?? [])
            ->map(fn ($value, $key) => ['key' => (string) $key, 'value' => (string) $value])
            ->values()
            ->all();

        $rawUrl = '{{baseUrl}}/' . str_replace('api/v1/', '', $endpoint['sample_path']);
        if ($query !== []) {
            $rawUrl .= '?' . http_build_query($endpoint['sample_query']);
        }

        return [
            'name' => $this->humanizeRequestName($endpoint),
            'request' => [
                'method' => $endpoint['method'],
                'header' => [
                    ['key' => 'Accept', 'value' => 'application/json'],
                ],
                'url' => [
                    'raw' => $rawUrl,
                    'host' => ['{{baseUrl}}'],
                    'path' => array_values(array_filter(explode('/', str_replace('api/v1/', '', $endpoint['sample_path'])))),
                    'query' => $query,
                ],
                'description' => $this->buildRequestDescription($endpoint),
            ],
            'response' => $this->buildStandardResponses($endpoint),
        ];
    }

    private function buildManualTestingFolder(Collection $manual): array
    {
        return [
            'name' => '99. Manual Testing Notes',
            'item' => $manual->map(function (array $endpoint) {
                return [
                    'name' => $this->humanizeRequestName($endpoint),
                    'request' => [
                        'method' => $endpoint['method'],
                        'header' => [
                            ['key' => 'Accept', 'value' => 'application/json'],
                        ],
                        'url' => [
                            'raw' => '{{baseUrl}}/' . str_replace('api/v1/', '', $endpoint['sample_path']),
                            'host' => ['{{baseUrl}}'],
                            'path' => array_values(array_filter(explode('/', str_replace('api/v1/', '', $endpoint['sample_path'])))),
                        ],
                        'description' => $endpoint['unsupported_reason'] ?: 'Manual validation recommended.',
                    ],
                ];
            })->values()->all(),
        ];
    }

    private function buildRequestDescription(array $endpoint): string
    {
        $authNote = $endpoint['requires_auth']
            ? 'Requires bearer token.'
            : 'Does not require bearer token.';

        return trim($authNote . ' Sample request is aligned with the in-app company API tester.');
    }

    private function buildTokenResponses(): array
    {
        return [
            $this->makeJsonResponse(
                'Token Generated',
                'OK',
                200,
                [
                    'success' => true,
                    'message' => 'Token generated successfully',
                    'data' => [
                        'access_token' => '1|sample-token',
                        'token_type' => 'Bearer',
                    ],
                ]
            ),
            $this->makeJsonResponse(
                'Validation Failed',
                'Unprocessable Entity',
                422,
                [
                    'message' => 'The given data was invalid.',
                    'errors' => [
                        'client_key' => ['The client key field is required.'],
                        'client_secret' => ['The client secret field is required.'],
                    ],
                ]
            ),
            $this->makeJsonResponse(
                'Invalid Credentials',
                'Unauthorized',
                401,
                [
                    'success' => false,
                    'message' => 'Invalid API credentials',
                    'data' => null,
                ]
            ),
            $this->makeJsonResponse(
                'Inactive Account',
                'Forbidden',
                403,
                [
                    'success' => false,
                    'message' => 'Account is inactive. Please contact support.',
                    'data' => null,
                ]
            ),
        ];
    }

    private function buildOcrHealthResponses(): array
    {
        return [
            $this->makeJsonResponse(
                'OCR Healthy',
                'OK',
                200,
                [
                    'success' => true,
                    'message' => 'OCR service is healthy.',
                    'data' => [
                        'status' => 'up',
                    ],
                ]
            ),
            $this->makeJsonResponse(
                'OCR Service Error',
                'Service Unavailable',
                503,
                [
                    'success' => false,
                    'message' => 'OCR service is currently unavailable.',
                    'data' => null,
                ]
            ),
        ];
    }

    private function buildStandardResponses(array $endpoint): array
    {
        $responses = [
            $this->makeJsonResponse(
                'Success',
                'OK',
                200,
                [
                    'success' => true,
                    'message' => 'Request completed successfully.',
                    'data' => [
                        'endpoint' => $endpoint['uri'],
                        'method' => $endpoint['method'],
                        'sample' => 'Example response payload for demo purposes.',
                    ],
                ]
            ),
        ];

        if ($endpoint['requires_auth']) {
            $responses[] = $this->makeJsonResponse(
                'Unauthenticated',
                'Unauthorized',
                401,
                [
                    'status' => false,
                    'message' => 'Unauthenticated.',
                ]
            );

            $responses[] = $this->makeJsonResponse(
                'No Active Subscription',
                'Payment Required',
                402,
                [
                    'status' => false,
                    'message' => 'Payment Required. You do not have an active subscription.',
                ]
            );

            $responses[] = $this->makeJsonResponse(
                'Credits Exhausted',
                'Payment Required',
                402,
                [
                    'status' => false,
                    'message' => 'API credits exhausted. Please upgrade your plan or wait for the next month for credit refresh.',
                ]
            );

            $responses[] = $this->makeJsonResponse(
                'Access Denied',
                'Forbidden',
                403,
                [
                    'success' => false,
                    'message' => 'Your current plan does not include this API category.',
                    'data' => null,
                ]
            );
        }

        if (str_contains($endpoint['uri'], '{')) {
            $responses[] = $this->makeJsonResponse(
                'Resource Not Found',
                'Not Found',
                404,
                [
                    'success' => false,
                    'message' => 'Requested resource was not found.',
                    'data' => null,
                ]
            );
        }

        return $responses;
    }

    private function makeJsonResponse(string $name, string $status, int $code, array $body): array
    {
        return [
            'name' => $name,
            'status' => $status,
            'code' => $code,
            '_postman_previewlanguage' => 'json',
            'body' => json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        ];
    }

    private function humanizeRequestName(array $endpoint): string
    {
        $path = str_replace('api/v1/', '', $endpoint['uri']);
        $segments = array_values(array_filter(explode('/', $path)));
        $method = strtoupper($endpoint['method']);
        $normalizedCategory = $this->normalizedCategory($endpoint['category']);
        $baseResource = $segments[0] ?? '';
        $detailResource = Str::singular($this->displayResourceLabel($baseResource));

        if ($segments === ['auth', 'token']) {
            return 'Generate Access Token';
        }

        if ($segments === ['address', 'validate']) {
            return 'Validate Address by Pincode';
        }

        if ($segments === ['address', 'autocomplete']) {
            return 'Autocomplete Address';
        }

        if ($segments === ['currency', 'convert']) {
            return 'Convert Currency';
        }

        if ($segments === ['ocr', 'health']) {
            return 'Check OCR Health';
        }

        if ($segments === ['ocr', 'extract']) {
            return 'Extract OCR Data';
        }

        if ($segments === ['market', 'indices']) {
            return 'List Market Indices';
        }

        if ($segments === ['market', 'snapshot']) {
            return 'Get Market Snapshot';
        }

        if ($segments === ['market', 'quote', '{symbol}']) {
            return 'Get Market Quote by Symbol';
        }

        if ($segments === ['market', 'stocks']) {
            return 'List Market Stocks';
        }

        if ($segments === ['market', 'heatmap']) {
            return 'Get Market Heatmap';
        }

        if ($segments === ['market', 'breadth']) {
            return 'Get Market Breadth';
        }

        if ($segments === ['geospatial', 'statistics']) {
            return 'Get Geospatial Statistics';
        }

        if ($segments === ['geospatial', 'distance']) {
            return 'Calculate Distance Between Coordinates';
        }

        if ($segments === ['geospatial', 'geocode']) {
            return 'Reverse Geocode Coordinates';
        }

        if ($segments === ['geospatial', 'boundary']) {
            return 'Find Locations in Boundary';
        }

        if ($segments === ['geospatial', 'cluster']) {
            return 'Cluster Nearby Locations';
        }

        if ($segments === ['geospatial', 'nearby']) {
            return 'Find Nearby Locations';
        }

        if ($segments === ['user', 'usage']) {
            return 'Get API Usage Summary';
        }

        if ($segments === ['user', 'usage-breakdown']) {
            return 'Get API Usage Breakdown';
        }

        if ($segments === ['user', 'usage-history']) {
            return 'Get API Usage History';
        }

        if ($segments === ['mf', 'list']) {
            return 'List Mutual Funds';
        }

        if ($segments === ['mf', 'search']) {
            return 'Search Mutual Funds';
        }

        if ($segments === ['mf', 'compare']) {
            return 'Compare Mutual Funds';
        }

        if ($segments === ['mf', 'filters']) {
            return 'Get Mutual Fund Filters';
        }

        if ($segments === ['mf', 'details', '{isin}']) {
            return 'Get Mutual Fund Details';
        }

        if ($segments === ['mf', 'history', '{isin}']) {
            return 'Get Mutual Fund History';
        }

        if ($segments === ['mf', '{isin}', 'similar-funds']) {
            return 'Get Similar Mutual Funds';
        }

        if (($segments[1] ?? null) === 'analysis' && isset($segments[2])) {
            return Str::singular($this->displayResourceLabel($segments[0])) . ' Analysis: ' . $this->labelFromSlug($segments[2]);
        }

        if (($segments[1] ?? null) === 'filter' && isset($segments[2])) {
            return 'Filter ' . $this->displayResourceLabel($segments[0]) . ' by ' . $this->labelFromSlug($segments[2]);
        }

        if (($segments[1] ?? null) === 'search') {
            return 'Search ' . $this->displayResourceLabel($segments[0]);
        }

        if (($segments[1] ?? null) === 'nearby') {
            return 'Find Nearby ' . $this->displayResourceLabel($segments[0]);
        }

        if (($segments[1] ?? null) === 'coverage') {
            return 'Get ' . Str::singular($this->displayResourceLabel($segments[0])) . ' Coverage';
        }

        if (($segments[1] ?? null) === 'performance') {
            return 'Get ' . Str::singular($this->displayResourceLabel($segments[0])) . ' Performance';
        }

        if (($segments[1] ?? null) === 'returns') {
            return 'Get ' . Str::singular($this->displayResourceLabel($segments[0])) . ' Returns';
        }

        if (($segments[1] ?? null) === 'top') {
            return 'List Top ' . $this->displayResourceLabel($segments[0]);
        }

        if ($method === 'POST' && count($segments) === 1) {
            return 'Create ' . Str::singular($this->displayResourceLabel($normalizedCategory));
        }

        if ($method === 'GET' && count($segments) === 1) {
            return 'List ' . $this->displayResourceLabel($normalizedCategory);
        }

        if ($method === 'GET' && count($segments) === 2 && str_starts_with($segments[1], '{')) {
            return 'Get ' . $detailResource . ' Details';
        }

        if ($method === 'GET' && count($segments) >= 3 && str_starts_with($segments[1], '{')) {
            return match ($segments[2]) {
                'banks' => 'List ' . $detailResource . ' Banks',
                'branches' => 'List ' . $detailResource . ' Branches',
                'states' => 'Get ' . $detailResource . ' States',
                'cities' => 'Get ' . $detailResource . ' Cities',
                'neighbors' => 'Get ' . $detailResource . ' Neighbors',
                'timezones' => 'Get ' . $detailResource . ' Timezones',
                'history' => 'Get ' . $detailResource . ' History',
                'metrics' => 'Get ' . $detailResource . ' Metrics',
                'valuation' => 'Get ' . $detailResource . ' Valuation',
                'valuation-history' => 'Get ' . $detailResource . ' Valuation History',
                'coverage' => 'Get ' . $detailResource . ' Coverage',
                'swift-branches' => 'List ' . $detailResource . ' SWIFT Branches',
                'activity-metrics' => 'Get ' . $detailResource . ' Activity Metrics',
                'dual-exchange' => 'Get ' . $detailResource . ' Dual Exchange Data',
                'ohlc' => 'Get ' . $detailResource . ' OHLC Data',
                'peers' => 'Get ' . $detailResource . ' Peers',
                'economic-summary' => 'Get ' . $detailResource . ' Economic Summary',
                default => 'Get ' . $detailResource . ' ' . $this->labelFromSlug($segments[2]),
            };
        }

        if ($method === 'GET' && count($segments) === 2 && ! str_starts_with($segments[1], '{')) {
            return match ($segments[0]) {
                'user' => 'Get ' . $this->labelFromSlug($segments[1]),
                'market' => 'Get ' . $this->labelFromSlug($segments[1]),
                default => 'Get ' . Str::singular($this->displayResourceLabel($segments[0])) . ' ' . $this->labelFromSlug($segments[1]),
            };
        }

        if ($method === 'GET' && count($segments) >= 3 && ! str_starts_with($segments[1], '{')) {
            $prefix = match ($segments[0]) {
                'countries', 'country' => 'Country',
                'equities', 'equity' => 'Equity',
                'indices' => 'Index',
                'market' => 'Market',
                default => Str::singular($this->displayResourceLabel($segments[0])),
            };

            return $prefix . ': ' . collect(array_slice($segments, 1))
                ->map(fn (string $segment) => $this->labelFromSlug($segment))
                ->implode(' ');
        }

        return Str::title(str_replace(['/', '-', '{', '}'], [' ', ' ', '', ''], $path));
    }

    private function categoryFamilyKey(string $category): string
    {
        return match ($this->normalizedCategory($category)) {
            'Authentication' => 'authentication',
            'User & Account' => 'user-account',
            'OCR' => 'ocr',
            'Webhooks' => 'webhooks',
            'Countries', 'States', 'Cities', 'Regions', 'Sub-Regions', 'Timezones', 'Address', 'Geospatial', 'Pincodes' => 'geography-address',
            'Banks', 'Currency' => 'banking-currency',
            'Equities', 'Indices', 'Mutual Funds', 'Market' => 'market-data',
            default => 'other',
        };
    }

    private function categoryFamilyConfig(string $familyKey): array
    {
        return match ($familyKey) {
            'geography-address' => [
                'order' => 1,
                'label' => 'Geography & Address',
                'description' => 'Country, state, city, pincode, timezone, address, and geospatial APIs.',
            ],
            'banking-currency' => [
                'order' => 2,
                'label' => 'Banking & Currency',
                'description' => 'Bank coverage, branch lookup, and currency conversion APIs.',
            ],
            'market-data' => [
                'order' => 3,
                'label' => 'Market Data',
                'description' => 'Equities, indices, mutual funds, and market overview APIs.',
            ],
            'user-account' => [
                'order' => 4,
                'label' => 'User & Account',
                'description' => 'Profile and customer account APIs.',
            ],
            'ocr' => [
                'order' => 5,
                'label' => 'OCR',
                'description' => 'OCR status and extraction APIs.',
            ],
            'webhooks' => [
                'order' => 6,
                'label' => 'Webhooks',
                'description' => 'Webhook endpoints for integrations and event handling.',
            ],
            default => [
                'order' => 7,
                'label' => 'Other APIs',
                'description' => 'Additional APIs grouped for completeness.',
            ],
        };
    }

    private function normalizedCategory(string $category): string
    {
        return match (strtolower($category)) {
            'auth' => 'Authentication',
            'user' => 'User & Account',
            'ocr' => 'OCR',
            'webhooks' => 'Webhooks',
            'country', 'countries' => 'Countries',
            'state', 'states' => 'States',
            'city', 'cities' => 'Cities',
            'region', 'regions' => 'Regions',
            'sub-region', 'sub-regions' => 'Sub-Regions',
            'timezone', 'timezones' => 'Timezones',
            'address' => 'Address',
            'geospatial' => 'Geospatial',
            'pincode', 'pincodes' => 'Pincodes',
            'bank', 'banks', 'branch' => 'Banks',
            'currency' => 'Currency',
            'equity', 'equities' => 'Equities',
            'indices', 'index' => 'Indices',
            'mf' => 'Mutual Funds',
            'market' => 'Market',
            default => Str::title($category),
        };
    }

    private function displayResourceLabel(string $value): string
    {
        $normalized = $this->normalizedCategory(Str::title(str_replace('-', ' ', $value)));

        return $normalized === Str::title(str_replace('-', ' ', $value))
            ? $this->labelFromSlug($value)
            : $normalized;
    }

    private function requestSortWeight(array $endpoint): int
    {
        $path = str_replace('api/v1/', '', $endpoint['uri']);

        return match (true) {
            $path === 'auth/token' => 1,
            str_contains($path, '/search') => 2,
            str_contains($path, '/autocomplete') => 3,
            str_contains($path, '/validate') => 4,
            str_contains($path, '/convert') => 5,
            str_contains($path, '/snapshot') => 6,
            str_contains($path, '/quote/') => 7,
            str_contains($path, '{') => 20,
            default => 10,
        };
    }

    private function labelFromSlug(string $slug): string
    {
        $slug = str_replace(['{', '}'], '', $slug);

        return Str::of($slug)
            ->replace('-', ' ')
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    private function shouldExcludeFromSalesCollection(string $uri): bool
    {
        return in_array($uri, [
            'api/v1/auth/token',
            'api/v1/country/{country}',
            'api/v1/country/{country}/banks',
            'api/v1/country/{country}/cities',
            'api/v1/country/{country}/neighbors',
            'api/v1/country/{country}/states',
            'api/v1/country/{country}/timezones',
            'api/v1/state/{state}',
            'api/v1/state/{state}/banks',
            'api/v1/state/{state}/cities',
            'api/v1/city/{city}',
            'api/v1/city/{city}/banks',
            'api/v1/bank/{bank}/branches',
            'api/v1/bank/{bank}/coverage',
        ], true);
    }
}
