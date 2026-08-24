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
        'api_key' => env('PHANTOMBUSTER_API_KEY'),
        'api_url' => env('PHANTOMBUSTER_API_URL', 'https://api.phantombuster.com/api/v1'),
        // LinkedIn Post Likers Export - extracts users who liked a specific post (requires post URL)
        'linkedin_post_likers_phantom_id' => env('PHANTOMBUSTER_LINKEDIN_POST_LIKERS_PHANTOM_ID'),
        // LinkedIn Post Comments Export - extracts users who commented on a specific post (requires post URL)
        'linkedin_post_comments_phantom_id' => env('PHANTOMBUSTER_LINKEDIN_POST_COMMENTS_PHANTOM_ID'),
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
        // LinkedIn Search Export - exports search results from a LinkedIn search URL
        'linkedin_search_export_phantom_id' => env('PHANTOMBUSTER_LINKEDIN_SEARCH_EXPORT_PHANTOM_ID'),
        // LinkedIn Profile Scraper - scrapes full profile data including email
        'linkedin_profile_scraper_phantom_id' => env('PHANTOMBUSTER_LINKEDIN_PROFILE_SCRAPER_PHANTOM_ID'),
    ],

    'email_scraping' => [
        // Daily limit for email scraping per user (profiles per day)
        'daily_limit_per_user' => env('DAILY_EMAIL_SCRAPING_LIMIT', 100),
        'batch_size' => (int) env('EMAIL_ENRICHMENT_BATCH_SIZE', 25),
        'job_chunk_size' => (int) env('EMAIL_ENRICHMENT_JOB_CHUNK_SIZE', 5),
        'job_chunk_stagger_seconds' => (int) env('EMAIL_ENRICHMENT_JOB_CHUNK_STAGGER_SECONDS', 3),
    ],

    'competitor_followers' => [
        'company_posts_limit' => env('COMPETITOR_POSTS_LIMIT', 15),
        'page_size' => env('COMPETITOR_PAGE_SIZE', 100),
        'max_engagers_per_post' => env('COMPETITOR_MAX_ENGAGERS_PER_POST', 500),
        'max_posts_scan' => env('COMPETITOR_MAX_POSTS_SCAN', 30),
    ],

    'unipile_pacing' => [
        'daily_invites' => (int) env('UNIPILE_DAILY_INVITE_CAP', 40),
        'daily_new_chats' => (int) env('UNIPILE_DAILY_NEW_CHAT_CAP', 60),
        'daily_messages' => (int) env('UNIPILE_DAILY_MESSAGE_CAP', 200),
        'chat_launch_stagger_seconds' => (int) env('UNIPILE_CHAT_LAUNCH_STAGGER_SECONDS', 8),
        'chat_launch_jitter_seconds' => (int) env('UNIPILE_CHAT_LAUNCH_JITTER_SECONDS', 7),
        'profile_lookup_delay_min_ms' => (int) env('UNIPILE_PROFILE_LOOKUP_DELAY_MIN_MS', 1000),
        'profile_lookup_delay_max_ms' => (int) env('UNIPILE_PROFILE_LOOKUP_DELAY_MAX_MS', 3000),
        'harvest_page_delay_min_ms' => (int) env('UNIPILE_HARVEST_PAGE_DELAY_MIN_MS', 800),
        'harvest_page_delay_max_ms' => (int) env('UNIPILE_HARVEST_PAGE_DELAY_MAX_MS', 2500),
        'account_lock_seconds' => (int) env('UNIPILE_ACCOUNT_LOCK_SECONDS', 25),
        'account_lock_wait_seconds' => (int) env('UNIPILE_ACCOUNT_LOCK_WAIT_SECONDS', 20),
        'outreach_inflight_per_user' => (int) env('OUTREACH_INFLIGHT_PER_USER', 2),
        'campaign_inflight_per_user' => (int) env('CAMPAIGN_INFLIGHT_PER_USER', 2),
        'campaign_lead_stagger_seconds' => (int) env('CAMPAIGN_LEAD_STAGGER_SECONDS', 60),
        'outreach_lead_stagger_seconds' => (int) env('OUTREACH_LEAD_STAGGER_SECONDS', 60),
        'temp_limit_min_minutes' => (int) env('UNIPILE_TEMP_LIMIT_MIN_MINUTES', 45),
        'temp_limit_max_minutes' => (int) env('UNIPILE_TEMP_LIMIT_MAX_MINUTES', 90),
        'temp_limit_escalate_after' => (int) env('UNIPILE_TEMP_LIMIT_ESCALATE_AFTER', 2),
        'inflight_lease_seconds' => (int) env('INFLIGHT_LEASE_SECONDS', 1800),
        'contact_prep_batch_size' => (int) env(
            'OUTREACH_CONTACT_PREP_BATCH_SIZE',
            env('EMAIL_ENRICHMENT_BATCH_SIZE', 25)
        ),
    ],

    'unipile' => [
        'base_url' => env('UNIPILE_BASE_URL', 'https://api1.unipile.com:13111/api/v1'),
        'default_country' => env('UNIPILE_DEFAULT_COUNTRY', 'US'),
        'api_key' => env('UNIPILE_API_KEY'),
        'webhook_secret' => env('UNIPILE_WEBHOOK_SECRET'),
        'webhook_callback_path' => env('UNIPILE_WEBHOOK_CALLBACK_PATH', '/unipile/callback'),
        'mock' => filter_var(env('UNIPILE_MOCK', false), FILTER_VALIDATE_BOOLEAN),
        'account_id_param' => env('UNIPILE_ACCOUNT_ID_PARAM', 'query'),
        'endpoints' => [
            'hosted_auth_link'   => env('UNIPILE_ENDPOINT_HOSTED_AUTH_LINK', '/hosted/accounts/link'),
            'connect_account'    => env('UNIPILE_ENDPOINT_CONNECT_ACCOUNT', '/accounts'),
            'list_accounts'      => env('UNIPILE_ENDPOINT_LIST_ACCOUNTS', '/accounts'),
            'get_account'        => env('UNIPILE_ENDPOINT_GET_ACCOUNT', '/accounts/%s'),
            'delete_account'     => env('UNIPILE_ENDPOINT_DELETE_ACCOUNT', '/accounts/%s'),
            'search'             => env('UNIPILE_ENDPOINT_SEARCH', '/linkedin/search'),
            'send_invitation'    => env('UNIPILE_ENDPOINT_SEND_INVITATION', '/users/invite'),
            'list_invitations'   => env('UNIPILE_ENDPOINT_LIST_INVITATIONS', '/users/invitations'),
            'accept_invitation'  => env('UNIPILE_ENDPOINT_ACCEPT_INVITATION', '/users/invitations'),
            'withdraw_invitation'=> env('UNIPILE_ENDPOINT_WITHDRAW_INVITATION', '/users/invitations/%s'),
            'profile_action'     => env('UNIPILE_ENDPOINT_PROFILE_ACTION', '/users/%s'),
            'start_chat'         => env('UNIPILE_ENDPOINT_START_CHAT', '/chats'),
            'send_message'       => env('UNIPILE_ENDPOINT_SEND_MESSAGE', '/chats/%s/messages'),
            'list_chats'         => env('UNIPILE_ENDPOINT_LIST_CHATS', '/chats'),
            'list_messages'      => env('UNIPILE_ENDPOINT_LIST_MESSAGES', '/chats/%s/messages'),
            'send_email'         => env('UNIPILE_ENDPOINT_SEND_EMAIL', '/emails'),
            'list_emails'        => env('UNIPILE_ENDPOINT_LIST_EMAILS', '/emails'),
            'list_calendars'     => env('UNIPILE_ENDPOINT_LIST_CALENDARS', '/calendars'),
            'calendar_events'    => env('UNIPILE_ENDPOINT_CALENDAR_EVENTS', '/calendars/%s/events'),
            'calendar_event'     => env('UNIPILE_ENDPOINT_CALENDAR_EVENT', '/calendars/%s/events/%s'),
        ],
    ],
];
