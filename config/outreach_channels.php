<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Outreach channel enablement
    |--------------------------------------------------------------------------
    |
    | Toggle each platform on/off without removing integration code.
    | Disabled channels are hidden in outreach builder and unified inbox.
    | LinkedIn should stay enabled — it powers campaigns, content, and search.
    |
    */

    'enabled' => [
        'linkedin' => env('CHANNEL_LINKEDIN_ENABLED', true),
        'email' => env('CHANNEL_EMAIL_ENABLED', false),
        'whatsapp' => env('CHANNEL_WHATSAPP_ENABLED', false),
        'instagram' => env('CHANNEL_INSTAGRAM_ENABLED', false),
        'telegram' => env('CHANNEL_TELEGRAM_ENABLED', false),
        'twitter' => env('CHANNEL_TWITTER_ENABLED', false),
        'google_calendar' => env('CHANNEL_GOOGLE_CALENDAR_ENABLED', false),
        'outlook_calendar' => env('CHANNEL_OUTLOOK_CALENDAR_ENABLED', false),
    ],

    'inbox' => [
        'linkedin',
    ],

];
