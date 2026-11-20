<?php
/** Add a new slide post in one flow: title + images (files or direct URL). */
$active = 'posts';
$pageTitle = 'افزودن پست اسلایدی';
require __DIR__ . '/partials/layout_start.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    Csrf::check();
    $title = clean_text($_POST['title'] ?? '', 191);
    $url = clean_text($_POST['url'] ?? '', 600);

    $paths = [];
    try {
        $files = $_FILES['files'] ?? null;
        if ($files && isset($files['name'])) {
            foreach ((array) $files['name'] as $idx => $name) {
                if (($files['error'][$idx] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $paths[] = Uploader::store([
                    'name' => $files['name'][$idx],
                    'type' => $files['type'][$idx],
                    'tmp_name' => $files['tmp_name'][$idx],
                    'error' => $files['error'][$idx],
                    'size' => $files['size'][$idx],
                ], 'image');
            }
        }
        if ($url !== '') {
            if (!is_http_url($url)) {
                throw new RuntimeException('لینک مستقیم تصویر معتبر نیست.');
            }
            $paths[] = $url;
        }
        if (!$paths) {
            throw new RuntimeException('حداقل یک عکس انتخاب کنید یا یک لینک مستقیم بدهید.');
        }

        $max = (int) Database::val('SELECT COALESCE(MAX(sort), 0) FROM fh_posts');
        $postId = Database::insert(
            'INSERT INTO fh_posts (title, sort, is_active) VALUES (?, ?, 1)',
            [$title !== '' ? $title : null, $max + 1]
        );
        foreach ($paths as $i => $src) {
            Database::q('INSERT INTO fh_post_slides (post_id, src, sort) VALUES (?, ?, ?)', [$postId, $src, $i + 1]);
        }
        SiteData::clearCache();
        flash_set('ok', 'پست ساخته شد با ' . fa_digits((string) count($paths)) . ' اسلاید. برای مدیریت اسلایدها ادامه دهید.');
        redirect('post_edit.php?id=' . $postId);
    } catch (RuntimeException $e) {
        flash_set('err', $e->getMessage());
        redirect('post_add.php');
    }
}
?>
<div class="card">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead">add_photo_alternate</span>
    <h2>افزودن پست اسلایدی جدید</h2>
    <div class="spacer"></div>
    <a class="btn btn-ghost btn-sm" href="posts.php">
      <span class="material-symbols-rounded">arrow_forward</span>
      بازگشت به آرشیو
    </a>
  </div>
  <p class="card-note">عکس‌ها را به‌ترتیب انتخاب کنید؛ همان ترتیب در گالری سایت نمایش داده می‌شود. بعداً از «اسلایدها» می‌توانید اضافه/حذف کنید.</p>
  <form method="post" enctype="multipart/form-data" data-dirty>
    <?= Csrf::field() ?>
    <div class="field">
      <label for="title">عنوان (اختیاری، فقط برای خودتان)</label>
      <input class="input" id="title" name="title" placeholder="مثال: پست تغذیه در بارداری">
    </div>
    <div class="field">
      <label for="files">عکس‌های اسلاید (از دستگاه)</label>
      <input class="input" type="file" id="files" name="files[]" accept="image/*" multiple>
      <p class="hint">فرمت‌های مجاز: JPG، PNG، WebP، GIF — تا ۱۵ مگابایت هر عکس.</p>
    </div>
    <div class="field">
      <label for="url">یا لینک مستقیم یک عکس</label>
      <input class="input" type="url" dir="ltr" style="text-align:left" id="url" name="url" placeholder="https://… (اختیاری)">
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit">
        <span class="material-symbols-rounded">add</span>
        ساخت پست
      </button>
      <a class="btn btn-ghost" href="posts.php">انصراف</a>
    </div>
  </form>
</div>
<?php require __DIR__ . '/partials/layout_end.php'; ?>
