<?php

return [
    'mfa' => [
        'expiration_minutes' => (int) env('ADMIN_MFA_EXPIRATION_MINUTES', 10),
    ],

    'backups' => [
        'disk' => env('DATABASE_BACKUP_DISK', 'local'),
        'directory' => trim((string) env('DATABASE_BACKUP_DIRECTORY', 'backups/database'), '/\\'),
        'retention_days' => (int) env('DATABASE_BACKUP_RETENTION_DAYS', 30),
        'mysql_dump_binary' => env('MYSQLDUMP_BINARY', 'mysqldump'),
        'process_timeout_seconds' => (int) env('DATABASE_BACKUP_TIMEOUT_SECONDS', 300),
    ],
];
