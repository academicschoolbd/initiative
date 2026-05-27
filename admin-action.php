<?php
session_start();
require_once 'database.php';

$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS);
$id     = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: admin');
    exit();
}

if ($action === 'delete') {
    $stmt = $db->prepare("DELETE FROM registrations WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: admin');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update') {
    $school_name      = trim(filter_input(INPUT_POST, 'school_name', FILTER_SANITIZE_SPECIAL_CHARS));
    $school_name_bn   = trim(filter_input(INPUT_POST, 'school_name_bn', FILTER_SANITIZE_SPECIAL_CHARS));
    $subdomain        = strtolower(trim(filter_input(INPUT_POST, 'subdomain', FILTER_SANITIZE_SPECIAL_CHARS)));
    $union_name       = filter_input(INPUT_POST, 'union_name', FILTER_SANITIZE_SPECIAL_CHARS);
    $detailed_address = trim(filter_input(INPUT_POST, 'detailed_address', FILTER_SANITIZE_SPECIAL_CHARS));
    $latitude         = trim(filter_input(INPUT_POST, 'latitude', FILTER_SANITIZE_SPECIAL_CHARS));
    $longitude        = trim(filter_input(INPUT_POST, 'longitude', FILTER_SANITIZE_SPECIAL_CHARS));
    $owner_name       = trim(filter_input(INPUT_POST, 'owner_name', FILTER_SANITIZE_SPECIAL_CHARS));
    $owner_phone      = trim(filter_input(INPUT_POST, 'owner_phone', FILTER_SANITIZE_SPECIAL_CHARS));
    $owner_email      = filter_input(INPUT_POST, 'owner_email', FILTER_VALIDATE_EMAIL);
    $notes            = trim(filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_SPECIAL_CHARS));

    if (!empty($school_name) && !empty($subdomain) && $owner_email) {
        $up = $db->prepare("UPDATE registrations SET school_name=?, school_name_bn=?, subdomain=?, union_name=?, detailed_address=?, latitude=?, longitude=?, owner_name=?, owner_phone=?, owner_email=?, notes=? WHERE id=?");
        $up->execute([$school_name, $school_name_bn, $subdomain, $union_name, $detailed_address, $latitude, $longitude, $owner_name, $owner_phone, $owner_email, $notes, $id]);
        header('Location: admin');
        exit();
    }
}

$stmt = $db->prepare("SELECT * FROM registrations WHERE id = ?");
$stmt->execute([$id]);
$record = $stmt->fetch();

