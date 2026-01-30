<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'chatgpt' => [
        'key' => env('OPENAI_KEY')
    ],

    'linkedin' => [
        'api' => env('LINKEDIN_API'),
        'client' => env('LINKEDIN_CLIENT'),
        'secret' => env('LINKEDIN_SECRET'),
        'state' => env('LINKEDIN_STATE')
    ],

    'skrapp' => [
        'key' => env('SKRAPP_EMAIL_FINDER_KEY')
    ],

    'rapidapi' => [
        'key' => env('RAPIDAPI_KEY'),
        'allowed_hosts' => array_filter(array_map('trim', explode(',', env('RAPIDAPI_ALLOWED_HOSTS', 'linkedin-data-api.p.rapidapi.com,li-data-scraper.p.rapidapi.com,fresh-linkedin-profile-data.p.rapidapi.com'))))
    ],

    'calendly' => [
        'link' => env('CALENDLY_LINK', 'https://calendly.com/your-username'),
        'enabled' => env('CALENDLY_ENABLED', false),
        'client_id' => env('CALENDLY_CLIENT_ID'),
        'client_secret' => env('CALENDLY_CLIENT_SECRET'),
        'redirect' => env('CALENDLY_REDIRECT_URL'),
        'webhook_url' => env('CALENDLY_WEBHOOK_URL', 'https://app.linkdominator.com/api/calendly/webhook')
    ],

    'phantombuster' => [
        // Single API key (backward compatibility)
        'api_key' => env('PHANTOMBUSTER_API_KEY'),
        'api_url' => env('PHANTOMBUSTER_API_URL', 'https://api.phantombuster.com/api/v1'),
        
        // Multi-workspace support: comma-separated API keys
        // Format: key1,key2,key3,key4,key5
        'workspace_api_keys' => array_filter(array_map('trim', explode(',', env('PHANTOMBUSTER_API_KEY', '')))),
        
        // Multi-workspace phantom IDs (comma-separated, must match workspace count)
        // Format: id1,id2,id3,id4,id5
        'workspace_linkedin_post_likers_phantom_ids' => array_filter(array_map('trim', explode(',', env('PHANTOMBUSTER_LINKEDIN_POST_LIKERS_PHANTOM_ID', '')))),
        'workspace_linkedin_post_comments_phantom_ids' => array_filter(array_map('trim', explode(',', env('PHANTOMBUSTER_LINKEDIN_POST_COMMENTS_PHANTOM_ID', '')))),
        'workspace_linkedin_search_export_phantom_ids' => array_filter(array_map('trim', explode(',', env('PHANTOMBUSTER_LINKEDIN_SEARCH_EXPORT_PHANTOM_ID', '')))),
        'workspace_linkedin_profile_scraper_phantom_ids' => array_filter(array_map('trim', explode(',', env('PHANTOMBUSTER_LINKEDIN_PROFILE_SCRAPER_PHANTOM_ID', '')))),
        
        // Backward compatibility: single phantom IDs
        'linkedin_post_likers_phantom_id' => env('PHANTOMBUSTER_LINKEDIN_POST_LIKERS_PHANTOM_ID'),
        'linkedin_post_comments_phantom_id' => env('PHANTOMBUSTER_LINKEDIN_POST_COMMENTS_PHANTOM_ID'),
        'linkedin_search_export_phantom_id' => env('PHANTOMBUSTER_LINKEDIN_SEARCH_EXPORT_PHANTOM_ID'),
        'linkedin_profile_scraper_phantom_id' => env('PHANTOMBUSTER_LINKEDIN_PROFILE_SCRAPER_PHANTOM_ID'),
        
        // Limit how many company posts we process per run
        // Each post = 1 phantom call (likers only)
        // Lower = less PhantomBuster credits used, Higher = more data scraped
        'company_posts_limit' => env('COMPETITOR_POSTS_LIMIT', 15),
        
        // Stop early if we get enough engagers (saves credits)
        // Set to 0 to disable early stopping
        'min_engagers_for_early_stop' => env('PHANTOMBUSTER_MIN_ENGAGERS_EARLY_STOP', 1000),
        // Required for LinkedIn phantoms: Get manually from LinkedIn browser cookies
        // Steps: 1) Log into LinkedIn, 2) Open DevTools (F12) > Application > Cookies > linkedin.com, 3) Copy "li_at" cookie value
        'linkedin_session_cookie' => env('PHANTOMBUSTER_LINKEDIN_SESSION_COOKIE'),
        // Optional: Custom user agent (defaults to Chrome on Windows if not set)
        // To get: Open DevTools > Console > type: navigator.userAgent
        'linkedin_user_agent' => env('PHANTOMBUSTER_LINKEDIN_USER_AGENT', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36'),
        
        // Workspace lock timeout (seconds to wait for available workspace)
        'workspace_lock_timeout' => env('PHANTOMBUSTER_WORKSPACE_LOCK_TIMEOUT', 300), // 5 minutes
    ],

    'email_scraping' => [
        // Daily limit for email scraping per user (profiles per day)
        'daily_limit_per_user' => env('DAILY_EMAIL_SCRAPING_LIMIT', 100),
    ]
];
