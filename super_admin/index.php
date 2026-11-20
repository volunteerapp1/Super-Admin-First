<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

$admin = bb_require_super_admin();

$pdo = db();
$stats = [
    'tenants'   => (int)$pdo->query('SELECT COUNT(*) FROM blood_banks')->fetchColumn(),
    'active'    => (int)$pdo->query('SELECT COUNT(*) FROM blood_banks WHERE is_active = 1')->fetchColumn(),
    'pending'   => (int)$pdo->query("SELECT COUNT(*) FROM registration_requests WHERE status = 'pending'")->fetchColumn(),
    'donors'    => (int)$pdo->query('SELECT COALESCE(SUM(donor_count),0) FROM blood_banks')->fetchColumn(),
    'volunteers'=> (int)$pdo->query('SELECT COALESCE(SUM(volunteer_count),0) FROM blood_banks')->fetchColumn(),
];
$requests = $pdo->query(
    "SELECT * FROM registration_requests WHERE status = 'pending' ORDER BY created_at DESC LIMIT 10"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard - Super Admin</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background: #f3f4f6; color: #111827; }
    .topbar { background: #7f1d1d; color: #fff; padding: 14px 28px; display: flex; justify-content: space-between; align-items: center; }
    .topbar a { color: #fecaca; text-decoration: none; font-size: .9rem; }
    .topbar a:hover { color: #fff; }
    .wrap { max-width: 1100px; margin: 28px auto; padding: 0 20px; }
    h1 { font-size: 1.5rem; margin-bottom: 20px; }
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 28px; }
    .stat { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
    .stat .num { font-size: 1.9rem; font-weight: 700; color: #b91c1c; }
    .stat .lbl { color: #6b7280; font-size: .85rem; margin-top: 2px; }
    .card { background: #fff; border-radius: 12px; padding: 22px; box-shadow: 0 1px 3px rgba(0,0,0,.08); margin-bottom: 24px; }
    .card h2 { font-size: 1.05rem; margin-bottom: 12px; }
    table { width: 100%; border-collapse: collapse; font-size: .9rem; }
    th { text-align: left; color: #6b7280; font-weight: 600; padding: 8px; border-bottom: 1px solid #e5e7eb; }
    td { padding: 8px; border-bottom: 1px solid #f3f4f6; }
    .btn { display: inline-block; background: #b91c1c; color: #fff; padding: 9px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: .9rem; }
    .btn:hover { background: #991b1b; }
    .badge { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: .75rem; font-weight: 600; }
    .badge.pending { background: #fef3c7; color: #92400e; }
    .badge.approved { background: #dcfce7; color: #166534; }
    .badge.rejected { background: #fee2e2; color: #991b1b; }
</style>
</head>
<body>
<div class="topbar">
    <strong>BloodBank SaaS &middot; Super Admin</strong>
    <div>
        <a href="tenants.php">Tenants</a> &nbsp;|&nbsp;
        <a href="add_blood_bank.php">+ Add Blood Bank</a> &nbsp;|&nbsp;
        <a href="logout.php">Sign out (<?= bb_e($admin['username']) ?>)</a>
    </div>
</div>

<div class="wrap">
    <h1>Overview</h1>

    <div class="grid">
        <div class="stat"><div class="num"><?= $stats['tenants'] ?></div><div class="lbl">Blood banks</div></div>
        <div class="stat"><div class="num"><?= $stats['active'] ?></div><div class="lbl">Active tenants</div></div>
        <div class="stat"><div class="num"><?= $stats['pending'] ?></div><div class="lbl">Pending sign-up requests</div></div>
        <div class="stat"><div class="num"><?= number_format($stats['donors']) ?></div><div class="lbl">Donors (all tenants)</div></div>
        <div class="stat"><div class="num"><?= number_format($stats['volunteers']) ?></div><div class="lbl">Volunteers (all tenants)</div></div>
    </div>

    <div class="card">
        <h2>Pending self-registration requests</h2>
        <?php if (!$requests): ?>
            <p style="color:#6b7280;font-size:.9rem;">No pending requests right now.</p>
        <?php else: ?>
            <table>
                <tr><th>Organization</th><th>Contact</th><th>Email</th><th>Phone</th><th>City</th><th>Status</th><th></th></tr>
                <?php foreach ($requests as $r): ?>
                <tr>
                    <td><?= bb_e($r['org_name']) ?></td>
                    <td><?= bb_e($r['contact_name']) ?></td>
                    <td><?= bb_e($r['email']) ?></td>
                    <td><?= bb_e($r['phone']) ?></td>
                    <td><?= bb_e($r['city']) ?></td>
                    <td><span class="badge pending">pending</span></td>
                    <td>
                        <form method="post" action="actions.php" style="display:inline;">
                            <input type="hidden" name="csrf" value="<?= bb_e(bb_csrf_token()) ?>">
                            <input type="hidden" name="do" value="review_request">
                            <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">
                            <input type="hidden" name="decision" value="approved">
                            <button type="submit" style="background:#166534;color:#fff;border:0;border-radius:6px;padding:5px 10px;cursor:pointer;">Approve</button>
                        </form>
                        <form method="post" action="actions.php" style="display:inline;margin-left:6px;">
                            <input type="hidden" name="csrf" value="<?= bb_e(bb_csrf_token()) ?>">
                            <input type="hidden" name="do" value="review_request">
                            <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">
                            <input type="hidden" name="decision" value="rejected">
                            <button type="submit" style="background:#6b7280;color:#fff;border:0;border-radius:6px;padding:5px 10px;cursor:pointer;">Reject</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>

    <p><a class="btn" href="tenants.php">Manage tenants</a></p>
</div>
</body>
</html>
