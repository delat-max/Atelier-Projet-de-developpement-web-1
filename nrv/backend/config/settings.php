<?php

declare(strict_types=1);

return [
    'db' => [
        'dsn' => sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            getenv('DB_HOST') ?: '127.0.0.1',
            getenv('DB_PORT') ?: '3306',
            getenv('DB_NAME') ?: 'nrv_db'
        ),
        'user' => getenv('DB_USER') ?: 'nrv',
        'password' => getenv('DB_PASSWORD') ?: 'nrv',
    ],

    'auth' => [
        'admin_emails' => array_values(array_filter(array_map(
            static fn (string $email): string =>
                mb_strtolower(trim($email)),
            explode(
                ',',
                getenv('ADMIN_EMAILS') ?: 'admin@nrv.net'
            )
        ))),
    ],
];