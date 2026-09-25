<?php

namespace Tests\Unit;

use App\Models\CorporateAction;
use App\Models\MarketNews;
use PHPUnit\Framework\TestCase;

class ProviderBrandPrivacyTest extends TestCase
{
    public function test_news_provider_metadata_is_not_serialized(): void
    {
        $news = new MarketNews([
            'title' => 'Market update',
            'instrument_key' => 'NSE_EQ|INE000000001',
            'source' => 'Upstox',
            'raw_data' => ['provider' => 'upstox'],
        ]);

        $json = json_encode($news, JSON_THROW_ON_ERROR);

        $this->assertStringContainsString('Market update', $json);
        $this->assertStringNotContainsStringIgnoringCase('upstox', $json);
        $this->assertStringNotContainsString('instrument_key', $json);
        $this->assertStringNotContainsString('raw_data', $json);
    }

    public function test_corporate_action_raw_provider_data_is_not_serialized(): void
    {
        $action = new CorporateAction([
            'isin' => 'INE000000001',
            'type' => 'DIVIDEND',
            'name' => 'Dividend',
            'raw_data' => ['provider' => 'upstox'],
        ]);

        $json = json_encode($action, JSON_THROW_ON_ERROR);

        $this->assertStringContainsString('Dividend', $json);
        $this->assertStringNotContainsStringIgnoringCase('upstox', $json);
        $this->assertStringNotContainsString('raw_data', $json);
    }
}
