<?php
/**
 * AI proxy for the diet platform (admin-only session required).
 * Forwards chat/completions + models requests to Paasta; the API key always
 * comes from the server settings, never from the client.
 */

require_once dirname(__DIR__) . '/bootstrap.php';
send_security_headers();

if (!Auth::id() || !Auth::user()) {
    json_out(['ok' => false, 'error' => 'unauthorized'], 401);
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_out(['ok' => false, 'error' => 'method'], 405);
}

$ai = SiteData::group('ai');
$platform = SiteData::group('platform');
$apiKey = (string) ($platform['apiKey'] ?: $ai['apiKey']);
if ($apiKey === '') {
    json_out(['ok' => false, 'error' => 'کلید API تنظیم نشده است — از پنل، بخش دستیار هوشمند.'], 400);
}

$body = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($body)) {
    json_out(['ok' => false, 'error' => 'bad_request'], 400);
}

$url = (string) ($body['url'] ?? '');
$allowed = [
    $ai['endpoint'] ?: 'https://console.paasta.cloud/api/ai/v1/chat/completions',
    $ai['modelsEndpoint'] ?: 'https://console.paasta.cloud/api/ai/v1/models',
    'https://console.paasta.cloud/api/ai/v1/chat/completions',
    'https://console.paasta.cloud/api/ai/v1/models',
];
if (!in_array($url, $allowed, true)) {
    json_out(['ok' => false, 'error' => 'url_not_allowed'], 403);
}

$method = !empty($body['body']) ? 'POST' : 'GET';
$payload = isset($body['body']) && is_array($body['body']) ? $body['body'] : null;

@set_time_limit(180);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => $method,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
    ],
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_TIMEOUT => 170,
]);
if ($payload !== null) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
}
$res = curl_exec($ch);
$status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
if ($res === false) {
    $msg = curl_error($ch);
    curl_close($ch);
    json_out(['ok' => false, 'error' => 'اتصال به سرویس برقرار نشد: ' . $msg], 502);
}
curl_close($ch);

header('Content-Type: ' . ($_SERVER['HTTP_ACCEPT'] ?? 'application/json') . '; charset=utf-8');
http_response_code($status);
echo (string) $res;
