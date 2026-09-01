<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Database backup settings
    |--------------------------------------------------------------------------
    |
    | Backups are written as portable .sql dump files every N hours. The
    | recipient address receives the dump file by email after each run.
    |
    */

    'email_to' => env('BACKUP_EMAIL_TO', 'adonyo@proodev.com'),

    'every_hours' => 8,
];
