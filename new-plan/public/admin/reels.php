<?php
/** Reels manager — upload file / direct link / Aparat embed. */
$active = 'reels';
$pageTitle = 'ریلزها';
require __DIR__ . '/partials/layout_start.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title = clean_text($_POST['title'] ?? '', 191);
        $type = $_POST['source_type'] ?? 'file';
        try {
            if ($type === 'file') {
                if (empty($_FILES['file']['name'])) {
                    throw new RuntimeException('ویدیویی انتخاب نشده است.');
                }
                $src = Uploader::store($_FILES['file'], 'video');
            } elseif ($type === 'url') {
                $src = clean_text($_POST['url'] ?? '', 600);
                if (!is_http_url($src)) {
                    throw new RuntimeException('لینک مستقیم ویدیو معتبر نیست.');
                }
            } elseif ($type === 'embed') {
                $src = trim((string) ($_POST['embed'] ?? ''));
                // accept the raw Aparat iframe/script snippet; strip dangerous tags
                if ($src === '') {
                    throw new RuntimeException('کد embed خالی است.');
                }
                $allowed = '#<(iframe|script|div|blockquote|p|span|br|strong|b)\b#i';
                if (!preg_match($allowed, $src)) {
                    throw new RuntimeException('کد وارد شده شبیه embed معتبر نیست (iframe یا script).');
                }
                $src = strip_tags($src, '<iframe><script><div><blockquote><p><span><br><strong><b>');
                // Only allow safe attributes inside iframe/script
                if (preg_match_all('#<(iframe|script)\b([^>]*)>#i', $src, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $m) {
                        $attrs = $m[2];
                        if (preg_match('#\s(on\w+)\s*=#i', $attrs)) {
                            throw new RuntimeException('کد embed شامل رویدادهای جاوااسکریپتی ناخواسته است.');
                        }
                    }
                }
                if (strlen($src) > 4000) {
                    throw new RuntimeException('کد embed خیلی طولانی است.');
                }
            } else {
                throw new RuntimeException('نوع منبع نامعتبر است.');
            }

            $max = (int) Database::val('SELECT COALESCE(MAX(sort), 0) FROM fh_reels');
            Database::insert(
                'INSERT INTO fh_reels (title, source_type, source, sort, is_active) VALUES (?, ?, ?, ?, 1)',
                [$title !== '' ? $title : null, $type, $src, $max + 1]
            );
            SiteData::clearCache();
            flash_set('ok', 'ریلز جدید اضافه شد.');
        } catch (RuntimeException $e) {
            flash_set('err', $e->getMessage());
        }
        redirect('reels.php');
    }

    if ($action === 'toggle') {
        Database::q('UPDATE fh_reels SET is_active = 1 - is_active WHERE id = ?', [(int) ($_POST['id'] ?? 0)]);
        SiteData::clearCache();
        redirect('reels.php');
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $reel = Database::one('SELECT * FROM fh_reels WHERE id = ?', [$id]);
        if ($reel) {
            if ($reel['source_type'] === 'file') {
                Uploader::delete($reel['source']);
            }
            Database::q('DELETE FROM fh_reels WHERE id = ?', [$id]);
            SiteData::clearCache();
            flash_set('ok', 'ریلز حذف شد.');
        }
        redirect('reels.php');
    }

    if ($action === 'move') {
        $id = (int) ($_POST['move_id'] ?? 0);
        $direction = $_POST['direction'] === 'up' ? -1 : 1;
        $row = Database::one('SELECT id, sort FROM fh_reels WHERE id = ?', [$id]);
        if ($row) {
            $neighbor = Database::one(
                'SELECT id, sort FROM fh_reels WHERE sort ' . ($direction < 0 ? '<' : '>') . ' ? ORDER BY sort ' . ($direction < 0 ? 'DESC' : 'ASC') . ' LIMIT 1',
                [$row['sort']]
            );
            if ($neighbor) {
                Database::q('UPDATE fh_reels SET sort = ? WHERE id = ?', [$neighbor['sort'], $row['id']]);
                Database::q('UPDATE fh_reels SET sort = ? WHERE id = ?', [$row['sort'], $neighbor['id']]);
                SiteData::clearCache();
            }
        }
        redirect('reels.php');
    }
}

