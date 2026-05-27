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
        'primary'     => 3,
        'madrasah'    => 3,
        'high_school' => 3,
    ],

    // Anti-abuse
    'submission_cooldown_seconds' => 60,   // minimum seconds between submissions from same IP
    'force_https'                 => false, // true to redirect http -> https via PHP

    // Sponsor splash dialog. Show "Sponsored by Smartschool.bd & Institution.bd"
    // for 6 seconds when a visitor lands on the home page. Set whatsapp_contact
    // to the +88-prefixed mobile number that should receive WhatsApp pings;
    // leave empty to render the icon without a clickable wa.me link.
    'sponsor_dialog_enabled' => true,
    'whatsapp_contact'       => '+8801711000000',

    // Database
    'sqlite_path' => __DIR__ . '/database.sqlite',
];
