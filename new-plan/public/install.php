<?php
/**
 * One-shot installer: creates tables, seeds the site with the current content,
 * creates the admin user from config.env, then locks itself.
 *
 * Run:  https://domain/install.php  (POST the INSTALL_KEY, or GET with ?key=)
 * After success it refuses to run again unless DELETE key file manually.
 */

require_once __DIR__ . '/bootstrap.php';

$lockFile = dirname(__DIR__) . '/storage/installed.lock';
$alreadyInstalled = is_file($lockFile);

$installKey = (string) Env::get('INSTALL_KEY', '');
$givenKey = (string) ($_REQUEST['key'] ?? '');

$messages = [];
$errors = [];
$done = false;

try {
    Database::pdo();
    $dbOk = true;
} catch (Throwable $e) {
    $dbOk = false;
    $errors[] = 'اتصال به دیتابیس برقرار نشد: ' . $e->getMessage();
}

if ($alreadyInstalled) {
    $errors[] = 'نصب قبلاً انجام شده است. برای نصب مجدد، فایل storage/installed.lock را حذف کنید.';
} elseif ($dbOk && $_SERVER['REQUEST_METHOD'] === 'POST' && $installKey !== '' && hash_equals($installKey, $givenKey)) {
    // --- Run install ---
    $schema = (string) file_get_contents(__DIR__ . '/../app/schema.sql');
    Database::pdo()->exec($schema);
    $messages[] = 'جداول دیتابیس ساخته شد.';

    require_once __DIR__ . '/../app/seed.php';

    // settings groups from defaults
    foreach (['identity', 'links', 'seo', 'headings', 'location', 'sections', 'sectionOrder', 'colors', 'article', 'slogans', 'media', 'ai'] as $group) {
        SiteData::putGroup($group, SiteData::group($group));
    }
    $messages[] = 'تنظیمات پیش‌فرض ثبت شد.';

    // content
    foreach (seed_services() as $i => [$icon, $title, $text]) {
        Database::q('INSERT INTO fh_services (icon, title, text, sort, is_active) VALUES (?, ?, ?, ?, 1)', [$icon, $title, $text, $i + 1]);
    }
    foreach (seed_faq() as $i => [$question, $answer]) {
        Database::q('INSERT INTO fh_faq (question, answer, sort, is_active) VALUES (?, ?, ?, 1)', [$question, $answer, $i + 1]);
    }
    foreach (seed_menu() as $i => [$label, $href, $desktop, $mobile]) {
        Database::q(
            'INSERT INTO fh_menu (label, href, sort, is_active, show_desktop, show_mobile) VALUES (?, ?, ?, 1, ?, ?)',
            [$label, $href, $i + 1, $desktop, $mobile]
        );
    }
    $messages[] = 'محتوای اولیه (خدمات، سوالات، منو) ثبت شد.';

    // sample slide post (same as the previous static site: 7 slides)
    $seedSlides = [];
    for ($i = 1; $i <= 7; $i++) {
        $candidate = __DIR__ . '/uploads/posts/' . $i . '.jpg';
        if (is_file($candidate)) {
            $seedSlides[] = 'uploads/posts/' . $i . '.jpg';
        }
    }
    if ($seedSlides) {
        $postId = Database::insert('INSERT INTO fh_posts (title, sort, is_active) VALUES (?, 1, 1)', ['پست نمونه']);
        foreach ($seedSlides as $i => $src) {
            Database::q('INSERT INTO fh_post_slides (post_id, src, sort) VALUES (?, ?, ?)', [$postId, $src, $i + 1]);
        }
        $messages[] = 'پست اسلایدی نمونه ثبت شد.';
    }

    // admin user
    $adminUser = Env::get('ADMIN_USER', 'admin');
    $adminPass = Env::get('ADMIN_PASS', '');
    if (strlen($adminPass) < 8) {
        $errors[] = 'ADMIN_PASS در config.env حداقل باید ۸ کاراکتر باشد — نصب محتوا انجام شد اما کاربر ادمین ساخته نشد.';
    } else {
        Database::q(
            'INSERT INTO fh_admins (username, password_hash, display_name) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)',
            [mb_strtolower($adminUser), password_hash($adminPass, PASSWORD_DEFAULT), 'دکتر فاطمه حیدری']
        );
        $messages[] = 'کاربر ادمین ساخته شد.';
    }

    // storage dirs
    foreach (['storage/cache', 'storage'] as $dir) {
        $full = dirname(__DIR__) . '/' . $dir;
        if (!is_dir($full)) {
            @mkdir($full, 0755, true);
        }
    }

    @file_put_contents($lockFile, (string) time());
    $done = true;
    $messages[] = 'نصب کامل شد! از این‌جا به بعد این صفحه غیرفعال است.';
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <title>نصب سایت</title>
  <link rel="stylesheet" href="assets/vendor/fonts/site-fonts.css?v=<?= filemtime(__DIR__ . '/assets/vendor/fonts/site-fonts.css') ?>">
  <link rel="stylesheet" href="assets/admin/admin.css?v=<?= filemtime(__DIR__ . '/assets/admin/admin.css') ?>">
</head>
<body class="admin-body">
  <div class="login-wrap">
    <div class="login-card">
      <div class="login-brand">
        <img src="assets/images/logo.svg" alt="">
        <h1>نصب داینامیک سایت</h1>
        <p>یک بار اجرا کنید؛ بعد از نصب این صفحه خودش قفل می‌شود.</p>
      </div>

      <?php if ($errors): ?>
      <?php foreach ($errors as $err): ?>
      <div class="login-error"><span class="material-symbols-rounded">error</span><?= e($err) ?></div>
      <?php endforeach; ?>
      <?php endif; ?>

      <?php if ($messages): ?>
      <?php foreach ($messages as $msg): ?>
      <div class="login-error" style="background:var(--accent-soft);color:var(--accent)">
        <span class="material-symbols-rounded">check_circle</span><?= e($msg) ?>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>

      <?php if ($done): ?>
      <a class="btn btn-primary" href="admin/index.php" style="width:100%">
        <span class="material-symbols-rounded">login</span>
        ورود به پنل مدیریت
      </a>
      <a class="btn btn-ghost" href="index.php" style="width:100%;margin-top:10px">مشاهده سایت</a>
      <?php elseif (!$alreadyInstalled && $dbOk): ?>
      <form method="post">
        <div class="field">
          <label for="key">کلید نصب (INSTALL_KEY از config.env)</label>
          <input class="input" id="key" name="key" required dir="ltr" style="text-align:left" autocomplete="off">
        </div>
        <button class="btn btn-primary" type="submit" style="width:100%">
          <span class="material-symbols-rounded">rocket_launch</span>
          شروع نصب
        </button>
      </form>
      <?php else: ?>
      <a class="btn btn-ghost" href="index.php" style="width:100%">بازگشت</a>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
