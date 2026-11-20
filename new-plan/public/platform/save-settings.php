<?php
/**
 * Save platform-side edits back to the admin panel settings:
 * master prompt body, meal defaults, custom templates.
 */

require_once dirname(__DIR__) . '/bootstrap.php';
send_security_headers();

if (!Auth::id() || !Auth::user()) {
    json_out(['ok' => false, 'error' => 'unauthorized'], 401);
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_out(['ok' => false, 'error' => 'method'], 405);
}

$payload = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($payload)) {
    json_out(['ok' => false, 'error' => 'bad_request'], 400);
}

$platform = SiteData::group('platform');

if (isset($payload['masterPrompt'])) {
    $body = trim((string) $payload['masterPrompt']);
    if ($body !== '') {
        $platform['masterPrompt'] = $body;
    }
}

if (isset($payload['meals']) && is_array($payload['meals'])) {
    foreach ($platform['meals'] as $key => $_) {
        if (array_key_exists($key, $payload['meals'])) {
            $platform['meals'][$key] = (bool) $payload['meals'][$key];
        }
    }
}

if (isset($payload['customTemplates']) && is_array($payload['customTemplates'])) {
    $clean = [];
    foreach ($payload['customTemplates'] as $tpl) {
        if (!is_array($tpl) || empty($tpl['key']) || empty($tpl['label']) || empty($tpl['colors']) || !is_array($tpl['colors'])) {
            continue;
        }
        $key = preg_replace('/[^a-z0-9_]/i', '', (string) $tpl['key']);
        if ($key === '' || strlen($key) > 40) {
            continue;
        }
        $colors = [];
        foreach (['accent', 'accent2', 'gold', 'ink', 'bg'] as $c) {
            $colors[$c] = preg_match('/^#[0-9a-f]{6}$/i', (string) ($tpl['colors'][$c] ?? '')) ? $tpl['colors'][$c] : '#6f8b7a';
        }
        $clean[] = [
            'key' => $key,
            'label' => mb_substr(clean_text((string) $tpl['label'], 40), 0, 40),
            'dot' => $colors['accent'],
            'colors' => $colors,
            'badge' => mb_substr(clean_text((string) ($tpl['badge'] ?? ''), 4), 0, 4),
        ];
    }
    $platform['customTemplates'] = $clean;
}

SiteData::putGroup('platform', $platform);
json_out(['ok' => true]);
