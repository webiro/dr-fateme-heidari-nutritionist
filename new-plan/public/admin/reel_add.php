<?php
/** Add a new reel. */
$active = 'reels';
$pageTitle = 'افزودن ریلز';
require __DIR__ . '/partials/layout_start.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    Csrf::check();
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
            if ($src === '') {
                throw new RuntimeException('کد embed خالی است.');
            }
            if (!preg_match('#<(iframe|script)\b#i', $src)) {
                throw new RuntimeException('کد وارد شده شبیه embed معتبر نیست (iframe یا script).');
            }
            if (preg_match('#\son\w+\s*=#i', $src)) {
                throw new RuntimeException('کد embed شامل رویدادهای جاوااسکریپتی ناخواسته است.');
            }
            $src = strip_tags($src, '<iframe><script><div><blockquote><p><span><br><strong><b>');
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
        redirect('reels.php');
    } catch (RuntimeException $e) {
        flash_set('err', $e->getMessage());
        redirect('reel_add.php');
    }
}

$reel = null;
$isEdit = false;
?>
<div class="card">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead">add_circle</span>
    <h2>افزودن ریلز جدید</h2>
    <div class="spacer"></div>
    <a class="btn btn-ghost btn-sm" href="reels.php">
      <span class="material-symbols-rounded">arrow_forward</span>
      بازگشت به آرشیو
    </a>
  </div>
  <p class="card-note">می‌توانید ویدیو را از دستگاه آپلود کنید، لینک مستقیم بدهید یا کد embed آپارات را بچسبانید.</p>
  <?php require __DIR__ . '/partials/reel_form.php'; ?>
</div>
<?php require __DIR__ . '/partials/layout_end.php'; ?>
