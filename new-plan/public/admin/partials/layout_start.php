<?php
/**
 * Admin layout — start.
 * Expects (optional): $pageTitle (string), $active (string menu key).
 * Page must already have: bootstrap + Auth::requireAdmin() + Csrf::check() for POST.
 */

require_once dirname(__DIR__, 2) . '/bootstrap.php';
send_security_headers();

if (!defined('FH_ADMIN')) {
    define('FH_ADMIN', true);
}
Auth::requireAdmin();

$adminUser = Auth::user();
$active = $active ?? '';
$pageTitle = $pageTitle ?? 'پنل مدیریت';

$unreadCount = 0;
try {
    $unreadCount = (int) Database::val('SELECT COUNT(*) FROM fh_submissions WHERE is_read = 0');
} catch (Throwable $e) {
    $unreadCount = 0;
}
$aiKeyMissing = false;
try {
    $aiKeyMissing = (string) SiteData::group('ai')['apiKey'] === '';
} catch (Throwable $e) {
    $aiKeyMissing = false;
}

/** key => [href, label, icon] */
$navItems = [
    'dashboard' => ['dashboard.php', 'پیشخوان', 'dashboard'],
    'messages' => ['messages.php', 'درخواست‌های مشاوره', 'mark_email_unread'],
    'texts' => ['texts.php', 'متن‌ها و معرفی', 'format_h1'],
    'sections' => ['sections.php', 'ترتیب سکشن‌ها', 'view_agenda'],
    'services' => ['services.php', 'خدمات', 'medical_information'],
    'faq' => ['faq.php', 'سوالات متداول', 'quiz'],
    'menu' => ['menu.php', 'منوی سایت', 'menu_open'],
    'posts' => ['posts.php', 'پست‌های اسلایدی', 'photo_library'],
    'reels' => ['reels.php', 'ریلزها', 'movie'],
    'seo' => ['seo.php', 'متاتگ‌ها و سوشال', 'query_stats'],
    'seo_content' => ['seo_content.php', 'محتوای سئو', 'article'],
    'settings' => ['settings.php', 'اطلاعات و تماس', 'contact_page'],
    'location' => ['location.php', 'آدرس مطب', 'location_on'],
    'ai' => ['ai.php', 'تنظیمات هوشمند', 'neurology'],
    'ai_ideas' => ['ai_ideas.php', 'آرشیو ایده‌ها', 'lightbulb'],
    'platform_settings' => ['platform.php', 'تنظیمات پلتفرم', 'restaurant_menu'],
    'diet_history' => ['diet_history.php', 'سابقه رژیم‌ها', 'history'],
    'platform_open' => ['../platform/index.php', 'ورود به پلتفرم تغذیه', 'launch'],
    'account' => ['account.php', 'حساب کاربری', 'person'],
];

$navGroups = [
    ['label' => '', 'keys' => ['dashboard', 'messages']],
    ['label' => 'محتوای صفحه اصلی', 'keys' => ['texts', 'sections', 'services', 'faq', 'menu']],
    ['label' => 'رسانه‌ها', 'keys' => ['posts', 'reels']],
    ['label' => 'سئو و ارتباطات', 'keys' => ['seo', 'seo_content', 'settings', 'location']],
    ['label' => 'دستیار هوشمند', 'keys' => ['ai', 'ai_ideas']],
    ['label' => 'پلتفرم تغذیه', 'keys' => ['platform_settings', 'platform_open']],
    ['label' => '', 'keys' => ['account']],
];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl" data-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <title><?= e($pageTitle) ?> — پنل مدیریت</title>
  <link rel="icon" href="../assets/images/logo.svg" type="image/svg+xml">
  <link rel="stylesheet" href="../assets/vendor/fonts/site-fonts.css?v=<?= filemtime(dirname(__DIR__, 2) . '/assets/vendor/fonts/site-fonts.css') ?>">
  <link rel="stylesheet" href="../assets/admin/admin.css?v=<?= filemtime(dirname(__DIR__, 2) . '/assets/admin/admin.css') ?>">
</head>
<body class="admin-body">
<div class="admin-shell">
  <input type="checkbox" id="sidebar-toggle" class="sidebar-ctl" aria-hidden="true">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <img src="../assets/images/logo.svg" alt="" width="40" height="40">
      <div>
        <strong>پنل مدیریت</strong>
        <span><?= e($adminUser['display_name'] ?: $adminUser['username']) ?></span>
      </div>
    </div>
    <nav class="sidebar-nav">
      <?php foreach ($navGroups as $group): ?>
        <?php if ($group['label']): ?>
        <div class="nav-group-label"><?= e($group['label']) ?></div>
        <?php endif; ?>
        <div class="nav-group">
          <?php foreach ($group['keys'] as $key): [$href, $label, $icon] = $navItems[$key]; ?>
          <a class="side-link<?= $active === $key ? ' is-active' : '' ?>" href="<?= e($href) ?>"<?= $key === 'platform_open' ? ' target="_blank" rel="noopener"' : '' ?>>
            <span class="material-symbols-rounded"><?= $icon ?></span>
            <span><?= e($label) ?></span>
            <?php if ($key === 'messages' && $unreadCount > 0): ?>
            <span class="side-badge"><?= e(fa_digits((string) $unreadCount)) ?></span>
            <?php elseif ($key === 'ai' && $aiKeyMissing): ?>
            <span class="side-dot" title="کلید API هوش مصنوعی تنظیم نشده"></span>
            <?php endif; ?>
          </a>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-foot">
      <a class="side-link" href="../index.php" target="_blank" rel="noopener">
        <span class="material-symbols-rounded">open_in_new</span><span>مشاهده سایت</span>
      </a>
      <a class="side-link" href="logout.php">
        <span class="material-symbols-rounded">logout</span><span>خروج</span>
      </a>
    </div>
  </aside>
  <label class="sidebar-scrim" for="sidebar-toggle" aria-hidden="true"></label>

  <div class="admin-main">
    <header class="admin-topbar">
      <label class="icon-btn menu-trigger" for="sidebar-toggle" aria-label="باز کردن منو" role="button" tabindex="0">
        <span class="material-symbols-rounded">menu</span>
      </label>
      <h1 class="topbar-title"><?= e($pageTitle) ?></h1>
      <div class="topbar-actions">
        <button class="icon-btn" type="button" data-admin-theme aria-label="تغییر تم">
          <span class="material-symbols-rounded">dark_mode</span>
        </button>
        <a class="icon-btn" href="../index.php" target="_blank" rel="noopener" aria-label="مشاهده سایت">
          <span class="material-symbols-rounded">visibility</span>
        </a>
      </div>
    </header>
    <main class="admin-content">
      <?php $flash = flash_take(); ?>
      <?php if ($flash): ?>
      <div class="toast toast-<?= e($flash['type']) ?>" data-toast><?= e($flash['message']) ?>
        <button type="button" class="toast-close" aria-label="بستن">&times;</button>
      </div>
      <?php endif; ?>
