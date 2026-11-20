<?php
/**
 * Diet platform entry — restricted to the doctor's admin login.
 * Serves the platform app (app.html) with injected runtime settings.
 */

require_once dirname(__DIR__) . '/bootstrap.php';
send_security_headers();

if (!Auth::id() || !Auth::user()) {
    flash_set('err', 'برای ورود به پلتفرم تغذیه، ابتدا وارد پنل مدیریت شوید.');
    redirect('../admin/index.php');
}

$appFile = __DIR__ . '/app.html';
if (!is_file($appFile)) {
    http_response_code(500);
    exit('فایل پلتفرم پیدا نشد (platform/app.html).');
}

$platform = SiteData::group('platform');
$ai = SiteData::group('ai');

// Platform key: its own key, or fall back to the shared AI-assistant key.
$platformKey = (string) ($platform['apiKey'] ?: $ai['apiKey']);

$settings = [
    'platform' => [
        'clinician' => (string) $platform['clinician'],
        'lic' => (string) $platform['lic'],
        'brand' => (string) $platform['brand'],
        'addr' => (string) $platform['addr'],
        'phone' => (string) $platform['phone'],
        'instagram' => (string) $platform['instagram'],
    ],
    'meals' => $platform['meals'],
    'masterPrompt' => (string) $platform['masterPrompt'],
    'customTemplates' => array_values((array) ($platform['customTemplates'] ?? [])),
    'ai' => [
        'provider' => 'paasta',
        'endpoint' => (string) ($ai['endpoint'] ?: 'https://console.paasta.cloud/api/ai/v1/chat/completions'),
        'modelsEndpoint' => (string) ($ai['modelsEndpoint'] ?: 'https://console.paasta.cloud/api/ai/v1/models'),
        'model' => (string) ($platform['aiModel'] ?: $ai['model']),
        'key' => $platformKey,
        'keyFromPanel' => $platformKey !== '',
        'proxy' => 'ai-proxy.php',
    ],
];

$html = (string) file_get_contents($appFile);
$inject = "<script>window.PLATFORM_SETTINGS = " .
    json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) .
    ";</script>\n</head>";
// only the real </head> — the export template literal inside the app also
// contains one, and injecting there would break the page script
$headPos = strpos($html, '</head>');
if ($headPos !== false) {
    $html = substr_replace($html, $inject, $headPos, strlen('</head>'));
}
echo $html;
