<?php
/**
 * Record diet history from the platform: finals (on export) and drafts.
 * Upsert by (type, client_key) so re-exports update the same record.
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

$type = ($payload['type'] ?? '') === 'draft' ? 'draft' : 'final';
$source = in_array($payload['source'] ?? '', ['manual', 'ai', 'word'], true) ? $payload['source'] : null;
$clientKey = mb_substr(clean_text((string) ($payload['key'] ?? ''), 150), 0, 150);
if ($clientKey === '') {
    json_out(['ok' => false, 'error' => 'missing key'], 400);
}

$patientName = mb_substr(clean_text((string) ($payload['patientName'] ?? ''), 191), 0, 191);
$meta = $payload['patientMeta'] ?? [];
if (!is_array($meta)) {
    $meta = [];
}
$metaJson = json_encode([
    'age' => clean_text((string) ($meta['age'] ?? ''), 10),
    'height' => clean_text((string) ($meta['height'] ?? ''), 10),
    'weight' => clean_text((string) ($meta['weight'] ?? ''), 10),
    'goal' => clean_text((string) ($meta['goal'] ?? ''), 50),
    'date' => clean_text((string) ($meta['date'] ?? ''), 30),
], JSON_UNESCAPED_UNICODE);

$template = mb_substr(clean_text((string) ($payload['template'] ?? ''), 64), 0, 64);
$model = mb_substr(clean_text((string) ($payload['model'] ?? ''), 120), 0, 120);
$data = (string) ($payload['data'] ?? '');
if (strlen($data) > 2000000) {
    $data = substr($data, 0, 2000000);
}

$exports = null;
if (isset($payload['exports']) && is_array($payload['exports'])) {
    $clean = [];
    foreach (['pdf', 'html', 'image', 'print'] as $k) {
        if (isset($payload['exports'][$k])) {
            $clean[$k] = (int) $payload['exports'][$k];
        }
    }
    $exports = json_encode($clean, JSON_UNESCAPED_UNICODE);
}

$existing = Database::one(
    'SELECT id FROM fh_diet_records WHERE type = ? AND client_key = ? LIMIT 1',
    [$type, $clientKey]
);

if ($existing) {
    Database::q(
        'UPDATE fh_diet_records SET source = ?, patient_name = ?, patient_meta = ?, template = ?, model = ?, exports = COALESCE(?, exports), data = ?, updated_at = NOW() WHERE id = ?',
        [$source, $patientName, $metaJson, $template, $model, $exports, $data, $existing['id']]
    );
    json_out(['ok' => true, 'id' => (int) $existing['id'], 'updated' => true]);
}

$id = Database::insert(
    'INSERT INTO fh_diet_records (type, source, client_key, patient_name, patient_meta, template, model, exports, data) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
    [$type, $source, $clientKey, $patientName, $metaJson, $template, $model, $exports, $data]
);
json_out(['ok' => true, 'id' => $id, 'created' => true]);
