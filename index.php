<?php
/**
 * Smart Maheshkhali — public registration form.
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

// Honour the on/off toggle.
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

/**
 * Convenience: previously-submitted value for a field, HTML-escaped.
 */
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
        :root {
            --background:#f8fafc; --foreground:#0f172a; --card:#fff; --border:#e2e8f0;
            --muted:#64748b; --brand:#059669; --brand-glow:rgba(5,150,105,.1);
            --radius:12px;
        }
        *, *::before, *::after { box-sizing:border-box; }
        body { margin:0; padding:60px 16px; background:var(--background); color:var(--foreground);
               font-family:'Inter','Tiro Bangla',serif; -webkit-font-smoothing:antialiased; }
        .wrapper { max-width:680px; margin:0 auto; }
        .header-area { margin-bottom:32px; text-align:center; }
        .header-area h1 { font-family:'Tiro Bangla',serif; font-size:2.2rem; margin:0 0 8px; }
        .header-area p { color:var(--muted); margin:0; font-size:.95rem; }

        .progress-container { background:#e2e8f0; height:8px; border-radius:99px; overflow:hidden; }
        .progress-bar { background:linear-gradient(135deg,#10b981 0%,#059669 100%);
                        width:33.33%; height:100%; transition:width .4s cubic-bezier(.16,1,.3,1); }
        .step-indicators { display:flex; justify-content:space-between; margin:8px 0 24px;
                           font-size:.8rem; font-weight:600; color:var(--muted); gap:8px; }
        .step-indicators .active { color:var(--brand); }

        .portal-card { background:var(--card); border:1px solid var(--border); border-radius:var(--radius);
                       padding:40px; box-shadow:0 10px 25px -5px rgba(15,23,42,.04); }
        .form-step { display:none; }
        .form-step.active { display:block; animation:fadeIn .4s ease forwards; }
        @keyframes fadeIn { from { opacity:0; transform:translateY(4px); } to { opacity:1; transform:none; } }

        .section-headline { font-family:'Tiro Bangla',serif; font-size:1.3rem; font-weight:700;
                            margin:0 0 24px; color:var(--foreground); display:flex; align-items:center; gap:8px; }
        .section-headline::after { content:''; flex:1; height:1px; background:var(--border); }

        .field-group { display:flex; flex-direction:column; gap:6px; margin-bottom:20px; }
        .field-label { font-size:.88rem; font-weight:600; color:#334155; }
        .field-label .req { color:#dc2626; margin-left:2px; }
        .input-node { width:100%; padding:12px 14px; font-size:.95rem; font-family:inherit;
                      border:1px solid var(--border); border-radius:8px; transition:all .2s; background:#fff; color:inherit; }
        .input-node:focus { outline:none; border-color:var(--brand); box-shadow:0 0 0 3px var(--brand-glow); }

        .subdomain-widget { display:flex; align-items:stretch; }
        .subdomain-widget input { border-top-right-radius:0; border-bottom-right-radius:0; }
        .subdomain-append { display:flex; align-items:center; padding:0 16px; background:#f1f5f9;
                            border:1px solid var(--border); border-left:0;
                            border-top-right-radius:8px; border-bottom-right-radius:8px;
                            color:var(--muted); font-size:.9rem; font-weight:600; }

        .geo-btn { background:#f1f5f9; color:#334155; border:1px solid var(--border); padding:10px 16px;
                   border-radius:6px; font-size:.85rem; font-weight:600; cursor:pointer;
                   display:inline-flex; align-items:center; gap:6px; margin-top:4px; width:fit-content;
                   font-family:inherit; transition:all .2s; }
        .geo-btn:hover { background:#e2e8f0; }

        .grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        @media (max-width:560px) { .grid-2 { grid-template-columns:1fr; } }

        .btn-row { display:flex; justify-content:space-between; margin-top:32px; gap:12px; }
        .action-trigger { background:var(--foreground); color:#fff; border:none; padding:14px 28px;
                          font-size:.95rem; font-weight:600; border-radius:8px; cursor:pointer;
                          font-family:inherit; transition:all .2s; }
        .action-trigger:hover { background:#1e293b; }
        .action-trigger.btn-secondary { background:#fff; color:var(--foreground); border:1px solid var(--border); }
        .action-trigger.btn-secondary:hover { background:var(--background); }
        .action-trigger.btn-brand { background:var(--brand); }
        .action-trigger.btn-brand:hover { background:#047857; }

        .alert-box { background:#fef2f2; border:1px solid #fee2e2; border-radius:8px; padding:16px;
                     margin-bottom:24px; color:#991b1b; font-size:.88rem; }
        .alert-box ul { margin:0; padding-left:20px; }
        .alert-box li + li { margin-top:4px; }

        .terms-flex { display:flex; align-items:flex-start; gap:10px; padding:14px;
                      background:#f8fafc; border:1px solid var(--border); border-radius:8px;
                      cursor:pointer; font-size:.9rem; color:#334155; }
        .terms-flex input { margin-top:3px; }

        .hp { position:absolute; left:-9999px; top:-9999px; width:1px; height:1px;
              opacity:0; pointer-events:none; }

        .footer-link { text-align:center; margin-top:24px; font-size:.82rem; color:var(--muted); }
        .footer-link a { color:var(--muted); text-decoration:underline; }
    </style>
</head>
<body>

<div class="wrapper">
    <header class="header-area">
        <h1>স্মার্ট মহেশখালী অ্যাপ্লিকেশন পোর্টাল</h1>
        <p>ফ্রি স্কুল অটোমেশন পাইলট প্রোগ্রামে অংশগ্রহণের জন্য নিবন্ধন করুন।</p>
    </header>

    <div class="progress-container" aria-hidden="true">
        <div class="progress-bar" id="ui-progress"></div>
    </div>
    <div class="step-indicators" role="status" aria-live="polite">
        <span id="ind-1" class="active">১. প্রতিষ্ঠানের বিবরণ</span>
        <span id="ind-2">২. ব্যক্তিগত যোগাযোগ</span>
        <span id="ind-3">৩. অন্যান্য মডিউল</span>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert-box" role="alert">
            <strong>অনুগ্রহ করে নিচের ত্রুটিসমূহ ঠিক করুন:</strong>
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="portal-card">
        <form action="<?= e(url('/submit.php')) ?>" method="POST" id="multipart-form" novalidate>
            <?= csrf_field() ?>
            <!-- Honeypot: real users never fill this; bots usually do. -->
            <div class="hp" aria-hidden="true">
                <label for="website_url">Leave this empty</label>
                <input type="text" id="website_url" name="website_url" tabindex="-1" autocomplete="off">
            </div>

            <div class="form-step active" id="step-1">
                <h3 class="section-headline">প্রতিষ্ঠানের বিবরণ (Part 1)</h3>

                <div class="field-group">
                    <label class="field-label" for="institution_type">প্রতিষ্ঠানের ধরন<span class="req">*</span></label>
                    <select class="input-node" id="institution_type" name="institution_type" required>
                        <option value="">বাছাই করুন...</option>
                        <?php
                        $types = [
                            'primary'     => 'প্রাথমিক বিদ্যালয়',
                            'madrasah'    => 'মাদ্রাসা',
                            'high_school' => 'মাধ্যমিক বিদ্যালয়',
                        ];
                        $selectedType = (string) ($old['institution_type'] ?? '');
                        foreach ($types as $code => $label):
                            $sel = $selectedType === $code ? ' selected' : '';
                        ?>
                            <option value="<?= e($code) ?>"<?= $sel ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label" for="school_name">প্রতিষ্ঠানের নাম (English)<span class="req">*</span></label>
                    <input class="input-node" id="school_name" name="school_name" required
                           value="<?= $o('school_name') ?>"
                           placeholder="e.g. Moheshkhali Government High School">
                </div>

                <div class="field-group">
                    <label class="field-label" for="school_name_bn">প্রতিষ্ঠানের নাম (বাংলা)</label>
                    <input class="input-node" id="school_name_bn" name="school_name_bn"
                           value="<?= $o('school_name_bn') ?>"
                           placeholder="যেমন: মহেশখালী সরকারি উচ্চ বিদ্যালয়">
                </div>

                <div class="field-group">
                    <label class="field-label" for="subdomain">পছন্দসই সাবডোমেন<span class="req">*</span></label>
                    <div class="subdomain-widget">
                        <input class="input-node" id="subdomain" name="subdomain" required
                               pattern="[a-z0-9](?:[a-z0-9-]{1,30}[a-z0-9])"
                               minlength="3" maxlength="32"
                               value="<?= $o('subdomain') ?>"
                               placeholder="mghs"
                               autocapitalize="off" autocomplete="off" spellcheck="false">
                        <span class="subdomain-append">.smartschool.bd</span>
                    </div>
                    <small style="color:var(--muted); font-size:.78rem;">৩–৩২ অক্ষর; lowercase letters, digits, hyphens only.</small>
                </div>

                <div class="field-group">
                    <label class="field-label" for="union-selector">মহেশখালী উপজেলার ইউনিয়ন বাছাই করুন<span class="req">*</span></label>
                    <select class="input-node" id="union-selector" name="union_name" required
                            data-prev="<?= $o('union_name') ?>">
                        <option value="">ইউনিয়ন লোড হচ্ছে...</option>
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label" for="detailed_address">বিস্তারিত ঠিকানা<span class="req">*</span></label>
                    <input class="input-node" id="detailed_address" name="detailed_address" required
                           value="<?= $o('detailed_address') ?>"
                           placeholder="গ্রাম, ওয়ার্ড বা সুনির্দিষ্ট অবস্থান উল্লেখ করুন">
                </div>

                <div class="grid-2">
                    <div class="field-group">
                        <label class="field-label" for="geo-lat">অক্ষাংশ (Latitude)</label>
                        <input class="input-node" id="geo-lat" name="latitude" readonly
                               value="<?= $o('latitude') ?>" placeholder="অটোমেটিক জেনারেট হবে">
                    </div>
                    <div class="field-group">
                        <label class="field-label" for="geo-lng">দ্রাঘিমাংশ (Longitude)</label>
                        <input class="input-node" id="geo-lng" name="longitude" readonly
                               value="<?= $o('longitude') ?>" placeholder="অটোমেটিক জেনারেট হবে">
                    </div>
                </div>
                <button type="button" class="geo-btn" onclick="fetchCoordinates()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 2v4M12 18v4M4 12h4M16 12h4"/></svg>
                    বর্তমান জিপিএস লোকেশন সেট করুন
                </button>

                <div class="btn-row">
                    <span></span>
                    <button type="button" class="action-trigger" onclick="switchStep(2)">পরবর্তী ধাপ &rarr;</button>
                </div>
            </div>

            <div class="form-step" id="step-2">
                <h3 class="section-headline">ব্যক্তিগত যোগাযোগ (Part 2)</h3>

                <div class="field-group">
                    <label class="field-label" for="owner_name">আপনার সম্পূর্ণ নাম<span class="req">*</span></label>
                    <input class="input-node" id="owner_name" name="owner_name" required
                           value="<?= $o('owner_name') ?>" placeholder="প্রতিনিধির পূর্ণ নাম">
                </div>

                <div class="field-group">
                    <label class="field-label" for="owner_phone">মোবাইল নম্বর<span class="req">*</span></label>
                    <input class="input-node" id="owner_phone" type="tel" name="owner_phone" required
                           inputmode="tel" maxlength="20"
                           value="<?= $o('owner_phone') ?>" placeholder="01XXXXXXXXX">
                </div>

                <div class="field-group">
                    <label class="field-label" for="owner_email">ইমেইল এড্রেস<span class="req">*</span></label>
                    <input class="input-node" id="owner_email" type="email" name="owner_email" required
                           value="<?= $o('owner_email') ?>" placeholder="name@domain.com">
                </div>

                <div class="btn-row">
                    <button type="button" class="action-trigger btn-secondary" onclick="switchStep(1)">&larr; পূর্ববর্তী ধাপ</button>
                    <button type="button" class="action-trigger" onclick="switchStep(3)">পরবর্তী ধাপ &rarr;</button>
                </div>
            </div>

            <div class="form-step" id="step-3">
                <h3 class="section-headline">অন্যান্য তথ্য (Part 3)</h3>

                <div class="field-group">
                    <label class="field-label" for="notes">অতিরিক্ত তথ্য বা বিশেষ রিকোয়ারমেন্ট (ঐচ্ছিক)</label>
                    <textarea class="input-node" id="notes" name="notes" maxlength="2000"
                              style="min-height:120px;"
                              placeholder="আপনার বিশেষ কোনো মডিউলের প্রয়োজনীয়তা থাকলে এখানে উল্লেখ করুন..."><?= $o('notes') ?></textarea>
                </div>

                <label class="terms-flex" for="final-check">
                    <input type="checkbox" name="terms_accept" value="1" id="final-check" required
                           <?= !empty($old['terms_accept']) ? 'checked' : '' ?>>
                    <span>প্রদানকৃত সকল তথ্য সত্য এবং আমি পাইলট প্রোগ্রামের যাচাইকরণ প্রক্রিয়ার সাথে একমত।</span>
                </label>

                <div class="btn-row">
                    <button type="button" class="action-trigger btn-secondary" onclick="switchStep(2)">&larr; পূর্ববর্তী ধাপ</button>
                    <button type="submit" class="action-trigger btn-brand">আবেদন সম্পন্ন করুন</button>
                </div>
            </div>
        </form>
    </div>

    <p class="footer-link">
        &copy; <?= e((string) date('Y')) ?> <?= e($CONFIG['app_name']) ?>
    </p>
</div>

<script>
(function () {
    'use strict';

    // 1. Maheshkhali union list. The setTimeout simulates an async load
    //    so the UI state stays consistent if this is later wired to AJAX.
    document.addEventListener('DOMContentLoaded', function () {
        var unions = [
            'মহেশখালী পৌরসভা', 'বড় মহেশখালী', 'ছোট মহেশখালী', 'কুতুবজোম',
            'শাপলাপুর', 'হোয়ানক', 'কালারমারছড়া', 'মাতারবাড়ী', 'ধলঘাটা'
        ];
        setTimeout(function () {
            var sel = document.getElementById('union-selector');
            var prev = sel.getAttribute('data-prev') || '';
            sel.innerHTML = '<option value="">ইউনিয়ন নির্বাচন করুন...</option>';
            unions.forEach(function (u) {
                var opt = document.createElement('option');
                opt.value = u;
                opt.textContent = u;
                if (prev === u) { opt.selected = true; }
                sel.appendChild(opt);
            });
        }, 400);
    });

    // 2. HTML5 Geolocation API.
    window.fetchCoordinates = function () {
        if (!navigator.geolocation) {
            alert('আপনার ব্রাউজারটি জিপিএস ট্র্যাকিং সাপোর্ট করে না।');
            return;
        }
        navigator.geolocation.getCurrentPosition(function (position) {
            document.getElementById('geo-lat').value = position.coords.latitude.toFixed(6);
            document.getElementById('geo-lng').value = position.coords.longitude.toFixed(6);
        }, function () {
            alert('জিপিএস লোকেশন অ্যাক্সেস করা সম্ভব হয়নি। অনুগ্রহ করে ব্রাউজার পারমিশন চেক করুন।');
        });
    };

    // 3. Multi-step navigation with required-field validation.
    window.switchStep = function (stepNum) {
        if (stepNum > 1) {
            var current = document.querySelector('.form-step.active');
            var inputs = current.querySelectorAll('[required]');
            for (var i = 0; i < inputs.length; i++) {
                if (!inputs[i].checkValidity()) {
                    inputs[i].reportValidity();
                    return;
                }
            }
        }
        document.querySelectorAll('.form-step').forEach(function (el) { el.classList.remove('active'); });
        document.getElementById('step-' + stepNum).classList.add('active');

        var pct = stepNum === 1 ? '33.33%' : (stepNum === 2 ? '66.66%' : '100%');
        document.getElementById('ui-progress').style.width = pct;

        document.querySelectorAll('.step-indicators span').forEach(function (el, i) {
            if (i + 1 <= stepNum) { el.classList.add('active'); }
            else { el.classList.remove('active'); }
        });
    };

    // 4. If the page was reloaded due to validation errors, jump to the
    //    step that contains the first errored field.
    <?php if (!empty($errors)): ?>
    document.addEventListener('DOMContentLoaded', function () {
        // step 2 fields — if any of these are missing, skip ahead
        var step2 = ['owner_name','owner_phone','owner_email'];
        var step3 = ['terms_accept'];
        var hasStep2 = step2.some(function (n) { return !document.querySelector('[name="'+n+'"]').value; });
        var hasStep3 = !document.querySelector('[name="terms_accept"]').checked;
        if (!hasStep2 && hasStep3) { window.switchStep(3); }
        else if (hasStep2)         { window.switchStep(2); }
    });
    <?php endif; ?>
})();
</script>
</body>
</html>