$items = Database::all('SELECT * FROM fh_reels ORDER BY sort ASC, id ASC');
$typeLabels = ['file' => 'فایل', 'url' => 'لینک مستقیم', 'embed' => 'آپارات'];
?>
<div class="card">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead">movie</span>
    <h2>افزودن ریلز جدید</h2>
  </div>
  <form method="post" enctype="multipart/form-data" data-dirty>
    <?= Csrf::field() ?>
    <input type="hidden" name="action" value="create">
    <div class="field">
      <label for="title">عنوان (اختیاری، فقط برای خودتان)</label>
      <input class="input" id="title" name="title" placeholder="مثال: نکات تغذیه در رمضان">
    </div>
    <div class="field">
      <label>منبع ویدیو</label>
      <div class="seg">
        <span><input type="radio" name="source_type" value="file" id="rt-file" data-reel-type checked><label for="rt-file"><span class="material-symbols-rounded" style="font-size:18px">upload_file</span>آپلود فایل</label></span>
        <span><input type="radio" name="source_type" value="url" id="rt-url" data-reel-type><label for="rt-url"><span class="material-symbols-rounded" style="font-size:18px">link</span>لینک مستقیم</label></span>
        <span><input type="radio" name="source_type" value="embed" id="rt-embed" data-reel-type><label for="rt-embed"><span class="material-symbols-rounded" style="font-size:18px">code</span>اسکریپت آپارات</label></span>
      </div>
    </div>

    <div data-reel-panel="file">
      <div class="field">
        <label for="file">فایل ویدیو</label>
        <input class="input" type="file" id="file" name="file" accept="video/mp4,video/webm,video/quicktime">
        <p class="hint">فرمت‌های مجاز: MP4، WebM، MOV — نسخه عمودی (۹:۱۶) بهترین نمایش را دارد. حجم زیاد؟ از لینک مستقیم یا آپارات استفاده کنید.</p>
      </div>
    </div>

    <div data-reel-panel="url" hidden>
      <div class="field">
        <label for="url">لینک مستقیم ویدیو</label>
        <input class="input" type="url" dir="ltr" style="text-align:left" id="url" name="url" placeholder="https://example.com/video.mp4">
        <p class="hint">لینک باید مستقیم به فایل ویدیو (mp4/webm) ختم شود.</p>
      </div>
    </div>

    <div data-reel-panel="embed" hidden>
      <div class="field">
        <label for="embed">کد embed آپارات</label>
        <textarea class="textarea" dir="ltr" style="text-align:left" id="embed" name="embed" placeholder="<iframe src=&quot;https://www.aparat.com/video/video/embed/videohash/xxxx/vt/frame&quot; ...></iframe>"></textarea>
        <p class="hint">کد کامل embed را از صفحه ویدیو در آپارات کپی کنید (iframe یا script).</p>
      </div>
    </div>

    <div class="form-actions">
      <button class="btn btn-primary" type="submit">
        <span class="material-symbols-rounded">add</span>
        افزودن ریلز
      </button>
    </div>
  </form>
</div>

<div class="card">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead">list</span>
    <h2>ریلزها</h2>
    <span class="badge"><?= e(fa_digits((string) count($items))) ?></span>
  </div>
  <?php if (!$items): ?>
  <div class="empty"><span class="material-symbols-rounded">videocam_off</span>هنوز ریلزی اضافه نشده است.</div>
  <?php else: ?>
  <div class="rows">
    <?php foreach ($items as $i => $item): ?>
    <div class="row-item">
      <?php if ($item['source_type'] === 'file'): ?>
      <div class="reel-frame" style="width:52px;aspect-ratio:9/16">
        <video src="<?= e('../' . $item['source']) ?>" muted preload="metadata"></video>
      </div>
      <?php elseif ($item['source_type'] === 'url'): ?>
      <span class="thumb" style="display:grid;place-items:center">
        <span class="material-symbols-rounded" style="color:var(--primary)">cloud</span>
      </span>
      <?php else: ?>
      <span class="thumb" style="display:grid;place-items:center">
        <span class="material-symbols-rounded" style="color:var(--primary)">code</span>
      </span>
      <?php endif; ?>
      <div class="row-body">
        <h3><?= e($item['title'] ?: ('ریلز #' . fa_digits((string) $item['id']))) ?> <?= !$item['is_active'] ? '<span class="badge badge-muted">غیرفعال</span>' : '' ?></h3>
        <p><span class="badge badge-muted"><?= e($typeLabels[$item['source_type']]) ?></span>
          <?php if ($item['source_type'] === 'url'): ?>
          <span dir="ltr" style="font-size:11px"><?= e(mb_substr($item['source'], 0, 42)) ?>…</span>
          <?php endif; ?>
        </p>
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
        <form method="post" data-confirm="این ریلز حذف شود؟">
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
