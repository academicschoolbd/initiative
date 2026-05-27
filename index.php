<?php
/**
 * Smart Maheshkhali — public registration form (bd.education-style layout).
 *
 * 3-step wizard:
 *   Step 1 — Contact person          (যোগাযোগের তথ্য)
 *   Step 2 — Institution + Location  (প্রতিষ্ঠানের তথ্য)
 *   Step 3 — Domain + Vision         (অন্যান্য তথ্য)
 *
 * Falls back to a long scrollable form if JavaScript is disabled.
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

// Honour the on/off toggle — admin can close intake at any time.
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
           Geo-fetch button (custom for this project)
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

        /* -----------------------------------------------------------
           Sponsor splash modal
           ----------------------------------------------------------- */
        .bd-sponsor-overlay { position:fixed; inset:0; z-index:9999;
                              background:rgba(15,23,42,.55);
                              display:flex; align-items:center; justify-content:center;
                              padding:16px; opacity:0; pointer-events:none;
                              transition:opacity .25s ease; }
        .bd-sponsor-overlay.is-open { opacity:1; pointer-events:auto; }
        .bd-sponsor-card { position:relative; background:#fff; border-radius:16px;
                           max-width:440px; width:100%;
                           padding:38px 28px 26px; overflow:hidden;
                           box-shadow:0 25px 50px -12px rgba(0,0,0,.25);
                           transform:scale(.94); opacity:0;
                           transition:transform .3s cubic-bezier(.34,1.56,.64,1), opacity .3s ease; }
        .bd-sponsor-overlay.is-open .bd-sponsor-card { transform:scale(1); opacity:1; }
        .bd-sponsor-progress { position:absolute; top:0; left:0; right:0; height:3px;
                               background:#e2e8f0; }
        .bd-sponsor-progress-bar { height:100%; background:var(--brand); width:100%;
                                   transform-origin:left center; }
        .bd-sponsor-overlay.is-open .bd-sponsor-progress-bar {
            animation: bdSponsorTick 6s linear forwards;
        }
        @keyframes bdSponsorTick { from { transform:scaleX(1); }
                                   to   { transform:scaleX(0); } }
        .bd-sponsor-close { position:absolute; top:10px; right:10px;
                            width:30px; height:30px; border-radius:50%;
                            background:#f1f5f9; border:none;
                            color:#475569; font-size:1rem; font-weight:700;
                            cursor:pointer;
                            display:flex; align-items:center; justify-content:center;
                            transition:.15s; line-height:1; }
        .bd-sponsor-close:hover { background:#e2e8f0; color:#0f172a; }
        .bd-sponsor-eyebrow { text-align:center; color:var(--muted);
                              font-size:.78rem; margin:0 0 10px;
                              letter-spacing:.08em; text-transform:uppercase;
                              font-weight:600; }
        .bd-sponsor-title { text-align:center; font-family:'Tiro Bangla',serif;
                            font-size:1.3rem; line-height:1.4; margin:0;
                            color:var(--fg); }
        .bd-sponsor-title .amp { color:var(--brand); font-weight:400; padding:0 4px; }
        .bd-sponsor-divider { width:36px; height:2px; background:var(--brand);
                              border-radius:1px; margin:18px auto; }
        .bd-sponsor-credit { text-align:center; font-size:.92rem; color:#475569;
                             margin:0 0 18px; line-height:1.55; }
        .bd-sponsor-credit strong { color:var(--fg); }
        .bd-sponsor-whatsapp { display:inline-flex; align-items:center; gap:8px;
                               background:#25d366; color:#fff;
                               padding:10px 22px; border-radius:8px;
                               text-decoration:none; font-weight:600;
                               font-size:.9rem; font-family:inherit;
                               border:none; cursor:pointer;
                               transition:.15s; }
        .bd-sponsor-whatsapp:hover { background:#1da851; }
        .bd-sponsor-whatsapp svg   { width:18px; height:18px; flex:0 0 18px; }
        .bd-sponsor-whatsapp-row { display:flex; justify-content:center; }

        .bd-footer-link { text-align:center; margin-top:22px; font-size:.82rem; color:var(--muted); }

        /* -----------------------------------------------------------
           Responsive — phone & small tablet tightening
           ----------------------------------------------------------- */
        @media (max-width: 700px) {
            body { padding: 24px 12px; }
            .bd-hero { margin-bottom: 20px; }
            .bd-hero h1 { font-size: 1.55rem; }
            .bd-hero p  { font-size: .88rem; }
            .bd-step { padding: 22px 18px; border-radius: 12px; }
            .bd-step-helper { margin-bottom: 16px; font-size: .88rem; }
            .bd-section { padding: 12px 14px; gap: 10px; }
            .bd-section-icon { width: 28px; height: 28px; flex-basis: 28px; }
            .bd-section-icon svg { width: 16px; height: 16px; }
            .bd-section-title { font-size: .9rem; }
            .bd-section-title small { font-size: .74rem; }
            .bd-stepper { padding: 10px 12px; }
            .bd-stepper-num { width: 26px; height: 26px; flex-basis: 26px; font-size: .85rem; }
            .bd-stepper-line { min-width: 8px; }
            .bd-input, .bd-select { padding: 10px 12px; font-size: .92rem; }
            .bd-btn { padding: 11px 18px; font-size: .88rem; }
            .bd-step-nav { gap: 8px; }
            .bd-step-nav .bd-btn { flex: 1 1 auto; justify-content: center; }
            .bd-subdomain input { font-size: .88rem; }
            .bd-subdomain .bd-fix { padding: 0 10px; font-size: .82rem; }
            .bd-domain-preview { font-size: .82rem; padding: 8px 12px; }
            .bd-pill { padding: 10px 12px; font-size: .88rem; min-width: 0; }
            .bd-check { padding: 12px; font-size: .85rem; }
            .bd-error { padding: 12px 14px; font-size: .84rem; }
            /* Sponsor splash modal */
            .bd-sponsor-card { padding: 32px 22px 22px; border-radius: 12px; }
            .bd-sponsor-title { font-size: 1.1rem; }
            .bd-sponsor-credit { font-size: .86rem; }
            .bd-sponsor-whatsapp { padding: 9px 18px; font-size: .85rem; }
            .bd-sponsor-eyebrow { font-size: .72rem; }
        }
        @media (max-width: 380px) {
            body { padding: 16px 10px; }
            .bd-hero h1 { font-size: 1.3rem; }
            .bd-step { padding: 18px 14px; }
            .bd-stepper-num { width: 24px; height: 24px; flex-basis: 24px; font-size: .78rem; }
            .bd-pill { width: 100%; min-width: 0; }
            .bd-section-title small { display: none; }
            .bd-sponsor-title { font-size: 1rem; }
            .bd-sponsor-card { padding: 28px 18px 20px; }
        }
    </style>
</head>
<body>

<?php if (!empty($CONFIG['sponsor_dialog_enabled'])):
    $waDigits = preg_replace('/\D+/', '', (string) ($CONFIG['whatsapp_contact'] ?? ''));
?>
<!-- Sponsor splash dialog: auto-closes after 6 seconds. -->
<div class="bd-sponsor-overlay" id="bd-sponsor-modal"
     role="dialog" aria-modal="true" aria-labelledby="bd-sponsor-title"
     data-bd-sponsor>
    <div class="bd-sponsor-card">
        <div class="bd-sponsor-progress" aria-hidden="true">
            <div class="bd-sponsor-progress-bar"></div>
        </div>
        <button type="button" class="bd-sponsor-close" data-bd-sponsor-close
                aria-label="Close sponsor dialog">&times;</button>

        <p class="bd-sponsor-eyebrow" lang="bn">স্পনসর্ড বাই &nbsp;·&nbsp; Sponsored by</p>
        <h3 class="bd-sponsor-title" id="bd-sponsor-title">
            Smartschool.bd <span class="amp">&amp;</span> Institution.bd
        </h3>

        <div class="bd-sponsor-divider" aria-hidden="true"></div>

        <p class="bd-sponsor-credit">
            Initiative taken by <strong>Abu Taher</strong>
        </p>

        <div class="bd-sponsor-whatsapp-row">
            <?php if ($waDigits !== ''): ?>
                <a class="bd-sponsor-whatsapp"
                   href="https://wa.me/<?= e($waDigits) ?>"
                   target="_blank" rel="noopener noreferrer"
                   aria-label="Contact on WhatsApp">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M.057 24l1.687-6.163a11.867 11.867 0 0 1-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.81 11.81 0 0 1 8.413 3.488 11.83 11.83 0 0 1 3.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892h-.005a11.9 11.9 0 0 1-5.683-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885a9.86 9.86 0 0 0-2.892-7.001 9.825 9.825 0 0 0-6.99-2.901c-5.452 0-9.887 4.434-9.889 9.884a9.86 9.86 0 0 0 1.516 5.26l.235.374-1 3.648 3.74-.971zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.463 1.065 2.876 1.213 3.074.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/>
                    </svg>
                    <span>WhatsApp-এ যোগাযোগ &nbsp;·&nbsp; Contact</span>
                </a>
            <?php else: ?>
                <span class="bd-sponsor-whatsapp" style="background:#94a3b8; cursor:default;"
                      role="img" aria-label="WhatsApp icon (number not configured)">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M.057 24l1.687-6.163a11.867 11.867 0 0 1-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.81 11.81 0 0 1 8.413 3.488 11.83 11.83 0 0 1 3.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892h-.005a11.9 11.9 0 0 1-5.683-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885a9.86 9.86 0 0 0-2.892-7.001 9.825 9.825 0 0 0-6.99-2.901c-5.452 0-9.887 4.434-9.889 9.884a9.86 9.86 0 0 0 1.516 5.26l.235.374-1 3.648 3.74-.971zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.463 1.065 2.876 1.213 3.074.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/>
                    </svg>
                    <span>WhatsApp Contact</span>
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

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
                <small lang="bn">যোগাযোগের তথ্য</small>
            </span>
        </div>
        <div class="bd-stepper-line"></div>
        <div class="bd-stepper-item" data-bd-step-indicator="2">
            <span class="bd-stepper-num">2</span>
            <span class="bd-stepper-label">
                <strong>Institution</strong>
                <small lang="bn">প্রতিষ্ঠানের তথ্য</small>
            </span>
        </div>
        <div class="bd-stepper-line"></div>
        <div class="bd-stepper-item" data-bd-step-indicator="3">
            <span class="bd-stepper-num">3</span>
            <span class="bd-stepper-label">
                <strong>Other info</strong>
                <small lang="bn">অন্যান্য তথ্য</small>
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
        <!-- Honeypot — only bots fill this field. -->
        <div class="bd-hp" aria-hidden="true">
            <label for="website_url">Leave this empty</label>
            <input type="text" id="website_url" name="website_url" tabindex="-1" autocomplete="off">
        </div>

        <!-- ============ Step 1 — Contact (যোগাযোগের তথ্য) ============ -->
        <section class="bd-step is-active" data-bd-step="1">
            <p class="bd-step-helper" lang="bn">সঠিক তথ্য দিয়ে পূরণ করুন</p>

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
                        Contact person
                        <small lang="bn">যোগাযোগের তথ্য</small>
                    </span>
                </div>

                <div class="bd-field bd-field--span-2">
                    <label class="bd-label" for="owner_name">
                        Your full name <span class="req">*</span>
                    </label>
                    <input class="bd-input" id="owner_name" name="owner_name" required
                           value="<?= $o('owner_name') ?>"
                           placeholder="Headmaster / Principal name">
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="owner_phone">
                        WhatsApp / Mobile <span class="req">*</span>
                    </label>
                    <input class="bd-input" id="owner_phone" type="tel" name="owner_phone" required
                           inputmode="tel" maxlength="20"
                           value="<?= $o('owner_phone') ?>" placeholder="01XXXXXXXXX">
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="owner_email">
                        Official email <span class="req">*</span>
                    </label>
                    <input class="bd-input" id="owner_email" type="email" name="owner_email" required
                           value="<?= $o('owner_email') ?>" placeholder="you@school.edu.bd">
                </div>
            </div>

            <div class="bd-step-nav">
                <span></span>
                <button type="button" class="bd-btn" data-bd-next="2">
                    পরবর্তী &nbsp;·&nbsp; Next
                    <span class="bd-btn-arrow" aria-hidden="true">→</span>
                </button>
            </div>
        </section>

        <!-- ============ Step 2 — Institution (প্রতিষ্ঠানের তথ্য) ============ -->
        <section class="bd-step" data-bd-step="2">
            <p class="bd-step-helper" lang="bn">সঠিক তথ্য দিয়ে পূরণ করুন</p>

            <div class="bd-grid">
                <div class="bd-section">
                    <span class="bd-section-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 21h18M5 21V10l7-5 7 5v11M9 21v-6h6v6"/>
                        </svg>
                    </span>
                    <span class="bd-section-title">
                        Institution
                        <small lang="bn">প্রতিষ্ঠানের তথ্য</small>
                    </span>
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="institution_type">
                        Type of institute <span class="req">*</span>
                    </label>
                    <select class="bd-select" id="institution_type" name="institution_type" required>
                        <option value="">— Select —</option>
                        <?php
                        $types = [
                            'primary'     => 'প্রাথমিক বিদ্যালয় / Primary',
                            'madrasah'    => 'মাদ্রাসা / Madrasah',
                            'high_school' => 'মাধ্যমিক বিদ্যালয় / High School',
                        ];
                        $sel = (string) ($old['institution_type'] ?? '');
                        foreach ($types as $code => $label): ?>
                            <option value="<?= e($code) ?>" <?= $sel === $code ? 'selected' : '' ?>>
                                <?= e($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="union-selector">
                        Union <small lang="bn">(ইউনিয়ন)</small> <span class="req">*</span>
                    </label>
                    <select class="bd-select" id="union-selector" name="union_name" required
                            data-prev="<?= $o('union_name') ?>">
                        <option value="">ইউনিয়ন লোড হচ্ছে...</option>
                    </select>
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="school_name">
                        School name (English) <span class="req">*</span>
                    </label>
                    <input class="bd-input" id="school_name" name="school_name" required
                           value="<?= $o('school_name') ?>"
                           placeholder="e.g. Moheshkhali Government High School">
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="school_name_bn">School name (বাংলা)</label>
                    <input class="bd-input" id="school_name_bn" name="school_name_bn"
                           value="<?= $o('school_name_bn') ?>"
                           placeholder="যেমন: মহেশখালী সরকারি উচ্চ বিদ্যালয়">
                </div>

                <div class="bd-field bd-field--span-2">
                    <label class="bd-label" for="detailed_address">
                        Detailed address <small lang="bn">(বিস্তারিত ঠিকানা)</small>
                        <span class="req">*</span>
                    </label>
                    <input class="bd-input" id="detailed_address" name="detailed_address" required
                           value="<?= $o('detailed_address') ?>"
                           placeholder="গ্রাম, ওয়ার্ড বা সুনির্দিষ্ট অবস্থান">
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="total_students">
                        Total students <small lang="bn">(মোট শিক্ষার্থী)</small>
                        <span class="req">*</span>
                    </label>
                    <input class="bd-input" id="total_students" name="total_students" required
                           type="number" inputmode="numeric" min="0" max="20000"
                           value="<?= $o('total_students') ?>" placeholder="যেমন: 350">
                </div>

                <div class="bd-field">
                    <label class="bd-label" for="total_teachers">
                        Total teachers <small lang="bn">(মোট শিক্ষক)</small>
                        <span class="req">*</span>
                    </label>
                    <input class="bd-input" id="total_teachers" name="total_teachers" required
                           type="number" inputmode="numeric" min="0" max="2000"
                           value="<?= $o('total_teachers') ?>" placeholder="যেমন: 18">
                </div>

                <div class="bd-field bd-field--span-2">
                    <label class="bd-label" id="lbl-ict-teacher">
                        ICT-experienced teacher to manage the website / app?
                        <small lang="bn">(ওয়েবসাইট/অ্যাপ পরিচালনার জন্য ICT অভিজ্ঞ শিক্ষক আছেন কি?)</small>
                        <span class="req">*</span>
                    </label>
                    <div role="radiogroup" aria-labelledby="lbl-ict-teacher" class="bd-pills">
                        <?php $ictPicked = (string) ($old['ict_teacher_available'] ?? ''); ?>
                        <label class="bd-pill">
                            <input type="radio" name="ict_teacher_available" value="yes" required
                                   <?= $ictPicked === 'yes' ? 'checked' : '' ?>>
                            <span>হ্যাঁ আছেন &nbsp;·&nbsp; Yes</span>
                        </label>
                        <label class="bd-pill">
                            <input type="radio" name="ict_teacher_available" value="no"
                                   <?= $ictPicked === 'no' ? 'checked' : '' ?>>
                            <span>না, নেই &nbsp;·&nbsp; No</span>
                        </label>
                    </div>
                </div>

                <div class="bd-section bd-section--amber">
                    <span class="bd-section-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                    </span>
                    <span class="bd-section-title">
                        GPS coordinates <small lang="bn">জিপিএস অবস্থান</small>
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
                <button type="button" class="bd-btn bd-btn--ghost" data-bd-prev="1">
                    <span class="bd-btn-arrow" aria-hidden="true">←</span>
                    Back &nbsp;·&nbsp; পূর্ববর্তী
                </button>
                <button type="button" class="bd-btn" data-bd-next="3">
                    পরবর্তী &nbsp;·&nbsp; Next
                    <span class="bd-btn-arrow" aria-hidden="true">→</span>
                </button>
            </div>
        </section>

        <!-- ============ Step 3 — Other info (অন্যান্য তথ্য) ============ -->
        <section class="bd-step" data-bd-step="3">
            <p class="bd-step-helper" lang="bn">সঠিক তথ্য দিয়ে পূরণ করুন</p>

            <div class="bd-section bd-section--green bd-section--standalone">
                <span class="bd-section-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M2 12h20M12 2a15 15 0 0 1 0 20M12 2a15 15 0 0 0 0 20"/>
                    </svg>
                </span>
                <span class="bd-section-title">
                    Website domain <small lang="bn">আপনার ওয়েবসাইটের ঠিকানা</small>
                </span>
            </div>

            <div class="bd-field" style="margin-top:14px;">
                <label class="bd-label" for="subdomain">
                    Short name / prefix for your website domain
                    <span class="req">*</span>
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
                    Lowercase letters, digits and hyphens only. Min 3, max 32 characters.
                </small>
                <div class="bd-domain-preview bd-domain-preview--empty" data-bd-preview>
                    <span lang="bn">আপনার ওয়েবসাইট হবে :</span>
                    <span class="bd-domain-preview-url" data-bd-preview-url>your-name.smartschool.bd</span>
                </div>
            </div>

            <div class="bd-section" style="margin-top:18px;">
                <span class="bd-section-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2l2.5 6.5L21 9l-5 4 1.5 7L12 16l-5.5 4L8 13 3 9l6.5-.5z"/>
                    </svg>
                </span>
                <span class="bd-section-title">
                    Vision &amp; notes <small lang="bn">আপনার লক্ষ্য</small>
                </span>
            </div>

            <div class="bd-field" style="margin-top:12px;">
                <label class="bd-label" for="smart_school_reason">
                    Why do you want to make your school smart?
                    <small lang="bn">(স্কুলকে স্মার্ট করতে চান কেন?)</small>
                    <span class="req">*</span>
                </label>
                <textarea class="bd-input" id="smart_school_reason" name="smart_school_reason"
                          minlength="20" maxlength="2000" required
                          placeholder="আপনার লক্ষ্য, প্রত্যাশা এবং স্মার্ট স্কুল প্রোগ্রামের প্রতি আগ্রহের কারণ সংক্ষেপে বর্ণনা করুন..."
                          ><?= $o('smart_school_reason') ?></textarea>
                <small class="bd-hint">কমপক্ষে ২০ অক্ষর, সর্বোচ্চ ২০০০ অক্ষর।</small>
            </div>

            <div class="bd-field" style="margin-top:12px;">
                <label class="bd-label" for="notes">
                    Anything else? <small style="opacity:.7">(optional)</small>
                </label>
                <textarea class="bd-input" id="notes" name="notes" maxlength="2000" rows="2"
                          placeholder="যেমন: ৩৫০ জন শিক্ষার্থী, দুটি ক্যাম্পাস, অভিভাবক SMS দরকার..."
                          ><?= $o('notes') ?></textarea>
            </div>

            <label class="bd-check" for="final-check">
                <input type="checkbox" name="terms_accept" value="1" id="final-check" required
                       <?= !empty($old['terms_accept']) ? 'checked' : '' ?>>
                <span>
                    প্রদানকৃত সকল তথ্য সত্য এবং আমি পাইলট প্রোগ্রামের যাচাইকরণ প্রক্রিয়ার সাথে একমত।
                    আমি
                    <a href="<?= e(url('/terms.php')) ?>" target="_blank" rel="noopener">
                        শর্তাবলী &nbsp;·&nbsp; Terms of Service
                    </a>
                    এবং
                    <a href="<?= e(url('/privacy.php')) ?>" target="_blank" rel="noopener">
                        প্রাইভেসি পলিসি &nbsp;·&nbsp; Privacy Policy
                    </a>
                    মেনে নিচ্ছি।
                </span>
            </label>

            <div class="bd-step-nav">
                <button type="button" class="bd-btn bd-btn--ghost" data-bd-prev="2">
                    <span class="bd-btn-arrow" aria-hidden="true">←</span>
                    Back &nbsp;·&nbsp; পূর্ববর্তী
                </button>
                <button type="submit" class="bd-btn">
                    সাবমিট করুন &nbsp;·&nbsp; Submit signup
                    <span class="bd-btn-arrow" aria-hidden="true">→</span>
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

    /* ============ 3-step wizard navigation ============ */
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

    /* ============ Maheshkhali union list (no AJAX backend) ============ */
    document.addEventListener('DOMContentLoaded', function () {
        var unions = [
            'মহেশখালী পৌরসভা', 'বড় মহেশখালী', 'ছোট মহেশখালী', 'কুতুবজোম',
            'শাপলাপুর', 'হোয়ানক', 'কালারমারছড়া', 'মাতারবাড়ী', 'ধলঘাটা'
        ];
        var sel = document.getElementById('union-selector');
        if (!sel) return;
        var prev = sel.getAttribute('data-prev') || '';
        sel.innerHTML = '<option value="">— Select union —</option>';
        unions.forEach(function (u) {
            var opt = document.createElement('option');
            opt.value = u;
            opt.textContent = u;
            if (prev === u) { opt.selected = true; }
            sel.appendChild(opt);
        });
    });

    /* ============ Geolocation API ============ */
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
        var step2 = ['institution_type', 'school_name', 'union_name',
                     'detailed_address', 'total_students', 'total_teachers'];
        var hasMissing = function (names) {
            return names.some(function (n) {
                var el = document.querySelector('[name="' + n + '"]');
                return el && !String(el.value || '').trim();
            });
        };
        var ictMissing = !document.querySelector('input[name="ict_teacher_available"]:checked');
        var step3Missing =
                !String(document.querySelector('[name="smart_school_reason"]').value || '').trim()
             || !document.querySelector('[name="terms_accept"]').checked;

        if (hasMissing(step1))                            { activateStep(1); }
        else if (hasMissing(step2) || ictMissing)         { activateStep(2); }
        else if (step3Missing)                            { activateStep(3); }
    });
    <?php endif; ?>
})();
</script>

<?php if (!empty($CONFIG['sponsor_dialog_enabled'])): ?>
<script>
(function () {
    'use strict';
    var overlay = document.getElementById('bd-sponsor-modal');
    if (!overlay) return;

    var autoCloseTimer = null;
    function close() {
        overlay.classList.remove('is-open');
        if (autoCloseTimer) { clearTimeout(autoCloseTimer); autoCloseTimer = null; }
    }
    function open() {
        overlay.classList.add('is-open');
        autoCloseTimer = setTimeout(close, 6000);
    }

    // Show shortly after first paint so the CSS transition is visible.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { setTimeout(open, 200); });
    } else {
        setTimeout(open, 200);
    }

    // Manual close button.
    overlay.querySelectorAll('[data-bd-sponsor-close]').forEach(function (btn) {
        btn.addEventListener('click', close);
    });

    // Click-outside to dismiss.
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) { close(); }
    });

    // Escape key.
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.classList.contains('is-open')) { close(); }
    });
})();
</script>
<?php endif; ?>

</body>
</html>
