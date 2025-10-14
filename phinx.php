<?php
return [
'paths' => [
'migrations' => 'migrations'
],
'environments' => [
'default_migration_table' => 'phinxlog',
'default_environment' => 'development',
'development' => [
'adapter' => 'mysql',
'host' => getenv('DB_HOST') ?: '127.0.0.1',
'name' => getenv('DB_NAME') ?: 'khqr_store',
'user' => getenv('DB_USER') ?: 'root',
'pass' => getenv('DB_PASS') ?: 'ikhode',
'port' => getenv('DB_PORT') ?: 4987,
'charset' => 'utf8mb4',
],
],
'version_order' => 'execution'
];