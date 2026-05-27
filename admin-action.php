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

    // New fields
    $eiin_number       = input('eiin_number');
    $mpo_status        = input('mpo_status');
    $establishment_year_raw = input('establishment_year');
    $school_phone      = input('school_phone');
    $school_email_field = input('school_email');
    $school_website    = input('school_website');
    $division          = input('division');
    $district          = input('district');
    $upazila           = input('upazila');
    $union_ward        = input('union_ward');
    $village           = input('village');
    $postal_code       = input('postal_code');
    $num_buildings_raw   = input('num_buildings');
    $num_classrooms_raw  = input('num_classrooms');
    $has_computer_lab    = input('has_computer_lab');
    $has_science_lab     = input('has_science_lab');
    $has_library         = input('has_library');
    $has_playground      = input('has_playground');
    $total_students_boys_raw  = input('total_students_boys');
    $total_students_girls_raw = input('total_students_girls');
    $students_class_1_raw  = input('students_class_1');
    $students_class_2_raw  = input('students_class_2');
    $students_class_3_raw  = input('students_class_3');
    $students_class_4_raw  = input('students_class_4');
    $students_class_5_raw  = input('students_class_5');
    $students_class_6_raw  = input('students_class_6');
    $students_class_7_raw  = input('students_class_7');
    $students_class_8_raw  = input('students_class_8');
    $students_class_9_raw  = input('students_class_9');
    $students_class_10_raw = input('students_class_10');
    $male_teachers_raw      = input('male_teachers');
    $female_teachers_raw    = input('female_teachers');
    $trained_teachers_raw   = input('trained_teachers');
    $untrained_teachers_raw = input('untrained_teachers');
    $num_computers_raw          = input('num_computers');
    $has_internet               = input('has_internet');
    $internet_type              = input('internet_type');
    $num_projectors_raw         = input('num_projectors');
    $has_multimedia_classroom   = input('has_multimedia_classroom');
    $existing_software             = input('existing_software');
    $head_teacher_whatsapp_raw     = input('head_teacher_whatsapp');
    $managing_committee_chairman   = input('managing_committee_chairman');
    $managing_committee_phone_raw  = input('managing_committee_phone');

    $errors = [];
    $validTypes = array_keys(institution_types());

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

    // MPO status
    if ($mpo_status !== '' && !in_array($mpo_status, ['mpo', 'non_mpo', 'newly_nationalized'], true)) {
        $errors[] = 'MPO স্ট্যাটাস সঠিক নয়।';
    }
    // EIIN
    if ($eiin_number !== '' && (!ctype_digit($eiin_number) || strlen($eiin_number) < 4 || strlen($eiin_number) > 8)) {
        $errors[] = 'EIIN নম্বর ৪-৮ ডিজিটের হতে হবে।';
    }
    // Establishment year
    $establishment_year = null;
    if ($establishment_year_raw !== '') {
        if (!ctype_digit($establishment_year_raw) || strlen($establishment_year_raw) !== 4
            || (int) $establishment_year_raw < 1800 || (int) $establishment_year_raw > (int) date('Y')) {
            $errors[] = 'প্রতিষ্ঠার সাল সঠিক নয়।';
        } else {
            $establishment_year = (int) $establishment_year_raw;
        }
    }
    // School email
    if ($school_email_field !== '' && !filter_var($school_email_field, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'প্রতিষ্ঠানের ইমেইল সঠিক নয়।';
    }
    // Numeric fields
    $num_buildings = null;
    if ($num_buildings_raw !== '') {
        if (!ctype_digit($num_buildings_raw) || (int) $num_buildings_raw > 100) { $errors[] = 'ভবন সংখ্যা সঠিক নয়।'; }
        else { $num_buildings = (int) $num_buildings_raw; }
    }
    $num_classrooms = null;
    if ($num_classrooms_raw !== '') {
        if (!ctype_digit($num_classrooms_raw) || (int) $num_classrooms_raw > 500) { $errors[] = 'শ্রেণিকক্ষ সংখ্যা সঠিক নয়।'; }
        else { $num_classrooms = (int) $num_classrooms_raw; }
    }
    $total_students_boys = null;
    if ($total_students_boys_raw !== '') {
        if (!ctype_digit($total_students_boys_raw) || (int) $total_students_boys_raw > 20000) { $errors[] = 'ছাত্র সংখ্যা সঠিক নয়।'; }
        else { $total_students_boys = (int) $total_students_boys_raw; }
    }
    $total_students_girls = null;
    if ($total_students_girls_raw !== '') {
        if (!ctype_digit($total_students_girls_raw) || (int) $total_students_girls_raw > 20000) { $errors[] = 'ছাত্রী সংখ্যা সঠিক নয়।'; }
        else { $total_students_girls = (int) $total_students_girls_raw; }
    }
    for ($i = 1; $i <= 10; $i++) {
        $varName = 'students_class_' . $i;
        $rawName = $varName . '_raw';
        $$varName = null;
        if ($$rawName !== '') {
            if (!ctype_digit($$rawName) || (int) $$rawName > 5000) { $errors[] = "শ্রেণি $i এর শিক্ষার্থী সংখ্যা সঠিক নয়।"; }
            else { $$varName = (int) $$rawName; }
        }
    }
    $male_teachers = null;
    if ($male_teachers_raw !== '') {
        if (!ctype_digit($male_teachers_raw) || (int) $male_teachers_raw > 1000) { $errors[] = 'পুরুষ শিক্ষক সংখ্যা সঠিক নয়।'; }
        else { $male_teachers = (int) $male_teachers_raw; }
    }
    $female_teachers = null;
    if ($female_teachers_raw !== '') {
        if (!ctype_digit($female_teachers_raw) || (int) $female_teachers_raw > 1000) { $errors[] = 'মহিলা শিক্ষক সংখ্যা সঠিক নয়।'; }
        else { $female_teachers = (int) $female_teachers_raw; }
    }
    $trained_teachers = null;
    if ($trained_teachers_raw !== '') {
        if (!ctype_digit($trained_teachers_raw) || (int) $trained_teachers_raw > 2000) { $errors[] = 'প্রশিক্ষিত শিক্ষক সংখ্যা সঠিক নয়।'; }
        else { $trained_teachers = (int) $trained_teachers_raw; }
    }
    $untrained_teachers = null;
    if ($untrained_teachers_raw !== '') {
        if (!ctype_digit($untrained_teachers_raw) || (int) $untrained_teachers_raw > 2000) { $errors[] = 'অপ্রশিক্ষিত শিক্ষক সংখ্যা সঠিক নয়।'; }
        else { $untrained_teachers = (int) $untrained_teachers_raw; }
    }
    $num_computers = null;
    if ($num_computers_raw !== '') {
        if (!ctype_digit($num_computers_raw) || (int) $num_computers_raw > 1000) { $errors[] = 'কম্পিউটার সংখ্যা সঠিক নয়।'; }
        else { $num_computers = (int) $num_computers_raw; }
    }
    $num_projectors = null;
    if ($num_projectors_raw !== '') {
        if (!ctype_digit($num_projectors_raw) || (int) $num_projectors_raw > 100) { $errors[] = 'প্রজেক্টর সংখ্যা সঠিক নয়।'; }
        else { $num_projectors = (int) $num_projectors_raw; }
    }
    // Yes/no fields
    $yesNoFields = ['has_computer_lab', 'has_science_lab', 'has_library', 'has_playground', 'has_internet', 'has_multimedia_classroom'];
    foreach ($yesNoFields as $ynf) {
        if ($$ynf !== '' && !in_array($$ynf, ['yes', 'no'], true)) {
            $errors[] = "$ynf ফিল্ডের মান সঠিক নয়।";
            $$ynf = '';
        }
    }
    // Internet type
    if ($has_internet === 'no') { $internet_type = 'none'; }
    elseif ($internet_type !== '' && !in_array($internet_type, ['broadband', 'mobile_data', 'fiber', 'none'], true)) { $internet_type = ''; }
    // WhatsApp
    $head_teacher_whatsapp = '';
    if ($head_teacher_whatsapp_raw !== '') {
        $normalised = normalise_phone($head_teacher_whatsapp_raw);
        if ($normalised !== null) { $head_teacher_whatsapp = $normalised; }
        else {
            $digits = preg_replace('/[^\d]/', '', $head_teacher_whatsapp_raw);
            if (strlen($digits) >= 10 && strlen($digits) <= 15) { $head_teacher_whatsapp = $head_teacher_whatsapp_raw; }
            else { $errors[] = 'WhatsApp নম্বর সঠিক নয়।'; }
        }
    }
    // Managing committee phone
    $managing_committee_phone = '';
    if ($managing_committee_phone_raw !== '') {
        $normalised = normalise_phone($managing_committee_phone_raw);
        if ($normalised !== null) { $managing_committee_phone = $normalised; }
        else { $errors[] = 'পরিচালনা কমিটির চেয়ারম্যান ফোন নম্বর সঠিক নয়।'; }
    }
    // Length caps
    if (mb_strlen($existing_software) > 2000) { $existing_software = mb_substr($existing_software, 0, 2000); }

    if (!$errors) {
        $dupe = $db->prepare('SELECT id FROM registrations WHERE subdomain = ? AND id != ?');
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
                institution_type=?, school_name=?, school_name_bn=?, subdomain=?,
                union_name=?, detailed_address=?, latitude=?, longitude=?,
                owner_name=?, owner_phone=?, owner_email=?, notes=?,
                total_students=?, total_teachers=?, ict_teacher_available=?, smart_school_reason=?,
                eiin_number=?, mpo_status=?, establishment_year=?,
                school_phone=?, school_email=?, school_website=?,
                division=?, district=?, upazila=?, union_ward=?, village=?, postal_code=?,
                num_buildings=?, num_classrooms=?,
                has_computer_lab=?, has_science_lab=?, has_library=?, has_playground=?,
                total_students_boys=?, total_students_girls=?,
                students_class_1=?, students_class_2=?, students_class_3=?, students_class_4=?, students_class_5=?,
                students_class_6=?, students_class_7=?, students_class_8=?, students_class_9=?, students_class_10=?,
                male_teachers=?, female_teachers=?, trained_teachers=?, untrained_teachers=?,
                num_computers=?, has_internet=?, internet_type=?, num_projectors=?, has_multimedia_classroom=?,
                existing_software=?,
                head_teacher_name=?, head_teacher_phone=?, head_teacher_whatsapp=?, head_teacher_email=?,
                managing_committee_chairman=?, managing_committee_phone=?
             WHERE id = ?'
        );
        $stmt->execute([
            $institution_type, $school_name, $school_name_bn, $subdomain,
            $union_name, $detailed_address, $latitude ?: null, $longitude ?: null,
            $owner_name, $owner_phone, $validatedEmail, $notes ?: null,
            $total_students, $total_teachers,
            $ict_teacher_available !== '' ? $ict_teacher_available : null,
            $smart_school_reason !== '' ? $smart_school_reason : null,
            $eiin_number ?: null, $mpo_status ?: null, $establishment_year,
            $school_phone ?: null, $school_email_field ?: null, $school_website ?: null,
            $division ?: null, $district ?: null, $upazila ?: null,
            $union_ward ?: null, $village ?: null, $postal_code ?: null,
            $num_buildings, $num_classrooms,
            $has_computer_lab ?: null, $has_science_lab ?: null, $has_library ?: null, $has_playground ?: null,
            $total_students_boys, $total_students_girls,
            $students_class_1, $students_class_2, $students_class_3, $students_class_4, $students_class_5,
            $students_class_6, $students_class_7, $students_class_8, $students_class_9, $students_class_10,
            $male_teachers, $female_teachers, $trained_teachers, $untrained_teachers,
            $num_computers, $has_internet ?: null, $internet_type ?: null,
            $num_projectors, $has_multimedia_classroom ?: null,
            $existing_software ?: null,
            $owner_name, $owner_phone, $head_teacher_whatsapp ?: null, $validatedEmail,
            $managing_committee_chairman ?: null, $managing_committee_phone ?: null,
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

            <?php
            $mpoLabels = ['mpo' => 'এমপিও ভুক্ত', 'non_mpo' => 'নন-এমপিও', 'newly_nationalized' => 'নবজাতীয়করণ'];
            $ynLabel = function($val) { return $val === 'yes' ? 'হ্যাঁ' : ($val === 'no' ? 'না' : ''); };
            ?>

            <h3 style="font-size:.95rem; color:var(--brand); margin:24px 0 12px; border-bottom:2px solid #ecfdf5; padding-bottom:6px;">প্রতিষ্ঠানের তথ্য</h3>

            <div class="data-row">
                <div class="data-lbl">শিক্ষা প্রতিষ্ঠানের নাম</div>
                <div class="data-val">
                    <?= e($record['school_name']) ?>
                    <?php if (!empty($record['school_name_bn'])): ?>
                        (<?= e($record['school_name_bn']) ?>)
                    <?php endif; ?>
                </div>
            </div>
            <div class="data-row">
                <div class="data-lbl">প্রতিষ্ঠানের ধরন</div>
                <div class="data-val"><?= e(institution_label($record['institution_type'])) ?></div>
            </div>
            <div class="data-row">
                <div class="data-lbl">EIIN নম্বর</div>
                <div class="data-val"><?= e((string)($record['eiin_number'] ?? '')) ?: '<em style="color:var(--muted);">N/A</em>' ?></div>
            </div>
            <div class="data-row">
                <div class="data-lbl">MPO স্ট্যাটাস</div>
                <div class="data-val"><?= e($mpoLabels[$record['mpo_status'] ?? ''] ?? '') ?: '<em style="color:var(--muted);">N/A</em>' ?></div>
            </div>
            <div class="data-row">
                <div class="data-lbl">প্রতিষ্ঠার সাল</div>
                <div class="data-val"><?= e((string)($record['establishment_year'] ?? '')) ?: '<em style="color:var(--muted);">N/A</em>' ?></div>
            </div>
            <div class="data-row">
                <div class="data-lbl">সাবডোমেন এড্রেস</div>
                <div class="data-val" style="color:var(--brand); font-weight:700; font-family:monospace;">
                    <?= e($record['subdomain']) ?>.smartschool.bd
                </div>
            </div>
            <div class="data-row">
                <div class="data-lbl">প্রতিষ্ঠানের ফোন / ইমেইল / ওয়েবসাইট</div>
                <div class="data-val">
                    ফোন: <?= e((string)($record['school_phone'] ?? '')) ?: 'N/A' ?> |
                    ইমেইল: <?= e((string)($record['school_email'] ?? '')) ?: 'N/A' ?> |
                    ওয়েবসাইট: <?= e((string)($record['school_website'] ?? '')) ?: 'N/A' ?>
                </div>
            </div>

            <h3 style="font-size:.95rem; color:var(--brand); margin:24px 0 12px; border-bottom:2px solid #ecfdf5; padding-bottom:6px;">প্রধান শিক্ষক / যোগাযোগ</h3>

            <div class="data-row">
                <div class="data-lbl">প্রধান শিক্ষকের নাম</div>
                <div class="data-val"><?= e($record['owner_name'] ?? $record['head_teacher_name'] ?? '') ?></div>
            </div>
            <div class="data-row">
                <div class="data-lbl">ফোন নম্বর</div>
                <div class="data-val"><?= e($record['owner_phone'] ?? $record['head_teacher_phone'] ?? '') ?></div>
            </div>
            <div class="data-row">
                <div class="data-lbl">WhatsApp নম্বর</div>
                <div class="data-val"><?= e((string)($record['head_teacher_whatsapp'] ?? '')) ?: '<em style="color:var(--muted);">N/A</em>' ?></div>
            </div>
            <div class="data-row">
                <div class="data-lbl">ইমেইল</div>
                <div class="data-val"><?= e($record['owner_email'] ?? $record['head_teacher_email'] ?? '') ?></div>
            </div>
            <div class="data-row">
                <div class="data-lbl">পরিচালনা কমিটির চেয়ারম্যান</div>
                <div class="data-val"><?= e((string)($record['managing_committee_chairman'] ?? '')) ?: '<em style="color:var(--muted);">N/A</em>' ?></div>
            </div>
            <div class="data-row">
                <div class="data-lbl">চেয়ারম্যান ফোন</div>
                <div class="data-val"><?= e((string)($record['managing_committee_phone'] ?? '')) ?: '<em style="color:var(--muted);">N/A</em>' ?></div>
            </div>

            <h3 style="font-size:.95rem; color:var(--brand); margin:24px 0 12px; border-bottom:2px solid #ecfdf5; padding-bottom:6px;">ঠিকানা</h3>

            <div class="data-row">
                <div class="data-lbl">বিভাগ / জেলা / উপজেলা</div>
                <div class="data-val">
                    <?= e((string)($record['division'] ?? '')) ?: 'N/A' ?> /
                    <?= e((string)($record['district'] ?? '')) ?: 'N/A' ?> /
                    <?= e((string)($record['upazila'] ?? '')) ?: 'N/A' ?>
                </div>
            </div>
            <div class="data-row">
                <div class="data-lbl">ইউনিয়ন / ওয়ার্ড / গ্রাম / পোস্টাল কোড</div>
                <div class="data-val">
                    <?= e($record['union_name'] ?? '') ?> |
                    ওয়ার্ড: <?= e((string)($record['union_ward'] ?? '')) ?: 'N/A' ?> |
                    গ্রাম: <?= e((string)($record['village'] ?? '')) ?: 'N/A' ?> |
                    পোস্টাল কোড: <?= e((string)($record['postal_code'] ?? '')) ?: 'N/A' ?>
                </div>
            </div>
            <div class="data-row">
                <div class="data-lbl">বিস্তারিত ঠিকানা</div>
                <div class="data-val"><?= e($record['detailed_address'] ?? '') ?></div>
            </div>
            <div class="data-row">
                <div class="data-lbl">জিপিএস কোঅর্ডিনেটস</div>
                <div class="data-val">
                    Lat: <?= e((string)($record['latitude'] ?? '')) ?: 'N/A' ?>,
                    Lng: <?= e((string)($record['longitude'] ?? '')) ?: 'N/A' ?>
                </div>
            </div>

            <h3 style="font-size:.95rem; color:var(--brand); margin:24px 0 12px; border-bottom:2px solid #ecfdf5; padding-bottom:6px;">অবকাঠামো</h3>

            <div class="data-row">
                <div class="data-lbl">ভবন / শ্রেণিকক্ষ</div>
                <div class="data-val">
                    ভবন: <?= e((string)($record['num_buildings'] ?? '')) ?: 'N/A' ?> |
                    শ্রেণিকক্ষ: <?= e((string)($record['num_classrooms'] ?? '')) ?: 'N/A' ?>
                </div>
            </div>
            <div class="data-row">
                <div class="data-lbl">কম্পিউটার ল্যাব / বিজ্ঞান গবেষণাগার / লাইব্রেরি / খেলার মাঠ</div>
                <div class="data-val">
                    কম্পিউটার ল্যাব: <?= $ynLabel($record['has_computer_lab'] ?? '') ?: 'N/A' ?> |
                    বিজ্ঞান গবেষণাগার: <?= $ynLabel($record['has_science_lab'] ?? '') ?: 'N/A' ?> |
                    লাইব্রেরি: <?= $ynLabel($record['has_library'] ?? '') ?: 'N/A' ?> |
                    খেলার মাঠ: <?= $ynLabel($record['has_playground'] ?? '') ?: 'N/A' ?>
                </div>
            </div>

            <h3 style="font-size:.95rem; color:var(--brand); margin:24px 0 12px; border-bottom:2px solid #ecfdf5; padding-bottom:6px;">শিক্ষার্থী</h3>

            <div class="data-row">
                <div class="data-lbl">মোট শিক্ষার্থী / ছাত্র / ছাত্রী</div>
                <div class="data-val">
                    মোট: <strong><?= e((string)($record['total_students'] ?? '')) ?: 'N/A' ?></strong> |
                    ছাত্র: <?= e((string)($record['total_students_boys'] ?? '')) ?: 'N/A' ?> |
                    ছাত্রী: <?= e((string)($record['total_students_girls'] ?? '')) ?: 'N/A' ?>
                </div>
            </div>
            <?php
            $classData = [];
            for ($ci = 1; $ci <= 10; $ci++) {
                $cv = $record['students_class_' . $ci] ?? null;
                if ($cv !== null && $cv !== '') { $classData[] = "শ্রেণি $ci: $cv"; }
            }
            if ($classData): ?>
            <div class="data-row">
                <div class="data-lbl">শ্রেণি ভিত্তিক শিক্ষার্থী</div>
                <div class="data-val"><?= e(implode(' | ', $classData)) ?></div>
            </div>
            <?php endif; ?>

            <h3 style="font-size:.95rem; color:var(--brand); margin:24px 0 12px; border-bottom:2px solid #ecfdf5; padding-bottom:6px;">শিক্ষক</h3>

            <div class="data-row">
                <div class="data-lbl">মোট / পুরুষ / মহিলা / প্রশিক্ষিত / অপ্রশিক্ষিত</div>
                <div class="data-val">
                    মোট: <strong><?= e((string)($record['total_teachers'] ?? '')) ?: 'N/A' ?></strong> |
                    পুরুষ: <?= e((string)($record['male_teachers'] ?? '')) ?: 'N/A' ?> |
                    মহিলা: <?= e((string)($record['female_teachers'] ?? '')) ?: 'N/A' ?> |
                    প্রশিক্ষিত: <?= e((string)($record['trained_teachers'] ?? '')) ?: 'N/A' ?> |
                    অপ্রশিক্ষিত: <?= e((string)($record['untrained_teachers'] ?? '')) ?: 'N/A' ?>
                </div>
            </div>

            <h3 style="font-size:.95rem; color:var(--brand); margin:24px 0 12px; border-bottom:2px solid #ecfdf5; padding-bottom:6px;">প্রযুক্তি</h3>

            <div class="data-row">
                <div class="data-lbl">কম্পিউটার / ইন্টারনেট / প্রজেক্টর</div>
                <div class="data-val">
                    কম্পিউটার: <?= e((string)($record['num_computers'] ?? '')) ?: 'N/A' ?> |
                    ইন্টারনেট: <?= $ynLabel($record['has_internet'] ?? '') ?: 'N/A' ?>
                    <?php if (!empty($record['internet_type']) && $record['internet_type'] !== 'none'): ?>
                        (<?= e($record['internet_type']) ?>)
                    <?php endif; ?> |
                    প্রজেক্টর: <?= e((string)($record['num_projectors'] ?? '')) ?: 'N/A' ?>
                </div>
            </div>
            <div class="data-row">
                <div class="data-lbl">মাল্টিমিডিয়া শ্রেণিকক্ষ / বিদ্যমান সফটওয়্যার</div>
                <div class="data-val">
                    মাল্টিমিডিয়া: <?= $ynLabel($record['has_multimedia_classroom'] ?? '') ?: 'N/A' ?> |
                    সফটওয়্যার: <?= e((string)($record['existing_software'] ?? '')) ?: 'N/A' ?>
                </div>
            </div>
            <div class="data-row">
                <div class="data-lbl">ICT অভিজ্ঞ শিক্ষক আছেন?</div>
                <div class="data-val">
                    <?php $ictVal = (string) ($record['ict_teacher_available'] ?? ''); ?>
                    <?php if ($ictVal === 'yes'): ?>
                        <span style="color:#15803d; font-weight:600;">হ্যাঁ আছেন</span>
                    <?php elseif ($ictVal === 'no'): ?>
                        <span style="color:#b91c1c; font-weight:600;">না, নেই</span>
                    <?php else: ?>
                        <em style="color:var(--muted);">N/A</em>
                    <?php endif; ?>
                </div>
            </div>

            <h3 style="font-size:.95rem; color:var(--brand); margin:24px 0 12px; border-bottom:2px solid #ecfdf5; padding-bottom:6px;">ভিশন ও নোট</h3>

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
                    <?= !empty($record['notes'])
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

                <h3 style="font-size:.9rem; color:var(--brand); margin:20px 0 10px; border-bottom:1px solid #ecfdf5; padding-bottom:4px;">প্রতিষ্ঠানের মৌলিক তথ্য</h3>

                <div class="form-row">
                    <label class="fl" for="ed_type">প্রতিষ্ঠানের ধরন</label>
                    <select class="form-control" id="ed_type" name="institution_type" required>
                        <?php
                        $sel = (string) ($editOld['institution_type'] ?? $record['institution_type']);
                        foreach (institution_types() as $code => $label):
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
                    <label class="fl" for="ed_eiin">EIIN নম্বর</label>
                    <input class="form-control" id="ed_eiin" name="eiin_number"
                           value="<?= $v('eiin_number', '') ?>" placeholder="৪-৮ ডিজিট">
                </div>

                <div class="form-row">
                    <label class="fl" for="ed_mpo">MPO স্ট্যাটাস</label>
                    <select class="form-control" id="ed_mpo" name="mpo_status">
                        <?php $mpoCur = (string)($editOld['mpo_status'] ?? $record['mpo_status'] ?? ''); ?>
                        <option value="" <?= $mpoCur === '' ? 'selected' : '' ?>>-- নির্বাচন করুন --</option>
                        <option value="mpo" <?= $mpoCur === 'mpo' ? 'selected' : '' ?>>এমপিও ভুক্ত</option>
                        <option value="non_mpo" <?= $mpoCur === 'non_mpo' ? 'selected' : '' ?>>নন-এমপিও</option>
                        <option value="newly_nationalized" <?= $mpoCur === 'newly_nationalized' ? 'selected' : '' ?>>নবজাতীয়করণ</option>
                    </select>
                </div>

                <div class="form-row">
                    <label class="fl" for="ed_estyear">প্রতিষ্ঠার সাল</label>
                    <input class="form-control" id="ed_estyear" name="establishment_year" type="number" min="1800" max="<?= date('Y') ?>"
                           value="<?= $v('establishment_year', '') ?>">
                </div>

                <div class="form-row">
                    <label class="fl" for="ed_sub">Subdomain</label>
                    <input class="form-control" id="ed_sub" name="subdomain" required
                           pattern="[a-z0-9](?:[a-z0-9-]{1,30}[a-z0-9])"
                           autocapitalize="off" spellcheck="false"
                           value="<?= $v('subdomain', '') ?>">
                </div>

                <div class="form-row">
                    <label class="fl">প্রতিষ্ঠানের ফোন / ইমেইল / ওয়েবসাইট</label>
                    <div class="grid-2">
                        <input class="form-control" name="school_phone" placeholder="ফোন"
                               value="<?= $v('school_phone', '') ?>">
                        <input class="form-control" name="school_email" type="email" placeholder="ইমেইল"
                               value="<?= $v('school_email', '') ?>">
                    </div>
                    <input class="form-control" name="school_website" placeholder="ওয়েবসাইট" style="margin-top:8px;"
                           value="<?= $v('school_website', '') ?>">
                </div>

                <h3 style="font-size:.9rem; color:var(--brand); margin:20px 0 10px; border-bottom:1px solid #ecfdf5; padding-bottom:4px;">প্রধান শিক্ষক / যোগাযোগ</h3>

                <div class="form-row">
                    <label class="fl" for="ed_owner">প্রধান শিক্ষকের নাম</label>
                    <input class="form-control" id="ed_owner" name="owner_name" required
                           value="<?= $v('owner_name', '') ?>">
                </div>

                <div class="form-row">
                    <label class="fl" for="ed_phone">মোবাইল নম্বর</label>
                    <input class="form-control" id="ed_phone" name="owner_phone" required
                           value="<?= $v('owner_phone', '') ?>">
                </div>

                <div class="form-row">
                    <label class="fl" for="ed_whatsapp">WhatsApp নম্বর</label>
                    <input class="form-control" id="ed_whatsapp" name="head_teacher_whatsapp"
                           value="<?= $v('head_teacher_whatsapp', '') ?>">
                </div>

                <div class="form-row">
                    <label class="fl" for="ed_email">ইমেইল</label>
                    <input class="form-control" id="ed_email" type="email" name="owner_email" required
                           value="<?= $v('owner_email', '') ?>">
                </div>

                <div class="form-row">
                    <label class="fl">পরিচালনা কমিটির চেয়ারম্যান / ফোন</label>
                    <div class="grid-2">
                        <input class="form-control" name="managing_committee_chairman" placeholder="চেয়ারম্যান নাম"
                               value="<?= $v('managing_committee_chairman', '') ?>">
                        <input class="form-control" name="managing_committee_phone" placeholder="চেয়ারম্যান ফোন"
                               value="<?= $v('managing_committee_phone', '') ?>">
                    </div>
                </div>

                <h3 style="font-size:.9rem; color:var(--brand); margin:20px 0 10px; border-bottom:1px solid #ecfdf5; padding-bottom:4px;">ঠিকানা</h3>

                <div class="form-row">
                    <label class="fl">বিভাগ / জেলা / উপজেলা</label>
                    <div class="grid-2">
                        <input class="form-control" name="division" placeholder="বিভাগ"
                               value="<?= $v('division', '') ?>">
                        <input class="form-control" name="district" placeholder="জেলা"
                               value="<?= $v('district', '') ?>">
                    </div>
                    <input class="form-control" name="upazila" placeholder="উপজেলা" style="margin-top:8px;"
                           value="<?= $v('upazila', '') ?>">
                </div>

                <div class="form-row">
                    <label class="fl" for="ed_union">ইউনিয়ন</label>
                    <input class="form-control" id="ed_union" name="union_name" required
                           value="<?= $v('union_name', '') ?>">
                </div>

                <div class="form-row">
                    <label class="fl">ওয়ার্ড / গ্রাম / পোস্টাল কোড</label>
                    <div class="grid-2">
                        <input class="form-control" name="union_ward" placeholder="ওয়ার্ড"
                               value="<?= $v('union_ward', '') ?>">
                        <input class="form-control" name="village" placeholder="গ্রাম"
                               value="<?= $v('village', '') ?>">
                    </div>
                    <input class="form-control" name="postal_code" placeholder="পোস্টাল কোড" style="margin-top:8px;"
                           value="<?= $v('postal_code', '') ?>">
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

                <h3 style="font-size:.9rem; color:var(--brand); margin:20px 0 10px; border-bottom:1px solid #ecfdf5; padding-bottom:4px;">অবকাঠামো</h3>

                <div class="form-row">
                    <label class="fl">ভবন / শ্রেণিকক্ষ সংখ্যা</label>
                    <div class="grid-2">
                        <input class="form-control" type="number" min="0" max="100"
                               name="num_buildings" placeholder="ভবন"
                               value="<?= $v('num_buildings', '') ?>">
                        <input class="form-control" type="number" min="0" max="500"
                               name="num_classrooms" placeholder="শ্রেণিকক্ষ"
                               value="<?= $v('num_classrooms', '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <label class="fl">কম্পিউটার ল্যাব</label>
                    <select class="form-control" name="has_computer_lab">
                        <?php $hcl = (string)($editOld['has_computer_lab'] ?? $record['has_computer_lab'] ?? ''); ?>
                        <option value="" <?= $hcl === '' ? 'selected' : '' ?>>--</option>
                        <option value="yes" <?= $hcl === 'yes' ? 'selected' : '' ?>>হ্যাঁ</option>
                        <option value="no" <?= $hcl === 'no' ? 'selected' : '' ?>>না</option>
                    </select>
                </div>
                <div class="form-row">
                    <label class="fl">বিজ্ঞান গবেষণাগার</label>
                    <select class="form-control" name="has_science_lab">
                        <?php $hsl = (string)($editOld['has_science_lab'] ?? $record['has_science_lab'] ?? ''); ?>
                        <option value="" <?= $hsl === '' ? 'selected' : '' ?>>--</option>
                        <option value="yes" <?= $hsl === 'yes' ? 'selected' : '' ?>>হ্যাঁ</option>
                        <option value="no" <?= $hsl === 'no' ? 'selected' : '' ?>>না</option>
                    </select>
                </div>
                <div class="form-row">
                    <label class="fl">লাইব্রেরি</label>
                    <select class="form-control" name="has_library">
                        <?php $hlb = (string)($editOld['has_library'] ?? $record['has_library'] ?? ''); ?>
                        <option value="" <?= $hlb === '' ? 'selected' : '' ?>>--</option>
                        <option value="yes" <?= $hlb === 'yes' ? 'selected' : '' ?>>হ্যাঁ</option>
                        <option value="no" <?= $hlb === 'no' ? 'selected' : '' ?>>না</option>
                    </select>
                </div>
                <div class="form-row">
                    <label class="fl">খেলার মাঠ</label>
                    <select class="form-control" name="has_playground">
                        <?php $hpg = (string)($editOld['has_playground'] ?? $record['has_playground'] ?? ''); ?>
                        <option value="" <?= $hpg === '' ? 'selected' : '' ?>>--</option>
                        <option value="yes" <?= $hpg === 'yes' ? 'selected' : '' ?>>হ্যাঁ</option>
                        <option value="no" <?= $hpg === 'no' ? 'selected' : '' ?>>না</option>
                    </select>
                </div>

                <h3 style="font-size:.9rem; color:var(--brand); margin:20px 0 10px; border-bottom:1px solid #ecfdf5; padding-bottom:4px;">শিক্ষার্থী</h3>

                <div class="form-row">
                    <label class="fl">মোট শিক্ষার্থী / ছাত্র / ছাত্রী</label>
                    <div class="grid-2">
                        <input class="form-control" type="number" min="0" max="20000"
                               name="total_students" placeholder="মোট শিক্ষার্থী"
                               value="<?= $v('total_students', '') ?>">
                        <input class="form-control" type="number" min="0" max="20000"
                               name="total_students_boys" placeholder="ছাত্র"
                               value="<?= $v('total_students_boys', '') ?>">
                    </div>
                    <input class="form-control" type="number" min="0" max="20000"
                           name="total_students_girls" placeholder="ছাত্রী" style="margin-top:8px;"
                           value="<?= $v('total_students_girls', '') ?>">
                </div>

                <div class="form-row">
                    <label class="fl">শ্রেণি ভিত্তিক শিক্ষার্থী (ঐচ্ছিক)</label>
                    <div class="grid-2">
                        <?php for ($ci = 1; $ci <= 10; $ci++): ?>
                        <input class="form-control" type="number" min="0" max="5000"
                               name="students_class_<?= $ci ?>" placeholder="শ্রেণি <?= $ci ?>"
                               value="<?= $v('students_class_' . $ci, '') ?>">
                        <?php endfor; ?>
                    </div>
                </div>

                <h3 style="font-size:.9rem; color:var(--brand); margin:20px 0 10px; border-bottom:1px solid #ecfdf5; padding-bottom:4px;">শিক্ষক</h3>

                <div class="form-row">
                    <label class="fl">মোট শিক্ষক / পুরুষ / মহিলা</label>
                    <div class="grid-2">
                        <input class="form-control" type="number" min="0" max="2000"
                               name="total_teachers" placeholder="মোট শিক্ষক"
                               value="<?= $v('total_teachers', '') ?>">
                        <input class="form-control" type="number" min="0" max="1000"
                               name="male_teachers" placeholder="পুরুষ"
                               value="<?= $v('male_teachers', '') ?>">
                    </div>
                    <div class="grid-2" style="margin-top:8px;">
                        <input class="form-control" type="number" min="0" max="1000"
                               name="female_teachers" placeholder="মহিলা"
                               value="<?= $v('female_teachers', '') ?>">
                        <input class="form-control" type="number" min="0" max="2000"
                               name="trained_teachers" placeholder="প্রশিক্ষিত"
                               value="<?= $v('trained_teachers', '') ?>">
                    </div>
                    <input class="form-control" type="number" min="0" max="2000"
                           name="untrained_teachers" placeholder="অপ্রশিক্ষিত" style="margin-top:8px;"
                           value="<?= $v('untrained_teachers', '') ?>">
                </div>

                <div class="form-row">
                    <label class="fl">ICT শিক্ষক আছেন?</label>
                    <select class="form-control" name="ict_teacher_available">
                        <?php
                        $ictCur = (string) ($editOld['ict_teacher_available'] ?? $record['ict_teacher_available'] ?? '');
                        ?>
                        <option value=""    <?= $ictCur === ''    ? 'selected' : '' ?>>-- উল্লেখ নেই --</option>
                        <option value="yes" <?= $ictCur === 'yes' ? 'selected' : '' ?>>হ্যাঁ আছেন</option>
                        <option value="no"  <?= $ictCur === 'no'  ? 'selected' : '' ?>>না, নেই</option>
                    </select>
                </div>

                <h3 style="font-size:.9rem; color:var(--brand); margin:20px 0 10px; border-bottom:1px solid #ecfdf5; padding-bottom:4px;">প্রযুক্তি</h3>

                <div class="form-row">
                    <label class="fl">কম্পিউটার / প্রজেক্টর সংখ্যা</label>
                    <div class="grid-2">
                        <input class="form-control" type="number" min="0" max="1000"
                               name="num_computers" placeholder="কম্পিউটার"
                               value="<?= $v('num_computers', '') ?>">
                        <input class="form-control" type="number" min="0" max="100"
                               name="num_projectors" placeholder="প্রজেক্টর"
                               value="<?= $v('num_projectors', '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <label class="fl">ইন্টারনেট সংযোগ</label>
                    <select class="form-control" name="has_internet">
                        <?php $hiCur = (string)($editOld['has_internet'] ?? $record['has_internet'] ?? ''); ?>
                        <option value="" <?= $hiCur === '' ? 'selected' : '' ?>>--</option>
                        <option value="yes" <?= $hiCur === 'yes' ? 'selected' : '' ?>>হ্যাঁ</option>
                        <option value="no" <?= $hiCur === 'no' ? 'selected' : '' ?>>না</option>
                    </select>
                </div>

                <div class="form-row">
                    <label class="fl">ইন্টারনেটের ধরন</label>
                    <select class="form-control" name="internet_type">
                        <?php $itCur = (string)($editOld['internet_type'] ?? $record['internet_type'] ?? ''); ?>
                        <option value="" <?= $itCur === '' ? 'selected' : '' ?>>--</option>
                        <option value="broadband" <?= $itCur === 'broadband' ? 'selected' : '' ?>>Broadband</option>
                        <option value="mobile_data" <?= $itCur === 'mobile_data' ? 'selected' : '' ?>>Mobile Data</option>
                        <option value="fiber" <?= $itCur === 'fiber' ? 'selected' : '' ?>>Fiber</option>
                        <option value="none" <?= $itCur === 'none' ? 'selected' : '' ?>>None</option>
                    </select>
                </div>

                <div class="form-row">
                    <label class="fl">মাল্টিমিডিয়া শ্রেণিকক্ষ</label>
                    <select class="form-control" name="has_multimedia_classroom">
                        <?php $hmCur = (string)($editOld['has_multimedia_classroom'] ?? $record['has_multimedia_classroom'] ?? ''); ?>
                        <option value="" <?= $hmCur === '' ? 'selected' : '' ?>>--</option>
                        <option value="yes" <?= $hmCur === 'yes' ? 'selected' : '' ?>>হ্যাঁ</option>
                        <option value="no" <?= $hmCur === 'no' ? 'selected' : '' ?>>না</option>
                    </select>
                </div>

                <div class="form-row">
                    <label class="fl" for="ed_software">বিদ্যমান সফটওয়্যার</label>
                    <textarea class="form-control" id="ed_software" name="existing_software"
                              maxlength="2000" style="min-height:70px;"><?= $v('existing_software', '') ?></textarea>
                </div>

                <h3 style="font-size:.9rem; color:var(--brand); margin:20px 0 10px; border-bottom:1px solid #ecfdf5; padding-bottom:4px;">ভিশন ও নোট</h3>

                <div class="form-row">
                    <label class="fl" for="ed_reason">স্কুলকে স্মার্ট করতে চান কেন?</label>
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
