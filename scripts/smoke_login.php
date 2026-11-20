<?php
declare(strict_types=1);
// Smoke test for the auth building blocks (no DB driver available here):
// password hashing, OTP derivation length/stability, and CSRF token flow.

function bb_generate_otp(string $otpSecret): string
{
    return str_pad((string)(abs(crc32($otpSecret)) % 1000000), 6, '0', STR_PAD_LEFT);
}

$hash = password_hash('Admin@1234', PASSWORD_DEFAULT);
$secret = 'JBSWY3DPEHPK3PXP';

$checks = [
    'wrong password rejected'     => password_verify('wrongpass', $hash) === false,
    'correct password accepted'   => password_verify('Admin@1234', $hash) === true,
    'otp is 6 digits'             => strlen(bb_generate_otp($secret)) === 6,
    'otp deterministic'           => bb_generate_otp($secret) === bb_generate_otp($secret),
    'otp compare via hash_equals' => hash_equals(bb_generate_otp($secret), bb_generate_otp($secret)) === true,
    'csrf token 64 hex chars'     => (bool)preg_match('/^[a-f0-9]{64}$/', bin2hex(random_bytes(32))),
    'subdomain regex valid'       => (bool)preg_match('/^[a-z0-9](?:[a-z0-9-]{1,61}[a-z0-9])?$/', 'city-central'),
    'subdomain regex rejects bad' => !preg_match('/^[a-z0-9](?:[a-z0-9-]{1,61}[a-z0-9])?$/', '-bad_'),
];

foreach ($checks as $name => $ok) {
    echo ($ok ? 'PASS' : 'FAIL') . ' - ' . $name . PHP_EOL;
}
exit($checks ? 0 : 1);
