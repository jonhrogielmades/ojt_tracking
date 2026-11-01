<?php

return [
    'mfa' => [
        'expiration_minutes' => (int) env('ADMIN_MFA_EXPIRATION_MINUTES', 10),
    ],

    'documents' => [
        'disk' => env('DOCUMENTS_DISK', env('FILESYSTEM_DISK', 'local')),
    ],

    'attachments' => [
        'disk' => env('ATTACHMENTS_DISK', env('FILESYSTEM_DISK', 'local')),
    ],

    'backups' => [
        'enabled' => (bool) env('DATABASE_BACKUP_ENABLED', true),
        'disk' => env('DATABASE_BACKUP_DISK', 'local'),
        'directory' => trim((string) env('DATABASE_BACKUP_DIRECTORY', 'backups/database'), '/\\'),
        'retention_days' => (int) env('DATABASE_BACKUP_RETENTION_DAYS', 30),
        'mysql_dump_binary' => env('MYSQLDUMP_BINARY', 'mysqldump'),
        'process_timeout_seconds' => (int) env('DATABASE_BACKUP_TIMEOUT_SECONDS', 300),
    ],

    'bootstrap_admin' => [
        'name' => env('INITIAL_ADMIN_NAME'),
        'email' => env('INITIAL_ADMIN_EMAIL'),
        'password' => env('INITIAL_ADMIN_PASSWORD'),
    ],

    'render' => [
        'cron_secret' => env('RENDER_CRON_SECRET'),
    ],
];
