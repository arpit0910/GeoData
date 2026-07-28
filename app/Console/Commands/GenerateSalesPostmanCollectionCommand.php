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
        return $supported
            ->groupBy('category')
            ->map(function (Collection $items, string $category) {
                return [
                    'name' => sprintf('%s. %s', str_pad((string) $this->categoryOrder($category), 2, '0', STR_PAD_LEFT), $category),
                    'item' => $items->map(fn (array $endpoint) => $this->buildRequestItem($endpoint))->values()->all(),
                ];
            })
            ->sortBy('name')
            ->values()
            ->all();
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
        $path = str_replace(['{', '}'], '', $path);

        return Str::title(str_replace(['/', '-'], [' ', ' '], $path));
    }

    private function categoryOrder(string $category): int
    {
        return match (strtolower($category)) {
            'auth' => 1,
            'ocr' => 2,
            'user' => 3,
            'regions', 'sub-regions', 'timezones', 'countries', 'states', 'cities', 'country', 'state', 'city', 'address', 'timezone', 'geospatial' => 4,
            'currency', 'banks', 'bank', 'branch', 'pincodes', 'pincode' => 5,
            'equities', 'equity', 'indices', 'mf', 'market' => 6,
            default => 7,
        };
    }
}
