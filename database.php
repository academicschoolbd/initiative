<?php
/**
 * Smart Maheshkhali — database connection and schema migrations.
 *
 * Exposes a global $db (PDO) and runs idempotent migrations on every
 * request so that fresh deployments work with zero manual SQL.
 *
 * This file is loaded by `includes/bootstrap.php`. Do not include it
 * directly; always go through bootstrap so configuration and error
 * handling are in place first.
 */

declare(strict_types=1);

if (!defined('SMK_BOOTSTRAPPED')) {
    require_once __DIR__ . '/includes/bootstrap.php';
}

/** @var array<string, mixed> $CONFIG */
$sqlitePath = $CONFIG['sqlite_path'];

try {
    $db = new PDO('sqlite:' . $sqlitePath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    // Better concurrency and foreign-key support.
    $db->exec('PRAGMA journal_mode = WAL;');
    $db->exec('PRAGMA foreign_keys = ON;');
} catch (PDOException $e) {
    error_log('[Smart Maheshkhali] DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Database unavailable. Please contact the administrator.";
    exit;
}

/**
 * Run idempotent schema migrations.
 *
 * Each migration is gated by a row in `schema_versions` so it runs
 * exactly once per database file. Adding a new migration is as simple
 * as appending another `if (!already_applied(...))` block below.
 */
(function (PDO $db): void {
    $db->exec('CREATE TABLE IF NOT EXISTS schema_versions (
        version INTEGER PRIMARY KEY,
        applied_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');

    $applied = $db->query('SELECT version FROM schema_versions')
        ->fetchAll(PDO::FETCH_COLUMN);
    $applied = array_map('intval', $applied);

    $migrations = [
        1 => function (PDO $db): void {
            // Initial schema (matches the legacy v0 layout so existing
            // databases need no data migration).
            $db->exec("CREATE TABLE IF NOT EXISTS registrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                institution_type TEXT NOT NULL,
                school_name TEXT NOT NULL,
                school_name_bn TEXT,
                subdomain TEXT NOT NULL,
                union_name TEXT NOT NULL,
                detailed_address TEXT NOT NULL,
                latitude TEXT,
                longitude TEXT,
                owner_name TEXT NOT NULL,
                owner_phone TEXT NOT NULL,
                owner_email TEXT NOT NULL,
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            $db->exec("CREATE TABLE IF NOT EXISTS settings (
                key TEXT PRIMARY KEY,
                value TEXT
            )");

            $db->exec("INSERT OR IGNORE INTO settings (key, value) VALUES ('form_enabled', '1')");
        },

        2 => function (PDO $db): void {
            // Enforce subdomain uniqueness and add helpful indexes.
            // SQLite cannot add UNIQUE to an existing column in place,
            // but a UNIQUE INDEX has the same effect at write time.
            $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_registrations_subdomain
                       ON registrations(subdomain)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_registrations_type
                       ON registrations(institution_type)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_registrations_created
                       ON registrations(created_at)");
        },

        3 => function (PDO $db): void {
            // Per-IP submission audit / rate limiting.
            $db->exec("CREATE TABLE IF NOT EXISTS submission_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ip_address TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_submission_log_ip_time
                       ON submission_log(ip_address, created_at)");
        },
    ];

    foreach ($migrations as $version => $migration) {
        if (in_array($version, $applied, true)) {
            continue;
        }
        $db->beginTransaction();
        try {
            $migration($db);
            $stmt = $db->prepare('INSERT INTO schema_versions (version) VALUES (?)');
            $stmt->execute([$version]);
            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            error_log('[Smart Maheshkhali] Migration #' . $version . ' failed: ' . $e->getMessage());
            throw $e;
        }
    }
})($db);
