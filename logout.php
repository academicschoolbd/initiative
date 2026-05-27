<?php
/**
 * Smart Maheshkhali — admin logout.
 *
 * Accepts both GET and POST; tokenless logout is acceptable because
 * the worst a CSRF can do is sign the user out.
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

admin_logout();
redirect('/login.php');
