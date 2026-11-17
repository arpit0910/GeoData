<?php

return [
    'user_agent' => env('NEWS_USER_AGENT', 'SetuGeo-News/1.0 (+https://setugeo.com)'),
    'limit' => 30,
    'timeout' => 15,
    'ca_bundle' => env('NEWS_CA_BUNDLE'),
    'max_bytes' => 2097152,
    'rules' => [
        'artificial-intelligence' => ['artificial intelligence', 'machine learning', 'llm', 'openai', 'neural network'],
        'smartphones-gadgets' => ['smartphone', 'iphone', 'android', 'gadget', 'wearable'],
        'software-applications' => ['software', 'application', 'app update'],
        'cybersecurity' => ['cybersecurity', 'ransomware', 'vulnerability', 'data breach', 'malware'],
        'startups-funding' => ['startup', 'funding', 'venture capital', 'series a', 'series b'],
        'programming-development' => ['programming', 'developer', 'javascript', 'python', 'laravel', 'rust', 'typescript'],
        'cloud-computing' => ['cloud computing', 'aws', 'kubernetes', 'azure', 'serverless'],
        'data-science' => ['data science', 'analytics', 'dataset'],
        'robotics-automation' => ['robotics', 'robot', 'automation'],
        'technology-companies' => ['microsoft', 'apple', 'google', 'nvidia', 'meta'],
        'indian-technology' => ['india', 'indian', 'bengaluru', 'isro'],
        'global-technology' => ['global technology', 'international technology'],
    ],
];
