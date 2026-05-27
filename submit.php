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

// New fields
$eiin_number       = input('eiin_number');
$mpo_status        = input('mpo_status');
$establishment_year_raw = input('establishment_year');
$school_phone      = input('school_phone');
$school_email      = input('school_email');
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
    'school_phone'        => 20,
    'school_email'        => 254,
    'school_website'      => 200,
    'village'             => 200,
    'postal_code'         => 10,
    'existing_software'   => 2000,
    'managing_committee_chairman' => 150,
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
$validTypes = array_keys(institution_types());

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

// ---------------------------------------------------------------------
// Validation for NEW fields
// ---------------------------------------------------------------------

// MPO status - required
if (!in_array($mpo_status, ['mpo', 'non_mpo', 'newly_nationalized'], true)) {
    $errors[] = 'MPO স্ট্যাটাস নির্বাচন করা বাধ্যতামূলক।';
}

// Division, District, Upazila - required
if ($division === '') {
    $errors[] = 'বিভাগ নির্বাচন করা বাধ্যতামূলক।';
}
if ($district === '') {
    $errors[] = 'জেলা নির্বাচন করা বাধ্যতামূলক।';
}
if ($upazila === '') {
    $errors[] = 'উপজেলা নির্বাচন করা বাধ্যতামূলক।';
}

// has_internet - required
if (!in_array($has_internet, ['yes', 'no'], true)) {
    $errors[] = 'ইন্টারনেট সংযোগ আছে কিনা নির্বাচন করুন।';
}

// EIIN number - optional, if provided must be 4-8 digits
if ($eiin_number !== '') {
    if (!ctype_digit($eiin_number) || strlen($eiin_number) < 4 || strlen($eiin_number) > 8) {
        $errors[] = 'EIIN নম্বর ৪-৮ ডিজিটের হতে হবে।';
    }
}

