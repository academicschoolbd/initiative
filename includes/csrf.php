<?php
/**
 * Smart Maheshkhali — CSRF token issue / verification.
 *
 * One token per session, lazily generated. Use `csrf_field()` inside
 * forms and `csrf_check()` at the top of any POST handler.
 */

declare(strict_types=1);

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Hidden input string ready to drop into a <form>.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

/**
 * Validate the CSRF token on the current POST. On failure responds 419
 * and exits. Constant-time comparison guards against timing oracles.
 */
function csrf_check(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }
    $submitted = $_POST['_csrf'] ?? '';
    $expected  = $_SESSION['csrf_token'] ?? '';

    if (!is_string($submitted) || $expected === '' || !hash_equals($expected, $submitted)) {
        http_response_code(419);
        header('Content-Type: text/plain; charset=utf-8');
        echo "Session expired or invalid request token. Please reload the page and try again.";
        exit;
    }
}
