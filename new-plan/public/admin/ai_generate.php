<?php
/** AJAX: generate content ideas with the AI, store as a new batch. */
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

@set_time_limit(150);

try {
    $result = AiService::generateIdeas();
    $promptSha = substr(sha1(SiteData::group('ai')['masterPrompt']), 0, 16);
    $batchId = Database::insert(
        'INSERT INTO fh_ai_batches (model, prompt_sha, items_count, raw_response) VALUES (?, ?, ?, ?)',
        [$result['model'], $promptSha, count($result['items']), mb_substr($result['raw'], 0, 100000)]
    );
    foreach ($result['items'] as $i => $item) {
        Database::q(
            'INSERT INTO fh_ai_ideas (batch_id, title, summary, format, hashtag) VALUES (?, ?, ?, ?, ?)',
            [$batchId, $item['title'], $item['summary'], $item['format'], $item['hashtag']]
        );
    }
    $items = Database::all('SELECT * FROM fh_ai_ideas WHERE batch_id = ? ORDER BY id ASC', [$batchId]);
    json_out(['ok' => true, 'batchId' => $batchId, 'items' => $items]);
} catch (Throwable $e) {
    json_out(['ok' => false, 'error' => $e->getMessage()]);
}
