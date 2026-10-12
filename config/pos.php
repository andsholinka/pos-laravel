<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Demo accounts on the login page
    |--------------------------------------------------------------------------
    |
    | Shows the "Akun Demo" box with click-to-fill buttons on the login page.
    | Turn this off (POS_DEMO_ACCOUNTS=false) for a real deployment.
    |
    */

    'show_demo_accounts' => env('POS_DEMO_ACCOUNTS', false),

    /*
    |--------------------------------------------------------------------------
    | Dashboard low-stock list size
    |--------------------------------------------------------------------------
    */

    'low_stock_dashboard_limit' => 8,

];
