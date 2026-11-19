<?php
/** Slide manager for a single post: upload files or add direct URLs, reorder, delete. */
$active = 'posts';
$pageTitle = 'اسلایدهای پست';
require __DIR__ . '/partials/layout_start.php';

$postId = (int) ($_GET['id'] ?? 0);
$post = $postId ? Database::one('SELECT * FROM fh_posts WHERE id = ?', [$postId]) : null;
if (!$post) {
    flash_set('err', 'پست مورد نظر پیدا نشد.');
    redirect('posts.php');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'upload') {
        $newIds = [];
        try {
            $files = $_FILES['files'] ?? null;
            if (!$files || !isset($files['name'])) {
                throw new RuntimeException('هیچ فایلی انتخاب نشده است.');
            }
            $names = (array) $files['name'];
            foreach ($names as $idx => $name) {
                if (($files['error'][$idx] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $single = [
                    'name' => $files['name'][$idx],
                    'type' => $files['type'][$idx],
                    'tmp_name' => $files['tmp_name'][$idx],
                    'error' => $files['error'][$idx],
                    'size' => $files['size'][$idx],
                ];
                $path = Uploader::store($single, 'image');
                $max = (int) Database::val('SELECT COALESCE(MAX(sort), 0) FROM fh_post_slides WHERE post_id = ?', [$postId]);
                $newIds[] = Database::insert(
                    'INSERT INTO fh_post_slides (post_id, src, sort) VALUES (?, ?, ?)',
                    [$postId, $path, $max + 1]
                );
            }
            if (!$newIds) {
                throw new RuntimeException('فایلی برای آپلود پیدا نشد.');
            }
            SiteData::clearCache();
            flash_set('ok', fa_digits((string) count($newIds)) . ' اسلاید اضافه شد.');
        } catch (RuntimeException $e) {
            flash_set('err', $e->getMessage());
        }
        redirect('post_edit.php?id=' . $postId);
    }

    if ($action === 'add_url') {
        $url = clean_text($_POST['url'] ?? '', 600);
        if (!is_http_url($url)) {
            flash_set('err', 'لینک مستقیم تصویر معتبر نیست (باید با http یا https شروع شود).');
            redirect('post_edit.php?id=' . $postId);
        }
        $max = (int) Database::val('SELECT COALESCE(MAX(sort), 0) FROM fh_post_slides WHERE post_id = ?', [$postId]);
        Database::insert('INSERT INTO fh_post_slides (post_id, src, sort) VALUES (?, ?, ?)', [$postId, $url, $max + 1]);
        SiteData::clearCache();
        flash_set('ok', 'اسلاید با لینک مستقیم اضافه شد.');
        redirect('post_edit.php?id=' . $postId);
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $slide = Database::one('SELECT * FROM fh_post_slides WHERE id = ? AND post_id = ?', [$id, $postId]);
        if ($slide) {
            Uploader::delete($slide['src']);
            Database::q('DELETE FROM fh_post_slides WHERE id = ?', [$id]);
            SiteData::clearCache();
            flash_set('ok', 'اسلاید حذف شد.');
        }
        redirect('post_edit.php?id=' . $postId);
    }

    if ($action === 'move') {
        $id = (int) ($_POST['move_id'] ?? 0);
        $direction = $_POST['direction'] === 'up' ? -1 : 1;
        $row = Database::one('SELECT id, sort FROM fh_post_slides WHERE id = ? AND post_id = ?', [$id, $postId]);
        if ($row) {
            $neighbor = Database::one(
                'SELECT id, sort FROM fh_post_slides WHERE post_id = ? AND sort ' . ($direction < 0 ? '<' : '>') . ' ? ORDER BY sort ' . ($direction < 0 ? 'DESC' : 'ASC') . ' LIMIT 1',
                [$postId, $row['sort']]
            );
            if ($neighbor) {
                Database::q('UPDATE fh_post_slides SET sort = ? WHERE id = ?', [$neighbor['sort'], $row['id']]);
                Database::q('UPDATE fh_post_slides SET sort = ? WHERE id = ?', [$row['sort'], $neighbor['id']]);
                SiteData::clearCache();
            }
        }
        redirect('post_edit.php?id=' . $postId);
    }
}

$slides = Database::all('SELECT * FROM fh_post_slides WHERE post_id = ? ORDER BY sort ASC, id ASC', [$postId]);
?>
<div class="card">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead">collections</span>
    <h2>اسلایدهای «<?= e($post['title'] ?: ('پست #' . fa_digits((string) $postId))) ?>»</h2>
    <span class="badge"><?= e(fa_digits((string) count($slides))) ?> اسلاید</span>
    <div class="spacer"></div>
    <a class="btn btn-ghost btn-sm" href="posts.php">بازگشت</a>
  </div>
  <p class="card-note">ترتیب نمایش اسلایدها از راست به چپ است. می‌توانید چند عکس را هم‌زمان انتخاب کنید.</p>

  <form method="post" enctype="multipart/form-data">
    <?= Csrf::field() ?>
    <input type="hidden" name="action" value="upload">
    <div class="field">
      <label for="files">آپلود عکس از دستگاه</label>
      <input class="input" type="file" id="files" name="files[]" accept="image/*" multiple>
      <p class="hint">فرمت‌های مجاز: JPG، PNG، WebP، GIF — تا ۱۵ مگابایت هر عکس.</p>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit">
        <span class="material-symbols-rounded">upload</span>
        آپلود اسلایدها
      </button>
    </div>
  </form>

  <form method="post" style="margin-top:16px">
    <?= Csrf::field() ?>
    <input type="hidden" name="action" value="add_url">
    <div class="form-grid">
      <div class="field">
        <label for="url">یا درج لینک مستقیم عکس</label>
        <input class="input" type="url" dir="ltr" style="text-align:left" id="url" name="url" placeholder="https://...">
      </div>
      <div class="field" style="display:flex;align-items:flex-end">
        <button class="btn btn-ghost" type="submit" style="width:100%">
          <span class="material-symbols-rounded">link</span>
          افزودن لینک
        </button>
      </div>
    </div>
  </form>
</div>

<div class="card">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead">view_carousel</span>
    <h2>اسلایدها</h2>
  </div>
  <?php if (!$slides): ?>
  <div class="empty"><span class="material-symbols-rounded">image_not_supported</span>هنوز اسلایدی ندارد. اولین عکس را آپلود کنید.</div>
  <?php else: ?>
  <div class="slide-grid">
    <?php foreach ($slides as $i => $slide): ?>
    <div class="slide-card">
      <?php $src = media_href($slide['src'], '..'); ?>
      <img src="<?= e($src) ?>" alt="" loading="lazy">
      <div class="slide-tools">
        <span class="slide-order"><?= e(fa_digits((string) ($i + 1))) ?></span>
        <div style="display:flex;gap:4px">
          <form method="post" style="display:contents">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="move">
            <input type="hidden" name="move_id" value="<?= (int) $slide['id'] ?>">
            <input type="hidden" name="direction" value="">
            <button class="icon-btn" type="button" data-move="up" data-move-id="<?= (int) $slide['id'] ?>" aria-label="جابه‌جایی به راست" <?= $i === 0 ? 'disabled' : '' ?> style="width:36px;height:36px">
              <span class="material-symbols-rounded" style="font-size:18px">arrow_forward</span>
            </button>
            <button class="icon-btn" type="button" data-move="down" data-move-id="<?= (int) $slide['id'] ?>" aria-label="جابه‌جایی به چپ" <?= $i === count($slides) - 1 ? 'disabled' : '' ?> style="width:36px;height:36px">
              <span class="material-symbols-rounded" style="font-size:18px">arrow_back</span>
            </button>
          </form>
          <form method="post" data-confirm="این اسلاید حذف شود؟">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int) $slide['id'] ?>">
            <button class="icon-btn" type="submit" aria-label="حذف" style="color:var(--danger);width:36px;height:36px">
              <span class="material-symbols-rounded" style="font-size:18px">delete</span>
            </button>
          </form>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/partials/layout_end.php'; ?>
