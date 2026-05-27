<?php
/**
 * Smart Maheshkhali — admin login.
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if (admin_logged_in()) {
    redirect('/admin.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if (admin_login_throttled()) {
        $error = 'অনেকবার ভুল চেষ্টা হয়েছে। ১০ মিনিট পরে আবার চেষ্টা করুন।';
    } else {
        $username = input('username');
        $password = (string) ($_POST['password'] ?? '');

        if (admin_verify($username, $password)) {
            admin_clear_failed_logins();
            admin_login();
            $next = (string) flash_pull('login_redirect', '');
            // Only allow same-origin relative redirects to avoid open-redirect.
            if ($next !== '' && $next[0] === '/' && strpos($next, '//') !== 0) {
                header('Location: ' . $next, true, 302);
                exit;
            }
            redirect('/admin.php');
        }

        admin_record_failed_login();
        $error = 'ইউজারনেম বা পাসওয়ার্ড ভুল।';
        // small delay to slow down automated guessing
        usleep(400000);
    }
}
?>
<!doctype html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>এডমিন লগইন | <?= e($CONFIG['app_name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Tiro+Bangla&display=swap" rel="stylesheet">
    <style>
        :root { --bg:#fafafa; --fg:#09090b; --card:#fff; --border:#e4e4e7; --muted:#71717a; --brand:#0f172a; }
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family: 'Inter','Tiro Bangla',sans-serif; background:var(--bg); color:var(--fg);
               display:flex; align-items:center; justify-content:center; min-height:100vh; padding:24px; }
        .card { width:100%; max-width:400px; background:var(--card); border:1px solid var(--border);
                border-radius:14px; padding:36px 32px; box-shadow:0 10px 25px -5px rgba(15,23,42,.04); }
        h1 { font-family:'Tiro Bangla',serif; font-size:1.5rem; margin-bottom:6px; }
        .sub { color:var(--muted); font-size:.9rem; margin-bottom:24px; }
        label { display:block; font-size:.85rem; font-weight:600; color:#334155; margin-bottom:6px; }
        input[type=text], input[type=password] {
            width:100%; padding:11px 14px; border:1px solid var(--border); border-radius:8px;
            font-size:.95rem; font-family:inherit; margin-bottom:16px; transition:all .15s;
        }
        input:focus { outline:none; border-color:#059669; box-shadow:0 0 0 3px rgba(5,150,105,.1); }
        button { width:100%; padding:12px 16px; background:var(--brand); color:#fff; border:none;
                 border-radius:8px; font-size:.95rem; font-weight:600; cursor:pointer; font-family:inherit; }
        button:hover { background:#1e293b; }
        .error { background:#fef2f2; border:1px solid #fee2e2; color:#991b1b; padding:12px 14px;
                 border-radius:8px; font-size:.88rem; margin-bottom:18px; }
        .footer { margin-top:18px; text-align:center; font-size:.82rem; color:var(--muted); }
        .footer a { color:var(--muted); text-decoration:underline; }

        @media (max-width: 480px) {
            body { padding: 16px; }
            .card { padding: 28px 22px; border-radius: 12px; }
            h1 { font-size: 1.3rem; }
            .sub { font-size: .82rem; margin-bottom: 18px; }
            input[type=text], input[type=password] { padding: 10px 12px; font-size: .9rem; }
            button { padding: 11px 14px; font-size: .9rem; }
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>এডমিন লগইন</h1>
        <p class="sub">পাইলট প্রোগ্রামের ড্যাশবোর্ডে প্রবেশ করতে ক্রেডেনশিয়াল প্রদান করুন।</p>

        <?php if ($error): ?>
            <div class="error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= e(url('/login.php')) ?>" autocomplete="off">
            <?= csrf_field() ?>
            <label for="u">ইউজারনেম</label>
            <input id="u" type="text" name="username" required autofocus>

            <label for="p">পাসওয়ার্ড</label>
            <input id="p" type="password" name="password" required>

            <button type="submit">লগইন করুন</button>
        </form>

        <p class="footer"><a href="<?= e(url('/index.php')) ?>">&larr; পাবলিক ফর্মে ফিরুন</a></p>
    </div>
</body>
</html>
