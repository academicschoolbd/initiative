<?php
try {
    $db = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // প্রধান রেজিস্ট্রেশন টেবিল
    $db->exec("CREATE TABLE IF NOT EXISTS registrations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        institution_type TEXT,
        school_name TEXT,
        school_name_bn TEXT,
        subdomain TEXT,
        union_name TEXT,
        detailed_address TEXT,
        latitude TEXT,
        longitude TEXT,
        owner_name TEXT,
        owner_phone TEXT,
        owner_email TEXT,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // এডমিন সেটিংস টেবিল
    $db->exec("CREATE TABLE IF NOT EXISTS settings (
        key TEXT PRIMARY KEY,
        value TEXT
    )");

    // ডিফল্টভাবে ফর্ম ওপেন বা অন (1) রাখা হলো
    $db->exec("INSERT OR IGNORE INTO settings (key, value) VALUES ('form_enabled', '1')");

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}