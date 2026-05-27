<?php
/**
 * Smart Maheshkhali — generic helpers used across entry points.
 */

declare(strict_types=1);

/**
 * HTML-escape a value (UTF-8 safe). Returns empty string for null.
 */
function e($value): string
{
    if ($value === null) {
        return '';
    }
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Return $_POST[$key] trimmed, or fallback. Bangla-safe — does not strip
 * UTF-8 characters. We only trim and (optionally) collapse whitespace.
 */
function input(string $key, string $default = ''): string
{
    if (!isset($_POST[$key]) || !is_string($_POST[$key])) {
        return $default;
    }
    return trim($_POST[$key]);
}

/**
 * Build an absolute path under the configured base_path.
 *
 *   url('/admin')          => '/initiative/admin'
 *   url('/admin?id=3')     => '/initiative/admin?id=3'
 *   url('admin')           => '/initiative/admin'
 */
function url(string $path = ''): string
{
    global $CONFIG;
    $base = rtrim((string) ($CONFIG['base_path'] ?? ''), '/');
    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }
    if ($path[0] !== '/') {
        $path = '/' . $path;
    }
    return $base . $path;
}

/**
 * Issue a redirect using the configured base_path.
 */
function redirect(string $path): void
{
    header('Location: ' . url($path), true, 302);
    exit;
}

/**
 * Pull and clear a one-shot session value (flash).
 */
function flash_pull(string $key, $default = null)
{
    if (!isset($_SESSION[$key])) {
        return $default;
    }
    $value = $_SESSION[$key];
    unset($_SESSION[$key]);
    return $value;
}

/**
 * Best-effort client IP detection. We don't trust X-Forwarded-For unless
 * the operator explicitly knows their proxy; falling back to REMOTE_ADDR
 * is the safe default.
 */
function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Normalise a Bangladeshi mobile number to the canonical 11-digit form
 * (`01XXXXXXXXX`). Accepts spaces, dashes and `+88` / `88` prefixes,
 * including Bangla digits. Returns null if it can't be normalised.
 */
function normalise_phone(string $input): ?string
{
    // Bangla -> ASCII digits
    $bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
    $en = ['0','1','2','3','4','5','6','7','8','9'];
    $digits = str_replace($bn, $en, $input);

    // Strip everything that isn't a digit or leading +
    $digits = preg_replace('/[^\d+]/', '', $digits);
    $digits = preg_replace('/^\+?88/', '', (string) $digits);

    if (preg_match('/^01[3-9]\d{8}$/', (string) $digits)) {
        return $digits;
    }
    return null;
}

/**
 * Validate that a string is a safe DNS-style subdomain label: 3–32 chars,
 * lowercase letters / digits / hyphens, no leading/trailing hyphen.
 */
function is_valid_subdomain(string $value): bool
{
    return (bool) preg_match('/^[a-z0-9](?:[a-z0-9-]{1,30}[a-z0-9])$/', $value);
}

/**
 * Subdomains we never want anyone to claim — common admin / system names.
 */
function is_reserved_subdomain(string $value): bool
{
    static $reserved = [
        'admin','administrator','api','app','apps','auth','blog','cdn','cms',
        'dashboard','dev','docs','example','ftp','help','host','hosting',
        'imap','info','login','mail','master','my','news','official','panel',
        'pop','pop3','portal','proxy','public','root','school','schools','secure',
        'server','shop','site','smtp','staff','staging','status','store',
        'support','system','test','tests','user','users','vpn','web','webmail',
        'www','wp','smartschool',
    ];
    return in_array(strtolower($value), $reserved, true);
}

/**
 * Return the full map of institution type codes to Bangla labels.
 */
function institution_types(): array
{
    return [
        'primary'          => 'প্রাথমিক বিদ্যালয়',
        'high_school'      => 'মাধ্যমিক বিদ্যালয়',
        'madrasah'         => 'মাদ্রাসা',
        'dakhil_madrasah'  => 'দাখিল মাদ্রাসা',
        'alim_madrasah'    => 'আলিম মাদ্রাসা',
        'technical'        => 'কারিগরি শিক্ষা প্রতিষ্ঠান',
        'college'          => 'কলেজ',
    ];
}

/**
 * Render the Bangla label for an institution_type code.
 */
function institution_label(string $code): string
{
    return institution_types()[$code] ?? $code;
}
