<?php
/**
 * Load a saved diet record from the panel archive back into the platform.
 * GET load-record.php?id=NN → { ok, data } (data = serialized form JSON).
 */

require_once dirname(__DIR__) . '/bootstrap.php';
send_security_headers();

if (!Auth::id() || !Auth::user()) {
    json_out(['ok' => false, 'error' => 'unauthorized'], 401);
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    json_out(['ok' => false, 'error' => 'method'], 405);
}

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    json_out(['ok' => false, 'error' => 'missing id'], 400);
}

$rec = Database::one('SELECT data FROM fh_diet_records WHERE id = ? LIMIT 1', [$id]);
if (!$rec) {
    json_out(['ok' => false, 'error' => 'not found'], 404);
}

$data = json_decode((string) $rec['data'], true);
if (!is_array($data)) {
    json_out(['ok' => false, 'error' => 'bad data'], 422);
}

json_out(['ok' => true, 'data' => $data]);
