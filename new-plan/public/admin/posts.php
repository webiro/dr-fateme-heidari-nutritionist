<?php
/** Slide posts — list & create & delete & reorder. Slides are managed in post_edit.php. */
$active = 'posts';
$pageTitle = 'پست‌های اسلایدی';
require __DIR__ . '/partials/layout_start.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title = clean_text($_POST['title'] ?? '', 191);
        $max = (int) Database::val('SELECT COALESCE(MAX(sort), 0) FROM fh_posts');
        $id = Database::insert(
            'INSERT INTO fh_posts (title, sort, is_active) VALUES (?, ?, 1)',
            [$title !== '' ? $title : null, $max + 1]
        );
        SiteData::clearCache();
        flash_set('ok', 'پست ساخته شد؛ حالا اسلایدهای آن را اضافه کنید.');
        redirect('post_edit.php?id=' . $id);
    }

    if ($action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        Database::q('UPDATE fh_posts SET is_active = 1 - is_active WHERE id = ?', [$id]);
        SiteData::clearCache();
        redirect('posts.php');
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        // remove uploaded slide files
        foreach (Database::all('SELECT src FROM fh_post_slides WHERE post_id = ?', [$id]) as $slide) {
            Uploader::delete($slide['src']);
        }
        Database::q('DELETE FROM fh_posts WHERE id = ?', [$id]);
        SiteData::clearCache();
        flash_set('ok', 'پست و اسلایدهای آن حذف شد.');
        redirect('posts.php');
    }

    if ($action === 'move') {
        $id = (int) ($_POST['move_id'] ?? 0);
        $direction = $_POST['direction'] === 'up' ? -1 : 1;
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
    <span class="material-symbols-rounded icon-lead">add_photo_alternate</span>
    <h2>پست جدید</h2>
  </div>
  <p class="card-note">پست اسلایدی = یک خانه در بخش «اینستاگرام» سایت که روی آن کلیک می‌شود و اسلایدهایش در همان صفحه باز می‌شود.</p>
  <form method="post">
    <?= Csrf::field() ?>
    <input type="hidden" name="action" value="create">
    <div class="form-grid">
      <div class="field">
        <label for="title">عنوان (اختیاری، فقط برای خودتان)</label>
        <input class="input" id="title" name="title" placeholder="مثال: پست تغذیه در بارداری">
      </div>
      <div class="field" style="display:flex;align-items:flex-end">
        <button class="btn btn-primary" type="submit" style="width:100%">
          <span class="material-symbols-rounded">add</span>
          ساخت پست و رفتن به اسلایدها
        </button>
      </div>
    </div>
  </form>
</div>

<div class="card">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead">list</span>
    <h2>پست‌ها</h2>
    <span class="badge"><?= e(fa_digits((string) count($items))) ?></span>
  </div>
  <?php if (!$items): ?>
  <div class="empty"><span class="material-symbols-rounded">photo_library</span>هنوز پستی ساخته نشده است. از فرم بالا اولین پست را بسازید.</div>
  <?php else: ?>
  <div class="rows">
    <?php foreach ($items as $i => $item): ?>
    <div class="row-item">
      <?php $thumb = $item['thumb'] ? media_href($item['thumb'], '..') : null; ?>
      <?php if ($thumb && preg_match('#^https?://#i', (string) $item['thumb']) === 0): ?>
      <img class="thumb" src="<?= e($thumb) ?>" alt="" loading="lazy">
      <?php else: ?>
      <span class="thumb" style="display:grid;place-items:center">
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
          <button class="icon-btn" type="button" data-move="up" data-move-id="<?= (int) $item['id'] ?>" aria-label="انتقال به بالا" <?= $i === 0 ? 'disabled' : '' ?>>
            <span class="material-symbols-rounded">arrow_upward</span>
          </button>
          <button class="icon-btn" type="button" data-move="down" data-move-id="<?= (int) $item['id'] ?>" aria-label="انتقال به پایین" <?= $i === count($items) - 1 ? 'disabled' : '' ?>>
            <span class="material-symbols-rounded">arrow_downward</span>
          </button>
        </form>
        <form method="post" style="display:contents">
          <?= Csrf::field() ?>
          <input type="hidden" name="action" value="toggle">
          <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
          <button class="icon-btn" type="submit" aria-label="فعال/غیرفعال">
            <span class="material-symbols-rounded"><?= $item['is_active'] ? 'visibility' : 'visibility_off' ?></span>
          </button>
        </form>
        <a class="btn btn-primary btn-sm" href="post_edit.php?id=<?= (int) $item['id'] ?>">
          <span class="material-symbols-rounded">collections</span>
          اسلایدها
        </a>
        <form method="post" data-confirm="پست و همه اسلایدهای آن برای همیشه حذف شود؟">
          <?= Csrf::field() ?>
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
          <button class="icon-btn" type="submit" aria-label="حذف" style="color:var(--danger)">
            <span class="material-symbols-rounded">delete</span>
          </button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/partials/layout_end.php'; ?>
