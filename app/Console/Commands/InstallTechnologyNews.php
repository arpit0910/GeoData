<?php

namespace App\Console\Commands;

use App\Models\{NewsCategory, NewsSource};
use Illuminate\Console\Command;

class InstallTechnologyNews extends Command
{
    protected $signature = 'news:install';
    protected $description = 'Idempotently install technology categories and reviewed source defaults';
    public function handle(): int
    {
        $names = ['Artificial Intelligence', 'Smartphones & Gadgets', 'Software & Applications', 'Cybersecurity', 'Startups & Funding', 'Programming & Development', 'Cloud Computing', 'Data Science', 'Robotics & Automation', 'Technology Companies', 'Indian Technology', 'Global Technology', 'General Technology'];
        $slugs = array_merge(array_keys(config('technology_news.rules')), ['general-technology']);
        foreach ($names as $i => $name) { NewsCategory::firstOrCreate(['slug' => $slugs[$i]], ['name' => $name, 'sort_order' => $i]); }
        $sources = [
            ['Hacker News', 'hackernews', 'https://news.ycombinator.com', null, 'https://hacker-news.firebaseio.com/v0', 'https://github.com/HackerNews/API', 'Public API documented; repository MIT license is not a blanket license to publishers. Await metadata display approval.'],
            ['DEV Community', 'dev', 'https://dev.to', null, 'https://dev.to/api/articles?per_page=30&page=1', 'https://dev.to/terms', 'Public API access does not grant public-display rights. Obtain explicit permission before enabling.'],
            ['BBC News', 'rss', 'https://www.bbc.com', 'https://feeds.bbci.co.uk/news/technology/rss.xml', null, 'https://www.bbc.com/usingthebbc/terms/', 'Business RSS usage requires permission. Disabled pending a suitable licence.'],
            ['TechCrunch', 'rss', 'https://techcrunch.com', 'https://techcrunch.com/feed/', null, 'https://techcrunch.com/terms-of-service/', 'No commercial syndication permission verified. Disabled.'],
            ['The Verge', 'rss', 'https://www.theverge.com', 'https://www.theverge.com/rss/index.xml', null, 'https://www.voxmedia.com/legal/terms-of-use', 'No commercial syndication permission verified. Disabled.'],
            ['NASA Technology', 'rss', 'https://www.nasa.gov', 'https://www.nasa.gov/technology/feed/', null, 'https://www.nasa.gov/nasa-brand-center/images-and-media/', 'Headline and source link only, factual editorial use, NASA attribution; no endorsement, third-party text, images, or logos.'],
            ['Mozilla Blog', 'rss', 'https://blog.mozilla.org', 'https://blog.mozilla.org/en/feed/', null, 'https://www.mozilla.org/en-US/foundation/licensing/website-content/', 'Attributed headlines under CC BY-SA 3.0 or later, unless otherwise indicated. No excerpts, images or trademarks. Source text remains under its original licence.'],
        ];
        foreach ($sources as [$name, $type, $base, $feed, $api, $reference, $notes]) {
            $enabled = in_array($name, ['NASA Technology', 'Mozilla Blog'], true);
            NewsSource::firstOrCreate(['name' => $name], ['source_type' => $type, 'base_url' => $base, 'feed_url' => $feed, 'api_endpoint' => $api,
                'permission_reference' => $reference, 'permission_notes' => $notes, 'display_mode' => $enabled ? 'headline' : 'disabled',
                'permissions_verified_at' => $enabled ? now('UTC') : null, 'is_active' => $enabled, 'allow_ai_rewrite' => $enabled, 'allow_feed_content' => $enabled]);
        }
        $this->info('Categories and sources installed. Existing administrator choices were preserved.');
        return 0;
    }
}
