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
            ['NASA Technology', 'rss', 'https://www.nasa.gov', 'https://www.nasa.gov/technology/feed/', null, 'https://www.nasa.gov/nasa-brand-center/images-and-media/', 'Original NASA feed text for factual editorial adaptations with attribution. No endorsement, third-party text, images or logos.'],
            ['Mozilla Blog', 'rss', 'https://blog.mozilla.org', 'https://blog.mozilla.org/en/feed/', null, 'https://www.mozilla.org/en-US/foundation/licensing/website-content/', 'Original feed text and adaptations under CC BY-SA 3.0 or later, unless otherwise indicated. Attribute Mozilla and author, link the original and licence, mark changes and retain ShareAlike. No images or trademarks.'],
            ['Fedora Magazine', 'rss', 'https://fedoramagazine.org', 'https://fedoramagazine.org/feed/', null, 'https://fedoramagazine.org/terms-and-conditions/', 'Original text under CC BY-SA 4.0 unless marked otherwise. Attribute By [author] from Fedora Magazine, link the original and licence, indicate adaptations and retain ShareAlike. No images, logos or separately restricted content.'],
            ['Electronic Frontier Foundation', 'rss', 'https://www.eff.org', 'https://www.eff.org/rss/updates.xml', null, 'https://www.eff.org/copyright', 'Original EFF text under CC BY 4.0 unless marked otherwise. Attribute EFF and author, link original and licence, and mark adaptations. Full article text is supplied in RSS description. No images or third-party restricted material.'],
            ['The Document Foundation', 'rss', 'https://blog.documentfoundation.org', 'https://blog.documentfoundation.org/feed/', null, 'https://blog.documentfoundation.org/#copyright', 'Original blog text under CC BY-SA 3.0 unless marked otherwise. Attribute The Document Foundation and author, link original and licence, mark adaptations and retain ShareAlike. No images, trademarks or separately restricted content.'],
            ['Wikimedia Diff Technology', 'rss', 'https://diff.wikimedia.org', 'https://diff.wikimedia.org/category/technology/feed/', null, 'https://diff.wikimedia.org/editorial-guidelines/', 'Original Diff article text under CC BY-SA 4.0. Attribute each author and Diff, link to the original and licence, and retain ShareAlike. Exclude images and separately credited third-party material.'],
            ['NIST Cybersecurity Insights', 'rss', 'https://www.nist.gov', 'https://www.nist.gov/blogs/cybersecurity-insights/rss.xml', null, 'https://www.nist.gov/coo/nist-rss-feeds', 'NIST provides its RSS feed for syndication. Import headlines and links for editorial monitoring. The feed descriptions are excerpts.'],
            ['OpenAI Codex Releases', 'rss', 'https://github.com/openai/codex', 'https://github.com/openai/codex/releases.atom', null, 'https://docs.github.com/en/site-policy/github-terms/github-terms-of-service', 'Monitor official release identifiers, titles, timestamps and links for editorial reference. Release-note text is excluded.'],
            ['Anthropic Claude Code Releases', 'rss', 'https://github.com/anthropics/claude-code', 'https://github.com/anthropics/claude-code/releases.atom', null, 'https://docs.github.com/en/site-policy/github-terms/github-terms-of-service', 'Monitor official release identifiers, titles, timestamps and links for editorial reference. Release-note text is excluded.'],
        ];
        $licenses = [
            'NASA Technology' => ['NASA editorial-use guidelines', 'https://www.nasa.gov/nasa-brand-center/images-and-media/'],
            'Mozilla Blog' => ['CC BY-SA 3.0 or later', 'https://creativecommons.org/licenses/by-sa/3.0/'],
            'Fedora Magazine' => ['CC BY-SA 4.0', 'https://creativecommons.org/licenses/by-sa/4.0/'],
            'Electronic Frontier Foundation' => ['CC BY 4.0', 'https://creativecommons.org/licenses/by/4.0/'],
            'The Document Foundation' => ['CC BY-SA 3.0', 'https://creativecommons.org/licenses/by-sa/3.0/'],
            'Wikimedia Diff Technology' => ['CC BY-SA 4.0', 'https://creativecommons.org/licenses/by-sa/4.0/'],
        ];
        $metadataSources = ['OpenAI Codex Releases', 'Anthropic Claude Code Releases', 'NIST Cybersecurity Insights'];
        $created = 0;
        foreach ($sources as [$name, $type, $base, $feed, $api, $reference, $notes]) {
            $fullText = isset($licenses[$name]);
            $enabled = $fullText || in_array($name, $metadataSources, true);
            $source = NewsSource::firstOrCreate(['name' => $name], ['source_type' => $type, 'base_url' => $base, 'feed_url' => $feed, 'api_endpoint' => $api,
                'permission_reference' => $reference, 'permission_notes' => $notes, 'display_mode' => $enabled ? 'headline' : 'disabled',
                'permissions_verified_at' => $enabled ? now('UTC') : null, 'is_active' => $enabled, 'allow_ai_rewrite' => $fullText, 'allow_feed_content' => $fullText,
                'feed_content_field' => $name === 'Electronic Frontier Foundation' ? 'description' : 'content',
                'license_name' => $licenses[$name][0] ?? null, 'license_url' => $licenses[$name][1] ?? null]);
            if ($source->wasRecentlyCreated) { $created++; }
        }
        if (trim((string) config('technology_news.newsapi.key')) !== '') {
            $bulk = NewsSource::firstOrCreate(['name' => 'NewsAPI Technology Discovery'], [
                'source_type' => 'newsapi',
                'base_url' => 'https://newsapi.org',
                'api_endpoint' => 'https://newsapi.org/v2/everything',
                'permission_reference' => 'https://newsapi.org/terms',
                'permission_notes' => 'Headline, publisher attribution, publication time and link for internal discovery.',
                'display_mode' => 'headline',
                'permissions_verified_at' => now('UTC'),
                'is_active' => true,
                'sync_interval_minutes' => 60,
                'allow_ai_rewrite' => false,
                'allow_feed_content' => false,
            ]);
            $created += (int) $bulk->wasRecentlyCreated;
        }
        \App\Services\TechnologyNews\NewsCache::invalidate();
        $this->info('Categories installed; '.$created.' new sources added. Existing administrator choices were preserved.');
        return 0;
    }
}
