<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Site administrator
    |--------------------------------------------------------------------------
    |
    | The admin account is created (or promoted) by the AdminSeeder. Set
    | ADMIN_PASSWORD in .env before seeding; it is only used when the
    | account is first created.
    |
    */

    'admin_email' => env('ADMIN_EMAIL', 'ppoussane@gmail.com'),

    'admin_password' => env('ADMIN_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | Languages
    |--------------------------------------------------------------------------
    |
    | Visitors switch language with the EN / فا switch. The choice is kept in
    | the session and a cookie. "dir" sets the page direction.
    |
    */

    'locales' => [
        'en' => ['name' => 'English', 'short' => 'EN', 'dir' => 'ltr'],
        'fa' => ['name' => 'فارسی', 'short' => 'فا', 'dir' => 'rtl'],
    ],

];
