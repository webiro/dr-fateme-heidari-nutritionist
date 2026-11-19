<?php
/** Admin dashboard — overview + latest submissions. */
$active = 'dashboard';
$pageTitle = 'پیشخوان';
require __DIR__ . '/partials/layout_start.php';

$stats = [
    'unread' => (int) Database::val('SELECT COUNT(*) FROM fh_submissions WHERE is_read = 0'),
    'total_messages' => (int) Database::val('SELECT COUNT(*) FROM fh_submissions'),
    'posts' => (int) Database::val('SELECT COUNT(*) FROM fh_posts WHERE is_active = 1'),
    'reels' => (int) Database::val('SELECT COUNT(*) FROM fh_reels WHERE is_active = 1'),
    'services' => (int) Database::val('SELECT COUNT(*) FROM fh_services WHERE is_active = 1'),
    'faqs' => (int) Database::val('SELECT COUNT(*) FROM fh_faq WHERE is_active = 1'),
];
$latest = Database::all('SELECT id, name, goal, phone, is_read, created_at FROM fh_submissions ORDER BY id DESC LIMIT 5');
$lastLogin = $adminUser['last_login_at'];
?>
<div class="stat-grid">
  <a class="stat-card<?= $stats['unread'] ? ' warn' : '' ?>" href="messages.php">
    <span class="stat-ico"><span class="material-symbols-rounded">mark_email_unread</span></span>
    <div><b><?= e(fa_digits((string) $stats['unread'])) ?></b><span>درخواست جدید</span></div>
  </a>
  <a class="stat-card" href="posts.php">
    <span class="stat-ico"><span class="material-symbols-rounded">photo_library</span></span>
    <div><b><?= e(fa_digits((string) $stats['posts'])) ?></b><span>پست اسلایدی فعال</span></div>
  </a>
  <a class="stat-card" href="reels.php">
    <span class="stat-ico"><span class="material-symbols-rounded">movie</span></span>
    <div><b><?= e(fa_digits((string) $stats['reels'])) ?></b><span>ریلز فعال</span></div>
  </a>
  <a class="stat-card ok" href="texts.php">
    <span class="stat-ico"><span class="material-symbols-rounded">edit_document</span></span>
    <div><b><?= e(fa_digits((string) ($stats['services'] + $stats['faqs']))) ?></b><span>خدمات و سوالات</span></div>
  </a>
</div>

<div class="card">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead">inbox</span>
    <h2>آخرین درخواست‌های مشاوره</h2>
    <div class="spacer"></div>
    <a class="btn btn-ghost btn-sm" href="messages.php">مشاهده همه</a>
  </div>
  <?php if (!$latest): ?>
  <div class="empty"><span class="material-symbols-rounded">inbox</span>هنوز درخواستی ثبت نشده است. اولین پیام‌ها همین‌جا نمایش داده می‌شوند.</div>
  <?php else: ?>
  <div class="rows">
    <?php foreach ($latest as $msg): ?>
    <div class="row-item">
      <div class="row-body">
        <h3><?= e($msg['name']) ?> <?= !$msg['is_read'] ? '<span class="badge">جدید</span>' : '' ?></h3>
        <p><?= e($msg['goal']) ?> · <?= e($msg['phone']) ?> · <?= e(jalali_datetime($msg['created_at'])) ?></p>
      </div>
      <div class="row-tools">
        <a class="btn btn-ghost btn-sm" href="messages.php#msg-<?= (int) $msg['id'] ?>">مشاهده</a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead">tips_and_updates</span>
    <h2>دسترسی سریع</h2>
  </div>
  <div class="rows">
    <div class="row-item">
      <div class="row-body">
        <h3>تغییر متن‌های صفحه اصلی</h3>
        <p>هدینگ‌ها، معرفی، شعارها و متن فرم رزرو را هر وقت خواستید عوض کنید.</p>
      </div>
      <div class="row-tools"><a class="btn btn-ghost btn-sm" href="texts.php">ویرایش متن‌ها</a></div>
    </div>
    <div class="row-item">
      <div class="row-body">
        <h3>افزودن ریلز جدید</h3>
        <p>آپلود فایل ویدیو، درج لینک مستقیم یا اسکریپت آپارات.</p>
      </div>
      <div class="row-tools"><a class="btn btn-ghost btn-sm" href="reels.php">مدیریت ریلز</a></div>
    </div>
    <div class="row-item">
      <div class="row-body">
        <h3>آدرس و راه‌های ارتباطی</h3>
        <p>آدرس مطب، موقعیت نقشه، شماره تماس و شبکه‌های اجتماعی.</p>
      </div>
      <div class="row-tools"><a class="btn btn-ghost btn-sm" href="settings.php">تنظیمات سایت</a></div>
    </div>
  </div>
  <?php if ($lastLogin): ?>
  <p class="card-note" style="margin-top:12px">آخرین ورود شما: <?= e(jalali_datetime($lastLogin)) ?></p>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/partials/layout_end.php'; ?>
