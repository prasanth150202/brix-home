<?php
declare(strict_types=1);

/*
 * TEMPORARY one-time page to set the main admin's email and password when
 * nobody can sign in and there is no server access. Delete this file, its
 * view (views/admin/recover.php) and its route in app/routes.php once used.
 *
 * - Needs the recovery key. Only its hash is stored here.
 * - Works once: after a successful change it switches itself off.
 * - Stops working after RECOVERY_EXPIRES even if never used.
 * - Wrong keys count towards the same per-visitor limit as failed sign-ins.
 */

const RECOVERY_KEY_HASH = '$2y$10$YR8s8jDdZ/khUCarGHoEjOa2iGaUr/L2PfvTShHAbiU7BYD7638qW';
const RECOVERY_EXPIRES = '2026-11-15 23:59:59';

function admin_recover(): void
{
    header('Referrer-Policy: no-referrer');
    header('Cache-Control: no-store');

    $used = (string) db_val("SELECT `value` FROM settings WHERE `key` = 'admin_recovery_used'") === '1';
    if ($used || time() > strtotime(RECOVERY_EXPIRES)) {
        abort(404);
    }

    $errors = [];
    $email = mb_strtolower(trim((string) input('email', '')));

    if (is_post()) {
        verify_csrf();
        if (login_throttled()) {
            $errors['key'] = 'Too many attempts. Wait 15 minutes and try again.';
        } elseif (!password_verify((string) ($_POST['key'] ?? ''), RECOVERY_KEY_HASH)) {
            db_insert('login_attempts', ['ip_hash' => ip_hash(), 'email' => 'recovery', 'attempted_at' => now()]);
            $errors['key'] = 'That recovery key is not correct.';
        } else {
            $password = (string) ($_POST['password'] ?? '');
            $admin = db_one("SELECT id FROM users WHERE role = 'admin' ORDER BY id LIMIT 1");

            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
                $errors['email'] = 'Enter a valid email address.';
            } elseif (db_val('SELECT id FROM users WHERE email = ? AND id <> ?', [$email, $admin['id'] ?? 0])) {
                $errors['email'] = 'Another user already has that email.';
            }
            if ($problem = password_problem($password)) {
                $errors['password'] = $problem;
            } elseif ($password !== (string) ($_POST['password_confirm'] ?? '')) {
                $errors['password_confirm'] = 'The two passwords do not match.';
            }

            if (!$errors) {
                $data = ['email' => $email, 'password_hash' => password_hash($password, PASSWORD_DEFAULT), 'updated_at' => now()];
                if ($admin) {
                    db_update('users', $data, 'id = ?', [$admin['id']]);
                } else {
                    db_insert('users', $data + ['name' => 'Admin', 'role' => 'admin', 'created_at' => now()]);
                }
                db_exec(
                    "INSERT INTO settings (`key`, `value`, updated_at) VALUES ('admin_recovery_used', '1', ?)
                     ON DUPLICATE KEY UPDATE `value` = '1', updated_at = VALUES(updated_at)",
                    [now()]
                );
                db_exec('DELETE FROM login_attempts WHERE ip_hash = ?', [ip_hash()]);
                log_message('auth', 'Admin email and password set from the recovery page', ['user' => (int) ($admin['id'] ?? 0)]);
                logout_admin();
                flash('success', 'Done. Sign in with your new email and password. The recovery page is now switched off.');
                redirect('/admin/login');
            }
        }
    }

    render('admin/recover', ['errors' => $errors, 'email' => $email, 'title' => 'Recover admin access'], 'admin/auth_layout');
}
