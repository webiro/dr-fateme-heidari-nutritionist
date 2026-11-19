<?php
/**
 * Secure file uploads.
 * - MIME sniffed with finfo (never trusts the client name/type)
 * - Extension whitelist per kind (image / video)
 * - Random hex filename, files land under public/uploads/<kind>/
 */

final class Uploader
{
    /** kind => [allowed extensions, allowed mime prefixes, max bytes] */
    private const RULES = [
        'image' => [
            'ext'  => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
            'mime' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
            'max'  => 15 * 1024 * 1024, // 15 MB
        ],
        'video' => [
            'ext'  => ['mp4', 'webm', 'mov', 'm4v', 'ogv'],
            'mime' => ['video/mp4', 'video/webm', 'video/quicktime', 'video/ogg'],
            'max'  => 512 * 1024 * 1024, // 512 MB (raise php.ini limits as needed)
        ],
    ];

    /**
     * Store an uploaded file. Returns the relative web path ("uploads/image/ab12....jpg")
     * or throws RuntimeException with a Persian message.
     */
    public static function store(array $file, string $kind): string
    {
        if (!isset(self::RULES[$kind])) {
            throw new RuntimeException('نوع فایل پشتیبانی نمی‌شود.');
        }
        $rule = self::RULES[$kind];

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException(self::uploadErrorText((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE)));
        }
        if (!is_uploaded_file($file['tmp_name'] ?? '') && !is_file($file['tmp_name'] ?? '')) {
            throw new RuntimeException('فایل دریافتی معتبر نیست.');
        }
        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > $rule['max']) {
            throw new RuntimeException('حجم فایل بیش از حد مجاز است (حداکثر ' . human_size($rule['max']) . ').');
        }

        $mime = '';
        if (function_exists('finfo_open')) {
            $fi = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($fi, $file['tmp_name']);
            finfo_close($fi);
        }
        if ($mime === '' || !in_array($mime, $rule['mime'], true)) {
            throw new RuntimeException('فرمت فایل مجاز نیست.');
        }

        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if (!in_array($ext, $rule['ext'], true)) {
            // derive extension from the sniffed mime instead
            $ext = match ($mime) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/gif' => 'gif',
                'video/mp4' => 'mp4',
                'video/webm' => 'webm',
                'video/quicktime' => 'mov',
                'video/ogg' => 'ogv',
                default => throw new RuntimeException('فرمت فایل مجاز نیست.'),
            };
        }

        $dir = dirname(__DIR__) . '/public/uploads/' . $kind;
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new RuntimeException('ساخت پوشه آپلود ناموفق بود.');
        }

        $name = bin2hex(random_bytes(16)) . '.' . $ext;
        $target = $dir . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $target) && !@rename($file['tmp_name'], $target)) {
            throw new RuntimeException('ذخیره فایل ناموفق بود.');
        }
        @chmod($target, 0644);

        return 'uploads/' . $kind . '/' . $name;
    }

    /** Delete a stored relative path (uploads/<kind>/<file>) if it is inside uploads/. */
    public static function delete(?string $relative): void
    {
        if (!$relative || str_contains($relative, '..') || str_starts_with($relative, 'http')) {
            return;
        }
        $full = dirname(__DIR__) . '/public/' . ltrim($relative, '/');
        $uploadsRoot = realpath(dirname(__DIR__) . '/public/uploads');
        $real = realpath($full);
        if ($real && $uploadsRoot && str_starts_with($real, $uploadsRoot) && is_file($real)) {
            @unlink($real);
        }
    }

    private static function uploadErrorText(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'حجم فایل از محدودیت سرور بیشتر است (upload_max_filesize).',
            UPLOAD_ERR_PARTIAL => 'آپلود ناقص ماند؛ دوباره تلاش کنید.',
            UPLOAD_ERR_NO_FILE => 'هیچ فایلی انتخاب نشده است.',
            UPLOAD_ERR_NO_TMP_DIR => 'پوشه موقت سرور در دسترس نیست.',
            UPLOAD_ERR_CANT_WRITE => 'سرور اجازه نوشتن فایل را نداشت.',
            UPLOAD_ERR_EXTENSION => 'آپلود توسط یک افزونه PHP متوقف شد.',
            default => 'خطای ناشناخته در آپلود.',
        };
    }
}
