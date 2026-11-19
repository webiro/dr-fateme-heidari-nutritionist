<?php
/** QR digital card page — same frontend as the static qrcode.html, data from the database. */

require_once __DIR__ . '/bootstrap.php';

try {
    Database::pdo();
} catch (Throwable $e) {
    require __DIR__ . '/install.php';
    exit;
}

$config = SiteData::config();
$identity = $config['doctor'];
$seo = $config['seo'];
$V = SiteData::version();

function json_block_qr(string $id, array $value): string
{
    $body = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    $body = str_replace('</', '<\\/', (string) $body);
    return "  <script type=\"application/json\" id=\"{$id}\">\n{$body}\n  </script>";
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>کارت دیجیتال | <?= e($identity['nameFa']) ?></title>
  <meta name="description" content="<?= e($seo['description']) ?>">
  <meta name="robots" content="noindex,follow">
  <link rel="icon" href="assets/images/logo.svg" type="image/svg+xml">
  <link rel="stylesheet" href="assets/vendor/fonts/site-fonts.css?v=<?= e($V) ?>">
  <link rel="stylesheet" href="assets/app.css?v=<?= e($V) ?>" id="app-css">
</head>
<body data-page="qr" class="theme-v1">
  <div id="loader" role="status">
    <div class="loader-mark">
      <div class="loader-mono">
        <img src="assets/images/logo.svg" alt="" width="42" height="42">
      </div>
    </div>
  </div>

  <main class="qr-page">
    <article class="qr-card">
      <div class="qr-photo">
        <img src="assets/images/doctor.jpg" alt="<?= e($identity['nameFa']) ?>" width="148" height="148">
      </div>
      <h1><?= e($identity['nameFa']) ?></h1>
      <p class="qr-en" lang="en" dir="ltr"><?= e($identity['nameEn']) ?></p>
      <p class="qr-role"><?= e($identity['title']) ?></p>
      <div class="qr-actions" id="qr-actions"></div>
      <div class="qr-location" id="qr-location" hidden></div>
      <button class="theme-btn qr-theme" type="button" data-theme-toggle aria-label="تغییر زمینه">
        <span class="material-symbols-rounded">dark_mode</span>
      </button>
    </article>
  </main>

  <script src="assets/app.js?v=<?= e($V) ?>" defer></script>
  <script>
    window.addEventListener("load", () => {
      const l = document.getElementById("loader");
      if (l) { l.classList.add("is-gone"); setTimeout(() => l.remove(), 600); }
    });
  </script>
<?= json_block_qr('site-config', $config) . "\n" . json_block_qr('site-slogans', SiteData::slogans()) . "\n" . json_block_qr('site-media', SiteData::media()) ?>
</body>
</html>
