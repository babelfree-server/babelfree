<?php
// Application configuration
return [
    'db' => [
        'host'    => 'localhost',
        'dbname'  => 'jaguar_app',
        'user'    => 'jaguar_user',
        'pass'    => 'GMTbBJRvuOrwfvGpzmu7u0Ff0VBFaTt',
        'charset' => 'utf8mb4',
    ],
    'token_expiry_days' => 30,
    'verify_expiry_hours' => 24,
    'reset_expiry_hours' => 1,
    'rate_limits' => [
        'login'    => ['max' => 5,  'window' => 900],   // 5 per 15 min
        'register' => ['max' => 3,  'window' => 3600],  // 3 per hour
        'forgot'   => ['max' => 3,  'window' => 3600],  // 3 per hour
        'general'  => ['max' => 60, 'window' => 60],    // 60 per min
    ],
    'mail' => [
        'from_email' => 'noreply@babelfree.com',
        'from_name'  => 'El Viaje del Jaguar',
        'use_smtp'   => false, // use sendmail (Postfix)
    ],
    'base_url' => 'https://babelfree.com',
    // CEFR levels where immersion is locked (no language toggle)
    'immersion_locked_levels' => ['A2 Advanced', 'B1', 'B2', 'C1', 'C2'],
];
