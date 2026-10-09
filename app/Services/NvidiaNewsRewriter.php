<?php

namespace App\Services;

use App\Models\MarketNews;
use RuntimeException;

class NvidiaNewsRewriter extends GroqNewsRewriter
{
    /** @return array{title: string, summary: string} */
    public function rewrite(MarketNews $news): array
    {
        $settings = (array) config('services.nvidia', []);
        if (trim((string) ($settings['api_key'] ?? '')) === '') {
            throw new RuntimeException('NVIDIA_API_KEY is not configured.');
        }

        // Reuse the mature editorial validation and retry pipeline while
        // selecting NVIDIA NIM as the actual provider for every model call.
        config(['services.groq' => array_merge($settings, [
            'provider' => 'NVIDIA',
        ])]);

        return parent::rewrite($news);
    }
}
