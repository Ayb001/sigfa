<?php

use App\Models\User;
use App\Models\Client;

return [

    'defaults' => [
        'guard' => 'staff',
        'passwords' => 'users',
    ],

    'guards' => [
        'staff' => [
            'driver' => 'jwt',
            'provider' => 'users',
        ],
        'client' => [
            'driver' => 'jwt',
            'provider' => 'clients',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => User::class,
        ],
        'clients' => [
            'driver' => 'eloquent',
            'model' => Client::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
        'clients' => [
            'provider' => 'clients',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,

];
