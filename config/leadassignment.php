<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Auto-assign Lead ke CS
    |--------------------------------------------------------------------------
    |
    | Ketika lead baru masuk tanpa handler (mis. dari webhook Scalev tanpa
    | message_variables.handler), lead di-assign otomatis ke CS aktif.
    |
    | Strategi:
    | - least_loaded : ke CS dengan lead 'new' paling sedikit (rekomendasi, self-balancing)
    | - round_robin  : bergantian antar CS aktif
    |
    */

    'auto_assign' => env('LEAD_AUTO_ASSIGN', true),

    'strategy' => env('LEAD_ASSIGN_STRATEGY', 'least_loaded'),

];
