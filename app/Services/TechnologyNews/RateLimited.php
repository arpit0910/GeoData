<?php

namespace App\Services\TechnologyNews;

class RateLimited extends \RuntimeException
{
    public $seconds;
    public function __construct(int $seconds)
    {
        $this->seconds = min(604800, max(1, $seconds));
        parent::__construct('Provider rate limited; retry deferred according to Retry-After.');
    }
}
