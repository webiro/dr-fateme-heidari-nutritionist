<?php
/**
 * Authentication for the admin panel.
 * - BCRYPT/Argon2id via password_hash()
 * - Brute-force lockout: 5 failed attempts per username+IP → 10 minute cooldown
 * - Session id regeneration on login, strict session handling (see bootstrap)
 */

final class Auth
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 10;

    public static function check(string $username, string $password): bool
    {
        $username = mb_strtolower(trim($username));
        if ($username === '' || $password === '') {
            return false;
        }

        if (self::isLocked($username)) {
            return false;
        }

        $user = Database::one(
            'SELECT id, password_hash FROM fh_admins WHERE username = ? LIMIT 1',
            [$username]
        );
        $ok = false;
        if ($user && password_verify($password, $user['password_hash'])) {
            // Transparent hash upgrade if the algorithm/cost changed.
            if (password_needs_rehash($user['password_hash'], self::algo())) {
                Database::q(
                    'UPDATE fh_admins SET password_hash = ? WHERE id = ?',
                    [password_hash($password, self::algo()), $user['id']]
                );
            }
            $ok = true;
        }

        Database::q(
            'INSERT INTO fh_login_attempts (username, ip, success, created_at) VALUES (?, ?, ?, NOW())',
            [$username, client_ip(), $ok ? 1 : 0]
        );

        if ($ok && $user) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $user['id'];
            $_SESSION['admin_username'] = $username;
            Database::q('UPDATE fh_admins SET last_login_at = NOW() WHERE id = ?', [$user['id']]);
            // clear attempts for this identity
            Database::q('DELETE FROM fh_login_attempts WHERE username = ? AND success = 0', [$username]);
        }
        return $ok;
    }

    public static function attemptsLeft(string $username): int
    {
        $username = mb_strtolower(trim($username));
        $used = (int) Database::val(
            'SELECT COUNT(*) FROM fh_login_attempts
             WHERE username = ? AND success = 0 AND created_at > (NOW() - INTERVAL ' . self::LOCKOUT_MINUTES . ' MINUTE)',
            [$username]
        );
        return max(0, self::MAX_ATTEMPTS - $used);
    }

    public static function isLocked(string $username): bool
    {
        return self::attemptsLeft($username) <= 0;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function id(): ?int
    {
        return isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null;
    }

    public static function user(): ?array
    {
        if (!self::id()) {
            return null;
        }
        return Database::one('SELECT id, username, display_name, last_login_at FROM fh_admins WHERE id = ? LIMIT 1', [self::id()]);
    }

    /** Guard: redirect to login when not authenticated. */
    public static function requireAdmin(): void
    {
        if (!self::id() || !self::user()) {
            session_regenerate_id(true);
            redirect('index.php');
        }
    }

    public static function changePassword(int $userId, string $newPassword): void
    {
        Database::q(
            'UPDATE fh_admins SET password_hash = ? WHERE id = ?',
            [password_hash($newPassword, self::algo()), $userId]
        );
    }

    private static function algo(): string
    {
        return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
    }
}
