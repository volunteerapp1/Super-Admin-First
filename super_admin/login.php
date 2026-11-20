<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

bb_session_start();

if (!empty($_SESSION[BB_SESSION_ADMIN])) {
    bb_redirect('index.php');
}

$step = 'password';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!bb_csrf_check($_POST['csrf'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } elseif (isset($_POST['step']) && $_POST['step'] === 'password') {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        $st = db()->prepare('SELECT * FROM admins WHERE username = ? OR email = ? LIMIT 1');
        $st->execute([$username, $username]);
        $admin = $st->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION[BB_SESSION_PENDING] = [
                'id'       => (int)$admin['id'],
                'username' => $admin['username'],
                'otp'      => bb_generate_otp($admin['otp_secret']),
                'at'       => time(),
            ];
            audit_log('super_admin', (int)$admin['id'], $admin['username'], 'login step 1 passed (password ok, OTP pending)');
            $step = 'otp';
        } else {
            audit_log('super_admin', null, $username !== '' ? $username : null, 'failed login attempt');
            $error = 'Invalid username or password.';
        }
    } elseif (isset($_POST['step']) && $_POST['step'] === 'otp') {
        $pending = $_SESSION[BB_SESSION_PENDING] ?? null;
        $code = preg_replace('/\D/', '', (string)($_POST['otp'] ?? ''));
        if (!$pending || (time() - (int)$pending['at']) > 600) {
            unset($_SESSION[BB_SESSION_PENDING]);
            $error = 'Your OTP window expired. Please sign in again.';
        } elseif ($code !== '' && hash_equals((string)$pending['otp'], $code)) {
            session_regenerate_id(true);
            $_SESSION[BB_SESSION_ADMIN] = [
                'id'       => $pending['id'],
                'username' => $pending['username'],
            ];
            unset($_SESSION[BB_SESSION_PENDING]);
            audit_log('super_admin', (int)$pending['id'], $pending['username'], 'login complete (OTP verified)');
            bb_redirect('index.php');
        } else {
            audit_log('super_admin', (int)($pending['id'] ?? 0), $pending['username'] ?? null, 'invalid OTP entered');
            $error = 'That code is not correct. Please check and try again.';
            $step = 'otp';
        }
    }
}

if ($error === '' && !empty($_SESSION[BB_SESSION_PENDING]) && ($_POST['step'] ?? '') !== 'otp') {
    $step = 'otp';
}
$pendingForBanner = $_SESSION[BB_SESSION_PENDING] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Super Admin - BloodBank SaaS</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        min-height: 100vh; display: flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, #7f1d1d 0%, #991b1b 50%, #b91c1c 100%);
        padding: 24px;
    }
    .card {
        background: #fff; border-radius: 16px; padding: 40px 36px;
        width: 100%; max-width: 420px; box-shadow: 0 24px 60px rgba(0,0,0,.35);
    }
    h1 { font-size: 1.4rem; color: #7f1d1d; margin-bottom: 4px; }
    p.sub { color: #6b7280; font-size: .9rem; margin-bottom: 24px; }
    label { display: block; font-size: .85rem; font-weight: 600; color: #374151; margin: 14px 0 6px; }
    input {
        width: 100%; padding: 11px 14px; border: 1px solid #d1d5db; border-radius: 8px;
        font-size: 1rem; outline: none; transition: border-color .15s, box-shadow .15s;
    }
    input:focus { border-color: #b91c1c; box-shadow: 0 0 0 3px rgba(185,28,28,.15); }
    button {
        width: 100%; margin-top: 22px; padding: 12px; background: #b91c1c; color: #fff;
        border: 0; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer;
        transition: background .15s;
    }
    button:hover { background: #991b1b; }
    .error {
        background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c;
        padding: 10px 14px; border-radius: 8px; font-size: .88rem; margin-top: 16px;
    }
    .hint {
        margin-top: 18px; padding: 10px 14px; border-radius: 8px;
        background: #f3f4f6; color: #4b5563; font-size: .8rem;
    }
    a { color: #b91c1c; }
</style>
</head>
<body>
<div class="card">
    <h1><?= $step === 'otp' ? 'Two-Step Verification' : 'Super Admin Sign In' ?></h1>
    <p class="sub"><?= $step === 'otp'
        ? 'Enter the 6-digit code we generated for your account.'
        : 'Restricted area. Sign in with your admin credentials.' ?></p>

    <form method="post" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= bb_e(bb_csrf_token()) ?>">
        <?php if ($step === 'password'): ?>
            <input type="hidden" name="step" value="password">
            <label for="username">Username or email</label>
            <input id="username" name="username" type="text" required autofocus>
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required>
        <?php else: ?>
            <input type="hidden" name="step" value="otp">
            <label for="otp">6-digit code</label>
            <input id="otp" name="otp" type="text" inputmode="numeric" pattern="\d{6}"
                   maxlength="6" required autofocus>
        <?php endif; ?>
        <button type="submit"><?= $step === 'otp' ? 'Verify & Continue' : 'Continue' ?></button>
    </form>

    <?php if ($error): ?><div class="error"><?= bb_e($error) ?></div><?php endif; ?>

    <?php if ($step === 'otp' && $pendingForBanner): ?>
        <div class="hint">
            Dev mode: OTP delivery is not configured yet, so the code is shown here:
            <strong><?= bb_e($pendingForBanner['otp']) ?></strong>.
            Wire SMS/email delivery before going live.
        </div>
    <?php endif; ?>
</div>
</body>
</html>
