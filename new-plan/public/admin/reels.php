<?php
/** Reels — archive table. */
$active = 'reels';
$pageTitle = 'ریلزها';
require __DIR__ . '/partials/layout_start.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    if ($action === 'delete' && $id) {
        $reel = Database::one('SELECT * FROM fh_reels WHERE id = ?', [$id]);
        if ($reel && $reel['source_type'] === 'file') {
            Uploader::delete($reel['source']);
        }
        Database::q('DELETE FROM fh_reels WHERE id = ?', [$id]);
        SiteData::clearCache();
        flash_set('ok', 'ریلز حذف شد.');
        redirect('reels.php');
    }
    if ($action === 'toggle' && $id) {
        Database::q('UPDATE fh_reels SET is_active = 1 - is_active WHERE id = ?', [$id]);
        SiteData::clearCache();
        redirect('reels.php');
    }
}

$items = Database::all('SELECT * FROM fh_reels ORDER BY sort ASC, id ASC');
$typeLabels = ['file' => 'فایل', 'url' => 'لینک مستقیم', 'embed' => 'آپارات'];
?>
<div class="card">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead">movie</span>
    <h2>آرشیو ریلزها</h2>
    <span class="badge"><?= e(fa_digits((string) count($items))) ?></span>
    <div class="spacer"></div>
    <a class="btn btn-ghost btn-sm" href="reel_order.php">
      <span class="material-symbols-rounded">swap_vert</span>
      ترتیب نمایش
    </a>
    <a class="btn btn-primary btn-sm" href="reel_add.php">
      <span class="material-symbols-rounded">add</span>
      افزودن ریلز
    </a>
  </div>
  <p class="card-note">ریلزهای فعال در کاروسل «ویدیوها» صفحه اصلی نمایش داده می‌شوند؛ فرمت عمودی ۹:۱۶ بهترین نمایش را دارد.</p>
  <?php if (!$items): ?>
  <div class="empty"><span class="material-symbols-rounded">videocam_off</span>هنوز ریلزی اضافه نشده است. اولین ریلز را اضافه کنید.</div>
  <?php else: ?>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th>پیش‌نمایش</th>
          <th>عنوان</th>
          <th>منبع</th>
          <th>وضعیت</th>
          <th>عملیات</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $item): ?>
        <tr>
          <td>
            <?php if ($item['source_type'] === 'file'): ?>
            <video class="row-thumb" src="<?= e('../' . $item['source']) ?>" muted preload="metadata"></video>
            <?php else: ?>
            <span class="row-thumb" style="display:grid;place-items:center">
              <span class="material-symbols-rounded" style="color:var(--primary)"><?= $item['source_type'] === 'url' ? 'cloud' : 'code' ?></span>
            </span>
            <?php endif; ?>
          </td>
          <td class="cell-title"><?= e($item['title'] ?: ('ریلز #' . fa_digits((string) $item['id']))) ?></td>
          <td>
            <span class="badge badge-muted"><?= e($typeLabels[$item['source_type']]) ?></span>
            <?php if ($item['source_type'] === 'url'): ?>
            <div class="cell-muted code-mono" style="max-width:180px" title="<?= e($item['source']) ?>"><?= e(mb_substr($item['source'], 0, 36)) ?>…</div>
            <?php endif; ?>
          </td>
          <td><?= $item['is_active'] ? '<span class="badge badge-ok">فعال</span>' : '<span class="badge badge-muted">غیرفعال</span>' ?></td>
          <td class="cell-actions">
            <a class="icon-btn" href="reel_edit.php?id=<?= (int) $item['id'] ?>" aria-label="ویرایش">
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
            <form method="post" data-confirm="این ریلز حذف شود؟">
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
