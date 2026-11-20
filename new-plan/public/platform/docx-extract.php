<?php
/**
 * Word (.docx) import — extracts the diet program from a Word file with AI.
 * Pipeline: ZipArchive → word/document.xml → plain text → Paasta AI → diet JSON.
 */

require_once dirname(__DIR__) . '/bootstrap.php';
send_security_headers();

if (!Auth::id() || !Auth::user()) {
    json_out(['ok' => false, 'error' => 'unauthorized'], 401);
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_out(['ok' => false, 'error' => 'method'], 405);
}

@set_time_limit(240);

$file = $_FILES['docx'] ?? null;
if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    json_out(['ok' => false, 'error' => 'فایلی انتخاب نشده است یا آپلود ناقص ماند.']);
}
$originalName = (string) ($file['name'] ?? '');
if (!preg_match('/\.docx$/i', $originalName)) {
    json_out(['ok' => false, 'error' => 'فقط فایل Word با پسوند .docx پذیرفته می‌شود (فرمت قدیمی .doc را اول در Word با Save As به docx تبدیل کنید).']);
}
if ((int) $file['size'] > 20 * 1024 * 1024) {
    json_out(['ok' => false, 'error' => 'حجم فایل بیش از ۲۰ مگابایت است.']);
}

// ---- Extract plain text from the docx ----
/** Pure-PHP ZIP reader fallback (works on hosts without php-zip). */
function fh_zip_read(string $file): ?array
{
    $data = @file_get_contents($file);
    if ($data === false || strlen($data) < 22) {
        return null;
    }
    $pos = strrpos($data, "PK\x05\x06");
    if ($pos === false) {
        return null;
    }
    $eocd = unpack('vdisk/vcdStart/vcdEntriesNum/vtotalEntries/vsize/voffset/vcommentLen', substr($data, $pos + 4, 18));
    $entries = [];
    $p = $eocd['offset'];
    for ($i = 0; $i < $eocd['totalEntries']; $i++) {
        if (substr($data, $p, 4) !== "PK\x01\x02") {
            break;
        }
        $h = unpack('vverMade/vverNeed/vflags/vmethod/vmtime/vmdate/Vcrc/Vcsize/Vusize/vnameLen/vextraLen/vcommentLen/vdiskStart/vintAttr/VextAttr/VlocalOffset', substr($data, $p + 4, 42));
        $name = substr($data, $p + 46, $h['nameLen']);
        if ($h['flags'] & 0x1) { // encrypted — unsupported
            $p += 46 + $h['nameLen'] + $h['extraLen'] + $h['commentLen'];
            continue;
        }
        $entries[$name] = ['csize' => $h['csize'], 'offset' => $h['localOffset']];
        $p += 46 + $h['nameLen'] + $h['extraLen'] + $h['commentLen'];
    }
    return $entries ? ['data' => $data, 'entries' => $entries] : null;
}

function fh_zip_get(array $zip, string $name): ?string
{
    if (!isset($zip['entries'][$name])) {
        return null;
    }
    $e = $zip['entries'][$name];
    $d = $zip['data'];
    $p = $e['offset'];
    if (substr($d, $p, 4) !== "PK\x03\x04") {
        return null;
    }
    $h = unpack('vflags/vmethod/vmtime/vmdate/Vcrc/Vcsize/Vusize/vnameLen/vextraLen', substr($d, $p + 26, 26));
    $start = $p + 30 + $h['nameLen'] + $h['extraLen'];
    $raw = substr($d, $start, $h['csize']);
    if ($h['method'] === 0) {
        return $raw;
    }
    if ($h['method'] === 8) {
        $out = @gzinflate($raw);
        return $out === false ? null : $out;
    }
    return null;
}

