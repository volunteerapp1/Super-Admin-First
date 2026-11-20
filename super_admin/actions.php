<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

$admin = bb_require_super_admin();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !bb_csrf_check($_POST['csrf'] ?? null)) {
    bb_redirect('tenants.php?err=' . urlencode('Your session expired. Please try again.'));
}

$do = (string)($_POST['do'] ?? '');

switch ($do) {
    case 'save_sms': {
        $tenantId = (int)($_POST['tenant_id'] ?? 0);
        $enabled  = isset($_POST['sms_enabled']) ? 1 : 0;
        $apiKey   = trim((string)($_POST['sms_api_key'] ?? ''));
        $senderId = trim((string)($_POST['sms_sender_id'] ?? ''));
        $unicode  = isset($_POST['sms_unicode']) ? 1 : 0;
        $balance  = (float)($_POST['sms_balance'] ?? 0);

        if ($tenantId <= 0 || $balance < 0 || $balance > 999999) {
            bb_redirect('tenants.php?err=' . urlencode('Invalid SMS wallet update.'));
        }

        $check = $pdo->prepare('SELECT org_name FROM blood_banks WHERE id = ?');
        $check->execute([$tenantId]);
        $org = $check->fetchColumn();
        if ($org === false) {
            bb_redirect('tenants.php?err=' . urlencode('Blood bank not found.'));
        }

        $st = $pdo->prepare(
            'UPDATE blood_banks
             SET sms_enabled = ?, sms_api_key = ?, sms_sender_id = ?, sms_unicode = ?, sms_balance = ?
             WHERE id = ?'
        );
        $st->execute([
            $enabled,
            $apiKey !== '' ? $apiKey : null,
            $senderId !== '' ? $senderId : null,
            $unicode,
            $balance,
            $tenantId,
        ]);
        audit_log('super_admin', (int)$admin['id'], $admin['username'],
            "updated SMS config/wallet for tenant #{$tenantId} ({$org}) - enabled={$enabled}, balance={$balance}");
        bb_redirect('tenants.php?msg=' . urlencode("SMS settings saved for \"{$org}\". Balance: " . number_format($balance, 2)));
    }

    case 'toggle_active': {
        $tenantId = (int)($_POST['tenant_id'] ?? 0);
        $check = $pdo->prepare('SELECT org_name, is_active FROM blood_banks WHERE id = ?');
        $check->execute([$tenantId]);
        $row = $check->fetch();
        if (!$row) {
            bb_redirect('tenants.php?err=' . urlencode('Blood bank not found.'));
        }
        $newState = ((int)$row['is_active']) === 1 ? 0 : 1;
        $st = $pdo->prepare('UPDATE blood_banks SET is_active = ? WHERE id = ?');
        $st->execute([$newState, $tenantId]);
        audit_log('super_admin', (int)$admin['id'], $admin['username'],
            'toggled tenant #' . $tenantId . ' (' . $row['org_name'] . ') active=' . $newState);
        bb_redirect('tenants.php?msg=' . urlencode(($newState ? 'Enabled' : 'Disabled') . ' "' . $row['org_name'] . '".'));
    }

    case 'review_request': {
        $requestId = (int)($_POST['request_id'] ?? 0);
        $decision  = ($_POST['decision'] ?? '') === 'approved' ? 'approved' : 'rejected';

        $check = $pdo->prepare("SELECT * FROM registration_requests WHERE id = ? AND status = 'pending'");
        $check->execute([$requestId]);
        $req = $check->fetch();
        if (!$req) {
            bb_redirect('index.php?err=' . urlencode('Request not found or already reviewed.'));
        }

        $mark = $pdo->prepare("UPDATE registration_requests SET status = ?, reviewed_at = NOW() WHERE id = ?");
        $mark->execute([$decision, $requestId]);

        if ($decision === 'approved') {
            // Auto-provision the tenant from the approved request.
            $base = strtolower(trim((string)preg_replace('/[^a-z0-9]/i', '-', $req['org_name']), '-'));
            $sub = $base !== '' ? $base : 'bank-' . $requestId;
            $exists = $pdo->prepare('SELECT id FROM blood_banks WHERE subdomain = ?');
            $attempt = 0;
            while (true) {
                $exists->execute([$sub]);
                if (!$exists->fetch()) {
                    break;
                }
                $attempt++;
                $sub = $base . '-' . $attempt;
            }
            $ins = $pdo->prepare(
                'INSERT INTO blood_banks
                    (org_name, subdomain, admin_name, admin_email, admin_phone,
                     admin_password, package_type, subscription_status, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)'
            );
            $ins->execute([
                $req['org_name'], $sub, $req['contact_name'], $req['email'], $req['phone'],
                password_hash('Tenant@1234', PASSWORD_DEFAULT), 'free', 'free',
            ]);
            $newId = (int)$pdo->lastInsertId();
            bb_sync_tenant_metrics($newId);
            audit_log('super_admin', (int)$admin['id'], $admin['username'],
                "approved request #{$requestId} and provisioned tenant #{$newId} ({$req['org_name']})");
            bb_redirect('index.php?msg=' . urlencode("Approved and provisioned \"{$req['org_name']}\" as tenant. Temporary admin password: Tenant@1234"));
        }

        audit_log('super_admin', (int)$admin['id'], $admin['username'],
            "rejected registration request #{$requestId} ({$req['org_name']})");
        bb_redirect('index.php?msg=' . urlencode("Request from \"{$req['org_name']}\" rejected."));
    }

    default:
        bb_redirect('tenants.php?err=' . urlencode('Unknown action.'));
}
