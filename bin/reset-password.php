<?php
declare(strict_types=1);

/*
 * Sets a new password for an admin account, for when nobody can sign in.
 * Needs access to the server.
 *
 *   php bin/reset-password.php                      lists the accounts
 *   php bin/reset-password.php --email=you@x.com    sets a random password and prints it
 *   php bin/reset-password.php --email=you@x.com --password="new password"
 */

if (PHP_SAPI !== 'cli') {
    exit("Run this from the command line.\n");
}

require dirname(__DIR__) . '/app/bootstrap.php';

$opts = getopt('', ['email:', 'password:']);
$email = mb_strtolower(trim((string) ($opts['email'] ?? '')));

if ($email === '') {
    echo "Accounts:\n";
    foreach (db_all('SELECT email, name, role FROM users ORDER BY id') as $row) {
        printf("  %-40s %-8s %s\n", $row['email'], $row['role'], $row['name']);
    }
    echo "\nReset one with: php bin/reset-password.php --email=<email> [--password=<new password>]\n";
    exit(0);
}

$user = db_one('SELECT id, email FROM users WHERE email = ?', [$email]);
if ($user === null) {
    fwrite(STDERR, "No account has the email $email. Run without --email to list the accounts.\n");
    exit(1);
}

$generated = !isset($opts['password']);
$password = $generated
    ? substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(18))), 0, 16)
    : (string) $opts['password'];
if ($problem = password_problem($password)) {
    fwrite(STDERR, $problem . "\n");
    exit(1);
}

db_update('users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT), 'updated_at' => now()], 'id = ?', [$user['id']]);
// Let the account sign in straight away even if it was locked out by failed attempts.
db_exec('DELETE FROM login_attempts WHERE email = ?', [$email]);
log_message('auth', 'Password reset from the command line', ['user' => (int) $user['id']]);

echo "Password changed for {$user['email']}.\n";
if ($generated) {
    echo "  New password: $password\n";
}
echo "Sign in at " . absolute_url('/admin/login') . " and change it under My account if you like.\n";
