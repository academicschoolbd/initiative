<?php
session_start();
$data = $_SESSION['submission_success'] ?? null;

if (!$data) {
    header('Location: index');
    exit();
}
unset($_SESSION['submission_success']);
?>
<!doctype html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>নিবন্ধন সফল হয়েছে | অভিনন্দন!</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Tiro+Bangla&display=swap" rel="stylesheet">
    
    <style>
        body { 
            background: #f0fdf4; color: #0f172a; 
            font-family: 'Inter', 'Tiro Bangla', serif; 
            display: flex; align-items: center; justify-content: center; 
            min-height: 100vh; padding: 24px;
        }
        .success-card {
            background: #ffffff; border: 1px solid #bbf7d0;
            border-radius: 16px; padding: 56px 40px; text-align: center;
            max-width: 520px; width: 100%; box-shadow: 0 20px 25px -5px rgba(4, 120, 87, 0.05);
            animation: popIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }
        @keyframes popIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
        
        .icon-shield {
            width: 72px; height: 72px; background: #dcfce7;
            color: #15803d; border-radius: 50%; display: flex;
            align-items: center; justify-content: center; margin: 0 auto 28px;
        }
        h2 { font-family: 'Tiro Bangla', serif; font-size: 1.8rem; color: #166534; margin-bottom: 12px; }
        p { color: #475569; font-size: 1rem; margin-bottom: 28px; line-height: 1.6; }
        
        .meta-data-box {
            background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px;
            text-align: left; margin-bottom: 32px; font-size: 0.95rem;
        }
        .meta-data-box div { margin-bottom: 8px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 8px; }
        .meta-data-box div:last-child { margin-bottom: 0; border-bottom: 0; padding-bottom: 0; }
        
        .btn-home {
            display: inline-flex; background: #166534; color: #fff;
            padding: 14px 32px; border-radius: 8px; font-weight: 600;
            text-decoration: none; font-size: 0.95rem; transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(22, 101, 52, 0.2);
        }
        .btn-home:hover { background: #14532d; transform: translateY(-1px); }
    </style>
</head>
<body>

<div class="success-card">
    <div class="icon-shield">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <h2>অভিনন্দন! আবেদন সফল হয়েছে</h2>
    <p>স্মার্ট মহেশখালী পাইলট প্রজেক্টের ফ্রি অটোমেশন সফটওয়্যারের জন্য আপনার প্রতিষ্ঠানের তথ্য পুঙ্খানুপুঙ্খভাবে ডাটাবেজে সংরক্ষণ করা হয়েছে।</p>
    
    <div class="meta-data-box">
        <div><strong>শিক্ষা প্রতিষ্ঠান:</strong> <?= htmlspecialchars($data['school']) ?></div>
        <div><strong>প্রস্তাবিত ডোমেন রুট:</strong> <span style="font-family: monospace; color:#059669; font-weight:700;"><?= htmlspecialchars($data['subdomain']) ?></span></div>
    </div>

    <a href="index" class="btn-home bn">মূল পাতায় ফিরুন</a>
</div>

</body>
</html>