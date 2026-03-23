<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application constants
    |--------------------------------------------------------------------------
    |
    | Configure values that you want to reuse across the application. Access
    | them anywhere with config('constants.key'). Values that should change
    | per environment can read from env variables with sane defaults.
    |
    */
    'starting_coins_balance' => 10,
    'turn_timer_ms' => env('TURN_TIMER_MS', 20000),
];
    
