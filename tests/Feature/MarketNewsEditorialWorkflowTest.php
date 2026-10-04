<?php

namespace Tests\Feature;

use App\Models\MarketNews;
use App\Services\GeminiNewsRewriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class MarketNewsEditorialWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_rewrite_is_published_automatically_with_low_temperature(): void
    {
        $this->configureGemini();
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

            $instruction = (string) data_get($request->data(), 'systemInstruction.parts.0.text');
            if (str_contains($instruction, 'independent financial-news fact checker')) {
                return $this->geminiResponse([
                    'intent_preserved' => true,
                    'facts_preserved' => true,
                    'tone_preserved' => true,
                    'attributions_preserved' => true,
                    'issues' => [],
                ]);
            }

            return $this->geminiResponse([
                'title' => 'Company reports flat quarterly revenue at Rs 100 crore',
                'paragraphs' => [
                    'The company reported quarterly revenue of Rs 100 crore, unchanged from the previous period.',
                    'Management said demand remained stable across its principal business segments during the quarter.',
                    'The company update added that operating conditions were broadly consistent with those recorded in the preceding quarter.',
                ],
            ]);
        });

        $news = $this->createNews($sourceBody);
        app(GeminiNewsRewriter::class)->rewrite($news);

        $news->refresh();
        $this->assertSame(MarketNews::STATUS_PUBLISHED, $news->editorial_status);
        $this->assertTrue($news->is_published);
        $this->assertSame('Company reports flat quarterly revenue at Rs 100 crore', $news->title);
        $this->assertSame('gemini-test-model', $news->rewrite_model);
        $this->assertSame(3, $news->rewrite_version);
        $this->assertSame($sourceBody, $news->original_content);
        $this->get('/market/news')
            ->assertOk()
            ->assertSee('Company reports flat quarterly revenue at Rs 100 crore')
            ->assertDontSee('Company revenue flat');

        Http::assertSent(function (Request $request): bool {
            return $request->hasHeader('x-goog-api-key', 'test-gemini-key')
                && data_get($request->data(), 'generationConfig.temperature') === 0.1
                && data_get($request->data(), 'generationConfig.responseMimeType') === 'application/json'
                && str_contains((string) data_get($request->data(), 'contents.0.parts.0.text'), 'Management said demand remained stable');
        });
        Http::assertSent(function (Request $request): bool {
            return data_get($request->data(), 'generationConfig.temperature') === 0
                && str_contains(
                    (string) data_get($request->data(), 'systemInstruction.parts.0.text'),
                    'independent financial-news fact checker'
                );
        });
    }

    public function test_semantically_rejected_rewrite_remains_private_without_a_review_system(): void
    {
        $this->configureGemini();
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

            $instruction = (string) data_get($request->data(), 'systemInstruction.parts.0.text');
            if (str_contains($instruction, 'independent financial-news fact checker')) {
                return $this->geminiResponse([
                    'intent_preserved' => false,
                    'facts_preserved' => true,
                    'tone_preserved' => false,
                    'attributions_preserved' => true,
                    'issues' => ['The draft makes the neutral source sound optimistic.'],
                ]);
            }

            return $this->geminiResponse([
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
            app(GeminiNewsRewriter::class)->rewrite($news);
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
        $this->assertFalse(Route::has('admin.market-news.index'));
    }

    public function test_failed_regeneration_keeps_the_last_verified_article_public(): void
    {
        $this->configureGemini();
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
            app(GeminiNewsRewriter::class)->rewrite($news);
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

    private function configureGemini(): void
    {
        config([
            'services.gemini.api_key' => 'test-gemini-key',
            'services.gemini.model' => 'gemini-test-model',
            'services.gemini.fallback_models' => '',
            'services.gemini.endpoint' => 'https://gemini.test/v1beta',
            'services.gemini.temperature' => 0.1,
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

    private function geminiResponse(array $payload)
    {
        return Http::response([
            'candidates' => [[
                'content' => ['parts' => [[
                    'text' => json_encode($payload),
                ]]],
            ]],
        ]);
    }
}
