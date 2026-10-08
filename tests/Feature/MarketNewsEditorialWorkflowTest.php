<?php

namespace Tests\Feature;

use App\Models\MarketNews;
use App\Services\GroqNewsRewriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
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
                'paragraphs' => [
                    'The company reported quarterly revenue of Rs 100 crore, unchanged from the previous period.',
                    'Management said demand remained stable across its principal business segments during the quarter.',
                    'The company update added that operating conditions were broadly consistent with those recorded in the preceding quarter.',
                ],
            ]);
        });

        $news = $this->createNews($sourceBody);
        app(GroqNewsRewriter::class)->rewrite($news);

        $news->refresh();
        $this->assertSame(MarketNews::STATUS_READY, $news->editorial_status);
        $this->assertFalse($news->is_published);
        $this->assertSame('Company reports flat quarterly revenue at Rs 100 crore', $news->title);
        $this->assertSame('groq-test-model', $news->rewrite_model);
        $this->assertSame(4, $news->rewrite_version);
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

    public function test_transient_capacity_error_uses_the_fallback_model(): void
    {
        $this->configureGroq();
        config(['services.groq.fallback_models' => 'groq-fallback-model']);
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
        config([
            'services.groq.api_key' => 'test-groq-key',
            'services.groq.model' => 'groq-test-model',
            'services.groq.fallback_models' => '',
            'services.groq.endpoint' => 'https://groq.test/openai/v1/chat/completions',
            'services.groq.temperature' => 0.1,
            'services.groq.retry_attempts' => 4,
            'services.groq.retry_initial_delay_ms' => 0,
            'services.groq.retry_max_delay_ms' => 0,
            'services.groq.retry_jitter_ms' => 0,
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
