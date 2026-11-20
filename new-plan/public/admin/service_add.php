<?php
/** Add a new service. */
$active = 'services';
$pageTitle = 'افزودن خدمت';
$useIconPicker = true;
require __DIR__ . '/partials/layout_start.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    Csrf::check();
    $icon = clean_text($_POST['icon'] ?? 'nutrition', 64);
    $title = clean_text($_POST['title'] ?? '', 191);
    $text = clean_text($_POST['text'] ?? '', 1000);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    if ($title === '') {
        flash_set('err', 'عنوان خدمت نمی‌تواند خالی باشد.');
        redirect('service_add.php');
    }
    if (!preg_match('/^[a-z0-9_]+$/', $icon)) {
        $icon = 'nutrition';
    }
    $max = (int) Database::val('SELECT COALESCE(MAX(sort), 0) FROM fh_services');
    Database::insert(
        'INSERT INTO fh_services (icon, title, text, sort, is_active) VALUES (?, ?, ?, ?, ?)',
        [$icon, $title, $text, $max + 1, $isActive]
    );
    SiteData::clearCache();
    flash_set('ok', 'خدمت جدید اضافه شد. برای تنظیم ترتیب نمایش، صفحه «ترتیب نمایش» را ببینید.');
    redirect('services.php');
}

$service = null;
$isEdit = false;
?>
<div class="card">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead">add_circle</span>
    <h2>افزودن خدمت جدید</h2>
    <div class="spacer"></div>
    <a class="btn btn-ghost btn-sm" href="services.php">
      <span class="material-symbols-rounded">arrow_forward</span>
      بازگشت به آرشیو
    </a>
  </div>
  <p class="card-note">پس از ذخیره، خدمت به انتهای لیست اضافه می‌شود و می‌توانید ترتیبش را تغییر دهید.</p>
  <?php require __DIR__ . '/partials/service_form.php'; ?>
</div>
<?php require __DIR__ . '/partials/layout_end.php'; ?>
