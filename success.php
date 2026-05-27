<?php
/**
 * Smart Maheshkhali — post-submission confirmation page.
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$data = flash_pull('submission_success');
if (!is_array($data)) {
    redirect('/index.php');
}
?>
<!doctype html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>নিবন্ধন সফল হয়েছে | <?= e($CONFIG['app_name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Tiro+Bangla&display=swap" rel="stylesheet">

    <style>
        body { margin:0; padding:24px; background:#f0fdf4; color:#0f172a;
               font-family:'Inter','Tiro Bangla',serif;
               display:flex; align-items:center; justify-content:center; min-height:100vh; }
        .success-card { background:#fff; border:1px solid #bbf7d0; border-radius:16px;
                        padding:48px 36px; text-align:center; max-width:520px; width:100%;
                        box-shadow:0 20px 25px -5px rgba(4,120,87,.05);
                        animation:popIn .5s cubic-bezier(.34,1.56,.64,1) forwards; }
        @keyframes popIn { from { opacity:0; transform:scale(.95); } to { opacity:1; transform:none; } }

        .icon-shield { width:72px; height:72px; background:#dcfce7; color:#15803d;
                       border-radius:50%; display:flex; align-items:center; justify-content:center;
                       margin:0 auto 28px; }
        h2 { font-family:'Tiro Bangla',serif; font-size:1.7rem; color:#166534;
             margin:0 0 12px; }
        p { color:#475569; font-size:1rem; margin:0 0 24px; line-height:1.6; }

        .meta-data-box { background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px;
                         padding:18px; text-align:left; margin-bottom:28px; font-size:.92rem; }
        .meta-data-box div { margin-bottom:8px; padding-bottom:8px;
                             border-bottom:1px dashed #e2e8f0; }
        .meta-data-box div:last-child { border-bottom:0; padding-bottom:0; margin-bottom:0; }

        .btn-home { display:inline-flex; background:#166534; color:#fff;
                    padding:12px 28px; border-radius:8px; font-weight:600;
                    text-decoration:none; font-size:.95rem; transition:.2s;
                    box-shadow:0 4px 12px rgba(22,101,52,.2); }
        .btn-home:hover { background:#14532d; transform:translateY(-1px); }

        @media (max-width: 480px) {
            body { padding: 16px; }
            .success-card { padding: 32px 22px; border-radius: 14px; }
            h2 { font-size: 1.4rem; }
            p  { font-size: .9rem; }
            .meta-data-box { padding: 14px; font-size: .85rem; margin-bottom: 22px; }
            .icon-shield { width: 60px; height: 60px; margin-bottom: 22px; }
            .btn-home { padding: 11px 22px; font-size: .9rem; }
        }
    </style>
</head>
<body>

<div class="success-card">
    <div class="icon-shield">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <polyline points="20 6 9 17 4 12"/>
        </svg>
    </div>

    <h2>অভিনন্দন! আবেদন সফল হয়েছে</h2>
    <p>স্মার্ট মহেশখালী পাইলট প্রজেক্টের ফ্রি অটোমেশন সফটওয়্যারের জন্য আপনার প্রতিষ্ঠানের তথ্য পুঙ্খানুপুঙ্খভাবে সংরক্ষণ করা হয়েছে। কর্তৃপক্ষ যাচাই শেষে আপনার সাথে যোগাযোগ করবে।</p>

    <div class="meta-data-box">
        <div><strong>শিক্ষা প্রতিষ্ঠান:</strong> <?= e((string) ($data['school'] ?? '')) ?></div>
        <div>
            <strong>প্রস্তাবিত ডোমেন রুট:</strong>
            <span style="font-family:monospace; color:#059669; font-weight:700;">
                <?= e((string) ($data['subdomain'] ?? '')) ?>
            </span>
        </div>
    </div>

    <a href="<?= e(url('/index.php')) ?>" class="btn-home">মূল পাতায় ফিরুন</a>
</div>

</body>
</html>
