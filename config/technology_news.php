<?php

return [
    'user_agent' => env('NEWS_USER_AGENT', 'SetuGeo-News/1.0 (+https://setugeo.com)'),
    'limit' => 30,
    'timeout' => 15,
    'ca_bundle' => env('NEWS_CA_BUNDLE'),
    'max_bytes' => 2097152,
    'newsapi' => [
        'key' => env('TECH_NEWS_API_KEY'),
        'max_pages' => (int) env('TECH_NEWS_API_MAX_PAGES', 10),
        'query' => '"artificial intelligence" OR "generative AI" OR OpenAI OR Anthropic OR "Claude Code" OR Codex OR cybersecurity OR ransomware OR phishing OR "security patch" OR "software update" OR "developer tools" OR "tech tips" OR "technology news" OR semiconductor OR smartphone OR "cloud computing"',
    ],
    'rules' => [
        'artificial-intelligence' => ['artificial intelligence', 'machine learning', 'llm', 'openai', 'neural network', 'claude', 'claude code', 'anthropic', 'codex', 'ai agent', 'coding agent'],
        'smartphones-gadgets' => ['smartphone', 'iphone', 'android', 'gadget', 'wearable'],
        'software-applications' => ['software', 'application', 'app update'],
        'cybersecurity' => ['cybersecurity', 'ransomware', 'vulnerability', 'data breach', 'malware', 'security advisory', 'security patch', 'phishing', 'data leak'],
        'startups-funding' => ['startup', 'funding', 'venture capital', 'series a', 'series b'],
        'programming-development' => ['programming', 'developer', 'javascript', 'python', 'laravel', 'rust', 'typescript', 'developer tools', 'command line', 'terminal tips'],
        'cloud-computing' => ['cloud computing', 'aws', 'kubernetes', 'azure', 'serverless'],
        'data-science' => ['data science', 'analytics', 'dataset'],
        'robotics-automation' => ['robotics', 'robot', 'automation'],
        'technology-companies' => ['microsoft', 'apple', 'google', 'nvidia', 'meta'],
        'indian-technology' => ['india', 'indian', 'bengaluru', 'isro'],
        'global-technology' => ['global technology', 'international technology'],
    ],
];
