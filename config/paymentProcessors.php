<?php

return [
    'stripe' => [
        // 'model' => Shared\Models\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],
];
