<?php

return [

    // When false, any valid account can use the CRM and extension (no FE/Bundle gate).
    'require_entitlement' => env('BILLING_REQUIRE_ENTITLEMENT', false),

    'platform_admin_emails' => array_filter(array_map(
        'trim',
        explode(',', env('PLATFORM_ADMIN_EMAILS', ''))
    )),

    'entitlements' => [
        'FE',
        'OTO1',
        'OTO2',
        'OTO3',
        'OTO4',
        'OTO5',
        'OTO6',
        'OTO7',
        'OTO8',
        'Bundle',
    ],

];
