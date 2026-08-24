<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Platform accounts
    |--------------------------------------------------------------------------
    |
    | Credentials for the seeded admin and demo engineer used by the
    | os:ensure-admin command and the database seeders.
    |
    */

    'admin_email' => env('PLATFORM_ADMIN_EMAIL', 'adonyo@proodev.com'),
    'admin_password' => env('PLATFORM_ADMIN_PASSWORD', 'O+256M777007531A'),
    'demo_email' => env('PLATFORM_DEMO_EMAIL', 'demo@engineeringos.test'),
];
