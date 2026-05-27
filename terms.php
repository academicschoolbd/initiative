<?php
/**
 * Smart Maheshkhali — Terms of Service.
 *
 * Plain-language statement that participation in the free pilot
 * programme is at the discretion of the SmartSchool authority and
 * that the authority's decisions are final.
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
?>
<!doctype html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>শর্তাবলী | Terms of Service | <?= e($CONFIG['app_name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Tiro+Bangla&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:#f6f8fb; --fg:#0f172a; --card:#fff;
            --border:#e2e8f0; --muted:#64748b;
            --brand:#0d9488; --amber:#fef3c7; --amber-fg:#92400e;
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
        .legal-card p { line-height:1.75; color:#334155; margin:0 0 12px;
                        font-size:.95rem; }
        .legal-card ul { padding-left:22px; margin:6px 0 14px; line-height:1.75; }
        .legal-card li { margin-bottom:6px; color:#334155; font-size:.95rem; }
        .legal-card strong { color:var(--fg); }

        .legal-callout { background:var(--amber); color:var(--amber-fg);
                         padding:14px 16px; border-radius:10px;
                         margin:16px 0; border:1px solid #fcd34d;
                         font-size:.93rem; line-height:1.7; }
        .legal-callout strong { color:#7c2d12; }

        .legal-meta { color:var(--muted); font-size:.82rem; margin-top:24px;
                      padding-top:16px; border-top:1px solid var(--border); }

        .legal-back { display:inline-flex; align-items:center; gap:6px;
                      margin-top:18px; padding:10px 18px;
                      background:var(--brand); color:#fff; text-decoration:none;
                      border-radius:8px; font-weight:600; font-size:.9rem; }
        .legal-back:hover { background:#0f766e; }

        @media (max-width: 600px) {
            body { padding: 24px 12px; }
            .legal-hero h1 { font-size: 1.45rem; }
            .legal-card { padding: 24px 20px; border-radius: 12px; }
            .legal-card h2 { font-size: 1.1rem; margin: 18px 0 8px; }
            .legal-card p, .legal-card li { font-size: .88rem; line-height: 1.7; }
            .legal-callout { padding: 12px; font-size: .85rem; }
            .legal-back { padding: 10px 16px; font-size: .85rem; }
        }
    </style>
</head>
<body>

<div class="legal-shell">
    <header class="legal-hero">
        <h1>শর্তাবলী &nbsp;·&nbsp; Terms of Service</h1>
        <p>Smart Maheshkhali Free Pilot Programme</p>
    </header>

    <article class="legal-card">

        <h2>১. পাইলট প্রোগ্রাম &amp; অংশগ্রহণ</h2>
        <p lang="bn">
            স্মার্ট মহেশখালী প্রকল্পটি সম্পূর্ণরূপে একটি <strong>বিনামূল্যের পাইলট
            উদ্যোগ</strong>। এই প্রোগ্রামে অংশগ্রহণ স্বেচ্ছামূলক এবং সম্পূর্ণরূপে
            কর্তৃপক্ষের নির্বাচনের উপর নির্ভরশীল।
        </p>
        <p>
            Smart Maheshkhali is offered as a <strong>free pilot initiative</strong>
            with no monetary cost to participating institutions. Participation is
            voluntary and at the sole discretion of the SmartSchool authority.
        </p>

        <h2>২. কর্তৃপক্ষের সিদ্ধান্ত চূড়ান্ত &nbsp;·&nbsp; Authority Decisions Are Final</h2>
        <div class="legal-callout">
            <p style="margin:0;" lang="bn">
                <strong>আবেদনকারী হিসেবে আপনি স্বীকার করছেন:</strong>
                স্মার্ট স্কুল কর্তৃপক্ষ পাইলট প্রোগ্রাম সংক্রান্ত যে সিদ্ধান্ত গ্রহণ করবে
                — অন্তর্ভুক্তি, বাদ দেওয়া, সাবডোমেন বরাদ্দ, ফিচার, সময়সীমা ইত্যাদি —
                আমি তা <strong>মেনে চলবো</strong>। অযৌক্তিক, অপ্রাসঙ্গিক বা ভিত্তিহীন
                অভিযোগ গ্রহণযোগ্য হবে না।
            </p>
            <p style="margin:8px 0 0;">
                <strong>By applying, you acknowledge that:</strong>
                you will obey and abide by every decision taken by the SmartSchool
                authority for this free initiative — including inclusion, exclusion,
                subdomain allocation, available features, timelines and any other
                operational matter. <strong>Illogical, irrelevant or unfounded
                complaints will not be accepted.</strong>
            </p>
        </div>

        <h2>৩. দায়িত্ব ও সীমাবদ্ধতা &nbsp;·&nbsp; Responsibilities and Limitations</h2>
        <ul lang="bn">
            <li>আবেদনকারী সঠিক ও সত্য তথ্য প্রদান করতে বাধ্য। ভুল তথ্য পেলে
                নিবন্ধন বাতিল করা হবে।</li>
            <li>প্ল্যাটফর্মটি “যেমন আছে” (<em>as-is</em>) ভিত্তিতে দেওয়া হচ্ছে।
                সেবার ধারাবাহিকতা, আপটাইম বা নির্দিষ্ট ফিচারের নিশ্চয়তা দেওয়া হচ্ছে না।</li>
            <li>SmartSchool কর্তৃপক্ষ যেকোনো সময়, যেকোনো কারণে প্রোগ্রাম
                সংশোধন, স্থগিত বা সমাপ্ত করার অধিকার সংরক্ষণ করে।</li>
            <li>প্রতিষ্ঠান কর্তৃক সরবরাহকৃত সকল কন্টেন্ট/তথ্যের জন্য
                দায়িত্ব প্রতিষ্ঠানের নিজস্ব।</li>
        </ul>
        <ul>
            <li>Applicants must provide accurate and truthful information.
                Misrepresentation may result in cancellation of the registration.</li>
            <li>The service is offered <em>as-is</em>, without any guarantee of
                continuity, uptime or feature availability.</li>
            <li>The SmartSchool authority reserves the right to amend, suspend
                or terminate the programme at any time, for any reason.</li>
            <li>Each institution is solely responsible for the content and
                information it provides through the platform.</li>
        </ul>

        <h2>৪. গ্রহণযোগ্য ব্যবহার &nbsp;·&nbsp; Acceptable Use</h2>
        <p lang="bn">
            পাইলট প্রোগ্রামটি একাডেমিক ও অপারেশনাল উদ্দেশ্যে ব্যবহারের জন্য।
            অননুমোদিত বাণিজ্যিক উদ্দেশ্য, বেআইনি কন্টেন্ট, স্প্যাম, বা সিস্টেমের
            অপব্যবহার কঠোরভাবে নিষিদ্ধ এবং তাৎক্ষণিক বহিষ্কারের কারণ হতে পারে।
        </p>
        <p>
            The platform is provided strictly for academic and operational use.
            Unauthorised commercial activity, illegal content, spam or any abuse
            of the system is strictly prohibited and may result in immediate
            removal from the programme.
        </p>

        <h2>৫. গোপনীয়তা &nbsp;·&nbsp; Privacy</h2>
        <p>
            ব্যক্তিগত তথ্যের সংগ্রহ ও ব্যবহার সংক্রান্ত বিস্তারিত
            <a href="<?= e(url('/privacy.php')) ?>"
               style="color:var(--brand); font-weight:600;">প্রাইভেসি পলিসিতে</a>
            উল্লেখ আছে। আবেদনের মাধ্যমে আপনি সেই নীতিরও সাথে সম্মত হচ্ছেন।
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
