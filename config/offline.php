<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Offline sync
    |--------------------------------------------------------------------------
    |
    | Limits applied to records captured by guards without connectivity and
    | uploaded later by the PWA. Client timestamps older than max_age_days or
    | further in the future than clock_skew_minutes are rejected.
    |
    */

    'max_age_days' => (int) env('OFFLINE_SYNC_MAX_AGE_DAYS', 7),

    'clock_skew_minutes' => (int) env('OFFLINE_SYNC_CLOCK_SKEW_MINUTES', 5),

];
