<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$pdo = db();

$search   = trim((string)($_GET['q'] ?? ''));
$bloodG   = trim((string)($_GET['blood_group'] ?? ''));
$donors   = [];
$donorErr = false;

// Donor search - donors live in per-tenant tables (<subdomain>_donors).
// We search across active tenants and attach the owning blood bank.
if ($search !== '' || $bloodG !== '') {
    try {
        $tables = $pdo->query(
            "SELECT table_name FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name LIKE '%\_donors'"
        )->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $sub = substr((string)$table, 0, -7);
            if (!preg_match('/^[a-z0-9][a-z0-9-]{0,61}[a-z0-9]$/', $sub)) {
                continue;
            }
            $cols = $pdo->prepare(
                'SELECT COLUMN_NAME FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = ?'
            );
            $cols->execute([$table]);
            $colNames = $cols->fetchAll(PDO::FETCH_COLUMN);

            $nameCol = in_array('name', $colNames, true) ? 'name' : (in_array('donor_name', $colNames, true) ? 'donor_name' : null);
            $bgCol   = in_array('blood_group', $colNames, true) ? 'blood_group' : (in_array('group_name', $colNames, true) ? 'group_name' : null);
            $phCol   = in_array('phone', $colNames, true) ? 'phone' : null;
            if (!$nameCol || !$bgCol || !$phCol) {
                continue;
            }

            $sql = "SELECT bb.org_name, bb.admin_phone AS bank_phone,
                           t.`{$nameCol}` AS donor_name, t.`{$bgCol}` AS blood_group, t.`{$phCol}` AS phone
                    FROM `{$table}` t
                    JOIN blood_banks bb ON bb.subdomain = ? AND bb.is_active = 1
                    WHERE 1 = 1";
            $params = [$sub];
            if ($bloodG !== '') {
                $sql .= " AND t.`{$bgCol}` = ?";
                $params[] = $bloodG;
            }
            if ($search !== '') {
                $sql .= " AND (t.`{$nameCol}` LIKE ? OR t.`{$phCol}` LIKE ?)";
                $params[] = '%' . $search . '%';
                $params[] = '%' . $search . '%';
            }
            $sql .= ' LIMIT 50';
            $st = $pdo->prepare($sql);
            $st->execute($params);
            foreach ($st->fetchAll() as $row) {
                $donors[] = $row;
            }
        }
    } catch (Throwable $e) {
        error_log('donor search failed: ' . $e->getMessage());
        $donorErr = true;
    }
}

$banks = $pdo->query(
    'SELECT org_name, subdomain, admin_name, admin_phone, admin_email,
            subscription_status, donor_count, volunteer_count
     FROM blood_banks WHERE is_active = 1 ORDER BY donor_count DESC LIMIT 20'
)->fetchAll();

