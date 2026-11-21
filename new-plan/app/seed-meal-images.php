<?php
/**
 * One-off seed: uploads the platform's default meal images (public/platform/images)
 * through Uploader into public/uploads/image and registers them in the
 * platform settings (fh_settings → platform.mealImages). Idempotent —
 * keys that already have an image are left untouched.
 *
 * Run inside the web container:  php /var/www/html/app/seed-meal-images.php
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit('CLI only.' . PHP_EOL);
}

require_once __DIR__ . '/bootstrap.php';

$map = [
    'breakfast'  => 'chai.png',
    'morning'    => 'mive.png',
    'lunch'      => 'berenj.png',
    'afternoon'  => 'mast.png',
    'dinner'     => 'soop.png',
    'latenight'  => 'shir.png',
    'supplement' => 'mokamel.png',
];

$srcDir = dirname(__DIR__) . '/public/platform/images/';
$platform = SiteData::group('platform');
$mealImages = (array) ($platform['mealImages'] ?? []);

foreach ($map as $key => $file) {
    if (!empty($mealImages[$key])) {
        echo "skip {$key} (already set: {$mealImages[$key]})" . PHP_EOL;
        continue;
    }
    $src = $srcDir . $file;
    if (!is_file($src)) {
        echo "MISSING source for {$key}: {$src}" . PHP_EOL;
        continue;
    }
    $path = Uploader::store([
        'tmp_name' => $src,
        'name'     => 'platform-meal-' . $key . '-' . $file,
        'size'     => filesize($src),
        'error'    => UPLOAD_ERR_OK,
    ], 'image');
    $mealImages[$key] = $path;
    echo "uploaded {$key} → {$path}" . PHP_EOL;
}

$platform['mealImages'] = $mealImages;
SiteData::putGroup('platform', $platform);
echo "platform.mealImages saved (" . count($mealImages) . " keys)" . PHP_EOL;
