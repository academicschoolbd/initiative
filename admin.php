<?php
/**
 * Smart Maheshkhali — admin dashboard.
 *
 * Authenticated. Provides:
 *  - Form on/off toggle (POST + CSRF)
 *  - Per-category quota counters
 *  - Searchable, filterable, paginated registrations table
 *  - CSV export (?export=csv preserves search & filter)
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

require_admin();

// ---------------------------------------------------------------------
// Toggle the form_enabled setting (POST + CSRF).
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_toggle'])) {
    csrf_check();
    $current = (string) ($_POST['current_state'] ?? '0');
    $next    = $current === '1' ? '0' : '1';
    $stmt = $db->prepare("UPDATE settings SET value = ? WHERE key = 'form_enabled'");
    $stmt->execute([$next]);
    redirect('/admin.php');
}

// ---------------------------------------------------------------------
// Search / filter inputs (GET).
// ---------------------------------------------------------------------
$q          = trim((string) ($_GET['q'] ?? ''));
$typeFilter = (string) ($_GET['type'] ?? '');
$validTypes = ['', 'primary', 'madrasah', 'high_school'];
if (!in_array($typeFilter, $validTypes, true)) {
    $typeFilter = '';
}

$where  = [];
$params = [];

if ($q !== '') {
    $where[] = '(school_name LIKE :q
                OR school_name_bn LIKE :q
                OR subdomain LIKE :q
                OR owner_name LIKE :q
                OR owner_phone LIKE :q
                OR owner_email LIKE :q
                OR union_name LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}
if ($typeFilter !== '') {
    $where[] = 'institution_type = :type';
    $params[':type'] = $typeFilter;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ---------------------------------------------------------------------
// CSV export — same WHERE clause, no pagination.
// ---------------------------------------------------------------------
if (($_GET['export'] ?? '') === 'csv') {
    $stmt = $db->prepare(
        "SELECT id, institution_type, school_name, school_name_bn, subdomain,
                union_name, detailed_address, latitude, longitude,
                owner_name, owner_phone, owner_email, notes, created_at
           FROM registrations $whereSql
       ORDER BY id DESC"
    );
    $stmt->execute($params);

    $filename = 'smartmaheshkhali_registrations_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $out = fopen('php://output', 'w');
    // UTF-8 BOM so Excel renders Bangla correctly out of the box.
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, [
        'ID', 'Type', 'School (EN)', 'School (BN)', 'Subdomain',
        'Union', 'Address', 'Latitude', 'Longitude',
        'Owner Name', 'Owner Phone', 'Owner Email', 'Notes', 'Created At',
    ], ',', '"', '');
    while ($row = $stmt->fetch()) {
        fputcsv($out, [
            $row['id'],
            $row['institution_type'],
            $row['school_name'],
            $row['school_name_bn'],
            $row['subdomain'] . '.smartschool.bd',
            $row['union_name'],
            $row['detailed_address'],
            $row['latitude'],
            $row['longitude'],
            $row['owner_name'],
            $row['owner_phone'],
            $row['owner_email'],
            $row['notes'],
            $row['created_at'],
        ], ',', '"', '');
    }
    fclose($out);
    exit;
}

// ---------------------------------------------------------------------
// Stats (always full DB-wide totals, not filtered).
// ---------------------------------------------------------------------
$total       = (int) $db->query("SELECT COUNT(*) FROM registrations")->fetchColumn();
$primaries   = (int) $db->query("SELECT COUNT(*) FROM registrations WHERE institution_type='primary'")->fetchColumn();
$madrasahs   = (int) $db->query("SELECT COUNT(*) FROM registrations WHERE institution_type='madrasah'")->fetchColumn();
$highSchools = (int) $db->query("SELECT COUNT(*) FROM registrations WHERE institution_type='high_school'")->fetchColumn();

$quotas = (array) ($CONFIG['quotas'] ?? []);

$formStmt = $db->prepare("SELECT value FROM settings WHERE key = 'form_enabled'");
$formStmt->execute();
$formEnabled = $formStmt->fetchColumn() === '1';

// ---------------------------------------------------------------------
// Pagination.
// ---------------------------------------------------------------------
$perPage = 20;
$page    = max(1, (int) ($_GET['page'] ?? 1));

$countStmt = $db->prepare("SELECT COUNT(*) FROM registrations $whereSql");
$countStmt->execute($params);
$filteredTotal = (int) $countStmt->fetchColumn();
$pageCount     = max(1, (int) ceil($filteredTotal / $perPage));
if ($page > $pageCount) {
    $page = $pageCount;
}
$offset = ($page - 1) * $perPage;

$listSql = "SELECT * FROM registrations $whereSql
             ORDER BY id DESC
             LIMIT :limit OFFSET :offset";
$listStmt = $db->prepare($listSql);
foreach ($params as $k => $v) {
    $listStmt->bindValue($k, $v);
}
$listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();
$records = $listStmt->fetchAll();

// Helper: rebuild current URL with overridden params.
$buildUrl = function (array $overrides) use ($q, $typeFilter, $page): string {
    $base = [
        'q'    => $q !== '' ? $q : null,
        'type' => $typeFilter !== '' ? $typeFilter : null,
        'page' => $page > 1 ? $page : null,
    ];
    $merged = array_merge($base, $overrides);
    $merged = array_filter($merged, fn ($v) => $v !== null && $v !== '');
    $qs = $merged ? '?' . http_build_query($merged) : '';
    return url('/admin.php') . $qs;
};
?>
<!doctype html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>সেন্ট্রাল এডমিন ড্যাশবোর্ড | <?= e($CONFIG['app_name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Tiro+Bangla&display=swap" rel="stylesheet">
    <style>
        :root { --background:#fafafa; --foreground:#09090b; --card:#fff; --border:#e4e4e7;
                --muted:#71717a; --brand:#059669; --radius:8px; }
        *, *::before, *::after { box-sizing:border-box; }
        body { margin:0; font-family:'Inter','Tiro Bangla',sans-serif; background:var(--background);
               color:var(--foreground); padding:32px 24px; -webkit-font-smoothing:antialiased; }
        a { color:inherit; }
        .admin-container { max-width:1280px; margin:0 auto; }

        .top-bar { display:flex; justify-content:space-between; align-items:center;
                   margin-bottom:32px; flex-wrap:wrap; gap:20px; }
        .top-bar h1 { font-size:1.55rem; font-weight:700; letter-spacing:-.03em; margin:0; }
        .top-bar .lead { color:var(--muted); font-size:.9rem; margin:4px 0 0; }

        .toggle-box { display:flex; align-items:center; gap:12px; background:#fff; padding:10px 16px;
                      border:1px solid var(--border); border-radius:var(--radius); }
        .dot { width:10px; height:10px; border-radius:50%; display:inline-block; }
        .dot.on  { background:var(--brand); box-shadow:0 0 8px var(--brand); }
        .dot.off { background:#dc2626;       box-shadow:0 0 8px #dc2626; }
        .toggle-trigger { background:var(--foreground); color:#fff; border:none; padding:6px 14px;
                          font-size:.82rem; font-weight:600; border-radius:4px; cursor:pointer;
                          font-family:inherit; }
        .toggle-trigger.off-style { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; }

        .logout-link { font-size:.82rem; color:var(--muted); text-decoration:underline;
                       margin-left:8px; }

        .metrics-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));
                        gap:16px; margin-bottom:24px; }
        .metric-card { background:var(--card); border:1px solid var(--border); padding:20px 24px;
                       border-radius:var(--radius); box-shadow:0 1px 3px rgba(0,0,0,.02); }
        .metric-card h3 { font-size:.78rem; font-weight:600; color:var(--muted);
                          text-transform:uppercase; letter-spacing:.04em; margin:0 0 6px; }
        .metric-card .count { font-size:1.7rem; font-weight:700; }
        .metric-card .quota { font-size:.85rem; color:var(--muted); font-weight:500; margin-left:6px; }

        .controls { display:flex; gap:12px; align-items:center; flex-wrap:wrap;
                    margin-bottom:16px; }
        .controls form { display:flex; gap:8px; flex-wrap:wrap; flex:1; min-width:280px; }
        .controls input, .controls select { padding:9px 12px; border:1px solid var(--border);
                                            border-radius:6px; font-size:.9rem; font-family:inherit; background:#fff; }
        .controls input { flex:1; min-width:200px; }
        .controls .btn { background:var(--foreground); color:#fff; border:none; padding:9px 16px;
                          border-radius:6px; font-size:.85rem; font-weight:600; cursor:pointer;
                          text-decoration:none; display:inline-flex; align-items:center; font-family:inherit; }
        .controls .btn-secondary { background:#fff; color:var(--foreground); border:1px solid var(--border); }
        .controls .btn-export { background:var(--brand); }
        .controls .btn-export:hover { background:#047857; }

        .table-wrapper { background:var(--card); border:1px solid var(--border); border-radius:var(--radius);
                         overflow-x:auto; box-shadow:0 4px 6px -1px rgba(0,0,0,.02); }
        table { width:100%; border-collapse:collapse; text-align:left; font-size:.9rem; min-width:980px; }
        th { background:#f4f4f5; padding:12px 18px; color:#18181b; font-weight:600; font-size:.82rem;
             border-bottom:1px solid var(--border); }
        td { padding:14px 18px; border-bottom:1px solid var(--border); color:#3f3f46; vertical-align:middle; }
        tbody tr:last-child td { border-bottom:0; }
        tbody tr:hover { background:#fafafa; }

        .badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:99px;
                 font-size:.76rem; font-weight:600; }
        .badge.primary     { background:#eff6ff; color:#1e40af; }
        .badge.madrasah    { background:#fef3c7; color:#92400e; }
        .badge.high_school { background:#ecfdf5; color:#065f46; }

        .actions-cell { display:flex; gap:6px; flex-wrap:nowrap; }
        .action-btn { padding:5px 11px; font-size:.78rem; font-weight:600; border-radius:4px;
                      text-decoration:none; border:1px solid var(--border); background:#fff;
                      cursor:pointer; color:var(--foreground); font-family:inherit; }
        .action-btn:hover { background:#f4f4f5; }
        .action-btn.edit-btn   { color:#2563eb; }
        .action-btn.delete-btn { color:#dc2626; border-color:#fecaca; }
        .delete-form { display:inline; margin:0; }

        .pager { display:flex; justify-content:space-between; align-items:center;
                 margin-top:16px; font-size:.85rem; color:var(--muted); flex-wrap:wrap; gap:12px; }
        .pager-links a, .pager-links span {
            display:inline-block; padding:6px 11px; margin-left:4px; border-radius:4px;
            background:#fff; border:1px solid var(--border); text-decoration:none;
            color:var(--foreground); font-weight:500;
        }
        .pager-links span.current { background:var(--foreground); color:#fff; border-color:var(--foreground); }
        .pager-links a:hover { background:#f4f4f5; }
        .empty { text-align:center; padding:48px 16px; color:var(--muted); }
    </style>
</head>
<body>

<div class="admin-container">
    <header class="top-bar">
        <div>
            <h1>স্মার্ট মহেশখালী এডমিন প্যানেল</h1>
            <p class="lead">পাইলট প্রোগ্রামের সকল আবেদন ডেটা ও সিস্টেম গেটওয়ে কন্ট্রোল করুন।</p>
        </div>

        <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
            <div class="toggle-box">
                <span class="dot <?= $formEnabled ? 'on' : 'off' ?>"></span>
                <span style="font-size:.9rem; font-weight:600;">
                    পাবলিক ফর্ম স্ট্যাটাস: <?= $formEnabled ? 'চালু আছে' : 'বন্ধ আছে' ?>
                </span>
                <form method="POST" action="<?= e(url('/admin.php')) ?>" style="display:inline; margin:0;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="current_state" value="<?= $formEnabled ? '1' : '0' ?>">
                    <button type="submit" name="action_toggle"
                            class="toggle-trigger <?= $formEnabled ? '' : 'off-style' ?>">
                        <?= $formEnabled ? 'বন্ধ করুন (Turn Off)' : 'চালু করুন (Turn On)' ?>
                    </button>
                </form>
            </div>
            <a class="logout-link" href="<?= e(url('/logout.php')) ?>">লগআউট</a>
        </div>
    </header>

    <?php $flash = (string) flash_pull('admin_flash', ''); if ($flash !== ''): ?>
        <div role="status"
             style="background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46;
                    padding:12px 16px; border-radius:8px; margin-bottom:24px;
                    font-size:.9rem; font-weight:500;">
            <?= e($flash) ?>
        </div>
    <?php endif; ?>

    <section class="metrics-grid">
        <div class="metric-card">
            <h3>মোট আবেদনপত্র</h3>
            <div class="count"><?= e((string) $total) ?></div>
        </div>
        <div class="metric-card">
            <h3>প্রাথমিক বিদ্যালয়</h3>
            <div class="count">
                <?= e((string) $primaries) ?><?php if (!empty($quotas['primary'])): ?>
                    <span class="quota">/ <?= e((string) $quotas['primary']) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="metric-card">
            <h3>মাদ্রাসা মডিউল</h3>
            <div class="count">
                <?= e((string) $madrasahs) ?><?php if (!empty($quotas['madrasah'])): ?>
                    <span class="quota">/ <?= e((string) $quotas['madrasah']) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="metric-card">
            <h3>মাধ্যমিক বিদ্যালয়</h3>
            <div class="count">
                <?= e((string) $highSchools) ?><?php if (!empty($quotas['high_school'])): ?>
                    <span class="quota">/ <?= e((string) $quotas['high_school']) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <div class="controls">
        <form method="GET" action="<?= e(url('/admin.php')) ?>">
            <input type="text" name="q" value="<?= e($q) ?>"
                   placeholder="স্কুল, সাবডোমেন, ফোন বা ইমেইল দিয়ে খুঁজুন..." aria-label="Search">
            <select name="type" aria-label="Filter by type">
                <option value="">সব ধরন</option>
                <option value="primary"     <?= $typeFilter === 'primary' ? 'selected' : '' ?>>প্রাথমিক</option>
                <option value="madrasah"    <?= $typeFilter === 'madrasah' ? 'selected' : '' ?>>মাদ্রাসা</option>
                <option value="high_school" <?= $typeFilter === 'high_school' ? 'selected' : '' ?>>মাধ্যমিক</option>
            </select>
            <button type="submit" class="btn">খুঁজুন</button>
            <?php if ($q !== '' || $typeFilter !== ''): ?>
                <a href="<?= e(url('/admin.php')) ?>" class="btn btn-secondary">রিসেট</a>
            <?php endif; ?>
        </form>
        <a href="<?= e($buildUrl(['export' => 'csv', 'page' => null])) ?>" class="btn btn-export">
            CSV এক্সপোর্ট
        </a>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>শিক্ষা প্রতিষ্ঠানের নাম</th>
                    <th>ধরন</th>
                    <th>ইউনিয়ন</th>
                    <th>সাবডোমেন</th>
                    <th>মোবাইল</th>
                    <th>জমাদানের তারিখ</th>
                    <th>অ্যাকশন</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$records): ?>
                    <tr><td colspan="7" class="empty">
                        <?= ($q !== '' || $typeFilter !== '')
                            ? 'এই অনুসন্ধানের সাথে কোনো রেকর্ড মিলেনি।'
                            : 'এখনো কোনো প্রতিষ্ঠান তথ্য জমা দেয়নি।' ?>
                    </td></tr>
                <?php endif; ?>
                <?php foreach ($records as $r): ?>
                <tr>
                    <td>
                        <strong style="display:block; color:var(--foreground);"><?= e($r['school_name']) ?></strong>
                        <?php if ($r['school_name_bn']): ?>
                            <span style="font-size:.82rem; color:var(--muted);"><?= e($r['school_name_bn']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge <?= e($r['institution_type']) ?>"><?= e(institution_label($r['institution_type'])) ?></span></td>
                    <td><?= e($r['union_name']) ?></td>
                    <td><span style="font-family:monospace; color:var(--brand); font-weight:600; font-size:.85rem;">
                        <?= e($r['subdomain']) ?>.smartschool.bd
                    </span></td>
                    <td><?= e($r['owner_phone']) ?></td>
                    <td style="font-size:.82rem; color:var(--muted); white-space:nowrap;">
                        <?= e(date('Y-m-d H:i', strtotime((string) $r['created_at']))) ?>
                    </td>
                    <td class="actions-cell">
                        <a href="<?= e(url('/admin-action.php?action=view&id=' . (int) $r['id'])) ?>" class="action-btn">দেখুন</a>
                        <a href="<?= e(url('/admin-action.php?action=edit&id=' . (int) $r['id'])) ?>" class="action-btn edit-btn">এডিট</a>
                        <form method="POST" action="<?= e(url('/admin-action.php')) ?>" class="delete-form"
                              onsubmit="return confirm('আপনি কি নিশ্চিতভাবে এই রেকর্ডটি মুছে ফেলতে চান?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                            <button type="submit" class="action-btn delete-btn">ডিলিট</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($filteredTotal > 0): ?>
        <div class="pager">
            <span>
                মোট <?= e((string) $filteredTotal) ?> টি রেকর্ড;
                পৃষ্ঠা <?= e((string) $page) ?> / <?= e((string) $pageCount) ?>
            </span>
            <?php if ($pageCount > 1): ?>
                <div class="pager-links">
                    <?php if ($page > 1): ?>
                        <a href="<?= e($buildUrl(['page' => $page - 1])) ?>">&larr; পূর্ববর্তী</a>
                    <?php endif; ?>
                    <?php
                    $start = max(1, $page - 2);
                    $end   = min($pageCount, $page + 2);
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                        <?php if ($i === $page): ?>
                            <span class="current"><?= e((string) $i) ?></span>
                        <?php else: ?>
                            <a href="<?= e($buildUrl(['page' => $i])) ?>"><?= e((string) $i) ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($page < $pageCount): ?>
                        <a href="<?= e($buildUrl(['page' => $page + 1])) ?>">পরবর্তী &rarr;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
