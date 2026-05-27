<?php
/**
 * Smart Maheshkhali — Privacy Policy.
 *
 * Plain-language statement of what data is collected, how it is used,
 * and what an applicant's rights are.
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
?>
<!doctype html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>প্রাইভেসি পলিসি | Privacy Policy | <?= e($CONFIG['app_name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Tiro+Bangla&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:#f6f8fb; --fg:#0f172a; --card:#fff;
            --border:#e2e8f0; --muted:#64748b;
            --brand:#0d9488;
            --blue:#dbeafe; --blue-fg:#1e40af;
            --radius:14px;
        }
        *, *::before, *::after { box-sizing:border-box; }
        body { margin:0; padding:48px 16px;
               background:var(--bg); color:var(--fg);
               font-family:'Inter','Tiro Bangla',sans-serif;
               -webkit-font-smoothing:antialiased; }
        .legal-shell { max-width:760px; margin:0 auto; }
        .legal-hero { text-align:center; margin-bottom:24px; }
        .legal-hero h1 { font-family:'Tiro Bangla',serif; font-size:1.85rem;
                         margin:0 0 6px; }
        .legal-hero p { color:var(--muted); margin:0; font-size:.92rem; }

        .legal-card { background:var(--card); border:1px solid var(--border);
                      border-radius:var(--radius); padding:36px 36px 32px;
                      box-shadow:0 1px 2px rgba(15,23,42,.04); }
        .legal-card h2 { font-family:'Tiro Bangla',serif; font-size:1.25rem;
                         margin:24px 0 10px; color:var(--fg); }
        .legal-card h2:first-child { margin-top:0; }
        .legal-card p  { line-height:1.75; color:#334155; margin:0 0 12px;
                         font-size:.95rem; }
        .legal-card ul { padding-left:22px; margin:6px 0 14px; line-height:1.75; }
        .legal-card li { margin-bottom:6px; color:#334155; font-size:.95rem; }
        .legal-card strong { color:var(--fg); }

        .legal-callout { background:var(--blue); color:var(--blue-fg);
                         padding:14px 16px; border-radius:10px;
                         margin:16px 0; border:1px solid #93c5fd;
                         font-size:.93rem; line-height:1.7; }

        .legal-meta { color:var(--muted); font-size:.82rem; margin-top:24px;
                      padding-top:16px; border-top:1px solid var(--border); }

        .legal-back { display:inline-flex; align-items:center; gap:6px;
                      margin-top:18px; padding:10px 18px;
                      background:var(--brand); color:#fff; text-decoration:none;
                      border-radius:8px; font-weight:600; font-size:.9rem; }
        .legal-back:hover { background:#0f766e; }
    </style>
</head>
<body>

<div class="legal-shell">
    <header class="legal-hero">
        <h1>প্রাইভেসি পলিসি &nbsp;·&nbsp; Privacy Policy</h1>
        <p>Smart Maheshkhali Free Pilot Programme</p>
    </header>

    <article class="legal-card">

        <h2>১. কোন তথ্য সংগ্রহ করা হয় &nbsp;·&nbsp; What we collect</h2>
        <p lang="bn">নিবন্ধন ফর্মের মাধ্যমে আমরা শুধুমাত্র যাচাইকরণ ও যোগাযোগের জন্য প্রয়োজনীয় তথ্য সংগ্রহ করি:</p>
        <ul>
            <li>প্রতিষ্ঠানের নাম (English / বাংলা), ধরন, ইউনিয়ন ও বিস্তারিত ঠিকানা।</li>
            <li>ঐচ্ছিক GPS কোঅর্ডিনেট (latitude / longitude) — শুধুমাত্র যদি আপনি “GPS লোকেশন সেট করুন” বাটন চাপেন।</li>
            <li>মোট শিক্ষার্থী ও শিক্ষক সংখ্যা; ICT অভিজ্ঞ শিক্ষকের প্রাপ্যতা।</li>
            <li>প্রতিনিধির নাম, মোবাইল নম্বর, ইমেইল ঠিকানা।</li>
            <li>স্কুলকে স্মার্ট করার পেছনের কারণ (লিখিত মতামত)।</li>
            <li>স্বয়ংক্রিয় সিস্টেম ডেটা: জমাদানের তারিখ, IP ঠিকানা (শুধু স্প্যাম প্রতিরোধের উদ্দেশ্যে)।</li>
        </ul>

        <h2>২. কীভাবে ব্যবহার করা হয় &nbsp;·&nbsp; How we use it</h2>
        <ul lang="bn">
            <li><strong>অংশগ্রহণ যাচাইকরণ</strong> — আবেদনের সত্যতা ও যোগ্যতা যাচাইয়ের জন্য।</li>
            <li><strong>যোগাযোগ</strong> — অনুমোদন, কনফিগারেশন বা পরবর্তী পদক্ষেপ সম্পর্কে অবহিত করতে।</li>
            <li><strong>সার্ভিস ডেলিভারি</strong> — অনুমোদিত প্রতিষ্ঠানের জন্য সাবডোমেন এবং স্কুল ম্যানেজমেন্ট সিস্টেম প্রস্তুত করতে।</li>
            <li><strong>প্রোগ্রাম মূল্যায়ন</strong> — পাইলট প্রোগ্রামের কার্যকারিতা পরিমাপের জন্য সম্মিলিত (anonymised) পরিসংখ্যান তৈরি করতে।</li>
        </ul>

        <h2>৩. তৃতীয় পক্ষ &nbsp;·&nbsp; Third Parties</h2>
        <div class="legal-callout">
            আপনার ব্যক্তিগত তথ্য আমরা <strong>বিক্রি করি না</strong> এবং বিজ্ঞাপনের উদ্দেশ্যে কারো সাথে শেয়ার করি না।
            We <strong>do not sell</strong> your personal information, and we do not share it for advertising purposes.
        </div>
        <p>
            আমরা শুধু সেই সব ক্ষেত্রে সীমিত তথ্য শেয়ার করতে পারি যা প্রোগ্রাম পরিচালনার জন্য প্রয়োজন
            (যেমন: হোস্টিং সরবরাহকারী, ইমেইল প্রেরণ, বা আইনগতভাবে আদেশপ্রাপ্ত হলে কর্তৃপক্ষ)।
            সেক্ষেত্রেও তথ্য শুধুমাত্র প্রকল্পের প্রয়োজনে এবং কঠোর গোপনীয়তা ও নিরাপত্তা শর্তে ব্যবহৃত হবে।
        </p>

        <h2>৪. ডেটা সংরক্ষণ &nbsp;·&nbsp; Data Retention</h2>
        <p lang="bn">
            পাইলট প্রোগ্রাম চলাকালীন এবং প্রকল্প সমাপ্তির পরে অডিটের প্রয়োজন মেটাতে যুক্তিসঙ্গত সময় পর্যন্ত ডেটা সংরক্ষিত থাকবে।
            পরে আবেদনকারীর অনুরোধে অথবা প্রয়োজন না থাকলে ডেটা মুছে ফেলা হবে।
        </p>
        <p>
            Data is retained for the duration of the pilot programme and a reasonable
            period thereafter to meet audit requirements. After that, records are
            deleted on request or when no longer needed.
        </p>

        <h2>৫. নিরাপত্তা &nbsp;·&nbsp; Security</h2>
        <ul lang="bn">
            <li>সকল প্রশাসনিক প্রবেশাধিকার পাসওয়ার্ড দ্বারা সুরক্ষিত (bcrypt হ্যাশ)।</li>
            <li>সমস্ত ফর্ম জমাদানে CSRF টোকেন যাচাই করা হয়।</li>
            <li>সেশন কুকি HttpOnly এবং সম্ভব হলে Secure ফ্ল্যাগ সহ পরিবেশিত হয়।</li>
            <li>সেনসিটিভ ফাইল সমূহ (config, sqlite ডেটাবেজ) ওয়েব সার্ভার পর্যায়ে ব্লক করা।</li>
        </ul>

        <h2>৬. আপনার অধিকার &nbsp;·&nbsp; Your Rights</h2>
        <p lang="bn">
            আপনি যেকোনো সময় কর্তৃপক্ষের সাথে যোগাযোগ করে আপনার তথ্য সংশোধন, হালনাগাদ অথবা মুছে ফেলার অনুরোধ করতে পারেন।
            যৌক্তিক অনুরোধে আমরা যথাশীঘ্র সম্ভব সাড়া দেব।
        </p>

        <h2>৭. পরিবর্তন &nbsp;·&nbsp; Changes to this policy</h2>
        <p>
            এই নীতিতে যেকোনো গুরুত্বপূর্ণ পরিবর্তন এই পেজে প্রকাশ করা হবে।
            “সর্বশেষ হালনাগাদ” তারিখ পরিবর্তন থেকে আপনি জানতে পারবেন।
            We may update this policy; any material change will appear on this page,
            and the "Last updated" date will reflect the change.
        </p>

        <p class="legal-meta">
            সর্বশেষ হালনাগাদ &nbsp;·&nbsp; Last updated:
            <?= e(date('F j, Y')) ?>
        </p>

        <a href="<?= e(url('/index.php')) ?>" class="legal-back">
            &larr; নিবন্ধন ফর্মে ফিরুন &nbsp;·&nbsp; Back to registration
        </a>
    </article>
</div>

</body>
</html>
