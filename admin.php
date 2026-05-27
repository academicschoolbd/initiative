<?php
require_once 'database.php';

// ১. এডমিন ফর্ম অন/অফ টগল সাবমিট করলে তা হ্যান্ডেল করার লজিক
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_toggle'])) {
    $current_state = $_POST['current_state'] === '1' ? '0' : '1';
    $update_stmt = $db->prepare("UPDATE settings SET value = ? WHERE key = 'form_enabled'");
    $update_stmt->execute([$current_state]);
    header('Location: admin');
    exit();
}

// ২. বর্তমান অন/অফ স্ট্যাটাস চেক করা
$stmt = $db->prepare("SELECT value FROM settings WHERE key = 'form_enabled'");
$stmt->execute();
$form_enabled = $stmt->fetchColumn();

// ৩. স্ট্যাটিস্টিকস কাউন্টার ডেটা কুয়েরি
$total = $db->query("SELECT COUNT(*) FROM registrations")->fetchColumn();
$primaries = $db->query("SELECT COUNT(*) FROM registrations WHERE institution_type='primary'")->fetchColumn();
$madrasahs = $db->query("SELECT COUNT(*) FROM registrations WHERE institution_type='madrasah'")->fetchColumn();
$high_schools = $db->query("SELECT COUNT(*) FROM registrations WHERE institution_type='high_school'")->fetchColumn();

