<?php
/** Edit an existing reel. */
$active = 'reels';
$pageTitle = 'ویرایش ریلز';
require __DIR__ . '/partials/layout_start.php';

$id = (int) ($_GET['id'] ?? 0);
$reel = $id ? Database::one('SELECT * FROM fh_reels WHERE id = ?', [$id]) : null;
if (!$reel) {
    flash_set('err', 'ریلز مورد نظر پیدا نشد.');
    redirect('reels.php');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    Csrf::check();
    $title = clean_text($_POST['title'] ?? '', 191);
    $type = $_POST['source_type'] ?? $reel['source_type'];
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    try {
        $src = $reel['source'];
        if ($type === 'file') {
            if (!empty($_FILES['file']['name'])) {
                Uploader::delete($reel['source_type'] === 'file' ? $reel['source'] : null);
                $src = Uploader::store($_FILES['file'], 'video');
            } elseif ($reel['source_type'] !== 'file') {
                throw new RuntimeException('برای این ریلز هنوز فایلی ذخیره نشده؛ فایل انتخاب کنید.');
            }
        } elseif ($type === 'url') {
            $src = clean_text($_POST['url'] ?? '', 600);
            if (!is_http_url($src)) {
                throw new RuntimeException('لینک مستقیم ویدیو معتبر نیست.');
            }
            if ($reel['source_type'] === 'file') {
                Uploader::delete($reel['source']);
            }
        } elseif ($type === 'embed') {
            $src = trim((string) ($_POST['embed'] ?? ''));
            if ($src === '' || !preg_match('#<(iframe|script)\b#i', $src) || preg_match('#\son\w+\s*=#i', $src)) {
                throw new RuntimeException('کد embed معتبر نیست.');
            }
            $src = strip_tags($src, '<iframe><script><div><blockquote><p><span><br><strong><b>');
            if (strlen($src) > 4000) {
                throw new RuntimeException('کد embed خیلی طولانی است.');
            }
            if ($reel['source_type'] === 'file') {
                Uploader::delete($reel['source']);
            }
        } else {
            throw new RuntimeException('نوع منبع نامعتبر است.');
        }

        Database::q(
            'UPDATE fh_reels SET title = ?, source_type = ?, source = ?, is_active = ? WHERE id = ?',
            [$title !== '' ? $title : null, $type, $src, $isActive, $id]
        );
        SiteData::clearCache();
        flash_set('ok', 'ریلز ویرایش شد.');
        redirect('reels.php');
    } catch (RuntimeException $e) {
        flash_set('err', $e->getMessage());
        redirect('reel_edit.php?id=' . $id);
    }
}

$isEdit = true;
?>
<div class="card">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead">edit_circle</span>
    <h2>ویرایش ریلز</h2>
    <div class="spacer"></div>
    <a class="btn btn-ghost btn-sm" href="reels.php">
      <span class="material-symbols-rounded">arrow_forward</span>
      بازگشت به آرشیو
    </a>
  </div>
  <?php require __DIR__ . '/partials/reel_form.php'; ?>
</div>
<?php require __DIR__ . '/partials/layout_end.php'; ?>
