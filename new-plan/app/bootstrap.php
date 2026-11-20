<?php
/**
 * Bootstrap — loads the app classes, env and hardened session.
 * Every entrypoint (public pages, API, admin, installer) includes this first.
 */

declare(strict_types=1);

error_reporting(E_ALL);
// Display errors only in local/docker development; on production they are logged.
$envDebug = $_SERVER['APP_DEBUG'] ?? getenv('APP_DEBUG') ?: null;
if ($envDebug === null || $envDebug === '' || $envDebug === '0' || $envDebug === 'false') {
    $envDebug = '0';
}
ini_set('display_errors', $envDebug === '1' ? '1' : '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/Env.php';
Env::load();
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Csrf.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Uploader.php';
require_once __DIR__ . '/SiteData.php';
require_once __DIR__ . '/AiService.php';

/** Hardened session cookie (called before session_start()). */
function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['SERVER_PORT'] ?? '') == 443);

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_secure', $https ? '1' : '0');
    ini_set('session.gc_maxlifetime', '7200');

    session_name('fhsite');
    session_start();
}

/** Basic security headers for every response. */
function send_security_headers(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    if (!headers_sent()) {
        header_remove('X-Powered-By');
    }
}

send_security_headers();
start_secure_session();

// Buffer all output: stray notices never break headers on any PHP version.
ob_start();
