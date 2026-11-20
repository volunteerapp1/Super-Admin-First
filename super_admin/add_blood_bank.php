<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

$admin = bb_require_super_admin();
$pdo = db();

$packages = $pdo->query('SELECT * FROM package_limits ORDER BY FIELD(package_type, "free","trial","pro")')->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!bb_csrf_check($_POST['csrf'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $org     = trim((string)($_POST['org_name'] ?? ''));
        $sub     = strtolower(trim((string)($_POST['subdomain'] ?? '')));
        $name    = trim((string)($_POST['admin_name'] ?? ''));
        $email   = strtolower(trim((string)($_POST['admin_email'] ?? '')));
        $phone   = trim((string)($_POST['admin_phone'] ?? ''));
        $pass    = (string)($_POST['admin_password'] ?? '');
        $package = (string)($_POST['package_type'] ?? 'free');

        if ($org === '' || $sub === '' || $name === '' || $email === '' || $phone === '' || strlen($pass) < 8) {
            $error = 'All fields are required, and the password must be at least 8 characters.';
        } elseif (!preg_match('/^[a-z0-9](?:[a-z0-9-]{1,61}[a-z0-9])?$/', $sub)) {
            $error = 'Subdomain may only contain lowercase letters, numbers and hyphens.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid admin email address.';
        } elseif (!in_array($package, ['free', 'trial', 'pro'], true)) {
            $error = 'Please choose a valid package.';
        } else {
            try {
                $st = $pdo->prepare(
                    'INSERT INTO blood_banks
                        (org_name, subdomain, admin_name, admin_email, admin_phone,
                         admin_password, package_type, subscription_status, is_active)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)'
                );
                $st->execute([
                    $org, $sub, $name, $email, $phone,
                    password_hash($pass, PASSWORD_DEFAULT), $package, $package,
                ]);
                $newId = (int)$pdo->lastInsertId();
                audit_log('super_admin', (int)$admin['id'], $admin['username'], "provisioned tenant #{$newId} ({$org}, package {$package})");
                bb_sync_tenant_metrics($newId);
                bb_redirect('tenants.php?msg=' . urlencode("Blood bank \"{$org}\" provisioned successfully."));
            } catch (PDOException $e) {
                if ((int)$e->getCode() === 23000) {
                    $error = 'That subdomain or admin email is already taken.';
                } else {
                    error_log('add_blood_bank failed: ' . $e->getMessage());
                    $error = 'Something went wrong while saving. Please try again.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Add Blood Bank - Super Admin</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background: #f3f4f6; color: #111827; }
    .topbar { background: #7f1d1d; color: #fff; padding: 14px 28px; display: flex; justify-content: space-between; align-items: center; }
    .topbar a { color: #fecaca; text-decoration: none; font-size: .9rem; }
    .wrap { max-width: 640px; margin: 32px auto; padding: 0 20px; }
    h1 { font-size: 1.4rem; margin-bottom: 20px; }
    .card { background: #fff; border-radius: 12px; padding: 28px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
    label { display: block; font-size: .85rem; font-weight: 600; color: #374151; margin: 14px 0 6px; }
    input, select {
        width: 100%; padding: 10px 13px; border: 1px solid #d1d5db; border-radius: 8px; font-size: .95rem;
    }
    input:focus, select:focus { outline: none; border-color: #b91c1c; box-shadow: 0 0 0 3px rgba(185,28,28,.12); }
    .row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .hint { color: #6b7280; font-size: .78rem; margin-top: 4px; }
    .btn { width: 100%; margin-top: 24px; padding: 12px; background: #b91c1c; color: #fff; border: 0; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; }
    .btn:hover { background: #991b1b; }
    .error { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; padding: 10px 14px; border-radius: 8px; font-size: .88rem; margin-bottom: 8px; }
    a.back { color: #b91c1c; font-size: .9rem; text-decoration: none; display: inline-block; margin-top: 16px; }
</style>
</head>
<body>
<div class="topbar">
    <strong>BloodBank SaaS &middot; Super Admin</strong>
    <div><a href="tenants.php">Back to tenants</a></div>
</div>

<div class="wrap">
    <h1>Provision new blood bank</h1>
    <div class="card">
        <?php if ($error): ?><div class="error"><?= bb_e($error) ?></div><?php endif; ?>
        <form method="post" autocomplete="off">
            <input type="hidden" name="csrf" value="<?= bb_e(bb_csrf_token()) ?>">

            <label for="org_name">Organization name</label>
            <input id="org_name" name="org_name" type="text" required maxlength="190" value="<?= bb_e($_POST['org_name'] ?? '') ?>">

            <label for="subdomain">Subdomain</label>
            <input id="subdomain" name="subdomain" type="text" required maxlength="100"
                   pattern="[a-z0-9](?:[a-z0-9-]{1,61}[a-z0-9])?" value="<?= bb_e($_POST['subdomain'] ?? '') ?>">
            <div class="hint">Lowercase letters, numbers and hyphens only, e.g. <code>city-central</code>.</div>

            <div class="row">
                <div>
                    <label for="admin_name">Admin name</label>
                    <input id="admin_name" name="admin_name" type="text" required maxlength="150" value="<?= bb_e($_POST['admin_name'] ?? '') ?>">
                </div>
                <div>
                    <label for="admin_phone">Admin phone</label>
                    <input id="admin_phone" name="admin_phone" type="tel" required maxlength="25" value="<?= bb_e($_POST['admin_phone'] ?? '') ?>">
                </div>
            </div>

            <label for="admin_email">Admin email</label>
            <input id="admin_email" name="admin_email" type="email" required maxlength="190" value="<?= bb_e($_POST['admin_email'] ?? '') ?>">

            <label for="admin_password">Admin password</label>
            <input id="admin_password" name="admin_password" type="password" required minlength="8" autocomplete="new-password">
            <div class="hint">At least 8 characters. Stored as a secure hash.</div>

            <label for="package_type">Package</label>
            <select id="package_type" name="package_type">
                <?php foreach ($packages as $p): ?>
                    <option value="<?= bb_e($p['package_type']) ?>">
                        <?= ucfirst(bb_e($p['package_type'])) ?>
                        (<?= $p['max_donors'] === null ? 'unlimited' : $p['max_donors'] ?> donors /
                        <?= $p['max_volunteers'] === null ? 'unlimited' : $p['max_volunteers'] ?> volunteers)
                    </option>
                <?php endforeach; ?>
            </select>

            <button class="btn" type="submit">Provision blood bank</button>
        </form>
    </div>
    <a class="back" href="tenants.php">&larr; Back to tenant list</a>
</div>
</body>
</html>
