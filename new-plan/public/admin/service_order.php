<?php
/** Reorder services — big friendly rows with up/down arrows. */
$active = 'services';
$pageTitle = 'ترتیب نمایش خدمات';
require __DIR__ . '/partials/layout_start.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'move') {
    $id = (int) ($_POST['move_id'] ?? 0);
    $direction = ($_POST['direction'] ?? '') === 'up' ? -1 : 1;
    $row = Database::one('SELECT id, sort FROM fh_services WHERE id = ?', [$id]);
    if ($row) {
        $neighbor = Database::one(
            'SELECT id, sort FROM fh_services WHERE sort ' . ($direction < 0 ? '<' : '>') . ' ? ORDER BY sort ' . ($direction < 0 ? 'DESC' : 'ASC') . ' LIMIT 1',
            [$row['sort']]
        );
        if ($neighbor) {
            Database::q('UPDATE fh_services SET sort = ? WHERE id = ?', [$neighbor['sort'], $row['id']]);
            Database::q('UPDATE fh_services SET sort = ? WHERE id = ?', [$row['sort'], $neighbor['id']]);
            SiteData::clearCache();
        }
    }
    redirect('service_order.php');
}

$items = Database::all('SELECT * FROM fh_services ORDER BY sort ASC, id ASC');
?>
<div class="card">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead">swap_vert</span>
    <h2>ترتیب نمایش خدمات در سایت</h2>
  </div>
  <p class="card-note">ترتیب نمایش از بالا به پایین است. با دکمه‌های ↑ و ↓ جای هر خدمت را عوض کنید؛ تغییر بلافاصله در سایت اعمال می‌شود.</p>
  <?php if (!$items): ?>
  <div class="empty"><span class="material-symbols-rounded">note_stack</span>هنوز خدمتی ثبت نشده است.</div>
  <?php else: ?>
  <div class="rows">
    <?php foreach ($items as $i => $item): ?>
    <div class="row-item">
      <span class="sec-num"><?= e(fa_digits((string) ($i + 1))) ?></span>
      <span class="thumb" style="display:grid;place-items:center">
        <span class="material-symbols-rounded" style="color:var(--primary)"><?= e($item['icon']) ?></span>
      </span>
      <div class="row-body">
        <h3><?= e($item['title']) ?> <?= !$item['is_active'] ? '<span class="badge badge-muted">غیرفعال</span>' : '' ?></h3>
        <p class="multi"><?= e($item['text']) ?></p>
      </div>
      <div class="row-tools">
        <form method="post" style="display:contents">
          <?= Csrf::field() ?>
          <input type="hidden" name="action" value="move">
          <input type="hidden" name="move_id" value="<?= (int) $item['id'] ?>">
          <input type="hidden" name="direction" value="">
          <button class="icon-btn" type="button" data-move="up" data-move-id="<?= (int) $item['id'] ?>" aria-label="بالا" <?= $i === 0 ? 'disabled' : '' ?>>
            <span class="material-symbols-rounded">arrow_upward</span>
          </button>
          <button class="icon-btn" type="button" data-move="down" data-move-id="<?= (int) $item['id'] ?>" aria-label="پایین" <?= $i === count($items) - 1 ? 'disabled' : '' ?>>
            <span class="material-symbols-rounded">arrow_downward</span>
          </button>
        </form>
        <a class="icon-btn" href="service_edit.php?id=<?= (int) $item['id'] ?>" aria-label="ویرایش">
          <span class="material-symbols-rounded">edit</span>
        </a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <div class="form-actions">
    <a class="btn btn-ghost" href="services.php">
      <span class="material-symbols-rounded">arrow_forward</span>
      بازگشت به آرشیو
    </a>
  </div>
</div>
<?php require __DIR__ . '/partials/layout_end.php'; ?>
