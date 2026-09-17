<?php

return [
    'admin_username'    => env('ADMIN_USERNAME'),
    'admin_password'    => env('ADMIN_PASSWORD'),
    'slot_minutes'      => env('SLOT_MINUTES', 15),
    'currency_symbol'   => env('CURRENCY_SYMBOL', '৳'),
    'max_attempts'      => env('MAX_LOGIN_ATTEMPTS', 5),
    'lockout_minutes'   => env('LOCKOUT_MINUTES', 15),
];
