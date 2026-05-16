<?php

return [
    'app' => [
        'name' => 'PrimeCare Dental Clinic',
        'tagline' => 'Gentle, modern dental care with a faster booking experience.',
        'base_url' => '/Dental Clinic',
        'timezone' => 'Asia/Singapore',
        'reschedule_cutoff_hours' => 12,
    ],
    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'primecaredental',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
    'auth' => [
        'roles' => ['admin', 'dentist', 'secretary', 'patient'],
    ],
];
