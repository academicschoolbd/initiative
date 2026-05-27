<?php
session_start();
require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index');
    exit();
}

// ব্যাকএন্ড সিকিউরিটি চেক: এডমিন ফর্ম অফ রাখলে ডেটা সাবমিট হবে না
$stmt = $db->prepare("SELECT value FROM settings WHERE key = 'form_enabled'");
$stmt->execute();
if ($stmt->fetchColumn() !== '1') {
    die("Submission rejected: The registration portal is currently closed.");
}

$errors = [];

// ইনপুট স্যানিটাইজেশন এবং রিসিভ প্রসেস
$institution_type = filter_input(INPUT_POST, 'institution_type', FILTER_SANITIZE_SPECIAL_CHARS);
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
$terms_accept     = filter_input(INPUT_POST, 'terms_accept', FILTER_VALIDATE_INT);

// ভ্যালিডেশন রুলস চেক
if (empty($institution_type)) $errors[] = "প্রতিষ্ঠানের ধরন নির্বাচন করা বাধ্যতামূলক।";
if (empty($school_name)) $errors[] = "English School name is required.";
if (empty($subdomain) || !preg_match('/^[a-z0-9_-]{3,64}$/', $subdomain)) $errors[] = "সঠিক সাবডোমেন ফরম্যাট সাবমিট করুন।";
if (empty($union_name)) $errors[] = "ইউনিয়ন নির্বাচন করা বাধ্যতামূলক।";
if (empty($detailed_address)) $errors[] = "বিস্তারিত ঠিকানা প্রদান করা আবশ্যক।";
if (empty($owner_name)) $errors[] = "যোগাযোগকারী ব্যক্তির নাম প্রদান করা আবশ্যক।";
if (empty($owner_phone) || !preg_match('/^(?:\+88|88)?(01[3-9]\d{8})$/', $owner_phone)) $errors[] = "সঠিক ১১-ডিজিটের মোবাইল নম্বর প্রদান করুন।";
if (!$owner_email) $errors[] = "একটি বৈধ ইমেইল এড্রেস প্রদান করুন।";
if (!$terms_accept) $errors[] = "শর্তাবলীতে সম্মতি প্রদান করা বাধ্যতামূলক।";

// ভুল থাকলে ইনডেক্স ফর্মে ফেরত পাঠানো
if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['old_input'] = $_POST;
    header('Location: index');
    exit();
}

try {
    // পিএইচপি পিডিও (PDO) বাইন্ডিং গেটওয়ে
    $stmt = $db->prepare("INSERT INTO registrations (institution_type, school_name, school_name_bn, subdomain, union_name, detailed_address, latitude, longitude, owner_name, owner_phone, owner_email, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$institution_type, $school_name, $school_name_bn, $subdomain, $union_name, $detailed_address, $latitude, $longitude, $owner_name, $owner_phone, $owner_email, $notes]);
    
    $_SESSION['submission_success'] = [
        'school' => $school_name,
        'subdomain' => $subdomain . '.smartschool.bd'
    ];
    header('Location: success');
    exit();
} catch (PDOException $e) {
    $_SESSION['form_errors'] = ["সিস্টেম ক্র্যাশ ত্রুটি: " . $e->getMessage()];
    header('Location: index');
    exit();
}