if (!$record) die("Payload Entry Target Node Not Found.");
?>
<!doctype html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <title>অ্যাকশন সেন্টার | SmartSchool Engine</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Tiro+Bangla&display=swap" rel="stylesheet">
    <style>
        :root { --background: #fafafa; --foreground: #09090b; --card: #ffffff; --border: #e4e4e7; --muted: #71717a; --brand: #059669; --radius: 12px; }
        body { font-family: 'Inter', 'Tiro Bangla', sans-serif; background: var(--background); color: var(--foreground); padding: 40px 16px; }
        .action-card { max-width: 600px; margin: 0 auto; background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 40px; box-shadow: 0 10px 25px rgba(0,0,0,0.02); }
        h2 { font-family: 'Tiro Bangla', serif; font-size: 1.5rem; margin-bottom: 24px; border-bottom: 1px solid var(--border); padding-bottom: 12px; }
        .data-row { margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #f4f4f5; }
        .data-lbl { font-size: 0.8rem; font-weight: 700; color: var(--muted); text-transform: uppercase; }
        .data-val { font-size: 1rem; color: var(--foreground); margin-top: 2px; }
        .form-control { width: 100%; padding: 11px 14px; border: 1px solid var(--border); border-radius: 6px; font-size: 0.95rem; font-family: inherit; margin-top: 6px; }
        .btn-flex { display: flex; gap: 12px; margin-top: 28px; }
        .btn { padding: 12px 24px; font-size: 0.9rem; font-weight: 600; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-flex; border: 1px solid var(--border); background:#fff; color:var(--foreground); }
        .btn-save { background: var(--foreground); color: #fff; border: none; }
    </style>
</head>
<body>

<div class="action-card">
    <?php if ($action === 'view'): ?>
        <h2>আবেদনপত্রের বিবরণী (Preview)</h2>
        <div class="data-row"><div class="data-lbl">শিক্ষা প্রতিষ্ঠানের নাম</div><div class="data-val"><?= htmlspecialchars($record['school_name']) ?> (<?= htmlspecialchars($record['school_name_bn']) ?>)</div></div>
        <div class="data-row"><div class="data-lbl">সাবডোমেন এড্রেস</div><div class="data-val" style="color:var(--brand); font-weight:700;"><?= htmlspecialchars($record['subdomain']) ?>.smartschool.bd</div></div>
        <div class="data-row"><div class="data-lbl">মহেশখালী উপজেলার ইউনিয়ন ও বিস্তারিত ঠিকানা</div><div class="data-val bn">ইউনিয়ন: <?= htmlspecialchars($record['union_name']) ?>, ঠিকানা: <?= htmlspecialchars($record['detailed_address']) ?></div></div>
        <div class="data-row"><div class="data-lbl">জিপিএস কোঅর্ডিনেটস (GPS Location)</div><div class="data-val">Lat: <?= htmlspecialchars($record['latitude'] ?: 'N/A') ?> , Lng: <?= htmlspecialchars($record['longitude'] ?: 'N/A') ?></div></div>
        <div class="data-row"><div class="data-lbl">প্রধান প্রতিনিধির নাম ও মোবাইল</div><div class="data-val"><?= htmlspecialchars($record['owner_name']) ?> (<?= htmlspecialchars($record['owner_phone']) ?>)</div></div>
        <div class="data-row"><div class="data-lbl">ইমেইল ঠিকানা</div><div class="data-val"><?= htmlspecialchars($record['owner_email']) ?></div></div>
        <div class="data-row" style="border:0;"><div class="data-lbl">অতিরিক্ত নোটসমূহ</div><div class="data-val bn"><?= nl2br(htmlspecialchars($record['notes'] ?: 'কোনো নোট দেওয়া হয়নি।')) ?></div></div>
        
        <div class="btn-flex">
            <a href="admin" class="btn">ড্যাশবোর্ডে ফিরুন</a>
            <a href="admin-action?action=edit&id=<?= $record['id'] ?>" class="btn btn-save">Edit Data</a>
        </div>

    <?php elseif ($action === 'edit'): ?>
        <h2>তথ্য সংশোধন করুন (Edit Mode)</h2>
        <form action="admin-action?action=update&id=<?= $record['id'] ?>" method="POST">
            <div style="margin-bottom:14px;"><label class="data-lbl">School Name (English)</label><input class="form-control" name="school_name" value="<?= htmlspecialchars($record['school_name']) ?>" required></div>
            <div style="margin-bottom:14px;"><label class="data-lbl">প্রতিষ্ঠানের নাম (বাংলা)</label><input class="form-control bn" name="school_name_bn" value="<?= htmlspecialchars($record['school_name_bn']) ?>"></div>
            <div style="margin-bottom:14px;"><label class="data-lbl">Subdomain Route Prefix</label><input class="form-control" name="subdomain" value="<?= htmlspecialchars($record['subdomain']) ?>" required></div>
            <div style="margin-bottom:14px;"><label class="data-lbl">ইউনিয়ন</label><input class="form-control bn" name="union_name" value="<?= htmlspecialchars($record['union_name']) ?>" required></div>
            <div style="margin-bottom:14px;"><label class="data-lbl">বিস্তারিত ঠিকানা</label><input class="form-control bn" name="detailed_address" value="<?= htmlspecialchars($record['detailed_address']) ?>" required></div>
            <div style="margin-bottom:14px;"><label class="data-lbl">Latitude / Longitude</label>
                <div style="display:flex; gap:8px;"><input class="form-control" name="latitude" value="<?= htmlspecialchars($record['latitude']) ?>"><input class="form-control" name="longitude" value="<?= htmlspecialchars($record['longitude']) ?>"></div>
            </div>
            <div style="margin-bottom:14px;"><label class="data-lbl">Full Name</label><input class="form-control" name="owner_name" value="<?= htmlspecialchars($record['owner_name']) ?>" required></div>
            <div style="margin-bottom:14px;"><label class="data-lbl">Mobile Phone</label><input class="form-control" name="owner_phone" value="<?= htmlspecialchars($record['owner_phone']) ?>" required></div>
            <div style="margin-bottom:14px;"><label class="data-lbl">Email Address</label><input class="form-control" type="email" name="owner_email" value="<?= htmlspecialchars($record['owner_email']) ?>" required></div>
            <div style="margin-bottom:20px;"><label class="data-lbl">Notes</label><textarea class="form-control bn" name="notes" style="min-height:80px;"><?= htmlspecialchars($record['notes']) ?></textarea></div>
            
            <div class="btn-flex">
                <button type="submit" class="btn btn-save">Save Modifications</button>
                <a href="admin" class="btn">Cancel</a>
            </div>
        </form>
    <?php endif; ?>
</div>

</body>
</html>