<?php
declare(strict_types=1);

const LOGIN_MAX_ATTEMPTS = 8;
const LOGIN_WINDOW_SECONDS = 900;

function current_admin(): ?array
{
    static $cache = [];
    $id = (int) ($_SESSION['admin_id'] ?? 0);
    if ($id === 0) {
        return null;
    }
    if (!array_key_exists($id, $cache)) {
        $cache[$id] = db_one('SELECT id, name, email, role, last_login_at FROM users WHERE id = ?', [$id]);
    }
    return $cache[$id];
}

function is_admin_role(): bool
{
    return (current_admin()['role'] ?? '') === 'admin';
}

/**
 * Stop here unless someone is signed in. Pass 'admin' for pages editors may
 * not open (users, site settings, API keys).
 */
function require_admin(?string $role = null): array
{
    $user = current_admin();
    if ($user === null) {
        if (!is_post()) {
            $_SESSION['after_login'] = request_path();
        }
        redirect('/admin/login');
    }
    if ($role === 'admin' && $user['role'] !== 'admin') {
        abort(403, 'Only administrators can open this page.');
    }
    return $user;
}

function login_throttled(): bool
{
    $since = date('Y-m-d H:i:s', time() - LOGIN_WINDOW_SECONDS);
    $count = (int) db_val('SELECT COUNT(*) FROM login_attempts WHERE ip_hash = ? AND attempted_at > ?', [ip_hash(), $since]);
    return $count >= LOGIN_MAX_ATTEMPTS;
}

function attempt_login(string $email, string $password): bool
{
    $user = db_one('SELECT * FROM users WHERE email = ?', [mb_strtolower(trim($email))]);
    // Verify against a dummy hash when the email is unknown so both paths take the same time.
    $hash = $user['password_hash'] ?? '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';
    $ok = password_verify($password, $hash) && $user !== null;

    if (!$ok) {
        db_insert('login_attempts', ['ip_hash' => ip_hash(), 'email' => mb_substr($email, 0, 190), 'attempted_at' => now()]);
        return false;
    }

    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        db_update('users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], 'id = ?', [$user['id']]);
    }
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $user['id'];
    db_update('users', ['last_login_at' => now()], 'id = ?', [$user['id']]);
    db_exec('DELETE FROM login_attempts WHERE ip_hash = ?', [ip_hash()]);
    return true;
}

function logout_admin(): void
{
    unset($_SESSION['admin_id'], $_SESSION['after_login']);
    session_regenerate_id(true);
}

function password_problem(string $password): ?string
{
    if (mb_strlen($password) < 10) {
        return 'Use at least 10 characters for the password.';
    }
    return null;
}
