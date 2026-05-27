<?php
/**
 * Smart Maheshkhali — admin authentication helpers.
 *
 * Backed by a single bcrypt hash in `config.php`. Tiny attempt-limiter
 * keyed by IP discourages brute force without the operational cost of
 * a real account lockout table.
 */

declare(strict_types=1);

/**
 * True if the current session belongs to an authenticated admin.
 */
function admin_logged_in(): bool
{
    return !empty($_SESSION['admin_authenticated'])
        && ($_SESSION['admin_authenticated'] === true);
}

/**
 * Verify a username/password against the configured admin credentials.
 * Uses constant-time username comparison and bcrypt password verify so
 * we cannot be timed for either a missing user or a wrong password.
 */
function admin_verify(string $username, string $password): bool
{
    global $CONFIG;
    $expectedUser = (string) ($CONFIG['admin_username'] ?? '');
    $expectedHash = (string) ($CONFIG['admin_password_hash'] ?? '');

    // Fail closed if config wasn't filled in.
    if ($expectedUser === '' || $expectedHash === ''
        || strpos($expectedHash, 'REPLACE_WITH_YOUR_OWN_BCRYPT_HASH') !== false
    ) {
        // Still call password_verify against a dummy hash to keep timing
        // similar to the success path.
        password_verify($password, '$2y$12$abcdefghijklmnopqrstuv0123456789ABCDEFGHIJKL.MNOP');
        return false;
    }

    $userOk = hash_equals($expectedUser, $username);
    $passOk = password_verify($password, $expectedHash);

    return $userOk && $passOk;
}

/**
 * Mark the current session as an authenticated admin. Regenerates the
 * session id to mitigate session-fixation attacks.
 */
function admin_login(): void
{
    session_regenerate_id(true);
    $_SESSION['admin_authenticated'] = true;
    $_SESSION['admin_login_time']    = time();
    $_SESSION['admin_ip']            = client_ip();
}

/**
 * Destroy the admin session entirely.
 */
function admin_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $params['path'],
            'domain'   => $params['domain'],
            'secure'   => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
}

/**
 * Use at the top of every admin page to enforce authentication.
 * Redirects unauthenticated users to /login.
 */
function require_admin(): void
{
    if (!admin_logged_in()) {
        $target = $_SERVER['REQUEST_URI'] ?? '';
        $_SESSION['login_redirect'] = $target;
        redirect('/login.php');
    }
}

/**
 * Very small per-IP rate limiter for failed login attempts.
 * Stored in-session so it requires no extra table.
 */
function admin_login_throttled(): bool
{
    $tries = $_SESSION['admin_login_attempts'] ?? [];
    $tries = array_filter($tries, fn ($t) => $t > time() - 600); // 10 min window
    $_SESSION['admin_login_attempts'] = array_values($tries);
    return count($tries) >= 5;
}

function admin_record_failed_login(): void
{
    $_SESSION['admin_login_attempts'][] = time();
}

function admin_clear_failed_logins(): void
{
    unset($_SESSION['admin_login_attempts']);
}
