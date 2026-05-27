<?php
/**
 * Smart Maheshkhali - public registration form (bd.education-style layout).
 *
 * 5-step wizard:
 *   Step 1 - Head Teacher / Contact   (প্রধান শিক্ষকের তথ্য)
 *   Step 2 - Institution Basic Info   (প্রতিষ্ঠানের মৌলিক তথ্য)
 *   Step 3 - Address & Location       (ঠিকানা ও অবস্থান)
 *   Step 4 - Infrastructure & Students(অবকাঠামো ও শিক্ষার্থী)
 *   Step 5 - Technology & Vision      (প্রযুক্তি ও লক্ষ্য)
 *
 * Falls back to a long scrollable form if JavaScript is disabled.
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

// Honour the on/off toggle - admin can close intake at any time.
$stmt = $db->prepare("SELECT value FROM settings WHERE key = 'form_enabled'");
$stmt->execute();
$formEnabled = $stmt->fetchColumn() === '1';

if (!$formEnabled) {
    ?>
    <!doctype html>
    <html lang="bn">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>নিবন্ধন সাময়িকভাবে বন্ধ আছে | <?= e($CONFIG['app_name']) ?></title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Tiro+Bangla&display=swap" rel="stylesheet">
        <style>
            body { background:#fafafa; font-family:'Inter','Tiro Bangla',sans-serif;
                   display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; padding:24px; }
            .box { background:#fff; border:1px solid #e4e4e7; border-radius:12px; padding:40px;
                   text-align:center; max-width:480px; box-shadow:0 10px 15px -3px rgba(0,0,0,.04); }
            h2 { font-family:'Tiro Bangla',serif; color:#0f172a; margin:0 0 12px; }
            p { color:#71717a; font-size:.95rem; margin:0; line-height:1.6; }
        </style>
    </head>
    <body>
        <div class="box">
            <h2>নিবন্ধন সাময়িকভাবে বন্ধ</h2>
            <p>স্মার্ট মহেশখালী পাইলট প্রোগ্রামের নতুন আবেদন গ্রহণ এই মুহূর্তে বন্ধ আছে। বিস্তারিত তথ্যের জন্য অনুগ্রহ করে কর্তৃপক্ষের সাথে যোগাযোগ করুন।</p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Pull validation errors and previously submitted values (one-shot).
$errors = (array) flash_pull('form_errors', []);
$old    = (array) flash_pull('old_input', []);

/** Old-input helper: HTML-escaped value for an input. */
$o = function (string $key, string $default = '') use ($old): string {
    $v = $old[$key] ?? $default;
    return e(is_string($v) ? $v : (string) $v);
};
?>
<!doctype html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>স্মার্ট মহেশখালী | ফ্রি স্কুল অটোমেশন নিবন্ধন পোর্টাল</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Tiro+Bangla&display=swap" rel="stylesheet">

    <style>
        /* -----------------------------------------------------------
           Design tokens
           ----------------------------------------------------------- */
        :root {
            --bg:#f6f8fb; --fg:#0f172a; --card:#fff;
            --border:#e2e8f0; --border-strong:#cbd5e1;
            --muted:#64748b; --brand:#0d9488;
            --pink:#fce7f3; --pink-fg:#9d174d;
            --amber:#fef3c7; --amber-fg:#92400e;
            --green:#ccfbf1; --green-fg:#0f766e;
            --blue:#dbeafe; --blue-fg:#1e40af;
            --danger:#dc2626;
            --radius:14px; --radius-sm:8px;
        }
        *, *::before, *::after { box-sizing:border-box; }
        body { margin:0; padding:48px 16px;
               background:var(--bg); color:var(--fg);
               font-family:'Inter','Tiro Bangla',sans-serif;
               -webkit-font-smoothing:antialiased; }
        a { color:inherit; }

        .bd-shell { max-width:760px; margin:0 auto; }
        .bd-hero { text-align:center; margin-bottom:28px; }
        .bd-hero h1 { font-family:'Tiro Bangla',serif; font-size:2rem;
                      margin:0 0 6px; color:var(--fg); }
        .bd-hero p { color:var(--muted); margin:0; font-size:.95rem; }

        /* -----------------------------------------------------------
           Stepper
           ----------------------------------------------------------- */
        .bd-stepper { display:flex; align-items:center; gap:6px;
                      background:var(--card); border:1px solid var(--border);
                      border-radius:var(--radius); padding:14px 20px;
                      margin-bottom:20px; box-shadow:0 1px 2px rgba(15,23,42,.04); }
        .bd-stepper-item { display:flex; align-items:center; gap:10px; flex:0 0 auto;
                           padding:4px 6px; border-radius:8px;
                           cursor:default; transition:.15s; }
        .bd-stepper-item[data-bd-step-indicator]:not(.is-active) { cursor:pointer; }
        .bd-stepper-num { width:30px; height:30px; flex:0 0 30px;
                          display:inline-flex; align-items:center; justify-content:center;
                          background:#f1f5f9; color:var(--muted);
                          border-radius:50%; font-weight:700; font-size:.92rem;
                          transition:.2s; }
        .bd-stepper-label { display:flex; flex-direction:column; line-height:1.15;
                            font-size:.85rem; color:var(--muted); }
        .bd-stepper-label strong { font-weight:600; color:#475569; font-size:.92rem; }
        .bd-stepper-label small { color:var(--muted); font-size:.78rem; margin-top:1px; }
        .bd-stepper-line { flex:1 1 auto; height:2px; background:var(--border); border-radius:1px; }

        .bd-stepper-item.is-active .bd-stepper-num { background:var(--brand); color:#fff;
                                                     box-shadow:0 0 0 4px rgba(13,148,136,.15); }
        .bd-stepper-item.is-active .bd-stepper-label strong { color:var(--fg); }
        .bd-stepper-item.is-done .bd-stepper-num { background:#0f766e; color:#fff; }
        .bd-stepper-item.is-done .bd-stepper-label strong { color:#0f766e; }
        @media (max-width:560px) {
            .bd-stepper-label { display:none; }
            .bd-stepper { padding:12px; gap:4px; }
        }

        /* -----------------------------------------------------------
           Wizard panels
           ----------------------------------------------------------- */
        .bd-step { display:none; background:var(--card); border:1px solid var(--border);
                   border-radius:var(--radius); padding:32px;
                   box-shadow:0 1px 2px rgba(15,23,42,.04); }
        .bd-step.is-active { display:block; animation:bdFade .35s ease forwards; }
        @keyframes bdFade { from { opacity:0; transform:translateY(4px); }
                            to   { opacity:1; transform:none; } }

        .bd-step-helper { font-family:'Tiro Bangla',serif; color:var(--muted);
                          font-size:.92rem; margin:0 0 22px; }

        /* -----------------------------------------------------------
           Two-column field grid + section banners
           ----------------------------------------------------------- */
        .bd-grid { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:14px 16px; }
        @media (max-width:600px) { .bd-grid { grid-template-columns:1fr; } }

        .bd-section { grid-column:1/-1; display:flex; align-items:center; gap:12px;
                      padding:14px 18px; border-radius:var(--radius-sm);
                      background:var(--blue); color:var(--blue-fg);
                      margin-top:6px; }
        .bd-section--pink  { background:var(--pink);  color:var(--pink-fg); }
        .bd-section--amber { background:var(--amber); color:var(--amber-fg); }
        .bd-section--green { background:var(--green); color:var(--green-fg); }
        .bd-section--standalone { margin:6px 0 0; }
        .bd-section-icon { display:inline-flex; align-items:center; justify-content:center;
                           width:32px; height:32px; flex:0 0 32px;
                           background:rgba(255,255,255,.6); border-radius:8px; }
        .bd-section-title { font-weight:700; font-size:.95rem; display:flex; flex-direction:column;
                            line-height:1.2; }
        .bd-section-title small { font-weight:500; opacity:.8; font-size:.78rem; margin-top:2px; }

        /* -----------------------------------------------------------
           Form controls
           ----------------------------------------------------------- */
        .bd-field { display:flex; flex-direction:column; gap:6px; }
        .bd-field--span-2 { grid-column:1/-1; }
        .bd-label { font-weight:600; font-size:.86rem; color:#334155; }
        .bd-label .req { color:var(--danger); margin-left:2px; }
        .bd-input, .bd-select { width:100%; padding:11px 13px; font-size:.94rem;
                                font-family:inherit; color:inherit; background:#fff;
                                border:1px solid var(--border); border-radius:8px;
                                transition:.15s; }
        .bd-input:focus, .bd-select:focus { outline:none; border-color:var(--brand);
                                            box-shadow:0 0 0 3px rgba(13,148,136,.12); }
        textarea.bd-input { resize:vertical; min-height:96px; }
        .bd-hint { color:var(--muted); font-size:.78rem; margin:4px 0 0; }

        /* -----------------------------------------------------------
           Subdomain widget
           ----------------------------------------------------------- */
        .bd-subdomain { display:flex; align-items:stretch; flex-wrap:wrap;
                        border:1px solid var(--border); border-radius:8px;
                        overflow:hidden; transition:.15s; background:#fff; }
        .bd-subdomain:focus-within { border-color:var(--brand);
                                     box-shadow:0 0 0 3px rgba(13,148,136,.12); }
        .bd-subdomain .bd-fix { padding:0 12px; display:flex; align-items:center;
                                background:#f1f5f9; color:var(--muted);
                                font-weight:600; font-size:.88rem; user-select:none; }
        .bd-subdomain .bd-fix--suffix { font-family:'Inter',monospace; color:var(--brand); }
        .bd-subdomain input { flex:1 1 160px; padding:11px 12px; border:0; outline:none;
                              background:transparent; font-size:.94rem; font-family:'Inter',monospace; color:inherit; }

        .bd-domain-preview { margin-top:10px; padding:10px 14px; border-radius:8px;
                             background:#f0fdfa; border:1px dashed var(--brand);
                             color:#115e59; font-size:.88rem;
                             display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
        .bd-domain-preview--empty { background:#f8fafc; border-color:var(--border); color:var(--muted); }
        .bd-domain-preview-url { font-family:'Inter',monospace; font-weight:700; }

        /* -----------------------------------------------------------
           Radio pills (yes / no)
           ----------------------------------------------------------- */
        .bd-pills { display:flex; gap:10px; flex-wrap:wrap; }
        .bd-pill { flex:1 1 0; min-width:160px; display:flex; align-items:center; gap:10px;
                   padding:11px 14px; border:1px solid var(--border); border-radius:8px;
                   background:#fff; cursor:pointer; font-size:.92rem; color:#334155;
                   transition:.15s; }
        .bd-pill:hover { border-color:var(--border-strong); }
        .bd-pill input { accent-color:var(--brand); }
        .bd-pill:has(input:checked) { border-color:var(--brand); background:#ecfeff;
                                      box-shadow:0 0 0 3px rgba(13,148,136,.1); }

        /* -----------------------------------------------------------
           Buttons & step nav
           ----------------------------------------------------------- */
        .bd-step-nav { display:flex; justify-content:space-between; align-items:center;
                       margin-top:28px; gap:12px; flex-wrap:wrap; }
        .bd-btn { background:var(--brand); color:#fff; border:none; border-radius:8px;
                  padding:12px 22px; font-size:.92rem; font-weight:600; cursor:pointer;
                  font-family:inherit; display:inline-flex; align-items:center; gap:8px;
                  transition:.15s; }
        .bd-btn:hover { background:#0f766e; }
        .bd-btn--ghost { background:#fff; color:var(--fg); border:1px solid var(--border); }
        .bd-btn--ghost:hover { background:#f1f5f9; }
        .bd-btn-arrow { font-size:1.05rem; line-height:1; }

        /* -----------------------------------------------------------
           Geo-fetch button
           ----------------------------------------------------------- */
        .bd-geo { background:#f1f5f9; color:#334155; border:1px solid var(--border);
                  padding:9px 14px; border-radius:8px; font-size:.84rem; font-weight:600;
                  cursor:pointer; display:inline-flex; align-items:center; gap:6px;
                  font-family:inherit; transition:.15s; margin-top:4px; width:fit-content; }
        .bd-geo:hover { background:#e2e8f0; }

        /* -----------------------------------------------------------
           Terms + error block
           ----------------------------------------------------------- */
        .bd-check { display:flex; align-items:flex-start; gap:10px;
                    padding:14px; background:#f8fafc; border:1px solid var(--border);
                    border-radius:8px; cursor:pointer; font-size:.9rem; color:#334155;
                    margin-top:14px; }
        .bd-check input { margin-top:3px; accent-color:var(--brand); }
        .bd-check a { color:var(--brand); }

        .bd-error { background:#fef2f2; border:1px solid #fecaca; color:#991b1b;
                    border-radius:var(--radius-sm); padding:14px 16px; margin-bottom:18px;
                    font-size:.88rem; }
        .bd-error ul { margin:6px 0 0 18px; padding:0; }
        .bd-error li + li { margin-top:3px; }

        /* -----------------------------------------------------------
           Honeypot (off-screen, never visible to users)
           ----------------------------------------------------------- */
        .bd-hp { position:absolute; left:-9999px; top:-9999px; width:1px; height:1px;
                 opacity:0; pointer-events:none; }

        .bd-footer-link { text-align:center; margin-top:22px; font-size:.82rem; color:var(--muted); }

        /* -----------------------------------------------------------
           Class-wise student grid (5 columns desktop, 2 mobile)
           ----------------------------------------------------------- */
        .bd-class-grid { display:grid; grid-template-columns:repeat(5, 1fr); gap:10px;
                         grid-column:1/-1; }
        @media (max-width:600px) { .bd-class-grid { grid-template-columns:repeat(2, 1fr); } }
        .bd-class-grid .bd-field { gap:4px; }
        .bd-class-grid .bd-label { font-size:.78rem; }
        .bd-class-grid .bd-input { padding:8px 10px; font-size:.88rem; }
    </style>
</head>
<body>

<div class="bd-shell">

    <header class="bd-hero">
        <h1>স্মার্ট মহেশখালী অ্যাপ্লিকেশন পোর্টাল</h1>
        <p>ফ্রি স্কুল অটোমেশন পাইলট প্রোগ্রামে অংশগ্রহণের জন্য নিবন্ধন করুন।</p>
    </header>

    <!-- ========= Step indicator ========= -->
    <nav class="bd-stepper" aria-label="Form steps">
        <div class="bd-stepper-item is-active" data-bd-step-indicator="1">
            <span class="bd-stepper-num">1</span>
            <span class="bd-stepper-label">
                <strong>Contact</strong>
                <small lang="bn">প্রধান শিক্ষক</small>
            </span>
        </div>
        <div class="bd-stepper-line"></div>
        <div class="bd-stepper-item" data-bd-step-indicator="2">
            <span class="bd-stepper-num">2</span>
            <span class="bd-stepper-label">
                <strong>Institution</strong>
                <small lang="bn">প্রতিষ্ঠান</small>
            </span>
        </div>
        <div class="bd-stepper-line"></div>
        <div class="bd-stepper-item" data-bd-step-indicator="3">
            <span class="bd-stepper-num">3</span>
            <span class="bd-stepper-label">
                <strong>Address</strong>
                <small lang="bn">ঠিকানা</small>
            </span>
        </div>
        <div class="bd-stepper-line"></div>
        <div class="bd-stepper-item" data-bd-step-indicator="4">
            <span class="bd-stepper-num">4</span>
            <span class="bd-stepper-label">
                <strong>Infrastructure</strong>
                <small lang="bn">অবকাঠামো</small>
            </span>
        </div>
        <div class="bd-stepper-line"></div>
        <div class="bd-stepper-item" data-bd-step-indicator="5">
            <span class="bd-stepper-num">5</span>
            <span class="bd-stepper-label">
                <strong>Technology</strong>
                <small lang="bn">প্রযুক্তি</small>
            </span>
        </div>
    </nav>

    <?php if (!empty($errors)): ?>
        <div class="bd-error" role="alert">
            <strong>অনুগ্রহ করে নিচের ত্রুটিসমূহ ঠিক করুন:</strong>
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="<?= e(url('/submit.php')) ?>" method="POST" id="bd-form" novalidate>
        <?= csrf_field() ?>
        <!-- Honeypot -->
        <div class="bd-hp" aria-hidden="true">
            <label for="website_url">Leave this empty</label>
            <input type="text" id="website_url" name="website_url" tabindex="-1" autocomplete="off">
        </div>

        <!-- ============ Step 1 - Head Teacher / Contact ============ -->
        <section class="bd-step is-active" data-bd-step="1">
            <p class="bd-step-helper" lang="bn">প্রধান শিক্ষক / প্রিন্সিপালের তথ্য পূরণ করুন</p>

            <div class="bd-grid">
                <div class="bd-section bd-section--pink">
                    <span class="bd-section-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </span>
                    <span class="bd-section-title">
                        Head Teacher / Principal
                        <small lang="bn">প্রধান শিক্ষকের তথ্য</small>
                    </span>
                </div>

                <div class="bd-field bd-field--span-2">
                    <label class="bd-label" for="owner_name">
                        Head Teacher / Principal Name (প্রধান শিক্ষকের নাম) <span class="req">*</span>
                    </label>
                    <input class="bd-input" id="owner_name" name="owner_name" required
                           value="<?= $o('owner_name') ?>"
                           placeholder="প্রধান শিক্ষকের পূর্ণ নাম">
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="owner_phone">
                        Mobile Number (মোবাইল নম্বর) <span class="req">*</span>
                    </label>
                    <input class="bd-input" id="owner_phone" type="tel" name="owner_phone" required
                           inputmode="tel" maxlength="20"
                           value="<?= $o('owner_phone') ?>" placeholder="01XXXXXXXXX">
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="head_teacher_whatsapp">
                        WhatsApp Number (হোয়াটসঅ্যাপ নম্বর)
                    </label>
                    <input class="bd-input" id="head_teacher_whatsapp" type="tel" name="head_teacher_whatsapp"
                           inputmode="tel" maxlength="20"
                           value="<?= $o('head_teacher_whatsapp') ?>" placeholder="01XXXXXXXXX">
                </div>

                <div class="bd-field bd-field--span-2">
                    <label class="bd-label" for="owner_email">
                        Email Address (ইমেইল) <span class="req">*</span>
                    </label>
                    <input class="bd-input" id="owner_email" type="email" name="owner_email" required
                           value="<?= $o('owner_email') ?>" placeholder="you@school.edu.bd">
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="managing_committee_chairman">
                        Managing Committee Chairman (ম্যানেজিং কমিটির সভাপতি)
                    </label>
                    <input class="bd-input" id="managing_committee_chairman" name="managing_committee_chairman"
                           value="<?= $o('managing_committee_chairman') ?>"
                           placeholder="সভাপতির নাম">
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="managing_committee_phone">
                        Chairman's Mobile (সভাপতির মোবাইল)
                    </label>
                    <input class="bd-input" id="managing_committee_phone" type="tel" name="managing_committee_phone"
                           inputmode="tel" maxlength="20"
                           value="<?= $o('managing_committee_phone') ?>" placeholder="01XXXXXXXXX">
                </div>
            </div>

            <div class="bd-step-nav">
                <span></span>
                <button type="button" class="bd-btn" data-bd-next="2">
                    পরবর্তী &nbsp;&middot;&nbsp; Next
                    <span class="bd-btn-arrow" aria-hidden="true">&#8594;</span>
                </button>
            </div>
        </section>

        <!-- ============ Step 2 - Institution Info ============ -->
        <section class="bd-step" data-bd-step="2">
            <p class="bd-step-helper" lang="bn">প্রতিষ্ঠানের মৌলিক তথ্য পূরণ করুন</p>

            <div class="bd-grid">
                <div class="bd-section">
                    <span class="bd-section-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 21h18M5 21V10l7-5 7 5v11M9 21v-6h6v6"/>
                        </svg>
                    </span>
                    <span class="bd-section-title">
                        Institution Basic Info
                        <small lang="bn">প্রতিষ্ঠানের মৌলিক তথ্য</small>
                    </span>
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="institution_type">
                        Type of Institute (প্রতিষ্ঠানের ধরন) <span class="req">*</span>
                    </label>
                    <select class="bd-select" id="institution_type" name="institution_type" required>
                        <option value="">-- নির্বাচন করুন --</option>
                        <?php
                        $instTypes = institution_types();
                        $selType = (string) ($old['institution_type'] ?? '');
                        foreach ($instTypes as $code => $label): ?>
                            <option value="<?= e($code) ?>" <?= $selType === $code ? 'selected' : '' ?>>
                                <?= e($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="mpo_status">
                        MPO Status (এমপিও স্ট্যাটাস) <span class="req">*</span>
                    </label>
                    <select class="bd-select" id="mpo_status" name="mpo_status" required>
                        <option value="">-- নির্বাচন করুন --</option>
                        <?php
                        $mpoOptions = ['mpo' => 'MPO ভুক্ত (MPO)', 'non_mpo' => 'নন-এমপিও (Non-MPO)', 'newly_nationalized' => 'নবজাতীয়করণ (Newly Nationalized)'];
                        $selMpo = (string) ($old['mpo_status'] ?? '');
                        foreach ($mpoOptions as $mCode => $mLabel): ?>
                            <option value="<?= e($mCode) ?>" <?= $selMpo === $mCode ? 'selected' : '' ?>><?= e($mLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="school_name">
                        School Name (English) <span class="req">*</span>
                    </label>
                    <input class="bd-input" id="school_name" name="school_name" required
                           value="<?= $o('school_name') ?>"
                           placeholder="e.g. Moheshkhali Government High School">
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="school_name_bn">
                        School Name (বাংলা নাম)
                    </label>
                    <input class="bd-input" id="school_name_bn" name="school_name_bn"
                           value="<?= $o('school_name_bn') ?>"
                           placeholder="যেমন: মহেশখালী সরকারি উচ্চ বিদ্যালয়">
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="eiin_number">
                        EIIN Number (ইআইআইএন নম্বর)
                    </label>
                    <input class="bd-input" id="eiin_number" name="eiin_number"
                           inputmode="numeric" pattern="[0-9]*"
                           value="<?= $o('eiin_number') ?>" placeholder="যেমন: 104523">
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="establishment_year">
                        Establishment Year (প্রতিষ্ঠার সাল)
                    </label>
                    <input class="bd-input" id="establishment_year" name="establishment_year"
                           type="number" min="1800" max="2025" inputmode="numeric"
                           value="<?= $o('establishment_year') ?>" placeholder="যেমন: 1985">
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="school_phone">
                        School Phone (প্রতিষ্ঠানের ফোন)
                    </label>
                    <input class="bd-input" id="school_phone" type="tel" name="school_phone"
                           inputmode="tel"
                           value="<?= $o('school_phone') ?>" placeholder="01XXXXXXXXX">
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="school_email">
                        School Email (প্রতিষ্ঠানের ইমেইল)
                    </label>
                    <input class="bd-input" id="school_email" type="email" name="school_email"
                           value="<?= $o('school_email') ?>" placeholder="school@example.com">
                </div>

                <div class="bd-field bd-field--span-2">
                    <label class="bd-label" for="school_website">
                        Website (ওয়েবসাইট)
                    </label>
                    <input class="bd-input" id="school_website" type="url" name="school_website"
                           value="<?= $o('school_website') ?>" placeholder="https://www.example.com">
                </div>
            </div>

            <div class="bd-step-nav">
                <button type="button" class="bd-btn bd-btn--ghost" data-bd-prev="1">
                    <span class="bd-btn-arrow" aria-hidden="true">&#8592;</span>
                    Back &nbsp;&middot;&nbsp; পূর্ববর্তী
                </button>
                <button type="button" class="bd-btn" data-bd-next="3">
                    পরবর্তী &nbsp;&middot;&nbsp; Next
                    <span class="bd-btn-arrow" aria-hidden="true">&#8594;</span>
                </button>
            </div>
        </section>

        <!-- ============ Step 3 - Address & Location ============ -->
        <section class="bd-step" data-bd-step="3">
            <p class="bd-step-helper" lang="bn">প্রতিষ্ঠানের ঠিকানা ও অবস্থান</p>

            <div class="bd-grid">
                <div class="bd-section bd-section--amber">
                    <span class="bd-section-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                    </span>
                    <span class="bd-section-title">
                        Address & Location
                        <small lang="bn">ঠিকানা ও অবস্থান</small>
                    </span>
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="division">
                        Division (বিভাগ) <span class="req">*</span>
                    </label>
                    <select class="bd-select" id="division" name="division" required>
                        <?php
                        $divisions = ['ঢাকা', 'চট্টগ্রাম', 'রাজশাহী', 'খুলনা', 'বরিশাল', 'সিলেট', 'রংপুর', 'ময়মনসিংহ'];
                        $selDiv = (string) ($old['division'] ?? 'চট্টগ্রাম');
                        foreach ($divisions as $div): ?>
                            <option value="<?= e($div) ?>" <?= $selDiv === $div ? 'selected' : '' ?>><?= e($div) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="district">
                        District (জেলা) <span class="req">*</span>
                    </label>
                    <select class="bd-select" id="district" name="district" required>
                        <option value="কক্সবাজার" selected>কক্সবাজার</option>
                    </select>
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="upazila">
                        Upazila (উপজেলা) <span class="req">*</span>
                    </label>
                    <select class="bd-select" id="upazila" name="upazila" required>
                        <option value="মহেশখালী" selected>মহেশখালী</option>
                    </select>
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="union-selector">
                        Union (ইউনিয়ন) <span class="req">*</span>
                    </label>
                    <select class="bd-select" id="union-selector" name="union_name" required
                            data-prev="<?= $o('union_name') ?>">
                        <option value="">ইউনিয়ন লোড হচ্ছে...</option>
                    </select>
                    <input type="hidden" name="union_ward" id="union_ward" value="<?= $o('union_ward') ?>">
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="village">
                        Village/Mohalla (গ্রাম/মহল্লা)
                    </label>
                    <input class="bd-input" id="village" name="village"
                           value="<?= $o('village') ?>" placeholder="গ্রাম বা মহল্লার নাম">
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="postal_code">
                        Postal Code (পোস্ট কোড)
                    </label>
                    <input class="bd-input" id="postal_code" name="postal_code"
                           value="<?= $o('postal_code') ?>" placeholder="যেমন: 4760">
                </div>

                <div class="bd-field bd-field--span-2">
                    <label class="bd-label" for="detailed_address">
                        Detailed Address (বিস্তারিত ঠিকানা) <span class="req">*</span>
                    </label>
                    <input class="bd-input" id="detailed_address" name="detailed_address" required
                           value="<?= $o('detailed_address') ?>"
                           placeholder="গ্রাম, ওয়ার্ড বা সুনির্দিষ্ট অবস্থান">
                </div>

                <div class="bd-section bd-section--green">
                    <span class="bd-section-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 2v4M12 18v4M4 12h4M16 12h4"/>
                        </svg>
                    </span>
                    <span class="bd-section-title">
                        GPS Coordinates
                        <small lang="bn">জিপিএস অবস্থান</small>
                    </span>
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="geo-lat">Latitude</label>
                    <input class="bd-input" id="geo-lat" name="latitude" readonly
                           value="<?= $o('latitude') ?>" placeholder="অটোমেটিক জেনারেট হবে">
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="geo-lng">Longitude</label>
                    <input class="bd-input" id="geo-lng" name="longitude" readonly
                           value="<?= $o('longitude') ?>" placeholder="অটোমেটিক জেনারেট হবে">
                </div>

                <div class="bd-field bd-field--span-2">
                    <button type="button" class="bd-geo" onclick="fetchCoordinates()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 2v4M12 18v4M4 12h4M16 12h4"/>
                        </svg>
                        বর্তমান GPS লোকেশন সেট করুন
                    </button>
                </div>
            </div>

            <div class="bd-step-nav">
                <button type="button" class="bd-btn bd-btn--ghost" data-bd-prev="2">
                    <span class="bd-btn-arrow" aria-hidden="true">&#8592;</span>
                    Back &nbsp;&middot;&nbsp; পূর্ববর্তী
                </button>
                <button type="button" class="bd-btn" data-bd-next="4">
                    পরবর্তী &nbsp;&middot;&nbsp; Next
                    <span class="bd-btn-arrow" aria-hidden="true">&#8594;</span>
                </button>
            </div>
        </section>

        <!-- ============ Step 4 - Infrastructure & Students ============ -->
        <section class="bd-step" data-bd-step="4">
            <p class="bd-step-helper" lang="bn">অবকাঠামো ও শিক্ষার্থী সম্পর্কিত তথ্য</p>

            <div class="bd-grid">
                <div class="bd-section">
                    <span class="bd-section-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 21h18M5 21V10l7-5 7 5v11M9 21v-6h6v6"/>
                        </svg>
                    </span>
                    <span class="bd-section-title">
                        Infrastructure
                        <small lang="bn">অবকাঠামো</small>
                    </span>
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="num_buildings">
                        Number of Buildings (ভবন সংখ্যা)
                    </label>
                    <input class="bd-input" id="num_buildings" name="num_buildings"
                           type="number" min="0" inputmode="numeric"
                           value="<?= $o('num_buildings') ?>" placeholder="যেমন: 3">
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="num_classrooms">
                        Number of Classrooms (শ্রেণীকক্ষ সংখ্যা)
                    </label>
                    <input class="bd-input" id="num_classrooms" name="num_classrooms"
                           type="number" min="0" inputmode="numeric"
                           value="<?= $o('num_classrooms') ?>" placeholder="যেমন: 12">
                </div>

                <div class="bd-field">
                    <label class="bd-label" id="lbl-computer-lab">
                        Computer Lab (কম্পিউটার ল্যাব)
                    </label>
                    <div role="radiogroup" aria-labelledby="lbl-computer-lab" class="bd-pills">
                        <?php $compLab = (string) ($old['has_computer_lab'] ?? ''); ?>
                        <label class="bd-pill">
                            <input type="radio" name="has_computer_lab" value="yes" <?= $compLab === 'yes' ? 'checked' : '' ?>>
                            <span>হ্যাঁ (Yes)</span>
                        </label>
                        <label class="bd-pill">
                            <input type="radio" name="has_computer_lab" value="no" <?= $compLab === 'no' ? 'checked' : '' ?>>
                            <span>না (No)</span>
                        </label>
                    </div>
                </div>

                <div class="bd-field">
                    <label class="bd-label" id="lbl-science-lab">
                        Science Lab (বিজ্ঞানাগার)
                    </label>
                    <div role="radiogroup" aria-labelledby="lbl-science-lab" class="bd-pills">
                        <?php $sciLab = (string) ($old['has_science_lab'] ?? ''); ?>
                        <label class="bd-pill">
                            <input type="radio" name="has_science_lab" value="yes" <?= $sciLab === 'yes' ? 'checked' : '' ?>>
                            <span>হ্যাঁ (Yes)</span>
                        </label>
                        <label class="bd-pill">
                            <input type="radio" name="has_science_lab" value="no" <?= $sciLab === 'no' ? 'checked' : '' ?>>
                            <span>না (No)</span>
                        </label>
                    </div>
                </div>

                <div class="bd-field">
                    <label class="bd-label" id="lbl-library">
                        Library (লাইব্রেরি)
                    </label>
                    <div role="radiogroup" aria-labelledby="lbl-library" class="bd-pills">
                        <?php $lib = (string) ($old['has_library'] ?? ''); ?>
                        <label class="bd-pill">
                            <input type="radio" name="has_library" value="yes" <?= $lib === 'yes' ? 'checked' : '' ?>>
                            <span>হ্যাঁ (Yes)</span>
                        </label>
                        <label class="bd-pill">
                            <input type="radio" name="has_library" value="no" <?= $lib === 'no' ? 'checked' : '' ?>>
                            <span>না (No)</span>
                        </label>
                    </div>
                </div>

                <div class="bd-field">
                    <label class="bd-label" id="lbl-playground">
                        Playground (খেলার মাঠ)
                    </label>
                    <div role="radiogroup" aria-labelledby="lbl-playground" class="bd-pills">
                        <?php $pg = (string) ($old['has_playground'] ?? ''); ?>
                        <label class="bd-pill">
                            <input type="radio" name="has_playground" value="yes" <?= $pg === 'yes' ? 'checked' : '' ?>>
                            <span>হ্যাঁ (Yes)</span>
                        </label>
                        <label class="bd-pill">
                            <input type="radio" name="has_playground" value="no" <?= $pg === 'no' ? 'checked' : '' ?>>
                            <span>না (No)</span>
                        </label>
                    </div>
                </div>

                <div class="bd-section bd-section--pink">
                    <span class="bd-section-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </span>
                    <span class="bd-section-title">
                        Student Info
                        <small lang="bn">শিক্ষার্থী তথ্য</small>
                    </span>
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="total_students">
                        Total Students (মোট শিক্ষার্থী) <span class="req">*</span>
                    </label>
                    <input class="bd-input" id="total_students" name="total_students" required
                           type="number" inputmode="numeric" min="0" max="20000"
                           value="<?= $o('total_students') ?>" placeholder="যেমন: 350">
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="total_students_boys">
                        Boys (ছাত্র)
                    </label>
                    <input class="bd-input" id="total_students_boys" name="total_students_boys"
                           type="number" inputmode="numeric" min="0"
                           value="<?= $o('total_students_boys') ?>" placeholder="ছাত্র সংখ্যা">
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="total_students_girls">
                        Girls (ছাত্রী)
                    </label>
                    <input class="bd-input" id="total_students_girls" name="total_students_girls"
                           type="number" inputmode="numeric" min="0"
                           value="<?= $o('total_students_girls') ?>" placeholder="ছাত্রী সংখ্যা">
                </div>

                <!-- Class-wise student grid -->
                <div class="bd-field bd-field--span-2" style="margin-top:6px;">
                    <label class="bd-label">Class-wise Students (শ্রেণী অনুযায়ী শিক্ষার্থী)</label>
                </div>
                <div class="bd-class-grid">
                    <?php
                    $bnDigits = ['১ম', '২য়', '৩য়', '৪র্থ', '৫ম', '৬ষ্ঠ', '৭ম', '৮ম', '৯ম', '১০ম'];
                    for ($i = 1; $i <= 10; $i++): ?>
                    <div class="bd-field">
                        <label class="bd-label" for="students_class_<?= $i ?>">Class <?= $i ?> (<?= $bnDigits[$i - 1] ?>)</label>
                        <input class="bd-input" id="students_class_<?= $i ?>" name="students_class_<?= $i ?>"
                               type="number" inputmode="numeric" min="0"
                               value="<?= $o('students_class_' . $i) ?>" placeholder="0">
                    </div>
                    <?php endfor; ?>
                </div>

                <div class="bd-section bd-section--green">
                    <span class="bd-section-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </span>
                    <span class="bd-section-title">
                        Teacher Info
                        <small lang="bn">শিক্ষক তথ্য</small>
                    </span>
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="total_teachers">
                        Total Teachers (মোট শিক্ষক) <span class="req">*</span>
                    </label>
                    <input class="bd-input" id="total_teachers" name="total_teachers" required
                           type="number" inputmode="numeric" min="0" max="2000"
                           value="<?= $o('total_teachers') ?>" placeholder="যেমন: 18">
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="male_teachers">
                        Male (পুরুষ)
                    </label>
                    <input class="bd-input" id="male_teachers" name="male_teachers"
                           type="number" inputmode="numeric" min="0"
                           value="<?= $o('male_teachers') ?>" placeholder="পুরুষ শিক্ষক">
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="female_teachers">
                        Female (মহিলা)
                    </label>
                    <input class="bd-input" id="female_teachers" name="female_teachers"
                           type="number" inputmode="numeric" min="0"
                           value="<?= $o('female_teachers') ?>" placeholder="মহিলা শিক্ষক">
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="trained_teachers">
                        Trained (প্রশিক্ষণপ্রাপ্ত)
                    </label>
                    <input class="bd-input" id="trained_teachers" name="trained_teachers"
                           type="number" inputmode="numeric" min="0"
                           value="<?= $o('trained_teachers') ?>" placeholder="প্রশিক্ষণপ্রাপ্ত">
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="untrained_teachers">
                        Untrained (প্রশিক্ষণবিহীন)
                    </label>
                    <input class="bd-input" id="untrained_teachers" name="untrained_teachers"
                           type="number" inputmode="numeric" min="0"
                           value="<?= $o('untrained_teachers') ?>" placeholder="প্রশিক্ষণবিহীন">
                </div>
            </div>

            <div class="bd-step-nav">
                <button type="button" class="bd-btn bd-btn--ghost" data-bd-prev="3">
                    <span class="bd-btn-arrow" aria-hidden="true">&#8592;</span>
                    Back &nbsp;&middot;&nbsp; পূর্ববর্তী
                </button>
                <button type="button" class="bd-btn" data-bd-next="5">
                    পরবর্তী &nbsp;&middot;&nbsp; Next
                    <span class="bd-btn-arrow" aria-hidden="true">&#8594;</span>
                </button>
            </div>
        </section>

        <!-- ============ Step 5 - Technology & Vision ============ -->
        <section class="bd-step" data-bd-step="5">
            <p class="bd-step-helper" lang="bn">প্রযুক্তি ও ভবিষ্যৎ লক্ষ্য সম্পর্কিত তথ্য</p>

            <div class="bd-grid">
                <div class="bd-section">
                    <span class="bd-section-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                            <line x1="8" y1="21" x2="16" y2="21"/>
                            <line x1="12" y1="17" x2="12" y2="21"/>
                        </svg>
                    </span>
                    <span class="bd-section-title">
                        Technology
                        <small lang="bn">প্রযুক্তি</small>
                    </span>
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="num_computers">
                        Number of Computers (কম্পিউটার সংখ্যা)
                    </label>
                    <input class="bd-input" id="num_computers" name="num_computers"
                           type="number" inputmode="numeric" min="0"
                           value="<?= $o('num_computers') ?>" placeholder="যেমন: 10">
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="num_projectors">
                        Projectors (প্রজেক্টর সংখ্যা)
                    </label>
                    <input class="bd-input" id="num_projectors" name="num_projectors"
                           type="number" inputmode="numeric" min="0"
                           value="<?= $o('num_projectors') ?>" placeholder="যেমন: 2">
                </div>

                <div class="bd-field">
                    <label class="bd-label" id="lbl-has-internet">
                        Internet Connection (ইন্টারনেট সংযোগ) <span class="req">*</span>
                    </label>
                    <div role="radiogroup" aria-labelledby="lbl-has-internet" class="bd-pills">
                        <?php $hasNet = (string) ($old['has_internet'] ?? ''); ?>
                        <label class="bd-pill">
                            <input type="radio" name="has_internet" value="yes" required <?= $hasNet === 'yes' ? 'checked' : '' ?>>
                            <span>হ্যাঁ (Yes)</span>
                        </label>
                        <label class="bd-pill">
                            <input type="radio" name="has_internet" value="no" <?= $hasNet === 'no' ? 'checked' : '' ?>>
                            <span>না (No)</span>
                        </label>
                    </div>
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="internet_type">
                        Internet Type (ইন্টারনেটের ধরন)
                    </label>
                    <select class="bd-select" id="internet_type" name="internet_type">
                        <option value="">-- নির্বাচন করুন --</option>
                        <?php
                        $netTypes = ['broadband' => 'ব্রডব্যান্ড (Broadband)', 'mobile_data' => 'মোবাইল ডাটা (Mobile Data)', 'fiber' => 'ফাইবার (Fiber)', 'none' => 'নেই (None)'];
                        $selNet = (string) ($old['internet_type'] ?? '');
                        foreach ($netTypes as $nCode => $nLabel): ?>
                            <option value="<?= e($nCode) ?>" <?= $selNet === $nCode ? 'selected' : '' ?>><?= e($nLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="bd-field">
                    <label class="bd-label" id="lbl-multimedia">
                        Multimedia Classroom (মাল্টিমিডিয়া ক্লাসরুম)
                    </label>
                    <div role="radiogroup" aria-labelledby="lbl-multimedia" class="bd-pills">
                        <?php $mm = (string) ($old['has_multimedia_classroom'] ?? ''); ?>
                        <label class="bd-pill">
                            <input type="radio" name="has_multimedia_classroom" value="yes" <?= $mm === 'yes' ? 'checked' : '' ?>>
                            <span>হ্যাঁ (Yes)</span>
                        </label>
                        <label class="bd-pill">
                            <input type="radio" name="has_multimedia_classroom" value="no" <?= $mm === 'no' ? 'checked' : '' ?>>
                            <span>না (No)</span>
                        </label>
                    </div>
                </div>

                <div class="bd-field">
                    <label class="bd-label" id="lbl-ict-teacher">
                        ICT Teacher Available (আইসিটি শিক্ষক আছেন কি?) <span class="req">*</span>
                    </label>
                    <div role="radiogroup" aria-labelledby="lbl-ict-teacher" class="bd-pills">
                        <?php $ictPicked = (string) ($old['ict_teacher_available'] ?? ''); ?>
                        <label class="bd-pill">
                            <input type="radio" name="ict_teacher_available" value="yes" required <?= $ictPicked === 'yes' ? 'checked' : '' ?>>
                            <span>হ্যাঁ আছেন (Yes)</span>
                        </label>
                        <label class="bd-pill">
                            <input type="radio" name="ict_teacher_available" value="no" <?= $ictPicked === 'no' ? 'checked' : '' ?>>
                            <span>না, নেই (No)</span>
                        </label>
                    </div>
                </div>

                <div class="bd-field bd-field--span-2">
                    <label class="bd-label" for="existing_software">
                        Existing Software (ব্যবহৃত সফটওয়্যার)
                    </label>
                    <textarea class="bd-input" id="existing_software" name="existing_software"
                              rows="2" maxlength="1000"
                              placeholder="বর্তমানে কোন সফটওয়্যার ব্যবহার করছেন (যেমন: MS Office, Google Classroom)"><?= $o('existing_software') ?></textarea>
                </div>

                <div class="bd-section bd-section--green bd-section--standalone">
                    <span class="bd-section-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M2 12h20M12 2a15 15 0 0 1 0 20M12 2a15 15 0 0 0 0 20"/>
                        </svg>
                    </span>
                    <span class="bd-section-title">
                        Website Domain
                        <small lang="bn">আপনার ওয়েবসাইটের ঠিকানা</small>
                    </span>
                </div>

                <div class="bd-field bd-field--span-2" style="margin-top:10px;">
                    <label class="bd-label" for="subdomain">
                        Short name / prefix for your website domain <span class="req">*</span>
                    </label>
                    <div class="bd-subdomain">
                        <span class="bd-fix">www.</span>
                        <input id="subdomain" name="subdomain" required
                               pattern="[a-z0-9](?:[a-z0-9-]{1,30}[a-z0-9])"
                               minlength="3" maxlength="32"
                               value="<?= $o('subdomain') ?>" placeholder="example: mghs"
                               autocapitalize="off" autocomplete="off" spellcheck="false"
                               data-bd-subdomain>
                        <span class="bd-fix bd-fix--suffix">.smartschool.bd</span>
                    </div>
                    <small class="bd-hint">
                        Lowercase letters, digits and hyphens. 3-32 characters. No spaces.
                    </small>
                    <div class="bd-domain-preview bd-domain-preview--empty" data-bd-preview>
                        <span lang="bn">আপনার ওয়েবসাইট হবে :</span>
                        <span class="bd-domain-preview-url" data-bd-preview-url>your-name.smartschool.bd</span>
                    </div>
                </div>

                <div class="bd-section bd-section--amber bd-section--standalone">
                    <span class="bd-section-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2l2.5 6.5L21 9l-5 4 1.5 7L12 16l-5.5 4L8 13 3 9l6.5-.5z"/>
                        </svg>
                    </span>
                    <span class="bd-section-title">
                        Vision &amp; Notes
                        <small lang="bn">আপনার লক্ষ্য</small>
                    </span>
                </div>

                <div class="bd-field bd-field--span-2" style="margin-top:10px;">
                    <label class="bd-label" for="smart_school_reason">
                        Why do you want to make your school smart? (স্কুলকে স্মার্ট করতে চান কেন?) <span class="req">*</span>
                    </label>
                    <textarea class="bd-input" id="smart_school_reason" name="smart_school_reason"
                              minlength="20" maxlength="2000" required
                              placeholder="আপনার লক্ষ্য, প্রত্যাশা এবং স্মার্ট স্কুল প্রোগ্রামের প্রতি আগ্রহের কারণ সংক্ষেপে বর্ণনা করুন..."><?= $o('smart_school_reason') ?></textarea>
                    <small class="bd-hint">কমপক্ষে ২০ অক্ষর, সর্বোচ্চ ২০০০ অক্ষর।</small>
                </div>

                <div class="bd-field bd-field--span-2">
                    <label class="bd-label" for="notes">
                        Anything else? (অন্য কিছু?) <small style="opacity:.7">(optional)</small>
                    </label>
                    <textarea class="bd-input" id="notes" name="notes" maxlength="2000" rows="2"
                              placeholder="যেমন: ৩৫০ জন শিক্ষার্থী, দুটি ক্যাম্পাস, অভিভাবক SMS দরকার..."><?= $o('notes') ?></textarea>
                </div>
            </div>

            <label class="bd-check" for="final-check">
                <input type="checkbox" name="terms_accept" value="1" id="final-check" required
                       <?= !empty($old['terms_accept']) ? 'checked' : '' ?>>
                <span>
                    প্রদানকৃত সকল তথ্য সত্য এবং আমি পাইলট প্রোগ্রামের যাচাইকরণ প্রক্রিয়ার সাথে একমত।
                    আমি
                    <a href="<?= e(url('/terms.php')) ?>" target="_blank" rel="noopener">
                        শর্তাবলী &middot; Terms of Service
                    </a>
                    এবং
                    <a href="<?= e(url('/privacy.php')) ?>" target="_blank" rel="noopener">
                        প্রাইভেসি পলিসি &middot; Privacy Policy
                    </a>
                    মেনে নিচ্ছি।
                </span>
            </label>

            <div class="bd-step-nav">
                <button type="button" class="bd-btn bd-btn--ghost" data-bd-prev="4">
                    <span class="bd-btn-arrow" aria-hidden="true">&#8592;</span>
                    Back &nbsp;&middot;&nbsp; পূর্ববর্তী
                </button>
                <button type="submit" class="bd-btn">
                    সাবমিট করুন &nbsp;&middot;&nbsp; Submit signup
                    <span class="bd-btn-arrow" aria-hidden="true">&#8594;</span>
                </button>
            </div>
        </section>
    </form>

    <p class="bd-footer-link">
        &copy; <?= e((string) date('Y')) ?> <?= e($CONFIG['app_name']) ?>
    </p>
</div>

<script>
(function () {
    'use strict';

    /* ============ 5-step wizard navigation ============ */
    var stepEls    = document.querySelectorAll('[data-bd-step]');
    var indicators = document.querySelectorAll('[data-bd-step-indicator]');

    function activateStep(n) {
        n = String(n);
        stepEls.forEach(function (el) {
            el.classList.toggle('is-active', el.getAttribute('data-bd-step') === n);
        });
        indicators.forEach(function (el) {
            var k = el.getAttribute('data-bd-step-indicator');
            el.classList.toggle('is-active', k === n);
            el.classList.toggle('is-done',  Number(k) < Number(n));
        });
        var top = document.querySelector('.bd-stepper');
        if (top && top.scrollIntoView) {
            top.scrollIntoView({behavior: 'smooth', block: 'start'});
        }
    }

    function validateStep(n) {
        var panel = document.querySelector('[data-bd-step="' + n + '"]');
        if (!panel) return true;
        var inputs = panel.querySelectorAll('[required]');
        for (var i = 0; i < inputs.length; i++) {
            var el = inputs[i];
            // For radio groups, accept if ANY radio in the group is checked.
            if (el.type === 'radio') {
                var name = el.name;
                var checked = panel.querySelector('input[type=radio][name="' + name + '"]:checked');
                if (!checked) { el.focus(); el.reportValidity(); return false; }
                // Skip the rest of this radio group.
                while (i + 1 < inputs.length
                       && inputs[i + 1].type === 'radio'
                       && inputs[i + 1].name === name) { i++; }
                continue;
            }
            if (!el.checkValidity()) {
                el.focus();
                try { el.reportValidity(); } catch (_) {}
                return false;
            }
        }
        return true;
    }

    document.querySelectorAll('[data-bd-next]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var current = btn.closest('[data-bd-step]').getAttribute('data-bd-step');
            if (!validateStep(current)) return;
            activateStep(btn.getAttribute('data-bd-next'));
        });
    });
    document.querySelectorAll('[data-bd-prev]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            activateStep(btn.getAttribute('data-bd-prev'));
        });
    });
    indicators.forEach(function (el) {
        el.addEventListener('click', function () {
            var target  = Number(el.getAttribute('data-bd-step-indicator'));
            var current = Number(document.querySelector('[data-bd-step].is-active').getAttribute('data-bd-step'));
            if (target < current) { activateStep(target); }
        });
    });

    /* ============ Maheshkhali union list ============ */
    document.addEventListener('DOMContentLoaded', function () {
        var unions = [
            '\u09ae\u09b9\u09c7\u09b6\u0996\u09be\u09b2\u09c0 \u09aa\u09cc\u09b0\u09b8\u09ad\u09be',
            '\u09ac\u09dc \u09ae\u09b9\u09c7\u09b6\u0996\u09be\u09b2\u09c0',
            '\u099b\u09cb\u099f \u09ae\u09b9\u09c7\u09b6\u0996\u09be\u09b2\u09c0',
            '\u0995\u09c1\u09a4\u09c1\u09ac\u099c\u09cb\u09ae',
            '\u09b6\u09be\u09aa\u09b2\u09be\u09aa\u09c1\u09b0',
            '\u09b9\u09cb\u09df\u09be\u09a8\u0995',
            '\u0995\u09be\u09b2\u09be\u09b0\u09ae\u09be\u09b0\u099b\u09dc\u09be',
            '\u09ae\u09be\u09a4\u09be\u09b0\u09ac\u09be\u09dc\u09c0',
            '\u09a7\u09b2\u0998\u09be\u099f\u09be'
        ];
        var sel = document.getElementById('union-selector');
        if (!sel) return;
        var prev = sel.getAttribute('data-prev') || '';
        sel.innerHTML = '<option value="">-- \u0987\u0989\u09a8\u09bf\u09df\u09a8 \u09a8\u09bf\u09b0\u09cd\u09ac\u09be\u099a\u09a8 \u0995\u09b0\u09c1\u09a8 --</option>';
        unions.forEach(function (u) {
            var opt = document.createElement('option');
            opt.value = u;
            opt.textContent = u;
            if (prev === u) { opt.selected = true; }
            sel.appendChild(opt);
        });
        // Sync union_ward hidden field
        sel.addEventListener('change', function () {
            document.getElementById('union_ward').value = sel.value;
        });
        // Set initial value if pre-selected
        if (sel.value) {
            document.getElementById('union_ward').value = sel.value;
        }
    });

    /* ============ Geolocation API ============ */
    window.fetchCoordinates = function () {
        if (!navigator.geolocation) {
            alert('\u0986\u09aa\u09a8\u09be\u09b0 \u09ac\u09cd\u09b0\u09be\u0989\u099c\u09be\u09b0\u099f\u09bf \u099c\u09bf\u09aa\u09bf\u098f\u09b8 \u099f\u09cd\u09b0\u09cd\u09af\u09be\u0995\u09bf\u0982 \u09b8\u09be\u09aa\u09cb\u09b0\u09cd\u099f \u0995\u09b0\u09c7 \u09a8\u09be\u0964');
            return;
        }
        navigator.geolocation.getCurrentPosition(function (position) {
            document.getElementById('geo-lat').value = position.coords.latitude.toFixed(6);
            document.getElementById('geo-lng').value = position.coords.longitude.toFixed(6);
        }, function () {
            alert('\u099c\u09bf\u09aa\u09bf\u098f\u09b8 \u09b2\u09cb\u0995\u09c7\u09b6\u09a8 \u0985\u09cd\u09af\u09be\u0995\u09cd\u09b8\u09c7\u09b8 \u0995\u09b0\u09be \u09b8\u09ae\u09cd\u09ad\u09ac \u09b9\u09df\u09a8\u09bf\u0964 \u0985\u09a8\u09c1\u0997\u09cd\u09b0\u09b9 \u0995\u09b0\u09c7 \u09ac\u09cd\u09b0\u09be\u0989\u099c\u09be\u09b0 \u09aa\u09be\u09b0\u09ae\u09bf\u09b6\u09a8 \u099a\u09c7\u0995 \u0995\u09b0\u09c1\u09a8\u0964');
        });
    };

    /* ============ Live subdomain preview ============ */
    var subInput   = document.querySelector('[data-bd-subdomain]');
    var preview    = document.querySelector('[data-bd-preview]');
    var previewUrl = document.querySelector('[data-bd-preview-url]');
    if (subInput && preview && previewUrl) {
        var update = function () {
            var v = (subInput.value || '').toLowerCase().trim();
            if (!v) {
                previewUrl.textContent = 'your-name.smartschool.bd';
                preview.classList.add('bd-domain-preview--empty');
            } else {
                previewUrl.textContent = v + '.smartschool.bd';
                preview.classList.remove('bd-domain-preview--empty');
            }
        };
        subInput.addEventListener('input', update);
        update();
    }

    /* ============ On error reload, jump to first invalid step ============ */
    <?php if (!empty($errors)): ?>
    document.addEventListener('DOMContentLoaded', function () {
        var step1 = ['owner_name', 'owner_phone', 'owner_email'];
        var step2 = ['institution_type', 'school_name', 'mpo_status'];
        var step3 = ['union_name', 'detailed_address'];
        var step4 = ['total_students', 'total_teachers'];
        var step5 = ['subdomain', 'smart_school_reason'];

        var hasMissing = function (names) {
            return names.some(function (n) {
                var el = document.querySelector('[name="' + n + '"]');
                return el && !String(el.value || '').trim();
            });
        };
        var radioMissing = function (name) {
            return !document.querySelector('input[name="' + name + '"]:checked');
        };

        if (hasMissing(step1)) { activateStep(1); }
        else if (hasMissing(step2)) { activateStep(2); }
        else if (hasMissing(step3)) { activateStep(3); }
        else if (hasMissing(step4)) { activateStep(4); }
        else if (hasMissing(step5) || radioMissing('has_internet') || radioMissing('ict_teacher_available') || !document.querySelector('[name="terms_accept"]').checked) { activateStep(5); }
    });
    <?php endif; ?>
})();
</script>
</body>
</html>
