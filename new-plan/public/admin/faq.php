<?php
/** FAQ — archive table. */
$active = 'faq';
$pageTitle = 'سوالات متداول';
require __DIR__ . '/partials/layout_start.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    if ($action === 'delete' && $id) {
        Database::q('DELETE FROM fh_faq WHERE id = ?', [$id]);
        SiteData::clearCache();
        flash_set('ok', 'سوال حذف شد.');
        redirect('faq.php');
    }
    if ($action === 'toggle' && $id) {
        Database::q('UPDATE fh_faq SET is_active = 1 - is_active WHERE id = ?', [$id]);
        SiteData::clearCache();
        redirect('faq.php');
    }
}

$items = Database::all('SELECT * FROM fh_faq ORDER BY sort ASC, id ASC');
?>
<div class="card">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead">quiz</span>
    <h2>آرشیو سوالات متداول</h2>
    <span class="badge"><?= e(fa_digits((string) count($items))) ?></span>
    <div class="spacer"></div>
    <a class="btn btn-ghost btn-sm" href="faq_order.php">
      <span class="material-symbols-rounded">swap_vert</span>
      ترتیب نمایش
    </a>
    <a class="btn btn-primary btn-sm" href="faq_add.php">
      <span class="material-symbols-rounded">add</span>
      افزودن سوال
    </a>
  </div>
  <?php if (!$items): ?>
  <div class="empty"><span class="material-symbols-rounded">help</span>هنوز سوالی ثبت نشده است. اولین سوال را اضافه کنید.</div>
  <?php else: ?>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th>سوال</th>
          <th>پاسخ</th>
          <th>وضعیت</th>
          <th>عملیات</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $item): ?>
        <tr>
          <td class="cell-title" style="max-width:220px"><?= e($item['question']) ?></td>
          <td class="cell-muted" title="<?= e($item['answer']) ?>"><?= e($item['answer']) ?></td>
          <td><?= $item['is_active'] ? '<span class="badge badge-ok">فعال</span>' : '<span class="badge badge-muted">غیرفعال</span>' ?></td>
          <td class="cell-actions">
            <a class="icon-btn" href="faq_edit.php?id=<?= (int) $item['id'] ?>" aria-label="ویرایش">
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
            <form method="post" data-confirm="این سوال حذف شود؟">
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
