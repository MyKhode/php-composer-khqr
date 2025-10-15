<?php

return [
    'paths' => [
        'migrations' => 'migrations',
        'seeds' => 'seeds',
    ],

    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'development' => [
            'adapter' => 'mysql',
            'host' => getenv('DB_HOST') ?: 'mysql',
            'name' => getenv('DB_NAME') ?: 'khqr_store',
            'user' => getenv('DB_USER') ?: 'root',
            'pass' => getenv('DB_PASS') ?: 'ikhode',
            'port' => getenv('DB_PORT') ?: 3306,
            'charset' => 'utf8mb4',
        ],
    ],

    'version_order' => 'execution',
];
