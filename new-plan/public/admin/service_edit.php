<?php
/** Edit an existing service. */
$active = 'services';
$pageTitle = 'ویرایش خدمت';
$useIconPicker = true;
require __DIR__ . '/partials/layout_start.php';

$id = (int) ($_GET['id'] ?? 0);
$service = $id ? Database::one('SELECT * FROM fh_services WHERE id = ?', [$id]) : null;
if (!$service) {
    flash_set('err', 'خدمت مورد نظر پیدا نشد.');
    redirect('services.php');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    Csrf::check();
    $icon = clean_text($_POST['icon'] ?? 'nutrition', 64);
    $title = clean_text($_POST['title'] ?? '', 191);
    $text = clean_text($_POST['text'] ?? '', 1000);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    if ($title === '') {
        flash_set('err', 'عنوان خدمت نمی‌تواند خالی باشد.');
        redirect('service_edit.php?id=' . $id);
    }
    if (!preg_match('/^[a-z0-9_]+$/', $icon)) {
        $icon = 'nutrition';
    }
    Database::q(
        'UPDATE fh_services SET icon = ?, title = ?, text = ?, is_active = ? WHERE id = ?',
        [$icon, $title, $text, $isActive, $id]
    );
    SiteData::clearCache();
    flash_set('ok', 'خدمت ویرایش شد.');
    redirect('services.php');
}

$isEdit = true;
?>
<div class="card">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead">edit_circle</span>
    <h2>ویرایش خدمت: <?= e($service['title']) ?></h2>
    <div class="spacer"></div>
    <a class="btn btn-ghost btn-sm" href="services.php">
      <span class="material-symbols-rounded">arrow_forward</span>
      بازگشت به آرشیو
    </a>
  </div>
  <?php require __DIR__ . '/partials/service_form.php'; ?>
</div>
<?php require __DIR__ . '/partials/layout_end.php'; ?>
