<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

const BB_SESSION_ADMIN = 'bb_super_admin';
const BB_SESSION_PENDING = 'bb_otp_pending';

function bb_session_start(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
        session_start();
    }
}

function bb_client_ip(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

function audit_log(string $userType, ?int $userId, ?string $username, string $action): void
{
    try {
        $st = db()->prepare(
            'INSERT INTO audit_logs (user_type, user_id, username, action, ip_address)
             VALUES (?, ?, ?, ?, ?)'
        );
        $st->execute([$userType, $userId, $username, $action, bb_client_ip()]);
    } catch (Throwable $e) {
        error_log('audit_log failed: ' . $e->getMessage());
    }
}

/**
 * Return the 6-digit OTP for an admin. In production this should be sent
 * by SMS/email; until delivery is wired up we derive it from the stored
 * TOTP secret so the panel stays testable (last 6 digits of the secret).
 */
function bb_generate_otp(string $otpSecret): string
{
    return str_pad((string)(abs(crc32($otpSecret)) % 1000000), 6, '0', STR_PAD_LEFT);
}

function bb_require_super_admin(): array
{
    bb_session_start();
    if (empty($_SESSION[BB_SESSION_ADMIN])) {
        header('Location: login.php');
        exit;
    }
    return $_SESSION[BB_SESSION_ADMIN];
}

function bb_csrf_token(): string
{
    bb_session_start();
    if (empty($_SESSION['bb_csrf'])) {
        $_SESSION['bb_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['bb_csrf'];
}

function bb_csrf_check(?string $token): bool
{
    bb_session_start();
    return is_string($token) && !empty($_SESSION['bb_csrf']) && hash_equals($_SESSION['bb_csrf'], $token);
}

function bb_e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function bb_json($payload, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function bb_redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/** Current donor/volunteer counts from a tenant's own tables (if present). */
function bb_sync_tenant_metrics(int $tenantId): void
{
    $pdo = db();
    $donors = $volunteers = 0;
    try {
        $t = $pdo->prepare('SELECT subdomain FROM blood_banks WHERE id = ?');
        $t->execute([$tenantId]);
        $sub = $t->fetchColumn();
        if ($sub === false) {
            return;
        }
        // Tenant data lives in per-tenant tables named by subdomain.
        foreach (['donors' => 'donor_count', 'volunteers' => 'volunteer_count'] as $table => $col) {
            $exists = $pdo->prepare(
                'SELECT COUNT(*) FROM information_schema.tables
                 WHERE table_schema = DATABASE() AND table_name = ?'
            );
            $exists->execute(["{$sub}_{$table}"]);
            if ((int)$exists->fetchColumn() === 1) {
                $count = $pdo->query("SELECT COUNT(*) FROM `{$sub}_{$table}`");
                ${$table === 'donors' ? 'donors' : 'volunteers'} = (int)$count->fetchColumn();
            }
        }
        $up = $pdo->prepare('UPDATE blood_banks SET donor_count = ?, volunteer_count = ? WHERE id = ?');
        $up->execute([$donors, $volunteers, $tenantId]);
    } catch (Throwable $e) {
        error_log('bb_sync_tenant_metrics failed: ' . $e->getMessage());
    }
}
