<?php
session_start();
require_once 'database.php';

// এডমিন ফর্ম অন রেখেছে নাকি অফ রেখেছে তা চেক করা
$stmt = $db->prepare("SELECT value FROM settings WHERE key = 'form_enabled'");
$stmt->execute();
$form_enabled = $stmt->fetchColumn();

if ($form_enabled !== '1') {
    // ফর্ম অফ থাকলে Shadcn স্টাইলের ক্লিন নোটিশ দেখাবে
    ?>
    <!doctype html>
    <html lang="bn">
    <head>
        <meta charset="utf-8"><title>নিবন্ধন সাময়িকভাবে বন্ধ আছে</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Tiro+Bangla&display=swap" rel="stylesheet">
        <style>
            body { background: #fafafa; font-family: 'Inter', 'Tiro Bangla', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
            .box { background: #fff; border: 1px solid #e4e4e7; border-radius: 12px; padding: 40px; text-align: center; max-width: 450px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.04); }
            h2 { font-family: 'Tiro Bangla', serif; color: #0f172a; margin-bottom: 12px; }
            p { color: #71717a; font-size: 0.95rem; }
        </style>
    </head>
    <body>
        <div class="box">
            <h2>নিবন্ধন সাময়িকভাবে বন্ধ</h2>
            <p>স্মার্ট মহেশখালী পাইলট প্রোগ্রামের নতুন আবেদন গ্রহণ এই মুহূর্তে বন্ধ আছে। বিস্তারিত তথ্যের জন্য অনুগ্রহ করে কর্তৃপক্ষের সাথে যোগাযোগ করুন।</p>
        </div>
    </body>
    </html>
    <?php
    exit();
}

$errors = $_SESSION['form_errors'] ?? [];
$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['form_errors'], $_SESSION['old_input']);
?>
<!doctype html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>স্মارت মহেশখালী | ফ্রি স্কুল অটোমেশন নিবন্ধন পোর্টাল</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Tiro+Bangla&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --background: #f8fafc; --foreground: #0f172a; --card: #ffffff; --border: #e2e8f0;
            --muted: #64748b; --brand: #059669; --brand-glow: rgba(5, 150, 105, 0.1);
            --radius: 12px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background-color: var(--background); color: var(--foreground); font-family: 'Inter', 'Tiro Bangla', serif; padding: 60px 16px; -webkit-font-smoothing: antialiased; }
        .wrapper { max-width: 680px; margin: 0 auto; }
        .header-area { margin-bottom: 32px; text-align: center; }
        .header-area h1 { font-family: 'Tiro Bangla', serif; font-size: 2.2rem; margin-bottom: 8px; }
        
        /* Progress System Component */
        .progress-container { margin-bottom: 32px; background: #e2e8f0; height: 8px; border-radius: 99px; overflow: hidden; position: relative; }
        .progress-bar { background: var(--brand-gradient, linear-gradient(135deg, #10b981 0%, #059669 100%)); width: 33.33%; height: 100%; transition: width 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
        .step-indicators { display: flex; justify-content: space-between; margin-top: 8px; font-size: 0.8rem; font-weight: 600; color: var(--muted); }
        .step-indicators .active { color: var(--brand); }

        .portal-card { background-color: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 40px; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.04); }
        .form-step { display: none; }
        .form-step.active { display: block; animation: fadeIn 0.4s ease forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: none; } }

        .section-headline { font-family: 'Tiro Bangla', serif; font-size: 1.3rem; font-weight: 700; margin-bottom: 24px; color: var(--foreground); display: flex; align-items: center; gap: 8px; }
        .section-headline::after { content: ''; flex: 1; height: 1px; background: var(--border); }
        
        .field-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 20px; }
        .field-label { font-size: 0.88rem; font-weight: 600; color: #334155; }
        .input-node { width: 100%; padding: 12px 14px; font-size: 0.95rem; font-family: inherit; border: 1px solid var(--border); border-radius: 8px; transition: all 0.2s ease; }
        .input-node:focus { outline: none; border-color: var(--brand); box-shadow: 0 0 0 3px var(--brand-glow); }
        
        .subdomain-widget { display: flex; align-items: stretch; }
        .subdomain-widget input { border-top-right-radius: 0; border-bottom-right-radius: 0; }
        .subdomain-append { display: flex; align-items: center; padding: 0 16px; background-color: #f1f5f9; border: 1px solid var(--border); border-left: 0; border-top-right-radius: 8px; border-bottom-right-radius: 8px; color: var(--muted); font-size: 0.9rem; font-weight: 600; }

        .geo-btn { background: #f1f5f9; color: #334155; border: 1px solid var(--border); padding: 10px 16px; border-radius: 6px; font-size: 0.85rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; margin-top: 4px; width: fit-content; transition: all 0.2s; }
        .geo-btn:hover { background: #e2e8f0; }

        .btn-row { display: flex; justify-content: space-between; margin-top: 32px; gap: 12px; }
        .action-trigger { background: var(--foreground); color: #fff; border: none; padding: 14px 28px; font-size: 0.95rem; font-weight: 600; border-radius: 8px; cursor: pointer; transition: all 0.2s ease; }
        .action-trigger:hover { background: #1e293b; }
        .action-trigger.btn-secondary { background: #fff; color: var(--foreground); border: 1px solid var(--border); }
        .action-trigger.btn-secondary:hover { background: var(--background); }
        .action-trigger.btn-brand { background: var(--brand); }
        .action-trigger.btn-brand:hover { background: #047857; }

        .alert-box { background: #fef2f2; border: 1px solid #fee2e2; border-radius: 8px; padding: 16px; margin-bottom: 24px; color: #991b1b; font-size: 0.88rem; }
    </style>
</head>
<body>

<div class="wrapper">
    <header class="header-area">
        <h1>স্মার্ট মহেশখালী অ্যাপ্লিকেশন পোর্টাল</h1>
    </header>

    <div class="progress-container">
        <div class="progress-bar" id="ui-progress"></div>
    </div>
    <div class="step-indicators" style="margin-bottom: 24px;">
        <span id="ind-1" class="active">১. প্রতিষ্ঠানের বিবরণ</span>
        <span id="ind-2">২. ব্যক্তিগত যোগাযোগ</span>
        <span id="ind-3">৩. অন্যান্য মডিউল</span>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert-box"><?= implode('<br>', $errors) ?></div>
    <?php endif; ?>

    <div class="portal-card">
        <form action="submit" method="POST" id="multipart-form">
            
            <div class="form-step active" id="step-1">
                <h3 class="section-headline">প্রতিষ্ঠানের বিবরণ (Part 1)</h3>
                
                <div class="field-group">
                    <label class="field-label">প্রতিষ্ঠানের ধরন *</label>
                    <select class="input-node" name="institution_type" required>
                        <option value="">বাছাই করুন...</option>
                        <option value="primary">প্রাথমিক বিদ্যালয়</option>
                        <option value="madrasah">মাদ্রাসা</option>
                        <option value="high_school">মাধ্যমিক বিদ্যালয়</option>
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label">প্রতিষ্ঠানের নাম (English) *</label>
                    <input class="input-node" name="school_name" required placeholder="e.g. Moheshkhali Government High School">
                </div>

                <div class="field-group">
                    <label class="field-label">প্রতিষ্ঠানের নাম (বাংলা)</label>
                    <input class="input-node bn" name="school_name_bn" placeholder="যেমন: মহেশখালী সরকারি উচ্চ বিদ্যালয়">
                </div>

                <div class="field-group">
                    <label class="field-label">পছন্দসই সাবডোমেন *</label>
                    <div class="subdomain-widget">
                        <input class="input-node" name="subdomain" pattern="[a-z0-9_-]{3,64}" required placeholder="mghs">
                        <span class="subdomain-append">.smartschool.bd</span>
                    </div>
                </div>

                <div class="field-group">
                    <label class="field-label">মহেশখালী উপজেলার ইউনিয়ন বাছাই করুন *</label>
                    <select class="input-node" name="union_name" id="union-selector" required>
                        <option value="">ইউনিয়ন লোড হচ্ছে...</option>
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label">বিস্তারিত ঠিকানা *</label>
                    <input class="input-node bn" name="detailed_address" required placeholder="গ্রাম, ওয়ার্ড বা সুনির্দিষ্ট অবস্থান উল্লেখ করুন">
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                    <div class="field-group">
                        <label class="field-label">অক্ষাংশ (Latitude)</label>
                        <input class="input-node" name="latitude" id="geo-lat" readonly placeholder="অটোমেটিক জেনারেট হবে">
                    </div>
                    <div class="field-group">
                        <label class="field-label">দ্রাঘিমাংশ (Longitude)</label>
                        <input class="input-node" name="longitude" id="geo-lng" readonly placeholder="অটোমেটিক জেনারেট হবে">
                    </div>
                </div>
                <button type="button" class="geo-btn" onclick="fetchCoordinates()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 2v4M12 18v4M4 12h4M16 12h4"/></svg>
                    বর্তমান জিপিএস লোকেশন সেট করুন
                </button>

                <div class="btn-row">
                    <div></div>
                    <button type="button" class="action-trigger" onclick="switchStep(2)">পরবর্তী ধাপ &rarr;</button>
                </div>
            </div>

            <div class="form-step" id="step-2">
                <h3 class="section-headline">ব্যক্তিগত যোগাযোগ (Part 2)</h3>
                <div class="field-group">
                    <label class="field-label">আপনার সম্পূর্ণ নাম *</label>
                    <input class="input-node" name="owner_name" required placeholder="প্রতিনিধির পূর্ণ নাম">
                </div>
                <div class="field-group">
                    <label class="field-label">মোবাইল নম্বর *</label>
                    <input class="input-node" name="owner_phone" required placeholder="01XXXXXXXXX">
                </div>
                <div class="field-group">
                    <label class="field-label">ইমেইল এড্রেস *</label>
                    <input class="input-node" type="email" name="owner_email" required placeholder="name@domain.com">
                </div>

                <div class="btn-row">
                    <button type="button" class="action-trigger btn-secondary" onclick="switchStep(1)">&larr; পূর্ববর্তী ধাপ</button>
                    <button type="button" class="action-trigger" onclick="switchStep(3)">পরবর্তী ধাপ &rarr;</button>
                </div>
            </div>

            <div class="form-step" id="step-3">
                <h3 class="section-headline">অন্যান্য তথ্য (Part 3)</h3>
                <div class="field-group">
                    <label class="field-label">অতিরিক্ত তথ্য বা বিশেষ রিকোয়ারমেন্ট (ঐচ্ছিক)</label>
                    <textarea class="input-node bn" name="notes" style="min-height:120px;" placeholder="আপনার বিশেষ কোনো মডিউলের প্রয়োজনীয়তা থাকলে এখানে উল্লেখ করুন..."></textarea>
                </div>

                <label class="terms-flex" for="final-check">
                    <input type="checkbox" name="terms_accept" value="1" id="final-check" required>
                    <span class="bn">প্রদানকৃত সকল তথ্য সত্য এবং আমি পাইলট প্রোগ্রামের যাচাইকরণ প্রক্রিয়ার সাথে একমত।</span>
                </label>

                <div class="btn-row">
                    <button type="button" class="action-trigger btn-secondary" onclick="switchStep(2)">&larr; পূর্ববর্তী ধাপ</button>
                    <button type="submit" class="action-trigger btn-brand bn">আবেদন সম্পন্ন করুন</button>
                </div>
            </div>

        </form>
    </div>
</div>

<script>
    // ১. মহেশখালী উপজেলার ইউনিয়ন তালিকা মকিং (AJAX Simulation)
    document.addEventListener("DOMContentLoaded", function() {
        const unions = [
            "মহেশখালী পৌরসভা", "বড় মহেশখালী", "ছোট মহেশখালী", "কুতুবজোম", 
            "শাপলাপুর", "হোয়ানক", "কালারমারছড়া", "মাতারবাড়ী", "ধলঘাটা"
        ];
        
        // ১ সেকেন্ড ডিলে দিয়ে প্রপার AJAX মেকানিজম মক করা হলো
        setTimeout(() => {
            const selector = document.getElementById("union-selector");
            selector.innerHTML = '<option value="">ইউনিয়ন নির্বাচন করুন...</option>';
            unions.forEach(u => {
                selector.innerHTML += `<option value="${u}">${u}</option>`;
            });
        }, 600);
    });

    // ২. HTML5 Geolocation API Integration
    function fetchCoordinates() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                document.getElementById("geo-lat").value = position.coords.latitude.toFixed(6);
                document.getElementById("geo-lng").value = position.coords.longitude.toFixed(6);
            }, function(error) {
                alert("জিপিএস লোকেশন অ্যাক্সেস করা সম্ভব হয়নি। অনুগ্রহ করে ব্রাউজার পারমিশন চেক করুন।");
            });
        } else {
            alert("আপনার ব্রাউজারটি জিপিএস ট্র্যাকিং সাপোর্ট করে না।");
        }
    }

    // ৩. Multi-step Navigation with Dynamic Percent Progress Engine
    function switchStep(stepNum) {
        // ফর্ম ভ্যালিডেশন চেক (ধাপ অতিক্রম করার পূর্বে রিকোয়ার্ড ফিল্ড চেকিং)
        if (stepNum > 1) {
            const currentStepEl = document.querySelector('.form-step.active');
            const inputs = currentStepEl.querySelectorAll('[required]');
            let valid = true;
            inputs.forEach(input => {
                if (!input.checkValidity()) {
                    input.reportValidity();
                    valid = false;
                }
            });
            if (!valid) return;
        }

        // ক্লাসের স্টেট পরিবর্তন
        document.querySelectorAll('.form-step').forEach(el => el.classList.remove('active'));
        document.getElementById(`step-${stepNum}`).classList.add('active');
        
        // প্রোগ্রেস বার পার্সেন্টেজ কন্ট্রোল
        const pct = stepNum === 1 ? '33.33%' : (stepNum === 2 ? '66.66%' : '100%');
        document.getElementById('ui-progress').style.width = pct;
        
        // ইন্ডিকেটর কালার আপডেট
        document.querySelectorAll('.step-indicators span').forEach((el, index) => {
            if (index + 1 <= stepNum) el.classList.add('active');
            else el.classList.remove('active');
        });
    }
</script>
</body>
</html>