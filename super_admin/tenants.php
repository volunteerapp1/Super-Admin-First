<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

$admin = bb_require_super_admin();
$pdo = db();

$tenants = $pdo->query(
    'SELECT id, org_name, subdomain, admin_name, admin_email, admin_phone,
            subscription_status, is_active, sms_enabled, sms_api_key, sms_sender_id,
            sms_unicode, sms_balance, donor_count, volunteer_count, created_at
     FROM blood_banks ORDER BY created_at DESC'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tenants - Super Admin</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background: #f3f4f6; color: #111827; }
    .topbar { background: #7f1d1d; color: #fff; padding: 14px 28px; display: flex; justify-content: space-between; align-items: center; }
    .topbar a { color: #fecaca; text-decoration: none; font-size: .9rem; }
    .topbar a:hover { color: #fff; }
    .wrap { max-width: 1200px; margin: 28px auto; padding: 0 20px; }
    h1 { font-size: 1.4rem; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
    .btn { display: inline-block; background: #b91c1c; color: #fff; padding: 9px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: .9rem; }
    .btn:hover { background: #991b1b; }
    .btn.ghost { background: #fff; color: #b91c1c; border: 1px solid #fca5a5; }
    .card { background: #fff; border-radius: 12px; padding: 6px; box-shadow: 0 1px 3px rgba(0,0,0,.08); overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; font-size: .88rem; min-width: 900px; }
    th { text-align: left; color: #6b7280; font-weight: 600; padding: 10px; border-bottom: 1px solid #e5e7eb; white-space: nowrap; }
    td { padding: 10px; border-bottom: 1px solid #f3f4f6; white-space: nowrap; }
    .badge { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: .72rem; font-weight: 700; }
    .badge.on  { background: #dcfce7; color: #166534; }
    .badge.off { background: #fee2e2; color: #991b1b; }
    .badge.pill { background: #e5e7eb; color: #374151; }
    .balance { font-weight: 700; }
    .balance.low { color: #b45309; }
    .balance.empty { color: #991b1b; }
    .dialog::backdrop { background: rgba(0,0,0,.5); }
    .dialog { border: 0; border-radius: 14px; padding: 0; width: min(440px, 92vw); }
    .dialog .body { padding: 24px; }
    .dialog h2 { font-size: 1.1rem; margin-bottom: 4px; }
    .dialog .sub { color: #6b7280; font-size: .85rem; margin-bottom: 16px; }
    label { display: block; font-size: .82rem; font-weight: 600; color: #374151; margin: 12px 0 5px; }
    input, select {
        width: 100%; padding: 9px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: .95rem;
    }
    .toggle-row { display: flex; align-items: center; justify-content: space-between; margin-top: 14px; }
    .switch { position: relative; width: 44px; height: 24px; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; inset: 0; background: #d1d5db; border-radius: 999px; transition: background .2s; cursor: pointer; }
    .slider::before { content: ""; position: absolute; width: 18px; height: 18px; left: 3px; top: 3px; background: #fff; border-radius: 50%; transition: transform .2s; }
    .switch input:checked + .slider { background: #16a34a; }
    .switch input:checked + .slider::before { transform: translateX(20px); }
    .actions { display: flex; gap: 10px; margin-top: 20px; }
    .actions button, .actions a { flex: 1; padding: 10px; border-radius: 8px; font-weight: 600; font-size: .9rem; text-align: center; cursor: pointer; text-decoration: none; }
    .actions .save { background: #b91c1c; color: #fff; border: 0; }
    .actions .cancel { background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; }
    .flash { background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 10px 14px; border-radius: 8px; margin-bottom: 18px; font-size: .9rem; }
    .flash.err { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
</style>
</head>
<body>
<div class="topbar">
    <strong>BloodBank SaaS &middot; Super Admin</strong>
    <div>
        <a href="index.php">Dashboard</a> &nbsp;|&nbsp;
        <a href="logout.php">Sign out</a>
    </div>
</div>

<div class="wrap">
    <h1>
        Tenants
        <a class="btn" href="add_blood_bank.php">+ Provision blood bank</a>
    </h1>

    <?php if (!empty($_GET['msg'])): ?>
        <div class="flash"><?= bb_e($_GET['msg']) ?></div>
    <?php endif; ?>
    <?php if (!empty($_GET['err'])): ?>
        <div class="flash err"><?= bb_e($_GET['err']) ?></div>
    <?php endif; ?>

    <div class="card">
        <table>
            <thead>
            <tr>
                <th>Organization</th><th>Subdomain</th><th>Admin</th><th>Package</th>
                <th>SMS</th><th>SMS Balance</th><th>Donors</th><th>Volunteers</th>
                <th>Status</th><th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($tenants as $t): ?>
                <tr data-tenant='<?= bb_e(json_encode($t, JSON_UNESCAPED_UNICODE)) ?>'>
                    <td><strong><?= bb_e($t['org_name']) ?></strong></td>
                    <td><?= bb_e($t['subdomain']) ?></td>
                    <td><?= bb_e($t['admin_name']) ?> &middot; <?= bb_e($t['admin_phone']) ?></td>
                    <td><span class="badge pill"><?= bb_e($t['subscription_status']) ?></span></td>
                    <td>
                        <span class="badge <?= (int)$t['sms_enabled'] === 1 ? 'on' : 'off' ?>">
                            <?= (int)$t['sms_enabled'] === 1 ? 'ON' : 'OFF' ?>
                        </span>
                    </td>
                    <td class="balance <?= (float)$t['sms_balance'] <= 0 ? 'empty' : ((float)$t['sms_balance'] < 50 ? 'low' : '') ?>">
                        <?= number_format((float)$t['sms_balance'], 2) ?>
                    </td>
                    <td><?= number_format((int)$t['donor_count']) ?></td>
                    <td><?= number_format((int)$t['volunteer_count']) ?></td>
                    <td>
                        <span class="badge <?= (int)$t['is_active'] === 1 ? 'on' : 'off' ?>">
                            <?= (int)$t['is_active'] === 1 ? 'Active' : 'Disabled' ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn ghost open-sms" type="button" data-id="<?= (int)$t['id'] ?>">SMS &amp; Wallet</button>
                        <form method="post" action="actions.php" style="display:inline; margin-left:6px;">
                            <input type="hidden" name="csrf" value="<?= bb_e(bb_csrf_token()) ?>">
                            <input type="hidden" name="do" value="toggle_active">
                            <input type="hidden" name="tenant_id" value="<?= (int)$t['id'] ?>">
                            <button type="submit" style="background:transparent;border:1px solid #d1d5db;border-radius:6px;padding:5px 10px;cursor:pointer;">
                                <?= (int)$t['is_active'] === 1 ? 'Disable' : 'Enable' ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<dialog class="dialog" id="smsDialog">
    <div class="body">
        <h2>SMS &amp; Wallet</h2>
        <p class="sub" id="dOrg"></p>
        <form method="post" action="actions.php">
            <input type="hidden" name="csrf" value="<?= bb_e(bb_csrf_token()) ?>">
            <input type="hidden" name="do" value="save_sms">
            <input type="hidden" name="tenant_id" id="dTenantId" value="">

            <div class="toggle-row">
                <strong style="font-size:.9rem;">SMS enabled</strong>
                <label class="switch"><input type="checkbox" name="sms_enabled" id="dSmsEnabled" value="1"><span class="slider"></span></label>
            </div>

            <label for="dApiKey">API key</label>
            <input id="dApiKey" name="sms_api_key" type="text" placeholder="SMS gateway API key">

            <label for="dSenderId">Sender ID</label>
            <input id="dSenderId" name="sms_sender_id" type="text" maxlength="100" placeholder="e.g. 8809876543222">

            <div class="toggle-row">
                <strong style="font-size:.9rem;">Allow Unicode SMS</strong>
                <label class="switch"><input type="checkbox" name="sms_unicode" id="dSmsUnicode" value="1"><span class="slider"></span></label>
            </div>

            <label for="dBalance">Wallet balance (BDT)</label>
            <input id="dBalance" name="sms_balance" type="number" step="0.01" min="0" max="999999" placeholder="0.00">
            <p class="sub" id="dBalanceNow" style="margin-top:6px;"></p>

            <div class="actions">
                <a href="#" class="cancel" id="dCancel">Cancel</a>
                <button type="submit" class="save">Save &amp; Recharge</button>
            </div>
        </form>
    </div>
</dialog>

<script>
    const dialog = document.getElementById('smsDialog');
    document.querySelectorAll('.open-sms').forEach(btn => {
        btn.addEventListener('click', () => {
            const row = btn.closest('tr');
            const t = JSON.parse(row.dataset.tenant);
            document.getElementById('dTenantId').value = t.id;
            document.getElementById('dOrg').textContent = t.org_name;
            document.getElementById('dSmsEnabled').checked = t.sms_enabled === 1;
            document.getElementById('dApiKey').value = t.sms_api_key || '';
            document.getElementById('dSenderId').value = t.sms_sender_id || '';
            document.getElementById('dSmsUnicode').checked = t.sms_unicode === 1;
            document.getElementById('dBalance').value = parseFloat(t.sms_balance || 0).toFixed(2);
            document.getElementById('dBalanceNow').textContent =
                'Current balance: ' + parseFloat(t.sms_balance || 0).toFixed(2);
            dialog.showModal();
        });
    });
    document.getElementById('dCancel').addEventListener('click', e => {
        e.preventDefault();
        dialog.close();
    });
</script>
</body>
</html>
