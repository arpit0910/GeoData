<?php

namespace Tests\Feature;

use App\Exceptions\GroqRateLimitException;
use App\Models\MarketNews;
use App\Services\GroqNewsRewriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class MarketNewsEditorialWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_rewrite_waits_for_admin_approval_with_low_temperature(): void
    {
        $this->configureGroq();
        $sourceBody = 'The company reported quarterly revenue of Rs 100 crore, unchanged from the previous period. Management said demand remained stable across its main business segments. Operating conditions were also broadly consistent with the preceding quarter, according to the company update.';

        Http::fake(function (Request $request) use ($sourceBody) {
            if ($request->url() === 'https://upstox.com/news/test-article') {
                return Http::response(
                    '<html><head><script type="application/ld+json">'.json_encode([
                        '@type' => 'NewsArticle',
                        'articleBody' => $sourceBody,
                    ]).'</script></head></html>'
                );
            }

            $instruction = (string) data_get($request->data(), 'messages.0.content');
            if (str_contains($instruction, 'independent financial-news fact checker')) {
                return $this->groqResponse([
                    'intent_preserved' => true,
                    'facts_preserved' => true,
                    'tone_preserved' => true,
                    'attributions_preserved' => true,
                    'issues' => [],
                ]);
            }

            return $this->groqResponse([
                'title' => 'Company reports flat quarterly revenue at Rs 100 crore',
                'paragraph_1' => 'The company reported quarterly revenue of Rs 100 crore, unchanged from the previous period. Management said demand remained stable across its principal business segments during the quarter.',
                'paragraph_2' => 'The company update added that operating conditions were broadly consistent with those recorded in the preceding quarter, maintaining the pattern described for the previous period.',
            ]);
        });

        $news = $this->createNews($sourceBody);
        app(GroqNewsRewriter::class)->rewrite($news);

        $news->refresh();
        $this->assertSame(MarketNews::STATUS_READY, $news->editorial_status);
        $this->assertFalse($news->is_published);
        $this->assertSame('Company reports flat quarterly revenue at Rs 100 crore', $news->title);
        $this->assertSame('groq-test-model', $news->rewrite_model);
        $this->assertSame(7, $news->rewrite_version);
        $this->assertSame($sourceBody, $news->original_content);
        $this->get('/market/news')
            ->assertOk()
            ->assertDontSee('Company reports flat quarterly revenue at Rs 100 crore')
            ->assertDontSee('Company revenue flat');

        Http::assertSent(function (Request $request): bool {
            return $request->hasHeader('Authorization', 'Bearer test-groq-key')
                && data_get($request->data(), 'temperature') === 0.1
                && data_get($request->data(), 'response_format.type') === 'json_schema'
                && data_get($request->data(), 'response_format.json_schema.strict') === true
                && in_array('paragraph_1', data_get($request->data(), 'response_format.json_schema.schema.required', []), true)
                && in_array('paragraph_2', data_get($request->data(), 'response_format.json_schema.schema.required', []), true)
                && data_get($request->data(), 'response_format.json_schema.schema.properties.paragraphs') === null
                && str_contains((string) data_get($request->data(), 'messages.1.content'), '<source_summary>')
                && str_contains((string) data_get($request->data(), 'messages.1.content'), 'Revenue was Rs 100 crore')
                && str_contains((string) data_get($request->data(), 'messages.1.content'), 'Management said demand remained stable');
        });
        Http::assertSent(function (Request $request): bool {
            return data_get($request->data(), 'temperature') === 0
                && str_contains(
                    (string) data_get($request->data(), 'messages.0.content'),
                    'independent financial-news fact checker'
                );
        });
    }

    public function test_semantically_rejected_rewrite_remains_private(): void
    {
        $this->configureGroq();
        $sourceBody = 'The company reported quarterly revenue of Rs 100 crore, unchanged from the previous period. Management said demand remained stable across its main business segments. Operating conditions were also broadly consistent with the preceding quarter, according to the company update.';

        Http::fake(function (Request $request) use ($sourceBody) {
            if ($request->url() === 'https://upstox.com/news/test-article') {
                return Http::response(
                    '<script type="application/ld+json">'.json_encode([
                        '@type' => 'NewsArticle',
                        'articleBody' => $sourceBody,
                    ]).'</script>'
                );
            }

            $instruction = (string) data_get($request->data(), 'messages.0.content');
            if (str_contains($instruction, 'independent financial-news fact checker')) {
                return $this->groqResponse([
                    'intent_preserved' => false,
                    'facts_preserved' => true,
                    'tone_preserved' => false,
                    'attributions_preserved' => true,
                    'issues' => ['The draft makes the neutral source sound optimistic.'],
                ]);
            }

            return $this->groqResponse([
                'title' => 'Company reports flat quarterly revenue at Rs 100 crore',
                'paragraphs' => [
                    'The company reported quarterly revenue of Rs 100 crore, unchanged from the previous period.',
                    'Management said demand remained stable across its principal business segments during the quarter.',
                    'The company update added that operating conditions were broadly consistent with those recorded in the preceding quarter.',
                ],
            ]);
        });

        $news = $this->createNews($sourceBody);

        try {
            app(GroqNewsRewriter::class)->rewrite($news);
            $this->fail('A rewrite that changes intent should be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('neutral source sound optimistic', $exception->getMessage());
        }

        $news->refresh();
        $this->assertSame(MarketNews::STATUS_FAILED, $news->editorial_status);
        $this->assertFalse($news->is_published);
        $this->get('/market/news')
            ->assertOk()
            ->assertDontSee('Company reports flat quarterly revenue at Rs 100 crore');
        $this->assertTrue(Route::has('admin.market-news.index'));
    }

    public function test_complete_article_just_below_generation_target_is_accepted(): void
    {
        $rewriter = app(GroqNewsRewriter::class);
        $qualityCheck = new \ReflectionMethod($rewriter, 'assertEditorialQuality');
        $source = trim(str_repeat('source ', 532));
        $paragraphWordCounts = [79, 79, 78, 78, 78];
        $summary = collect($paragraphWordCounts)
            ->map(fn (int $words) => trim(str_repeat('draft ', $words)))
            ->implode("\n\n");

        $this->assertSame(392, str_word_count($summary));

        $qualityCheck->invoke(
            $rewriter,
            'Company reports detailed quarterly update across its operations',
            $summary,
            $source
        );

        $this->addToAssertionCount(1);
    }

    public function test_failed_checks_are_sent_back_to_groq_for_a_corrected_draft(): void
    {
        $this->configureGroq();
        $sourceBody = 'The company reported quarterly revenue of Rs 100 crore, unchanged from the previous period. Management said demand remained stable across its main business segments. Operating conditions were also broadly consistent with the preceding quarter, according to the company update.';
        $rewriteAttempts = 0;

        Http::fake(function (Request $request) use ($sourceBody, &$rewriteAttempts) {
            if ($request->url() === 'https://upstox.com/news/test-article') {
                return Http::response('<script type="application/ld+json">'.json_encode([
                    '@type' => 'NewsArticle',
                    'articleBody' => $sourceBody,
                ]).'</script>');
            }

            if (str_contains((string) data_get($request->data(), 'messages.0.content'), 'independent financial-news fact checker')) {
                return $this->groqResponse([
                    'intent_preserved' => true,
                    'facts_preserved' => true,
                    'tone_preserved' => true,
                    'attributions_preserved' => true,
                    'issues' => [],
                ]);
            }

            $rewriteAttempts++;
            if ($rewriteAttempts === 1) {
                return $this->groqResponse([
                    'title' => 'Company reports flat quarterly revenue at Rs 200 crore',
                    'paragraphs' => [
                        'The company reported quarterly revenue of Rs 200 crore, unchanged from the previous period.',
                        'Management said demand remained stable across its principal business segments during the quarter.',
                        'The company update added that operating conditions were broadly consistent with those recorded in the preceding quarter.',
                    ],
                ]);
            }

            return $this->groqResponse([
                'title' => 'Company reports flat quarterly revenue at Rs 100 crore',
                'paragraphs' => [
                    'The company reported quarterly revenue of Rs 100 crore, unchanged from the previous period.',
                    'Management said demand remained stable across its principal business segments during the quarter.',
                    'The company update added that operating conditions were broadly consistent with those recorded in the preceding quarter.',
                ],
            ]);
        });

        $news = $this->createNews($sourceBody);
        app(GroqNewsRewriter::class)->rewrite($news);

        $this->assertSame(2, $rewriteAttempts);
        $this->assertSame(MarketNews::STATUS_READY, $news->refresh()->editorial_status);
        Http::assertSent(fn (Request $request): bool => collect(data_get($request->data(), 'messages', []))
            ->pluck('content')
            ->contains(fn ($content) => str_contains(
                (string) $content,
                'Numerical fact check failed (missing source numbers: 100; unsupported draft numbers: 200)'
            )));
    }

    public function test_transient_capacity_error_uses_the_fallback_model(): void
    {
        $this->configureGroq();
        config([
            'services.groq.fallback_models' => 'groq-fallback-model',
            'services.groq.model_strategy' => 'primary',
        ]);
        $sourceBody = 'The company reported quarterly revenue of Rs 100 crore, unchanged from the previous period. Management said demand remained stable across its main business segments. Operating conditions were also broadly consistent with the preceding quarter, according to the company update.';
        $groqCalls = 0;

        Http::fake(function (Request $request) use ($sourceBody, &$groqCalls) {
            if ($request->url() === 'https://upstox.com/news/test-article') {
                return Http::response(
                    '<script type="application/ld+json">'.json_encode([
                        '@type' => 'NewsArticle',
                        'articleBody' => $sourceBody,
                    ]).'</script>'
                );
            }

            $groqCalls++;
            if ($groqCalls === 1) {
                return Http::response(['error' => ['message' => 'Model capacity is temporarily unavailable']], 503);
            }

            $instruction = (string) data_get($request->data(), 'messages.0.content');
            if (str_contains($instruction, 'independent financial-news fact checker')) {
                return $this->groqResponse([
                    'intent_preserved' => true,
                    'facts_preserved' => true,
                    'tone_preserved' => true,
                    'attributions_preserved' => true,
                    'issues' => [],
                ]);
            }

            return $this->groqResponse([
                'title' => 'Company reports flat quarterly revenue at Rs 100 crore',
                'paragraphs' => [
                    'The company reported quarterly revenue of Rs 100 crore, unchanged from the previous period.',
                    'Management said demand remained stable across its principal business segments during the quarter.',
                    'The company update added that operating conditions were broadly consistent with those recorded in the preceding quarter.',
                ],
            ]);
        });

        $news = $this->createNews($sourceBody);
        app(GroqNewsRewriter::class)->rewrite($news);

        $this->assertSame(MarketNews::STATUS_READY, $news->refresh()->editorial_status);
        $this->assertSame('groq-fallback-model', $news->rewrite_model);
        $this->assertSame(3, $groqCalls);
    }

    public function test_calls_rotate_across_all_configured_models(): void
    {
        $this->configureGroq();
        config([
            'services.groq.model' => 'openai/gpt-oss-rotation-a',
            'services.groq.fallback_models' => 'qwen/rotation-b,openai/gpt-oss-rotation-c,llama-rotation-d',
            'services.groq.model_strategy' => 'round_robin',
            'services.groq.strict_json_models' => 'openai/gpt-oss-rotation-a,qwen/rotation-b,openai/gpt-oss-rotation-c',
        ]);
        $sourceBody = 'The company reported quarterly revenue of Rs 100 crore, unchanged from the previous period. Management said demand remained stable across its main business segments. Operating conditions were also broadly consistent with the preceding quarter, according to the company update.';
        $models = [];
        $calls = [];

        Http::fake(function (Request $request) use ($sourceBody, &$models, &$calls) {
            if ($request->url() === 'https://upstox.com/news/test-article') {
                return Http::response('<script type="application/ld+json">'.json_encode([
                    '@type' => 'NewsArticle',
                    'articleBody' => $sourceBody,
                ]).'</script>');
            }

            $models[] = (string) data_get($request->data(), 'model');
            $isVerification = str_contains((string) data_get($request->data(), 'messages.0.content'), 'independent financial-news fact checker');
            $calls[] = [
                'model' => (string) data_get($request->data(), 'model'),
                'max_tokens' => (int) data_get($request->data(), 'max_completion_tokens'),
                'verification' => $isVerification,
                'response_format' => (string) data_get($request->data(), 'response_format.type'),
                'json_instruction' => collect(data_get($request->data(), 'messages', []))
                    ->pluck('content')
                    ->contains(fn ($content) => str_contains((string) $content, 'Return only one valid JSON object')),
            ];
            if ($isVerification) {
                return $this->groqResponse([
                    'intent_preserved' => true,
                    'facts_preserved' => true,
                    'tone_preserved' => true,
                    'attributions_preserved' => true,
                    'issues' => [],
                ]);
            }

            return $this->groqResponse([
                'title' => 'Company reports flat quarterly revenue at Rs 100 crore',
                'paragraphs' => [
                    'The company reported quarterly revenue of Rs 100 crore, unchanged from the previous period.',
                    'Management said demand remained stable across its principal business segments during the quarter.',
                    'The company update added that operating conditions were broadly consistent with those recorded in the preceding quarter.',
                ],
            ]);
        });

        $news = $this->createNews($sourceBody);
        app(GroqNewsRewriter::class)->rewrite($news);
        app(GroqNewsRewriter::class)->rewrite($news->refresh());

        $pool = [
            'openai/gpt-oss-rotation-a',
            'qwen/rotation-b',
            'openai/gpt-oss-rotation-c',
            'llama-rotation-d',
        ];
        $this->assertCount(4, $models);
        foreach (range(1, 3) as $index) {
            $previousPosition = array_search($models[$index - 1], $pool, true);
            $this->assertSame($pool[($previousPosition + 1) % count($pool)], $models[$index]);
        }
        $this->assertSame($models[2], $news->refresh()->rewrite_model);
        $this->assertTrue(collect($calls)->where('verification', true)->every(
            fn (array $call) => $call['max_tokens'] <= 512
        ));
        $this->assertTrue(collect($calls)->where('model', 'qwen/rotation-b')->every(
            fn (array $call) => $call['max_tokens'] <= 900
        ));
        $llamaCall = collect($calls)->firstWhere('model', 'llama-rotation-d');
        $this->assertSame('json_object', $llamaCall['response_format']);
        $this->assertTrue($llamaCall['json_instruction']);
    }

    public function test_rate_limited_story_is_deferred_until_provider_retry_time(): void
    {
        $this->configureGroq();
        config(['services.groq.fallback_models' => '']);
        $sourceBody = 'The company reported quarterly revenue of Rs 100 crore, unchanged from the previous period. Management said demand remained stable across its main business segments. Operating conditions were also broadly consistent with the preceding quarter, according to the company update.';

        Http::fake(function (Request $request) use ($sourceBody) {
            if ($request->url() === 'https://upstox.com/news/test-article') {
                return Http::response('<script type="application/ld+json">'.json_encode([
                    '@type' => 'NewsArticle',
                    'articleBody' => $sourceBody,
                ]).'</script>');
            }

            return Http::response([
                'error' => ['message' => 'Rate limit reached. Please try again in 3m48.096s.'],
            ], 429, ['Retry-After' => '228.096']);
        });

        $news = $this->createNews($sourceBody);

        try {
            app(GroqNewsRewriter::class)->rewrite($news);
            $this->fail('The simulated quota limit should defer the story.');
        } catch (GroqRateLimitException $exception) {
            $this->assertSame(229, $exception->retryAfterSeconds);
        }

        $news->refresh();
        $this->assertSame(MarketNews::STATUS_PENDING, $news->editorial_status);
        $this->assertTrue($news->rewrite_retry_at->isFuture());
        $this->assertStringContainsString('retry automatically', $news->rewrite_error);
        Http::assertSentCount(2);

        $this->artisan('market:rewrite-news', ['--limit' => 20])
            ->expectsOutputToContain('0 generated for review, 0 deferred, 0 failed')
            ->assertSuccessful();
        Http::assertSentCount(2);
    }

    public function test_failed_regeneration_keeps_the_last_verified_article_public(): void
    {
        $this->configureGroq();
        $sourceBody = 'The company reported quarterly revenue of Rs 100 crore, unchanged from the previous period. Management said demand remained stable across its main business segments. Operating conditions were also broadly consistent with the preceding quarter, according to the company update.';
        Http::fake(function (Request $request) use ($sourceBody) {
            if ($request->url() === 'https://upstox.com/news/test-article') {
                return Http::response(
                    '<script type="application/ld+json">'.json_encode([
                        '@type' => 'NewsArticle',
                        'articleBody' => $sourceBody,
                    ]).'</script>'
                );
            }

            return Http::response(['error' => ['message' => 'Temporarily unavailable']], 503);
        });

        $news = $this->createNews($sourceBody);
        $news->forceFill([
            'title' => 'Previously verified market article remains available',
            'summary' => 'This is the last version that completed every automatic verification check.',
            'editorial_status' => MarketNews::STATUS_PUBLISHED,
            'is_published' => true,
        ])->save();

        try {
            app(GroqNewsRewriter::class)->rewrite($news);
            $this->fail('The simulated provider failure should throw an exception.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Temporarily unavailable', $exception->getMessage());
        }

        $news->refresh();
        $this->assertSame(MarketNews::STATUS_PUBLISHED, $news->editorial_status);
        $this->assertTrue($news->is_published);
        $this->assertSame('Previously verified market article remains available', $news->title);
        $this->assertNotNull($news->rewrite_error);
        $this->get('/market/news')
            ->assertOk()
            ->assertSee('Previously verified market article remains available');
    }

    public function test_unrelated_extracted_article_is_rejected_before_generation(): void
    {
        $this->configureGroq();
        Http::fake([
            'https://upstox.com/news/test-article' => Http::response(
                '<script type="application/ld+json">'.json_encode([
                    '@type' => 'NewsArticle',
                    'articleBody' => str_repeat('A football tournament discussed players, stadium conditions, coaching and international fixtures. ', 5),
                ]).'</script>'
            ),
        ]);
        $news = $this->createNews('Unused source body for this test.');

        try {
            app(GroqNewsRewriter::class)->rewrite($news);
            $this->fail('An unrelated source article should be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('does not match', $exception->getMessage());
        }

        $news->refresh();
        $this->assertSame(MarketNews::STATUS_FAILED, $news->editorial_status);
        $this->assertFalse($news->is_published);
        Http::assertSentCount(1);
    }

    private function configureGroq(): void
    {
        Cache::forget('groq-news-model-cooldown:'.sha1('groq-test-model'));
        Cache::forget('groq-news-model-cooldown:'.sha1('groq-fallback-model'));
        config([
            'services.groq.api_key' => 'test-groq-key',
            'services.groq.model' => 'groq-test-model',
            'services.groq.fallback_models' => '',
            'services.groq.verification_models' => '',
            'services.groq.strict_json_models' => 'groq-test-model,groq-fallback-model',
            'services.groq.endpoint' => 'https://groq.test/openai/v1/chat/completions',
            'services.groq.temperature' => 0.1,
            'services.groq.retry_attempts' => 4,
            'services.groq.retry_initial_delay_ms' => 0,
            'services.groq.retry_max_delay_ms' => 0,
            'services.groq.retry_jitter_ms' => 0,
            'services.groq.rewrite_max_tokens' => 2048,
            'services.groq.verification_max_tokens' => 512,
            'services.groq.qwen_max_tokens' => 900,
        ]);
    }

    private function createNews(string $sourceBody): MarketNews
    {
        return MarketNews::create([
            'original_title' => 'Company revenue flat',
            'original_summary' => 'Revenue was Rs 100 crore, unchanged quarter on quarter.',
            'source_hash' => hash('sha256', "Company revenue flat\n{$sourceBody}"),
            'title' => 'Company revenue flat',
            'summary' => 'Revenue was Rs 100 crore, unchanged quarter on quarter.',
            'source' => 'Upstox',
            'article_url' => 'https://upstox.com/news/test-article',
            'editorial_status' => MarketNews::STATUS_PENDING,
            'is_published' => false,
            'published_at' => now(),
        ]);
    }

    private function groqResponse(array $payload)
    {
        return Http::response([
            'choices' => [[
                'message' => [
                    'role' => 'assistant',
                    'content' => json_encode($payload),
                ],
                'finish_reason' => 'stop',
            ]],
        ]);
    }
}
