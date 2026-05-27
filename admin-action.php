<?php
/**
 * Smart Maheshkhali — admin record actions: view, edit, update, delete.
 *
 * Auth-protected. State-changing actions (delete, update) require POST
 * + CSRF. The edit form mirrors the public submission validation so
 * admin edits cannot bypass the same business rules (subdomain
 * uniqueness, reserved names, phone format, ...).
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

require_admin();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ---------------------------------------------------------------------
// Resolve action + id from GET or POST.
// ---------------------------------------------------------------------
$action = (string) ($_REQUEST['action'] ?? '');
$id     = filter_var($_REQUEST['id'] ?? null, FILTER_VALIDATE_INT) ?: 0;

$validActions = ['view', 'edit', 'update', 'delete'];
if (!in_array($action, $validActions, true) || $id <= 0) {
    redirect('/admin.php');
}

// ---------------------------------------------------------------------
// DELETE — POST only, CSRF protected.
// ---------------------------------------------------------------------
if ($action === 'delete') {
    if ($method !== 'POST') {
        // GET delete attempts are blocked. Send the operator to admin
        // where the proper POST form lives.
        redirect('/admin.php');
    }
    csrf_check();
    $stmt = $db->prepare('DELETE FROM registrations WHERE id = ?');
    $stmt->execute([$id]);
    $_SESSION['admin_flash'] = 'রেকর্ডটি সফলভাবে মুছে ফেলা হয়েছে।';
    redirect('/admin.php');
}

// ---------------------------------------------------------------------
// UPDATE — POST + CSRF; revalidates everything before saving.
// ---------------------------------------------------------------------
if ($action === 'update') {
    if ($method !== 'POST') {
        redirect('/admin.php');
    }
    csrf_check();

    $existsStmt = $db->prepare('SELECT * FROM registrations WHERE id = ?');
    $existsStmt->execute([$id]);
    $current = $existsStmt->fetch();
    if (!$current) {
        $_SESSION['admin_flash'] = 'রেকর্ড পাওয়া যায়নি।';
        redirect('/admin.php');
    }

    $institution_type = input('institution_type');
    $school_name      = input('school_name');
    $school_name_bn   = input('school_name_bn');
    $subdomain        = strtolower(input('subdomain'));
    $union_name       = input('union_name');
    $detailed_address = input('detailed_address');
    $latitude         = input('latitude');
    $longitude        = input('longitude');
    $owner_name       = input('owner_name');
    $owner_phone_raw  = input('owner_phone');
    $owner_email      = input('owner_email');
    $notes            = input('notes');
    $total_students_raw    = input('total_students');
    $total_teachers_raw    = input('total_teachers');
    $ict_teacher_available = input('ict_teacher_available');
    $smart_school_reason   = input('smart_school_reason');

    $errors = [];
    $validTypes = ['primary', 'madrasah', 'high_school'];

    if (!in_array($institution_type, $validTypes, true)) {
        $errors[] = 'প্রতিষ্ঠানের ধরন নির্বাচন করা বাধ্যতামূলক।';
    }
    if ($school_name === '' || mb_strlen($school_name) < 3) {
        $errors[] = 'প্রতিষ্ঠানের নাম প্রদান আবশ্যক (কমপক্ষে ৩ অক্ষর)।';
    }
    if (!is_valid_subdomain($subdomain)) {
        $errors[] = 'সাবডোমেন ফরম্যাট সঠিক নয়।';
    } elseif (is_reserved_subdomain($subdomain)) {
        $errors[] = 'এই সাবডোমেনটি সংরক্ষিত। ভিন্ন একটি বেছে নিন।';
    }
    if ($union_name === '')       { $errors[] = 'ইউনিয়ন প্রদান আবশ্যক।'; }
    if ($detailed_address === '') { $errors[] = 'বিস্তারিত ঠিকানা প্রদান আবশ্যক।'; }
    if ($owner_name === '')       { $errors[] = 'যোগাযোগকারীর নাম প্রদান আবশ্যক।'; }

    if ($latitude !== '' && (!preg_match('/^-?\d{1,3}(\.\d{1,12})?$/', $latitude)
        || (float) $latitude < -90 || (float) $latitude > 90)) {
        $errors[] = 'অক্ষাংশ মান সঠিক নয়।';
    }
    if ($longitude !== '' && (!preg_match('/^-?\d{1,3}(\.\d{1,12})?$/', $longitude)
        || (float) $longitude < -180 || (float) $longitude > 180)) {
        $errors[] = 'দ্রাঘিমাংশ মান সঠিক নয়।';
    }

    $owner_phone = $owner_phone_raw === '' ? null : normalise_phone($owner_phone_raw);
    if ($owner_phone === null) {
        $errors[] = 'মোবাইল নম্বর সঠিক নয়।';
    }

    $validatedEmail = filter_var($owner_email, FILTER_VALIDATE_EMAIL);
    if (!$validatedEmail) {
        $errors[] = 'বৈধ ইমেইল প্রদান করুন।';
    }

    // Headcounts and operational profile (admin can leave blank to clear).
    $total_students = null;
    if ($total_students_raw !== '') {
        if (!ctype_digit($total_students_raw) || (int) $total_students_raw > 20000) {
            $errors[] = 'মোট শিক্ষার্থী সংখ্যা সঠিক নয়।';
        } else {
            $total_students = (int) $total_students_raw;
        }
    }

    $total_teachers = null;
    if ($total_teachers_raw !== '') {
        if (!ctype_digit($total_teachers_raw) || (int) $total_teachers_raw > 2000) {
            $errors[] = 'মোট শিক্ষক সংখ্যা সঠিক নয়।';
        } else {
            $total_teachers = (int) $total_teachers_raw;
        }
    }

    if ($ict_teacher_available !== '' && !in_array($ict_teacher_available, ['yes', 'no'], true)) {
        $errors[] = 'ICT শিক্ষক ফিল্ডের মান সঠিক নয়।';
        $ict_teacher_available = '';
    }

    if ($smart_school_reason !== '' && mb_strlen($smart_school_reason) > 2000) {
        $smart_school_reason = mb_substr($smart_school_reason, 0, 2000);
    }

    if (!$errors) {
        // Subdomain uniqueness — exclude this row.
        $dupe = $db->prepare(
            'SELECT id FROM registrations WHERE subdomain = ? AND id != ?'
        );
        $dupe->execute([$subdomain, $id]);
        if ($dupe->fetchColumn()) {
            $errors[] = 'এই সাবডোমেনটি ইতিমধ্যে অন্য রেকর্ডে নিবন্ধিত।';
        }
    }

    if ($errors) {
        $_SESSION['edit_errors']    = $errors;
        $_SESSION['edit_old_input'] = $_POST;
        redirect('/admin-action.php?action=edit&id=' . $id);
    }

    try {
        $stmt = $db->prepare(
            'UPDATE registrations SET
                institution_type = ?, school_name = ?, school_name_bn = ?,
                subdomain = ?, union_name = ?, detailed_address = ?,
                latitude = ?, longitude = ?, owner_name = ?,
                owner_phone = ?, owner_email = ?, notes = ?,
                total_students = ?, total_teachers = ?,
                ict_teacher_available = ?, smart_school_reason = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $institution_type, $school_name, $school_name_bn,
            $subdomain, $union_name, $detailed_address,
            $latitude, $longitude, $owner_name,
            $owner_phone, $validatedEmail, $notes,
            $total_students, $total_teachers,
            $ict_teacher_available !== '' ? $ict_teacher_available : null,
            $smart_school_reason !== '' ? $smart_school_reason : null,
            $id,
        ]);
    } catch (PDOException $e) {
        error_log('[Smart Maheshkhali] Update failed for #' . $id . ': ' . $e->getMessage());
        $_SESSION['edit_errors']    = ['সিস্টেমে সংরক্ষণ করতে ত্রুটি হয়েছে। আবার চেষ্টা করুন।'];
        $_SESSION['edit_old_input'] = $_POST;
        redirect('/admin-action.php?action=edit&id=' . $id);
    }

    $_SESSION['admin_flash'] = 'রেকর্ডটি সফলভাবে সংশোধন করা হয়েছে।';
    redirect('/admin.php');
}

// ---------------------------------------------------------------------
// VIEW / EDIT — fetch the record.
// ---------------------------------------------------------------------
$stmt = $db->prepare('SELECT * FROM registrations WHERE id = ?');
$stmt->execute([$id]);
$record = $stmt->fetch();

if (!$record) {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><body style="font-family:sans-serif;padding:40px;">';
    echo '<h2>রেকর্ড পাওয়া যায়নি</h2><p><a href="' . e(url('/admin.php')) . '">&larr; ড্যাশবোর্ডে ফিরুন</a></p>';
    exit;
}

$editErrors = (array) flash_pull('edit_errors', []);
$editOld    = (array) flash_pull('edit_old_input', []);

// For the edit form, prefer previously-submitted values over the DB row
// so validation errors don't lose typing.
$v = function (string $key, $fallback) use ($editOld, $record): string {
    $val = $editOld[$key] ?? $record[$key] ?? $fallback ?? '';
    return e(is_string($val) ? $val : (string) $val);
};
?>
<!doctype html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>অ্যাকশন সেন্টার | <?= e($CONFIG['app_name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Tiro+Bangla&display=swap" rel="stylesheet">
    <style>
        :root { --background:#fafafa; --foreground:#09090b; --card:#fff; --border:#e4e4e7;
                --muted:#71717a; --brand:#059669; --radius:12px; }
        *, *::before, *::after { box-sizing:border-box; }
        body { margin:0; font-family:'Inter','Tiro Bangla',sans-serif; background:var(--background);
               color:var(--foreground); padding:40px 16px; -webkit-font-smoothing:antialiased; }
        .wrapper { max-width:680px; margin:0 auto; }
        .breadcrumb { font-size:.85rem; color:var(--muted); margin-bottom:16px; }
        .breadcrumb a { color:var(--muted); }
        .action-card { background:var(--card); border:1px solid var(--border); border-radius:var(--radius);
                       padding:36px; box-shadow:0 10px 25px rgba(0,0,0,.02); }
        h2 { font-family:'Tiro Bangla',serif; font-size:1.45rem; margin:0 0 20px;
             border-bottom:1px solid var(--border); padding-bottom:14px; }

        .data-row { margin-bottom:14px; padding-bottom:12px; border-bottom:1px solid #f4f4f5; }
        .data-row:last-child { border-bottom:0; padding-bottom:0; margin-bottom:0; }
        .data-lbl { font-size:.78rem; font-weight:700; color:var(--muted);
                    text-transform:uppercase; letter-spacing:.04em; }
        .data-val { font-size:.98rem; color:var(--foreground); margin-top:2px; word-break:break-word; }

        label.fl { display:block; font-size:.82rem; font-weight:700; color:var(--muted);
                   text-transform:uppercase; letter-spacing:.04em; margin-bottom:6px; }
        .form-control { width:100%; padding:11px 14px; border:1px solid var(--border); border-radius:6px;
                        font-size:.95rem; font-family:inherit; background:#fff; color:inherit; transition:.15s; }
        .form-control:focus { outline:none; border-color:var(--brand); box-shadow:0 0 0 3px rgba(5,150,105,.1); }
        .form-row { margin-bottom:14px; }
        .grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
        @media (max-width:520px) { .grid-2 { grid-template-columns:1fr; } }

        .alert { background:#fef2f2; border:1px solid #fee2e2; color:#991b1b;
                 padding:14px; border-radius:8px; margin-bottom:18px; font-size:.88rem; }
        .alert ul { margin:6px 0 0 18px; padding:0; }

        .btn-flex { display:flex; gap:10px; margin-top:24px; flex-wrap:wrap; }
        .btn { padding:11px 22px; font-size:.9rem; font-weight:600; border-radius:6px; cursor:pointer;
               text-decoration:none; border:1px solid var(--border); background:#fff;
               color:var(--foreground); display:inline-flex; align-items:center; font-family:inherit; }
        .btn-save { background:var(--foreground); color:#fff; border:none; }
        .btn-save:hover { background:#1e293b; }
    </style>
</head>
<body>

<div class="wrapper">
    <div class="breadcrumb">
        <a href="<?= e(url('/admin.php')) ?>">ড্যাশবোর্ড</a> &nbsp;/&nbsp;
        রেকর্ড #<?= e((string) $record['id']) ?>
    </div>

    <div class="action-card">
        <?php if ($action === 'view'): ?>
            <h2>আবেদনপত্রের বিবরণী (Preview)</h2>

            <div class="data-row">
                <div class="data-lbl">শিক্ষা প্রতিষ্ঠানের নাম</div>
                <div class="data-val">
                    <?= e($record['school_name']) ?>
                    <?php if ($record['school_name_bn']): ?>
                        (<?= e($record['school_name_bn']) ?>)
                    <?php endif; ?>
                </div>
            </div>

            <div class="data-row">
                <div class="data-lbl">প্রতিষ্ঠানের ধরন</div>
                <div class="data-val"><?= e(institution_label($record['institution_type'])) ?></div>
            </div>

            <div class="data-row">
                <div class="data-lbl">সাবডোমেন এড্রেস</div>
                <div class="data-val" style="color:var(--brand); font-weight:700; font-family:monospace;">
                    <?= e($record['subdomain']) ?>.smartschool.bd
                </div>
            </div>

            <div class="data-row">
                <div class="data-lbl">ইউনিয়ন ও বিস্তারিত ঠিকানা</div>
                <div class="data-val">
                    ইউনিয়ন: <?= e($record['union_name']) ?><br>
                    ঠিকানা: <?= e($record['detailed_address']) ?>
                </div>
            </div>

            <div class="data-row">
                <div class="data-lbl">জিপিএস কোঅর্ডিনেটস</div>
                <div class="data-val">
                    Lat: <?= e($record['latitude']) ?: 'N/A' ?>,
                    Lng: <?= e($record['longitude']) ?: 'N/A' ?>
                </div>
            </div>

            <div class="data-row">
                <div class="data-lbl">প্রধান প্রতিনিধি</div>
                <div class="data-val">
                    <?= e($record['owner_name']) ?> (<?= e($record['owner_phone']) ?>)
                </div>
            </div>

            <div class="data-row">
                <div class="data-lbl">ইমেইল ঠিকানা</div>
                <div class="data-val"><?= e($record['owner_email']) ?></div>
            </div>

            <div class="data-row">
                <div class="data-lbl">শিক্ষার্থী ও শিক্ষক সংখ্যা</div>
                <div class="data-val">
                    মোট শিক্ষার্থী:
                    <strong><?= e((string) ($record['total_students'] ?? '')) ?: '<em style="color:var(--muted);">N/A</em>' ?></strong>
                    &nbsp;·&nbsp;
                    মোট শিক্ষক:
                    <strong><?= e((string) ($record['total_teachers'] ?? '')) ?: '<em style="color:var(--muted);">N/A</em>' ?></strong>
                </div>
            </div>

            <div class="data-row">
                <div class="data-lbl">ICT অভিজ্ঞ শিক্ষক আছেন?</div>
                <div class="data-val">
                    <?php $ictVal = (string) ($record['ict_teacher_available'] ?? ''); ?>
                    <?php if ($ictVal === 'yes'): ?>
                        <span style="color:#15803d; font-weight:600;">হ্যাঁ আছেন (Yes)</span>
                    <?php elseif ($ictVal === 'no'): ?>
                        <span style="color:#b91c1c; font-weight:600;">না, নেই (No)</span>
                    <?php else: ?>
                        <em style="color:var(--muted);">উল্লেখ করা হয়নি</em>
                    <?php endif; ?>
                </div>
            </div>

            <div class="data-row">
                <div class="data-lbl">স্কুলকে স্মার্ট করতে চান কেন?</div>
                <div class="data-val">
                    <?= !empty($record['smart_school_reason'])
                        ? nl2br(e($record['smart_school_reason']))
                        : '<em style="color:var(--muted);">উত্তর পাওয়া যায়নি।</em>' ?>
                </div>
            </div>

            <div class="data-row">
                <div class="data-lbl">অতিরিক্ত নোট</div>
                <div class="data-val">
                    <?= $record['notes']
                        ? nl2br(e($record['notes']))
                        : '<em style="color:var(--muted);">কোনো নোট দেওয়া হয়নি।</em>' ?>
                </div>
            </div>

            <div class="data-row">
                <div class="data-lbl">জমাদানের তারিখ</div>
                <div class="data-val">
                    <?= e(date('Y-m-d H:i', strtotime((string) $record['created_at']))) ?>
                </div>
            </div>

            <div class="btn-flex">
                <a href="<?= e(url('/admin.php')) ?>" class="btn">ড্যাশবোর্ডে ফিরুন</a>
                <a href="<?= e(url('/admin-action.php?action=edit&id=' . (int) $record['id'])) ?>"
                   class="btn btn-save">এডিট করুন</a>
            </div>

        <?php else: /* edit */ ?>
            <h2>তথ্য সংশোধন করুন (Edit Mode)</h2>

            <?php if ($editErrors): ?>
                <div class="alert" role="alert">
                    <strong>ত্রুটিসমূহ:</strong>
                    <ul>
                        <?php foreach ($editErrors as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="<?= e(url('/admin-action.php')) ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?= (int) $record['id'] ?>">

                <div class="form-row">
                    <label class="fl" for="ed_type">প্রতিষ্ঠানের ধরন</label>
                    <select class="form-control" id="ed_type" name="institution_type" required>
                        <?php
                        $types = [
                            'primary'     => 'প্রাথমিক বিদ্যালয়',
                            'madrasah'    => 'মাদ্রাসা',
                            'high_school' => 'মাধ্যমিক বিদ্যালয়',
                        ];
                        $sel = (string) ($editOld['institution_type'] ?? $record['institution_type']);
                        foreach ($types as $code => $label):
                        ?>
                            <option value="<?= e($code) ?>" <?= $sel === $code ? 'selected' : '' ?>>
                                <?= e($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <label class="fl" for="ed_school">School Name (English)</label>
                    <input class="form-control" id="ed_school" name="school_name" required
                           value="<?= $v('school_name', '') ?>">
                </div>

                <div class="form-row">
                    <label class="fl" for="ed_school_bn">প্রতিষ্ঠানের নাম (বাংলা)</label>
                    <input class="form-control" id="ed_school_bn" name="school_name_bn"
                           value="<?= $v('school_name_bn', '') ?>">
                </div>

                <div class="form-row">
                    <label class="fl" for="ed_sub">Subdomain</label>
                    <input class="form-control" id="ed_sub" name="subdomain" required
                           pattern="[a-z0-9](?:[a-z0-9-]{1,30}[a-z0-9])"
                           autocapitalize="off" spellcheck="false"
                           value="<?= $v('subdomain', '') ?>">
                </div>

                <div class="form-row">
                    <label class="fl" for="ed_union">ইউনিয়ন</label>
                    <input class="form-control" id="ed_union" name="union_name" required
                           value="<?= $v('union_name', '') ?>">
                </div>

                <div class="form-row">
                    <label class="fl" for="ed_addr">বিস্তারিত ঠিকানা</label>
                    <input class="form-control" id="ed_addr" name="detailed_address" required
                           value="<?= $v('detailed_address', '') ?>">
                </div>

                <div class="form-row">
                    <label class="fl">Latitude / Longitude</label>
                    <div class="grid-2">
                        <input class="form-control" name="latitude"
                               value="<?= $v('latitude', '') ?>" placeholder="Latitude">
                        <input class="form-control" name="longitude"
                               value="<?= $v('longitude', '') ?>" placeholder="Longitude">
                    </div>
                </div>

                <div class="form-row">
                    <label class="fl" for="ed_owner">Full Name</label>
                    <input class="form-control" id="ed_owner" name="owner_name" required
                           value="<?= $v('owner_name', '') ?>">
                </div>

                <div class="form-row">
                    <label class="fl" for="ed_phone">Mobile Phone</label>
                    <input class="form-control" id="ed_phone" name="owner_phone" required
                           value="<?= $v('owner_phone', '') ?>">
                </div>

                <div class="form-row">
                    <label class="fl" for="ed_email">Email Address</label>
                    <input class="form-control" id="ed_email" type="email" name="owner_email" required
                           value="<?= $v('owner_email', '') ?>">
                </div>

                <div class="form-row">
                    <label class="fl">Total Students / Teachers</label>
                    <div class="grid-2">
                        <input class="form-control" type="number" min="0" max="20000"
                               name="total_students" placeholder="শিক্ষার্থী"
                               value="<?= $v('total_students', '') ?>">
                        <input class="form-control" type="number" min="0" max="2000"
                               name="total_teachers" placeholder="শিক্ষক"
                               value="<?= $v('total_teachers', '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <label class="fl">ICT-experienced Teacher Available</label>
                    <select class="form-control" name="ict_teacher_available">
                        <?php
                        $ictCur = (string) ($editOld['ict_teacher_available'] ?? $record['ict_teacher_available'] ?? '');
                        ?>
                        <option value=""    <?= $ictCur === ''    ? 'selected' : '' ?>>— উল্লেখ নেই —</option>
                        <option value="yes" <?= $ictCur === 'yes' ? 'selected' : '' ?>>হ্যাঁ আছেন (Yes)</option>
                        <option value="no"  <?= $ictCur === 'no'  ? 'selected' : '' ?>>না, নেই (No)</option>
                    </select>
                </div>

                <div class="form-row">
                    <label class="fl" for="ed_reason">Why Smart School?</label>
                    <textarea class="form-control" id="ed_reason" name="smart_school_reason"
                              maxlength="2000" style="min-height:90px;"><?= $v('smart_school_reason', '') ?></textarea>
                </div>

                <div class="form-row">
                    <label class="fl" for="ed_notes">Notes</label>
                    <textarea class="form-control" id="ed_notes" name="notes" maxlength="2000"
                              style="min-height:90px;"><?= $v('notes', '') ?></textarea>
                </div>

                <div class="btn-flex">
                    <button type="submit" class="btn btn-save">পরিবর্তন সংরক্ষণ করুন</button>
                    <a href="<?= e(url('/admin.php')) ?>" class="btn">বাতিল</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
