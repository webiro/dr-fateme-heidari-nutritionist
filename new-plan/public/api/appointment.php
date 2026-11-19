<?php
/**
 * Appointment API — receives the contact form (JSON) and stores it in MySQL.
 * Replaces the Cloudflare Worker endpoint; the frontend keeps posting the same shape.
 */

// api/ is inside the web root; use the layout-agnostic loader.
require_once __DIR__ . '/../bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_out(['ok' => false, 'error' => 'method'], 405);
}

// --- Same-origin check (when Origin/Referer is provided by the browser) ---
$host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));

/** host[:port] of a URL, normalized for comparison with HTTP_HOST. */
function origin_netloc(string $url): ?string
{
    $parts = parse_url($url);
    if ($parts === false || !isset($parts['host'])) {
        return null;
    }
    $host = strtolower($parts['host']);
    if (isset($parts['port'])) {
        $host .= ':' . $parts['port'];
    }
    return $host;
}

$origin = origin_netloc((string) ($_SERVER['HTTP_ORIGIN'] ?? ''));
$referer = origin_netloc((string) ($_SERVER['HTTP_REFERER'] ?? ''));
if (($origin !== null && $origin !== $host)
    || ($origin === null && $referer !== null && $referer !== $host)) {
    json_out(['ok' => false, 'error' => 'forbidden'], 403);
}

$ip = client_ip();

// --- Simple rate limit: max 5 submissions per IP per hour ---
try {
    $recent = (int) Database::val(
        'SELECT COUNT(*) FROM fh_submissions WHERE ip = ? AND created_at > (NOW() - INTERVAL 1 HOUR)',
        [$ip]
    );
    if ($recent >= 5) {
        json_out(['ok' => false, 'error' => 'rate_limited'], 429);
    }
} catch (Throwable $e) {
    json_out(['ok' => false, 'error' => 'server'], 500);
}

// --- Parse body ---
$raw = file_get_contents('php://input');
$data = json_decode((string) $raw, true);
if (!is_array($data)) {
    $data = $_POST; // graceful fallback for non-JS submissions
}
if (!is_array($data)) {
    json_out(['ok' => false, 'error' => 'bad_request'], 400);
}

// --- Honeypot: silently "accept" bot submissions without storing them ---
$honeypot = trim((string) ($data['website_url'] ?? ''));
if ($honeypot !== '') {
    json_out(['ok' => true]);
}

$name = clean_text((string) ($data['name'] ?? ''), 100);
$age = normalize_digits(clean_text((string) ($data['age'] ?? ''), 10));
$height = normalize_digits(clean_text((string) ($data['height'] ?? ''), 10));
$goal = clean_text((string) ($data['goal'] ?? ''), 50);
$phone = normalize_digits(clean_text((string) ($data['phone'] ?? ''), 30));
$message = clean_text((string) ($data['message'] ?? ''), 2000);

// --- Validation ---
$errors = [];
if (mb_strlen($name) < 2) {
    $errors[] = 'نام';
}
if ($age === '' || !preg_match('/^\d{1,3}(\.\d+)?$/', $age)) {
    $errors[] = 'سن';
}
if ($height === '' || !preg_match('/^\d{2,3}(\.\d+)?$/', $height)) {
    $errors[] = 'قد';
}
if (!in_array($goal, ['افزایش وزن', 'کاهش وزن', 'پایداری وزن'], true)) {
    $errors[] = 'هدف مشاوره';
}
$digitsPhone = preg_replace('/\D/', '', $phone) ?? '';
if (strlen($digitsPhone) < 10 || strlen($digitsPhone) > 15) {
    $errors[] = 'شماره تماس';
}
if ($errors) {
    json_out(['ok' => false, 'error' => 'validation', 'fields' => $errors], 422);
}

// --- Duplicate guard: same phone within 10 minutes ---
$dup = (int) Database::val(
    'SELECT COUNT(*) FROM fh_submissions WHERE phone = ? AND created_at > (NOW() - INTERVAL 10 MINUTE)',
    [$digitsPhone]
);
if ($dup > 0) {
    json_out(['ok' => true, 'duplicate' => true]);
}

Database::q(
    'INSERT INTO fh_submissions (name, age, height, goal, phone, message, ip, user_agent, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())',
    [
        $name,
        $age,
        $height,
        $goal,
        $digitsPhone,
        $message !== '' ? $message : null,
        $ip,
        substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
    ]
);

json_out(['ok' => true]);
