<?php
/**
 * Public bootstrap loader — finds app/bootstrap.php from any layout:
 *   a) app/ next to the web root (Docker, cPanel option A: public_html + ~/app)
 *   b) app/ inside the web root (cPanel option B: whole project in public_html)
 *   c) one more level up (unusual nesting)
 */
$bootstrapCandidates = [
    __DIR__ . '/../app/bootstrap.php',
    __DIR__ . '/app/bootstrap.php',
    __DIR__ . '/../../app/bootstrap.php',
];
$loaded = false;
foreach ($bootstrapCandidates as $candidate) {
    if (is_file($candidate)) {
        require_once $candidate;
        $loaded = true;
        break;
    }
}
if (!$loaded) {
    http_response_code(500);
    exit('app/bootstrap.php پیدا نشد — ساختار پوشه‌ها را بررسی کنید.');
}
