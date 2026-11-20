<?php
/**
 * AiService — Paasta Cloud (OpenAI-compatible) chat completions.
 * Endpoint: https://console.paasta.cloud/api/ai/v1/chat/completions
 */

final class AiService
{
    public static function group(): array
    {
        return SiteData::group('ai');
    }

    public static function isConfigured(): bool
    {
        $ai = self::group();
        return $ai['apiKey'] !== '' && $ai['endpoint'] !== '' && $ai['model'] !== '';
    }

    /** Fetch available model ids from the provider catalog (models endpoint). */
    public static function listModels(): array
    {
        $ai = self::group();
        if ($ai['apiKey'] === '' || empty($ai['modelsEndpoint'])) {
            throw new RuntimeException('اول کلید API را ذخیره کنید.');
        }
        [$status, $body] = self::http($ai['modelsEndpoint'], 'GET', $ai['apiKey'], null, 30);
        if ($status !== 200) {
            throw new RuntimeException('دریافت لیست مدل‌ها ناموفق بود (کد ' . $status . ').');
        }
        $json = json_decode($body, true);
        $ids = [];
        if (isset($json['data']) && is_array($json['data'])) {
            foreach ($json['data'] as $row) {
                if (!empty($row['id'])) {
                    $ids[] = (string) $row['id'];
                }
            }
        }
        sort($ids, SORT_NATURAL | SORT_FLAG_CASE);
        return $ids;
    }

    /**
     * Run the master prompt and return an array of idea items.
     * Each item: title / summary / format / hashtag
     */
    public static function generateIdeas(): array
    {
        $ai = self::group();
        if (!self::isConfigured()) {
            throw new RuntimeException('تنظیمات هوش مصنوعی کامل نیست. کلید API و مدل را در «دستیار هوشمند → تنظیمات» ذخیره کنید.');
        }

        $payload = [
            'model' => $ai['model'],
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant. Always reply with valid JSON only.'],
                ['role' => 'user', 'content' => $ai['masterPrompt']],
            ],
            'temperature' => 0.9,
            'stream' => false,
        ];

        [$status, $body] = self::http($ai['endpoint'], 'POST', $ai['apiKey'], $payload, 120);
        if ($status !== 200) {
            $hint = '';
            $decoded = json_decode($body, true);
            if (isset($decoded['error']['message'])) {
                $hint = ' — ' . mb_substr((string) $decoded['error']['message'], 0, 200);
            }
            if ($status === 401) {
                throw new RuntimeException('کلید API نامعتبر است (401).' . $hint);
            }
            throw new RuntimeException('خطای سرویس هوش مصنوعی (کد ' . $status . ')' . $hint);
        }

        $json = json_decode($body, true);
        $content = $json['choices'][0]['message']['content'] ?? '';
        $items = self::parseIdeas((string) $content);
        if (!$items) {
            throw new RuntimeException('پاسخ مدل قابل پردازش نبود. لطفاً دوباره تلاش کنید یا مسترپرامپت را ویرایش کنید.');
        }
        return ['items' => $items, 'raw' => (string) $content, 'model' => $ai['model']];
    }

    /** Extract a JSON array of ideas from the model text (tolerates code fences). */
    private static function parseIdeas(string $content): array
    {
        $text = trim($content);
        // strip markdown fences
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $text) ?? $text;

        $items = [];
        // direct JSON decode
        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            if (isset($decoded['ideas']) && is_array($decoded['ideas'])) {
                $decoded = $decoded['ideas'];
            }
            if (isset($decoded[0]) || array_is_list($decoded)) {
                $items = $decoded;
            }
        }
        if (!$items) {
            // salvage the first [...] block
            if (preg_match('/\[.*\]/s', $text, $m)) {
                $decoded = json_decode($m[0], true);
                if (is_array($decoded)) {
                    $items = $decoded;
                }
            }
        }
        if (!$items) {
            // last resort: split titles by lines
            foreach (preg_split('/\r\n|\r|\n/', $text) ?: [] as $line) {
                $line = trim($line, " \t-*۰-۹0-9..–—");
                if (mb_strlen($line) >= 6) {
                    $items[] = ['title' => $line];
                }
            }
        }

        $out = [];
        foreach (array_slice($items, 0, 20) as $row) {
            if (!is_array($row)) {
                if (is_string($row) && mb_strlen($row) > 5) {
                    $row = ['title' => $row];
                } else {
                    continue;
                }
            }
            $title = clean_text((string) ($row['title'] ?? ''), 255);
            if ($title === '') {
                continue;
            }
            $format = clean_text((string) ($row['format'] ?? ''), 30);
            $out[] = [
                'title' => $title,
                'summary' => clean_text((string) ($row['summary'] ?? ''), 1000),
                'format' => in_array($format, ['reel', 'post', 'article'], true) ? $format : 'post',
                'hashtag' => clean_text((string) ($row['hashtag'] ?? ''), 191),
            ];
        }
        return $out;
    }

    /** @return array{0:int,1:string} */
    private static function http(string $url, string $method, string $apiKey, ?array $payload, int $timeout): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('curl روی سرور فعال نیست.');
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => $timeout,
        ]);
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
        }
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        if ($body === false) {
            $msg = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('ارتباط با سرویس برقرار نشد: ' . $msg);
        }
        return [$status, (string) $body];
    }
}
