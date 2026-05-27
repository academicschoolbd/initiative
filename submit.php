<?php
/**
 * Smart Maheshkhali — registration form processor.
 *
 * Validates everything server-side, enforces pilot quotas and subdomain
 * uniqueness, applies a per-IP cooldown, and logs every accepted
 * submission for audit. On any failure the user is sent back to the
 * form with all previously-entered values preserved.
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/index.php');
}

csrf_check();

// Backend re-check of the on/off toggle so that submissions can't slip
// through if an admin closes the form between page-load and submit.
$stmt = $db->prepare("SELECT value FROM settings WHERE key = 'form_enabled'");
$stmt->execute();
if ($stmt->fetchColumn() !== '1') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Submission rejected: registration is currently closed.";
    exit;
}

// Honeypot. Any value at all = bot. Pretend success without writing.
if (input('website_url') !== '') {
    error_log('[Smart Maheshkhali] Honeypot triggered from ' . client_ip());
    redirect('/success.php');
}

// Per-IP cooldown.
$cooldown = (int) ($CONFIG['submission_cooldown_seconds'] ?? 60);
if ($cooldown > 0) {
    $ip = client_ip();
    $check = $db->prepare(
        "SELECT created_at FROM submission_log
          WHERE ip_address = ?
          ORDER BY created_at DESC LIMIT 1"
    );
    $check->execute([$ip]);
    $lastAt = $check->fetchColumn();
    if ($lastAt) {
        $elapsed = time() - strtotime($lastAt . ' UTC');
        if ($elapsed >= 0 && $elapsed < $cooldown) {
            $_SESSION['form_errors'] = [
                'অনুগ্রহ করে কিছুক্ষণ পরে আবার চেষ্টা করুন। (Submissions are throttled per IP.)',
            ];
            $_SESSION['old_input'] = $_POST;
            redirect('/index.php');
        }
    }
}

// ---------------------------------------------------------------------
// Collect raw input (UTF-8 safe). No filter_input — we escape on output.
// ---------------------------------------------------------------------
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
$terms_accept     = !empty($_POST['terms_accept']);

// Length caps to prevent abusive payloads.
$caps = [
    'school_name'         => 200,
    'school_name_bn'      => 200,
    'union_name'          => 100,
    'detailed_address'    => 500,
    'owner_name'          => 150,
    'notes'               => 2000,
    'owner_email'         => 254,
    'smart_school_reason' => 2000,
];
foreach ($caps as $field => $max) {
    if (mb_strlen($$field) > $max) {
        $$field = mb_substr($$field, 0, $max);
    }
}

// ---------------------------------------------------------------------
// Validation
// ---------------------------------------------------------------------
$errors = [];
$validTypes = ['primary', 'madrasah', 'high_school'];

if (!in_array($institution_type, $validTypes, true)) {
    $errors[] = 'প্রতিষ্ঠানের ধরন নির্বাচন করা বাধ্যতামূলক।';
}

if ($school_name === '') {
    $errors[] = 'English school name is required.';
} elseif (mb_strlen($school_name) < 3) {
    $errors[] = 'প্রতিষ্ঠানের নাম খুব ছোট।';
}

if ($subdomain === '') {
    $errors[] = 'সাবডোমেন প্রদান করা বাধ্যতামূলক।';
} elseif (!is_valid_subdomain($subdomain)) {
    $errors[] = 'সাবডোমেন ৩-৩২ অক্ষরের, শুধুমাত্র lowercase letters, digits ও hyphen দিয়ে গঠিত হতে হবে।';
} elseif (is_reserved_subdomain($subdomain)) {
    $errors[] = 'এই সাবডোমেনটি সংরক্ষিত। ভিন্ন একটি বেছে নিন।';
}

if ($union_name === '') {
    $errors[] = 'ইউনিয়ন নির্বাচন করা বাধ্যতামূলক।';
}

if ($detailed_address === '') {
    $errors[] = 'বিস্তারিত ঠিকানা প্রদান করা আবশ্যক।';
}

// Headcounts: required, must parse as non-negative integers within sane bounds.
$total_students = null;
if ($total_students_raw === '' || !ctype_digit($total_students_raw)) {
    $errors[] = 'মোট শিক্ষার্থী সংখ্যা একটি বৈধ সংখ্যা হতে হবে।';
} elseif ((int) $total_students_raw > 20000) {
    $errors[] = 'শিক্ষার্থী সংখ্যা ২০,০০০ এর বেশি গ্রহণযোগ্য নয়। সঠিক সংখ্যা প্রদান করুন।';
} else {
    $total_students = (int) $total_students_raw;
}

$total_teachers = null;
if ($total_teachers_raw === '' || !ctype_digit($total_teachers_raw)) {
    $errors[] = 'মোট শিক্ষক সংখ্যা একটি বৈধ সংখ্যা হতে হবে।';
} elseif ((int) $total_teachers_raw > 2000) {
    $errors[] = 'শিক্ষক সংখ্যা ২,০০০ এর বেশি গ্রহণযোগ্য নয়। সঠিক সংখ্যা প্রদান করুন।';
} else {
    $total_teachers = (int) $total_teachers_raw;
}

// ICT-experienced teacher availability — strict yes/no enum.
if (!in_array($ict_teacher_available, ['yes', 'no'], true)) {
    $errors[] = 'ICT অভিজ্ঞ শিক্ষক আছেন কিনা জানাতে হ্যাঁ অথবা না নির্বাচন করুন।';
    $ict_teacher_available = '';
}

// Why-smart-school motivation paragraph.
if ($smart_school_reason === '') {
    $errors[] = 'স্কুলকে স্মার্ট করতে চান কেন — সেই কারণটি লিখুন।';
} elseif (mb_strlen($smart_school_reason) < 20) {
    $errors[] = 'স্মার্ট স্কুল সংক্রান্ত আপনার ব্যাখ্যা কমপক্ষে ২০ অক্ষরের হতে হবে।';
}

// Lat/lng are optional but if provided must parse as numbers in range.
if ($latitude !== '' && !preg_match('/^-?\d{1,3}(\.\d{1,12})?$/', $latitude)) {
    $errors[] = 'অক্ষাংশ (latitude) মান সঠিক নয়।';
    $latitude = '';
} elseif ($latitude !== '' && ((float) $latitude < -90 || (float) $latitude > 90)) {
    $errors[] = 'অক্ষাংশ -90 থেকে 90 এর মধ্যে হতে হবে।';
    $latitude = '';
}
if ($longitude !== '' && !preg_match('/^-?\d{1,3}(\.\d{1,12})?$/', $longitude)) {
    $errors[] = 'দ্রাঘিমাংশ (longitude) মান সঠিক নয়।';
    $longitude = '';
} elseif ($longitude !== '' && ((float) $longitude < -180 || (float) $longitude > 180)) {
    $errors[] = 'দ্রাঘিমাংশ -180 থেকে 180 এর মধ্যে হতে হবে।';
    $longitude = '';
}

if ($owner_name === '') {
    $errors[] = 'যোগাযোগকারী ব্যক্তির নাম প্রদান করা আবশ্যক।';
}

$owner_phone = $owner_phone_raw === '' ? null : normalise_phone($owner_phone_raw);
if ($owner_phone === null) {
    $errors[] = 'সঠিক ১১-ডিজিটের বাংলাদেশী মোবাইল নম্বর প্রদান করুন।';
}

$validatedEmail = filter_var($owner_email, FILTER_VALIDATE_EMAIL);
if (!$validatedEmail) {
    $errors[] = 'একটি বৈধ ইমেইল এড্রেস প্রদান করুন।';
}

if (!$terms_accept) {
    $errors[] = 'শর্তাবলীতে সম্মতি প্রদান করা বাধ্যতামূলক।';
}

// Quota and uniqueness checks only if everything else looks sane.
if (!$errors) {
    $quotas = (array) ($CONFIG['quotas'] ?? []);
    $limit  = (int) ($quotas[$institution_type] ?? 0);
    if ($limit > 0) {
        $countStmt = $db->prepare(
            'SELECT COUNT(*) FROM registrations WHERE institution_type = ?'
        );
        $countStmt->execute([$institution_type]);
        if ((int) $countStmt->fetchColumn() >= $limit) {
            $errors[] = sprintf(
                'এই ধরনের প্রতিষ্ঠানের জন্য পাইলট কোটা পূর্ণ হয়ে গেছে (%d/%d)।',
                $limit,
                $limit
            );
        }
    }

    $dupe = $db->prepare('SELECT id FROM registrations WHERE subdomain = ?');
    $dupe->execute([$subdomain]);
    if ($dupe->fetchColumn()) {
        $errors[] = 'এই সাবডোমেনটি ইতিমধ্যে নিবন্ধিত। অনুগ্রহ করে অন্য একটি বেছে নিন।';
    }
}

if ($errors) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['old_input']   = $_POST;
    redirect('/index.php');
}

// ---------------------------------------------------------------------
// Persist
// ---------------------------------------------------------------------
try {
    $db->beginTransaction();

    $insert = $db->prepare(
        'INSERT INTO registrations
            (institution_type, school_name, school_name_bn, subdomain,
             union_name, detailed_address, latitude, longitude,
             owner_name, owner_phone, owner_email, notes,
             total_students, total_teachers, ict_teacher_available,
             smart_school_reason)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $insert->execute([
        $institution_type,
        $school_name,
        $school_name_bn,
        $subdomain,
        $union_name,
        $detailed_address,
        $latitude,
        $longitude,
        $owner_name,
        $owner_phone,
        $validatedEmail,
        $notes,
        $total_students,
        $total_teachers,
        $ict_teacher_available,
        $smart_school_reason,
    ]);

    $log = $db->prepare('INSERT INTO submission_log (ip_address) VALUES (?)');
    $log->execute([client_ip()]);

    $db->commit();
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[Smart Maheshkhali] Insert failed: ' . $e->getMessage());

    // Race condition: someone grabbed the same subdomain between our
    // duplicate check and the INSERT. The unique index catches it.
    $msg = 'সিস্টেমে কারিগরি ত্রুটি হয়েছে। কিছুক্ষণ পর আবার চেষ্টা করুন।';
    if ($e->getCode() === '23000' || stripos($e->getMessage(), 'unique') !== false) {
        $msg = 'এই সাবডোমেনটি ইতিমধ্যে নিবন্ধিত। অনুগ্রহ করে অন্য একটি বেছে নিন।';
    }
    $_SESSION['form_errors'] = [$msg];
    $_SESSION['old_input']   = $_POST;
    redirect('/index.php');
}

$_SESSION['submission_success'] = [
    'school'    => $school_name,
    'subdomain' => $subdomain . '.smartschool.bd',
];
redirect('/success.php');
