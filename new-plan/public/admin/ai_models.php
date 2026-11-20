<?php
/** AJAX: fetch model catalog from Paasta (uses posted key, never stored unless saved). */
require_once dirname(__DIR__) . '/bootstrap.php';
send_security_headers();
header('Content-Type: application/json; charset=utf-8');
if (!Auth::id() || !Auth::user()) {
    json_out(['ok' => false, 'error' => 'unauthorized'], 401);
}
Csrf::check();
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_out(['ok' => false, 'error' => 'method'], 405);
}

$ai = SiteData::group('ai');
$postedKey = trim((string) ($_POST['api_key'] ?? ''));
// masked value means "keep the stored key"
$key = ($postedKey === '' || $postedKey === '••••••••••••') ? $ai['apiKey'] : $postedKey;
$model = clean_text($_POST['model'] ?? $ai['model'], 120);

$ai['apiKey'] = $key;
$ai['model'] = $model !== '' ? $model : $ai['model'];

try {
    $models = AiService::listModels();
    // prefer deepseek models first for convenience
    usort($models, static function ($a, $b) {
        $score = static fn(string $m) => (str_contains($m, 'deepseek') ? 0 : 1) * 10 + (str_contains($m, 'v4') ? 0 : 1) * 5 + (str_contains($m, 'free') ? 1 : 0);
        return $score($a) <=> $score($b);
    });
    json_out(['ok' => true, 'models' => array_slice($models, 0, 120)]);
} catch (Throwable $e) {
    json_out(['ok' => false, 'error' => $e->getMessage()]);
}
