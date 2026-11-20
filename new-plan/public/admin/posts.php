<?php
/** Slide posts — archive table. Add & ordering live on their own pages. */
$active = 'posts';
$pageTitle = 'پست‌های اسلایدی';
require __DIR__ . '/partials/layout_start.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    if ($action === 'toggle' && $id) {
        Database::q('UPDATE fh_posts SET is_active = 1 - is_active WHERE id = ?', [$id]);
        SiteData::clearCache();
        redirect('posts.php');
    }
    if ($action === 'delete' && $id) {
        foreach (Database::all('SELECT src FROM fh_post_slides WHERE post_id = ?', [$id]) as $slide) {
            Uploader::delete($slide['src']);
        }
        Database::q('DELETE FROM fh_posts WHERE id = ?', [$id]);
        SiteData::clearCache();
        flash_set('ok', 'پست و اسلایدهای آن حذف شد.');
        redirect('posts.php');
    }
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
    <span class="material-symbols-rounded icon-lead">photo_library</span>
    <h2>آرشیو پست‌های اسلایدی</h2>
    <span class="badge"><?= e(fa_digits((string) count($items))) ?></span>
    <div class="spacer"></div>
    <a class="btn btn-ghost btn-sm" href="post_order.php">
      <span class="material-symbols-rounded">swap_vert</span>
      ترتیب نمایش
    </a>
    <a class="btn btn-primary btn-sm" href="post_add.php">
      <span class="material-symbols-rounded">add</span>
      افزودن پست
    </a>
  </div>
  <p class="card-note">پست اسلایدی = یک خانه در بخش «اینستاگرام» سایت که روی آن کلیک می‌شود و اسلایدهایش در همان صفحه باز می‌شود.</p>
  <?php if (!$items): ?>
  <div class="empty"><span class="material-symbols-rounded">photo_library</span>هنوز پستی ساخته نشده است. اولین پست را اضافه کنید.</div>
  <?php else: ?>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th>کاور</th>
          <th>عنوان</th>
          <th>اسلایدها</th>
          <th>وضعیت</th>
          <th>عملیات</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $item): ?>
        <tr>
          <td>
            <?php if ($item['thumb'] && !str_starts_with((string) $item['thumb'], 'http')): ?>
            <img class="row-thumb" src="<?= e('../' . $item['thumb']) ?>" alt="" loading="lazy">
            <?php else: ?>
            <span class="row-thumb" style="display:grid;place-items:center">
              <span class="material-symbols-rounded" style="color:var(--muted)">image</span>
            </span>
            <?php endif; ?>
          </td>
          <td class="cell-title"><?= e($item['title'] ?: ('پست #' . fa_digits((string) $item['id']))) ?></td>
          <td><span class="badge badge-muted"><?= e(fa_digits((string) $item['slide_count'])) ?> اسلاید</span></td>
          <td><?= $item['is_active'] ? '<span class="badge badge-ok">فعال</span>' : '<span class="badge badge-muted">غیرفعال</span>' ?></td>
          <td class="cell-actions">
            <a class="btn btn-primary btn-sm" href="post_edit.php?id=<?= (int) $item['id'] ?>">
              <span class="material-symbols-rounded" style="font-size:17px">collections</span>
              اسلایدها
            </a>
            <form method="post">
              <?= Csrf::field() ?>
              <input type="hidden" name="action" value="toggle">
              <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
              <button class="icon-btn" type="submit" aria-label="فعال/غیرفعال">
                <span class="material-symbols-rounded" style="font-size:19px"><?= $item['is_active'] ? 'visibility' : 'visibility_off' ?></span>
              </button>
            </form>
            <form method="post" data-confirm="پست و همه اسلایدهای آن برای همیشه حذف شود؟">
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
