<?php
/** Services — archive table. Add/edit/order live on their own pages. */
$active = 'services';
$pageTitle = 'خدمات';
$useIconPicker = true;
require __DIR__ . '/partials/layout_start.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    if ($action === 'delete' && $id) {
        Database::q('DELETE FROM fh_services WHERE id = ?', [$id]);
        SiteData::clearCache();
        flash_set('ok', 'خدمت حذف شد.');
        redirect('services.php');
    }
    if ($action === 'toggle' && $id) {
        Database::q('UPDATE fh_services SET is_active = 1 - is_active WHERE id = ?', [$id]);
        SiteData::clearCache();
        redirect('services.php');
    }
}

$items = Database::all('SELECT * FROM fh_services ORDER BY sort ASC, id ASC');
?>
<div class="card">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead">medical_information</span>
    <h2>آرشیو خدمات</h2>
    <span class="badge"><?= e(fa_digits((string) count($items))) ?></span>
    <div class="spacer"></div>
    <a class="btn btn-ghost btn-sm" href="service_order.php">
      <span class="material-symbols-rounded">swap_vert</span>
      ترتیب نمایش
    </a>
    <a class="btn btn-primary btn-sm" href="service_add.php">
      <span class="material-symbols-rounded">add</span>
      افزودن خدمت
    </a>
  </div>
  <p class="card-note">خدمات فعال به‌ترتیب در بخش «خدمات تغذیه و رژیم غذایی» صفحه اصلی نمایش داده می‌شوند.</p>
  <?php if (!$items): ?>
  <div class="empty"><span class="material-symbols-rounded">note_stack</span>هنوز خدمتی ثبت نشده است. اولین خدمت را اضافه کنید.</div>
  <?php else: ?>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th>آیکون</th>
          <th>عنوان</th>
          <th>توضیح</th>
          <th>وضعیت</th>
          <th>عملیات</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $item): ?>
        <tr>
          <td><span class="material-symbols-rounded" style="color:var(--primary)"><?= e($item['icon']) ?></span></td>
          <td class="cell-title"><?= e($item['title']) ?></td>
          <td class="cell-muted" title="<?= e($item['text']) ?>"><?= e($item['text']) ?></td>
          <td><?= $item['is_active'] ? '<span class="badge badge-ok">فعال</span>' : '<span class="badge badge-muted">غیرفعال</span>' ?></td>
          <td class="cell-actions">
            <a class="icon-btn" href="service_edit.php?id=<?= (int) $item['id'] ?>" aria-label="ویرایش">
              <span class="material-symbols-rounded" style="font-size:19px">edit</span>
            </a>
            <form method="post">
              <?= Csrf::field() ?>
              <input type="hidden" name="action" value="toggle">
              <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
              <button class="icon-btn" type="submit" aria-label="فعال/غیرفعال">
                <span class="material-symbols-rounded" style="font-size:19px"><?= $item['is_active'] ? 'visibility' : 'visibility_off' ?></span>
              </button>
            </form>
            <form method="post" data-confirm="این خدمت حذف شود؟">
              <?= Csrf::field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
              <button class="icon-btn" type="submit" aria-label="حذف" style="color:var(--danger)">
                <span class="material-symbols-rounded" style="font-size:19px">delete</span>
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/partials/layout_end.php'; ?>