// School email - optional, if provided must be valid
if ($school_email !== '' && !filter_var($school_email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'প্রতিষ্ঠানের ইমেইল সঠিক নয়।';
}

// Establishment year - optional numeric
$establishment_year = null;
if ($establishment_year_raw !== '') {
    if (!ctype_digit($establishment_year_raw) || strlen($establishment_year_raw) !== 4) {
        $errors[] = 'প্রতিষ্ঠার সাল ৪ ডিজিটের হতে হবে।';
    } elseif ((int) $establishment_year_raw < 1800 || (int) $establishment_year_raw > (int) date('Y')) {
        $errors[] = 'প্রতিষ্ঠার সাল সঠিক নয়।';
    } else {
        $establishment_year = (int) $establishment_year_raw;
    }
}

// Optional numeric fields with bounds
$num_buildings = null;
if ($num_buildings_raw !== '') {
    if (!ctype_digit($num_buildings_raw) || (int) $num_buildings_raw > 100) {
        $errors[] = 'ভবন সংখ্যা সঠিক নয়।';
    } else {
        $num_buildings = (int) $num_buildings_raw;
    }
}

$num_classrooms = null;
if ($num_classrooms_raw !== '') {
    if (!ctype_digit($num_classrooms_raw) || (int) $num_classrooms_raw > 500) {
        $errors[] = 'শ্রেণিকক্ষ সংখ্যা সঠিক নয়।';
    } else {
        $num_classrooms = (int) $num_classrooms_raw;
    }
}

$total_students_boys = null;
if ($total_students_boys_raw !== '') {
    if (!ctype_digit($total_students_boys_raw) || (int) $total_students_boys_raw > 20000) {
        $errors[] = 'ছাত্র সংখ্যা সঠিক নয়।';
    } else {
        $total_students_boys = (int) $total_students_boys_raw;
    }
}

$total_students_girls = null;
if ($total_students_girls_raw !== '') {
    if (!ctype_digit($total_students_girls_raw) || (int) $total_students_girls_raw > 20000) {
        $errors[] = 'ছাত্রী সংখ্যা সঠিক নয়।';
    } else {
        $total_students_girls = (int) $total_students_girls_raw;
    }
}

// Per-class student counts
$classFields = [];
for ($i = 1; $i <= 10; $i++) {
    $varName = 'students_class_' . $i;
    $rawName = $varName . '_raw';
    $$varName = null;
    if ($$rawName !== '') {
        if (!ctype_digit($$rawName) || (int) $$rawName > 5000) {
            $errors[] = "শ্রেণি $i এর শিক্ষার্থী সংখ্যা সঠিক নয়।";
        } else {
            $$varName = (int) $$rawName;
        }
    }
    $classFields[] = $varName;
}

$male_teachers = null;
if ($male_teachers_raw !== '') {
    if (!ctype_digit($male_teachers_raw) || (int) $male_teachers_raw > 1000) {
        $errors[] = 'পুরুষ শিক্ষক সংখ্যা সঠিক নয়।';
    } else {
        $male_teachers = (int) $male_teachers_raw;
    }
}

$female_teachers = null;
if ($female_teachers_raw !== '') {
    if (!ctype_digit($female_teachers_raw) || (int) $female_teachers_raw > 1000) {
        $errors[] = 'মহিলা শিক্ষক সংখ্যা সঠিক নয়।';
    } else {
        $female_teachers = (int) $female_teachers_raw;
    }
}

$trained_teachers = null;
if ($trained_teachers_raw !== '') {
    if (!ctype_digit($trained_teachers_raw) || (int) $trained_teachers_raw > 2000) {
        $errors[] = 'প্রশিক্ষিত শিক্ষক সংখ্যা সঠিক নয়।';
    } else {
        $trained_teachers = (int) $trained_teachers_raw;
    }
}

$untrained_teachers = null;
if ($untrained_teachers_raw !== '') {
    if (!ctype_digit($untrained_teachers_raw) || (int) $untrained_teachers_raw > 2000) {
        $errors[] = 'অপ্রশিক্ষিত শিক্ষক সংখ্যা সঠিক নয়।';
    } else {
        $untrained_teachers = (int) $untrained_teachers_raw;
    }
}

$num_computers = null;
if ($num_computers_raw !== '') {
    if (!ctype_digit($num_computers_raw) || (int) $num_computers_raw > 1000) {
        $errors[] = 'কম্পিউটার সংখ্যা সঠিক নয়।';
    } else {
        $num_computers = (int) $num_computers_raw;
    }
}

$num_projectors = null;
if ($num_projectors_raw !== '') {
    if (!ctype_digit($num_projectors_raw) || (int) $num_projectors_raw > 100) {
        $errors[] = 'প্রজেক্টর সংখ্যা সঠিক নয়।';
    } else {
        $num_projectors = (int) $num_projectors_raw;
    }
}

// Optional yes/no fields - sanitize silently
if ($has_computer_lab !== '' && !in_array($has_computer_lab, ['yes', 'no'], true)) {
    $has_computer_lab = '';
}
if ($has_science_lab !== '' && !in_array($has_science_lab, ['yes', 'no'], true)) {
    $has_science_lab = '';
}
if ($has_library !== '' && !in_array($has_library, ['yes', 'no'], true)) {
    $has_library = '';
}
if ($has_playground !== '' && !in_array($has_playground, ['yes', 'no'], true)) {
    $has_playground = '';
}
if ($has_multimedia_classroom !== '' && !in_array($has_multimedia_classroom, ['yes', 'no'], true)) {
    $has_multimedia_classroom = '';
}

// Internet type logic
if ($has_internet === 'no') {
    $internet_type = 'none';
} elseif ($has_internet === 'yes') {
    if (!in_array($internet_type, ['broadband', 'mobile_data', 'fiber'], true)) {
        $internet_type = '';
    }
} else {
    $internet_type = '';
}

// WhatsApp number - optional, try normalise_phone first, then check 10-15 digits
$head_teacher_whatsapp = '';
if ($head_teacher_whatsapp_raw !== '') {
    $normalised = normalise_phone($head_teacher_whatsapp_raw);
    if ($normalised !== null) {
        $head_teacher_whatsapp = $normalised;
    } else {
        $digits = preg_replace('/[^\d]/', '', $head_teacher_whatsapp_raw);
        if (strlen($digits) >= 10 && strlen($digits) <= 15) {
            $head_teacher_whatsapp = $head_teacher_whatsapp_raw;
        } else {
            $errors[] = 'WhatsApp নম্বর সঠিক নয়।';
        }
    }
}

// Managing committee phone - optional, validate with normalise_phone
$managing_committee_phone = '';
if ($managing_committee_phone_raw !== '') {
    $normalised = normalise_phone($managing_committee_phone_raw);
    if ($normalised !== null) {
        $managing_committee_phone = $normalised;
    } else {
        $errors[] = 'পরিচালনা কমিটির চেয়ারম্যান ফোন নম্বর সঠিক নয়।';
    }
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
             smart_school_reason,
             eiin_number, mpo_status, establishment_year, school_phone, school_email, school_website,
             division, district, upazila, union_ward, village, postal_code,
             num_buildings, num_classrooms, has_computer_lab, has_science_lab, has_library, has_playground,
             total_students_boys, total_students_girls,
             students_class_1, students_class_2, students_class_3, students_class_4, students_class_5,
             students_class_6, students_class_7, students_class_8, students_class_9, students_class_10,
             male_teachers, female_teachers, trained_teachers, untrained_teachers,
             num_computers, has_internet, internet_type, num_projectors, has_multimedia_classroom,
             existing_software, head_teacher_name, head_teacher_phone, head_teacher_whatsapp,
             head_teacher_email, managing_committee_chairman, managing_committee_phone)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $insert->execute([
        $institution_type,
        $school_name,
        $school_name_bn,
        $subdomain,
        $union_name,
        $detailed_address,
        $latitude ?: null,
        $longitude ?: null,
        $owner_name,
        $owner_phone,
        $validatedEmail,
        $notes,
        $total_students,
        $total_teachers,
        $ict_teacher_available,
        $smart_school_reason,
        $eiin_number ?: null,
        $mpo_status,
        $establishment_year,
        $school_phone ?: null,
        $school_email ?: null,
        $school_website ?: null,
        $division,
        $district,
        $upazila,
        $union_ward ?: null,
        $village ?: null,
        $postal_code ?: null,
        $num_buildings,
        $num_classrooms,
        $has_computer_lab ?: null,
        $has_science_lab ?: null,
        $has_library ?: null,
        $has_playground ?: null,
        $total_students_boys,
        $total_students_girls,
        $students_class_1,
        $students_class_2,
        $students_class_3,
        $students_class_4,
        $students_class_5,
        $students_class_6,
        $students_class_7,
        $students_class_8,
        $students_class_9,
        $students_class_10,
        $male_teachers,
        $female_teachers,
        $trained_teachers,
        $untrained_teachers,
        $num_computers,
        $has_internet,
        $internet_type ?: null,
        $num_projectors,
        $has_multimedia_classroom ?: null,
        $existing_software ?: null,
        $owner_name,
        $owner_phone,
        $head_teacher_whatsapp ?: null,
        $validatedEmail,
        $managing_committee_chairman ?: null,
        $managing_committee_phone ?: null,
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
    'school'           => $school_name,
    'subdomain'        => $subdomain . '.smartschool.bd',
    'owner_name'       => $owner_name,
    'institution_type' => institution_label($institution_type),
    'union_name'       => $union_name,
];
redirect('/success.php');