$submitted = false;
$regError  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['do'] ?? '') === 'register_request') {
    $org   = trim((string)($_POST['org_name'] ?? ''));
    $name  = trim((string)($_POST['contact_name'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $city  = trim((string)($_POST['city'] ?? ''));

    if ($org === '' || $name === '' || $email === '' || $phone === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $regError = 'Please fill in all required fields with a valid email address.';
    } else {
        try {
            $st = $pdo->prepare(
                'INSERT INTO registration_requests (org_name, contact_name, email, phone, city)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $st->execute([$org, $name, $email, $phone, $city !== '' ? $city : null]);
            audit_log('tenant_admin', null, $org, 'submitted self-registration request from landing page');
            $submitted = true;
        } catch (Throwable $e) {
            error_log('registration request failed: ' . $e->getMessage());
            $regError = 'We could not save your request just now. Please try again shortly.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>BloodBank SaaS - Find Donors, Run Your Blood Bank</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; color: #111827; line-height: 1.5; }
    header {
        background: linear-gradient(135deg, #7f1d1d, #b91c1c); color: #fff;
        padding: 72px 20px 96px; text-align: center;
    }
    header h1 { font-size: clamp(1.8rem, 4vw, 3rem); font-weight: 800; max-width: 760px; margin: 0 auto 14px; }
    header p { color: #fecaca; max-width: 560px; margin: 0 auto; font-size: 1.05rem; }
    .badge-free { display: inline-block; background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.3); border-radius: 999px; padding: 4px 14px; font-size: .8rem; margin-bottom: 18px; letter-spacing: .5px; }
    main { max-width: 1080px; margin: -56px auto 0; padding: 0 20px 64px; }
    section { background: #fff; border-radius: 16px; box-shadow: 0 8px 30px rgba(0,0,0,.08); padding: 30px; margin-bottom: 28px; }
    h2 { font-size: 1.3rem; margin-bottom: 6px; color: #7f1d1d; }
    .sub { color: #6b7280; font-size: .92rem; margin-bottom: 18px; }
    form.search { display: flex; flex-wrap: wrap; gap: 10px; }
    input, select {
        padding: 11px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: .95rem; flex: 1; min-width: 160px;
    }
    input:focus, select:focus { outline: none; border-color: #b91c1c; box-shadow: 0 0 0 3px rgba(185,28,28,.12); }
    button {
        padding: 11px 22px; background: #b91c1c; color: #fff; border: 0; border-radius: 8px;
        font-size: .95rem; font-weight: 600; cursor: pointer; transition: background .15s;
    }
    button:hover { background: #991b1b; }
    table { width: 100%; border-collapse: collapse; font-size: .9rem; }
    th { text-align: left; color: #6b7280; font-weight: 600; padding: 9px 8px; border-bottom: 1px solid #e5e7eb; }
    td { padding: 10px 8px; border-bottom: 1px solid #f3f4f6; }
    .pill { display: inline-block; padding: 2px 10px; border-radius: 999px; background: #fee2e2; color: #b91c1c; font-weight: 700; font-size: .78rem; }
    .grid-banks { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }
    .bank { border: 1px solid #f3f4f6; border-radius: 12px; padding: 18px; transition: box-shadow .15s, transform .15s; }
    .bank:hover { box-shadow: 0 6px 20px rgba(0,0,0,.1); transform: translateY(-2px); }
    .bank h3 { font-size: 1rem; margin-bottom: 4px; }
    .bank p { color: #6b7280; font-size: .85rem; margin: 2px 0; }
    .bank .counts { margin-top: 10px; font-size: .8rem; color: #374151; }
    .counts strong { color: #b91c1c; }
    .two-col { display: grid; grid-template-columns: 1.4fr 1fr; gap: 30px; align-items: start; }
    @media (max-width: 800px) { .two-col { grid-template-columns: 1fr; } }
    label { display: block; font-size: .83rem; font-weight: 600; color: #374151; margin: 12px 0 5px; }
    .form input, .form select { width: 100%; flex: unset; }
    .success { background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 14px 16px; border-radius: 10px; font-size: .92rem; margin-bottom: 14px; }
    .error-box { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; padding: 12px 14px; border-radius: 10px; font-size: .9rem; margin-bottom: 14px; }
    .muted { color: #6b7280; font-size: .9rem; }
    footer { text-align: center; color: #9ca3af; font-size: .85rem; padding: 24px; }
</style>
</head>
<body>

<header>
    <span class="badge-free">MULTI-TENANT BLOOD BANK PLATFORM</span>
    <h1>Find blood donors. Run your blood bank. All in one place.</h1>
    <p>Search active donors, contact registered blood banks, or register your own blood bank for free.</p>
</header>

<main>
    <section>
        <h2>Search for blood donors</h2>
        <p class="sub">Search by name or phone across all active blood banks, and filter by blood group.</p>
        <form class="search" method="get" action="index.php#donors">
            <input type="text" name="q" placeholder="Donor name or phone" value="<?= bb_e($search) ?>">
            <select name="blood_group">
                <option value="">Any blood group</option>
                <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?>
                    <option value="<?= $bg ?>" <?= $bloodG === $bg ? 'selected' : '' ?>><?= $bg ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Search</button>
        </form>

        <?php if ($donorErr): ?>
            <p class="error-box" style="margin-top:16px;">Donor search is temporarily unavailable. Please try again shortly.</p>
        <?php elseif ($search !== '' || $bloodG !== ''): ?>
            <?php if (!$donors): ?>
                <p class="muted" style="margin-top:16px;">No donors matched your search. Try a different name, phone or blood group.</p>
            <?php else: ?>
                <div style="overflow-x:auto; margin-top:18px;">
                    <table>
                        <thead><tr><th>Donor</th><th>Blood group</th><th>Phone</th><th>Registered at</th></tr></thead>
                        <tbody>
                        <?php foreach ($donors as $d): ?>
                            <tr>
                                <td><strong><?= bb_e($d['donor_name']) ?></strong></td>
                                <td><span class="pill"><?= bb_e($d['blood_group']) ?></span></td>
                                <td><?= bb_e($d['phone']) ?></td>
                                <td><?= bb_e($d['org_name']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="muted" style="margin-top:10px;">Please call before visiting. Donor availability may vary.</p>
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <section>
        <h2>Active blood banks</h2>
        <p class="sub">Contact info for every active organization on the platform.</p>
        <div class="grid-banks">
            <?php foreach ($banks as $b): ?>
                <div class="bank">
                    <h3><?= bb_e($b['org_name']) ?></h3>
                    <p>Admin: <?= bb_e($b['admin_name']) ?></p>
                    <p>Phone: <?= bb_e($b['admin_phone']) ?></p>
                    <p>Email: <?= bb_e($b['admin_email']) ?></p>
                    <p class="counts">
                        <strong><?= number_format((int)$b['donor_count']) ?></strong> donors &middot;
                        <strong><?= number_format((int)$b['volunteer_count']) ?></strong> volunteers &middot;
                        <?= bb_e(ucfirst($b['subscription_status'])) ?> plan
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section>
        <div class="two-col">
            <div>
                <h2>Register your blood bank - free</h2>
                <p class="sub">Submit your organization for review. Our team activates new accounts after a quick check.</p>
                <ul style="color:#374151;font-size:.92rem;margin:0 0 8px 20px;">
                    <li>Free forever plan with up to 100 donors and 5 volunteers</li>
                    <li>Manage donors, volunteers and requests from your own portal</li>
                    <li>Optional SMS notifications with a prepaid wallet</li>
                </ul>
                <p class="muted">Already have an account? Sign in on your blood bank's own subdomain.</p>
            </div>
            <div class="form">
                <?php if ($submitted): ?>
                    <div class="success">
                        Thank you! Your request for <strong><?= bb_e($_POST['org_name'] ?? '') ?></strong> has been
                        submitted. Our team will review it and contact you soon.
                    </div>
                <?php else: ?>
                    <?php if ($regError): ?><div class="error-box"><?= bb_e($regError) ?></div><?php endif; ?>
                    <form method="post" action="index.php#register">
                        <input type="hidden" name="do" value="register_request">
                        <label for="org_name">Organization name *</label>
                        <input id="org_name" name="org_name" type="text" required maxlength="190">
                        <label for="contact_name">Contact person *</label>
                        <input id="contact_name" name="contact_name" type="text" required maxlength="150">
                        <label for="email">Email *</label>
                        <input id="email" name="email" type="email" required maxlength="190">
                        <label for="phone">Phone *</label>
                        <input id="phone" name="phone" type="tel" required maxlength="25">
                        <label for="city">City</label>
                        <input id="city" name="city" type="text" maxlength="120">
                        <button type="submit" style="width:100%;margin-top:16px;">Submit request</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<footer>BloodBank SaaS &middot; Multi-tenant blood bank management</footer>
</body>
</html>
