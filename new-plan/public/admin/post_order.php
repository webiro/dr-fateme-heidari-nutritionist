<?php
/** Reorder slide posts. */
$active = 'posts';
$pageTitle = 'ترتیب نمایش پست‌ها';
require __DIR__ . '/partials/layout_start.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'move') {
    $id = (int) ($_POST['move_id'] ?? 0);
    $direction = ($_POST['direction'] ?? '') === 'up' ? -1 : 1;
    $row = Database::one('SELECT id, sort FROM fh_posts WHERE id = ?', [$id]);
    if ($row) {
        $neighbor = Database::one(
            'SELECT id, sort FROM fh_posts WHERE sort ' . ($direction < 0 ? '<' : '>') . ' ? ORDER BY sort ' . ($direction < 0 ? 'DESC' : 'ASC') . ' LIMIT 1',
            [$row['sort']]
        );
        if ($neighbor) {
            Database::q('UPDATE fh_posts SET sort = ? WHERE id = ?', [$neighbor['sort'], $row['id']]);
            Database::q('UPDATE fh_posts SET sort = ? WHERE id = ?', [$row['sort'], $neighbor['id']]);
            SiteData::clearCache();
        }
    }
    redirect('post_order.php');
}

$items = Database::all(
    'SELECT p.*, (SELECT COUNT(*) FROM fh_post_slides s WHERE s.post_id = p.id) AS slide_count,
            (SELECT s2.src FROM fh_post_slides s2 WHERE s2.post_id = p.id ORDER BY s2.sort ASC, s2.id ASC LIMIT 1) AS thumb
     FROM fh_posts p
     ORDER BY p.sort ASC, p.id ASC'
);
?>
<div class="card">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead">swap_vert</span>
    <h2>ترتیب نمایش پست‌ها در گالری سایت</h2>
  </div>
  <?php if (!$items): ?>
  <div class="empty"><span class="material-symbols-rounded">photo_library</span>هنوز پستی ساخته نشده است.</div>
  <?php else: ?>
  <div class="rows">
    <?php foreach ($items as $i => $item): ?>
    <div class="row-item">
      <span class="sec-num"><?= e(fa_digits((string) ($i + 1))) ?></span>
      <?php if ($item['thumb'] && !str_starts_with((string) $item['thumb'], 'http')): ?>
      <img class="row-thumb" src="<?= e('../' . $item['thumb']) ?>" alt="" loading="lazy">
      <?php else: ?>
      <span class="row-thumb" style="display:grid;place-items:center">
        <span class="material-symbols-rounded" style="color:var(--muted)">image</span>
      </span>
      <?php endif; ?>
      <div class="row-body">
        <h3><?= e($item['title'] ?: ('پست #' . fa_digits((string) $item['id']))) ?> <?= !$item['is_active'] ? '<span class="badge badge-muted">غیرفعال</span>' : '' ?></h3>
        <p><?= e(fa_digits((string) $item['slide_count'])) ?> اسلاید</p>
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
        <a class="btn btn-primary btn-sm" href="post_edit.php?id=<?= (int) $item['id'] ?>">
          <span class="material-symbols-rounded" style="font-size:17px">collections</span>
          اسلایدها
        </a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <div class="form-actions">
    <a class="btn btn-ghost" href="posts.php">
      <span class="material-symbols-rounded">arrow_forward</span>
      بازگشت به آرشیو
    </a>
  </div>
</div>
<?php require __DIR__ . '/partials/layout_end.php'; ?>
