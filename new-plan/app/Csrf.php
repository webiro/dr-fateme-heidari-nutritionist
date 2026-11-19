<?php
/** CSRF protection — one token per session, validated on every POST. */

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = random_token(32);
        }
        return $_SESSION['csrf_token'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }

    /** Validate the token of the current POST; dies with 400 otherwise. */
    public static function check(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            return;
        }
        $sent = $_POST['_csrf'] ?? '';
        if (!is_string($sent) || $sent === '' || !hash_equals($_SESSION['csrf_token'] ?? '', $sent)) {
            http_response_code(400);
            exit('درخواست نامعتبر است (CSRF). صفحه را رفرش کنید و دوباره تلاش کنید.');
        }
    }
}
