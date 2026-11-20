<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

bb_session_start();
$admin = $_SESSION[BB_SESSION_ADMIN] ?? null;
if ($admin) {
    audit_log('super_admin', (int)$admin['id'], (string)$admin['username'], 'logout');
}
$_SESSION = [];
session_destroy();
bb_redirect('login.php');
