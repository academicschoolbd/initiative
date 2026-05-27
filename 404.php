<?php
/**
 * Smart Maheshkhali — generic 404 page.
 *
 * Wired up via .htaccess (`ErrorDocument 404`). Safe to hit directly
 * because it returns a 404 status code itself.
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

http_response_code(404);
?>
<!doctype html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>পেজ পাওয়া যায়নি | <?= e($CONFIG['app_name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Tiro+Bangla&display=swap" rel="stylesheet">
    <style>
        body { margin:0; padding:24px; background:#fafafa; color:#0f172a;
               font-family:'Inter','Tiro Bangla',sans-serif;
               display:flex; align-items:center; justify-content:center; min-height:100vh; }
        .card { background:#fff; border:1px solid #e4e4e7; border-radius:14px;
                padding:48px 36px; text-align:center; max-width:480px; width:100%;
                box-shadow:0 10px 25px -5px rgba(0,0,0,.04); }
        .code { font-size:3.5rem; font-weight:700; color:#94a3b8; margin:0; line-height:1; }
        h1 { font-family:'Tiro Bangla',serif; font-size:1.4rem; margin:14px 0 10px; color:#0f172a; }
        p  { color:#64748b; font-size:.95rem; margin:0 0 24px; line-height:1.6; }
        a  { display:inline-flex; background:#0f172a; color:#fff; padding:11px 24px;
             border-radius:8px; font-weight:600; text-decoration:none; font-size:.9rem; }
        a:hover { background:#1e293b; }
    </style>
</head>
<body>
<div class="card">
    <p class="code">404</p>
    <h1>পেজটি পাওয়া যায়নি</h1>
    <p>আপনি যে পেজটি খুঁজছেন সেটি সরিয়ে ফেলা হয়েছে অথবা ঠিকানাটি ভুল হতে পারে।</p>
    <a href="<?= e(url('/index.php')) ?>">মূল পাতায় ফিরুন</a>
</div>
</body>
</html>