// ৪. সকল রেজিস্ট্রেশন ডেটা ফেচ
$records = $db->query("SELECT * FROM registrations ORDER BY id DESC")->fetchAll();
?>
<!doctype html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>সেন্ট্রাল এডমিন ড্যাশবোর্ড | স্মার্ট মহেশখালী</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Tiro+Bangla&display=swap" rel="stylesheet">
    <style>
        :root { --background: #fafafa; --foreground: #09090b; --card: #ffffff; --border: #e4e4e7; --muted: #71717a; --brand: #059669; --radius: 8px; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', 'Tiro Bangla', sans-serif; background-color: var(--background); color: var(--foreground); padding: 40px; -webkit-font-smoothing: antialiased; }
        .admin-container { max-width: 1240px; margin: 0 auto; }
        
        .dashboard-header { margin-bottom: 32px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
        .dashboard-header h1 { font-size: 1.6rem; font-weight: 700; letter-spacing: -0.03em; }
        
        /* Premium Next.js Form On/Off Toggle Button Switch Layout */
        .toggle-box { display: flex; align-items: center; gap: 12px; background: #fff; padding: 10px 16px; border: 1px solid var(--border); border-radius: var(--radius); }
        .toggle-status-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
        .status-on { background: var(--brand); box-shadow: 0 0 8px var(--brand); }
        .status-off { background: #dc2626; box-shadow: 0 0 8px #dc2626; }
        .toggle-trigger { background: var(--foreground); color: #fff; border: none; padding: 6px 14px; font-size: 0.82rem; font-weight: 600; border-radius: 4px; cursor: pointer; }
        .toggle-trigger.off-style { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

        .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 32px; }
        .metric-card { background: var(--card); border: 1px solid var(--border); padding: 24px; border-radius: var(--radius); box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
        .metric-card h3 { font-size: 0.85rem; font-weight: 600; color: var(--muted); text-transform: uppercase; margin-bottom: 6px; }
        .metric-card .count { font-size: 1.8rem; font-weight: 700; }

        .table-wrapper { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); overflow-x: auto; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.92rem; min-width: 900px; }
        th { background: #f4f4f5; padding: 14px 20px; color: #18181b; font-weight: 600; font-size: 0.85rem; border-bottom: 1px solid var(--border); }
        td { padding: 16px 20px; border-bottom: 1px solid var(--border); color: #3f3f46; vertical-align: middle; }
        
        .badge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 99px; font-size: 0.78rem; font-weight: 600; }
        .badge.primary { background: #eff6ff; color: #1e40af; }
        .badge.madrasah { background: #fef3c7; color: #92400e; }
        .badge.high_school { background: #ecfdf5; color: #065f46; }

        .actions-cell { display: flex; gap: 6px; }
        .action-btn { padding: 6px 12px; font-size: 0.82rem; font-weight: 600; border-radius: 4px; text-decoration: none; border: 1px solid var(--border); background: #fff; cursor: pointer; }
        .action-btn:hover { background: #f4f4f5; }
        .action-btn.edit-btn { color: #2563eb; }
        .action-btn.delete-btn { color: #dc2626; }
    </style>
</head>
<body>

<div class="admin-container">
    <header class="dashboard-header">
        <div>
            <h1>স্মার্ট মহেশখালী এডমিন প্যানেল</h1>
            <p style="color: var(--muted); font-size: 0.9rem;">পাইলট প্রোগ্রামের সকল আবেদন ডেটা ও সিস্টেম গেটওয়ে কন্ট্রোল করুন।</p>
        </div>
        
        <div class="toggle-box">
            <span class="toggle-status-dot <?= $form_enabled === '1' ? 'status-on' : 'status-off' ?>"></span>
            <span class="bn" style="font-size:0.9rem; font-weight:600;">পাবলিক ফর্ম স্ট্যাটাস: <?= $form_enabled === '1' ? 'চালু আছে' : 'বন্ধ আছে' ?></span>
            <form method="POST" action="admin" style="display:inline;">
                <input type="hidden" name="current_state" value="<?= $form_enabled ?>">
                <button type="submit" name="action_toggle" class="toggle-trigger <?= $form_enabled === '1' ? '' : 'off-style' ?>">
                    <?= $form_enabled === '1' ? 'বন্ধ করুন (Turn Off)' : 'চালু করুন (Turn On)' ?>
                </button>
            </form>
        </div>
    </header>

    <section class="metrics-grid">
        <div class="metric-card"><h3>মোট আবেদনপত্র</h3><div class="count"><?= $total ?></div></div>
        <div class="metric-card"><h3>প্রাথমিক বিদ্যালয়</h3><div class="count"><?= $primaries ?> / 3</div></div>
        <div class="metric-card"><h3>মাদ্রাসা মডিউল</h3><div class="count"><?= $madrasahs ?> / 3</div></div>
        <div class="metric-card"><h3>মাধ্যমিক বিদ্যালয়</h3><div class="count"><?= $high_schools ?> / 3</div></div>
    </section>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>শিক্ষা প্রতিষ্ঠানের নাম</th>
                    <th>ধরন</th>
                    <th>ইউনিয়ন</th>
                    <th>সাবডোমেন লিংক</th>
                    <th>প্রতিনিধির মোবাইল নম্বর</th>
                    <th>অ্যাকশন</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 40px; color: var(--muted);">এখনো কোনো প্রতিষ্ঠান তথ্য জমা দেয়নি।</td></tr>
                <?php endif; ?>
                <?php foreach ($records as $r): ?>
                <tr>
                    <td>
                        <strong style="display:block; color: var(--foreground);"><?= htmlspecialchars($r['school_name']) ?></strong>
                        <span class="bn" style="font-size: 0.82rem; color: var(--muted);"><?= htmlspecialchars($r['school_name_bn']) ?></span>
                    </td>
                    <td><span class="badge <?= htmlspecialchars($r['institution_type']) ?> bn"><?= $r['institution_type'] === 'primary' ? 'প্রাথমিক' : ($r['institution_type'] === 'madrasah' ? 'মাদ্রাসা' : 'মাধ্যমিক') ?></span></td>
                    <td class="bn"><?= htmlspecialchars($r['union_name']) ?></td>
                    <td><span style="font-family: monospace; color:var(--brand); font-weight:600;"><?= htmlspecialchars($r['subdomain']) ?>.smartschool.bd</span></td>
                    <td><?= htmlspecialchars($r['owner_phone']) ?></td>
                    <td class="actions-cell">
                        <a href="admin-action?action=view&id=<?= $r['id'] ?>" class="action-btn">Preview</a>
                        <a href="admin-action?action=edit&id=<?= $r['id'] ?>" class="action-btn edit-btn">Edit</a>
                        <a href="admin-action?action=delete&id=<?= $r['id'] ?>" class="action-btn delete-btn" onclick="return confirm('আপনি কি নিশ্চিতভাবে এই রেকর্ডটি মুছে ফেলতে চান?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>