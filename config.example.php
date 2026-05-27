<?php
/**
 * Smart Maheshkhali — local configuration.
 *
 * 1) Copy this file to `config.php`.
 * 2) Generate an admin password hash:
 *      php -r "echo password_hash('your-strong-password', PASSWORD_DEFAULT), PHP_EOL;"
 * 3) Paste the resulting hash into ADMIN_PASSWORD_HASH below.
 *
 * `config.php` is git-ignored. Never commit real credentials.
 */

return [
    // Application
    'app_name'        => 'Smart Maheshkhali',
    'app_env'         => 'production',         // 'production' or 'development'
    'app_debug'       => false,                // true only on local dev
    'base_path'       => '/initiative',        // URL prefix; '' if served at domain root

    // Admin credentials
    'admin_username'        => 'admin',
    'admin_password_hash'   => '$2y$12$REPLACE_WITH_YOUR_OWN_BCRYPT_HASH_REPLACE_WITH_YOUR_O',

    // Pilot programme quotas (per institution_type). Set to 0 to disable a category.
    'quotas' => [
        'primary'         => 3,
        'high_school'     => 3,
        'madrasah'        => 3,
        'dakhil_madrasah' => 3,
        'alim_madrasah'   => 3,
        'technical'       => 3,
        'college'         => 3,
    ],

    // Anti-abuse
    'submission_cooldown_seconds' => 60,   // minimum seconds between submissions from same IP
    'force_https'                 => false, // true to redirect http -> https via PHP

    // Database
    'sqlite_path' => __DIR__ . '/database.sqlite',
];