$xml = false;
if (class_exists('ZipArchive')) {
    $zip = new ZipArchive();
    $opened = $zip->open($file['tmp_name'], ZipArchive::CHECKCONS);
    if ($opened === true) {
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
    }
} else {
    $zipData = fh_zip_read($file['tmp_name']);
    if ($zipData) {
        $xml = fh_zip_get($zipData, 'word/document.xml');
    }
}
if ($xml === false || $xml === null) {
    json_out(['ok' => false, 'error' => 'فایل Word قابل باز شدن نیست؛ دوباره از Word ذخیره کنید (Save As → .docx).']);
}

// w:p → newline, w:tab → tab, strip all other tags, decode entities
$text = preg_replace('#</w:p>#u', "\n", (string) $xml);
$text = preg_replace('#<w:tab[^>]*/>#u', "\t", (string) $text);
$text = preg_replace('#<w:br[^>]*/>#u', "\n", (string) $text);
$text = preg_replace('#<[^>]+>#u', '', (string) $text);
$text = html_entity_decode((string) $text, ENT_QUOTES | ENT_XML1, 'UTF-8');
$text = preg_replace("/\n{3,}/", "\n\n", (string) $text);
$text = trim((string) $text);

if (mb_strlen($text) < 40) {
    json_out(['ok' => false, 'error' => 'متن قابل استخراجی در فایل پیدا نشد.']);
}
$text = mb_substr($text, 0, 60000);

// ---- AI extraction ----
if (!AiService::isConfigured()) {
    json_out(['ok' => false, 'error' => 'کلید API هوش مصنوعی تنظیم نشده است — از پنل، بخش «دستیار هوشمند → تنظیمات هوشمند».']);
}

$extractionPrompt = <<<PROMPT
مت زیر، متن یک برنامه غذایی است که قبلاً در فایل Word نوشته شده است.
تمام داده‌های مربوط به برنامه را استخراج کن و اطلاعات فردی مراجع را هم در بیاور.
فقط و فقط یک شیء JSON معتبر با این ساختار برگردان؛ بدون هیچ متن اضافه و بدون markdown:
{
  "patient": { "name": "", "age": "", "height": "", "weight": "", "goal": "کاهش وزن|افزایش وزن|پایداری وزن|", "disease": "", "other": "", "date": "", "fasting": "" },
  "breakfast":  [ { "full": false, "items": ["..."] } ],
  "morning":    [ { "full": false, "items": ["..."] } ],
  "lunch":      [ { "full": false, "items": ["..."] } ],
  "afternoon":  [ { "full": false, "items": ["..."] } ],
  "dinner":     [ { "full": false, "items": ["..."] } ],
  "latenight":  [ { "full": false, "items": ["..."] } ],
  "supplements": ["..."],
  "notes":      [ { "title": "...", "text": "...", "warn": false } ]
}
راهنما:
- هر گزینهٔ وعده = یک خط از برنامه؛ آیتم‌های آن را با معیارهای خانگی جدا کن. اگر گزینه‌ای در متن نبود، آرایه خالی بگذار.
- فیلدهای فردی که در متن نیستند را رشته خالی بگذار؛ حدس نساز.
- هدف را فقط یکی از سه مقدار مجاز بگذار یا خالی.
- اعداد را با ارقام فارسی برگردان.

متن فایل Word:
---
PROMPT;

try {
    $content = AiService::chat(
        'You are a precise data-extraction assistant. Always reply with valid JSON only.',
        $extractionPrompt . "\n" . $text . "\n---",
        0.2
    );
} catch (Throwable $e) {
    json_out(['ok' => false, 'error' => $e->getMessage()]);
}

// tolerant JSON extraction
$plan = null;
$clean = trim(preg_replace('/^```(?:json)?\s*|\s*```$/m', '', (string) $content) ?? '');
$decoded = json_decode($clean, true);
if (is_array($decoded)) {
    $plan = $decoded;
} elseif (preg_match('/\{[\s\S]*\}/', $clean, $m)) {
    $plan = json_decode($m[0], true);
}
if (!is_array($plan)) {
    json_out(['ok' => false, 'error' => 'پاسخ مدل قابل پردازش نبود؛ لطفاً دوباره تلاش کنید.']);
}

json_out(['ok' => true, 'plan' => $plan]);
