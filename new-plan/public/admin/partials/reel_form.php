<?php
/** Shared reel add/edit form. Expects: $reel (null for add), $isEdit (bool). */
$reelType = $reel['source_type'] ?? 'file';
$sourceUrl = $reel['source_type'] === 'url' ? $reel['source'] : '';
$sourceEmbed = $reel['source_type'] === 'embed' ? $reel['source'] : '';
?>
<form method="post" enctype="multipart/form-data" data-dirty>
  <?= Csrf::field() ?>
  <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
  <input type="hidden" name="id" value="<?= (int) ($reel['id'] ?? 0) ?>">
  <div class="field">
    <label for="title">عنوان (اختیاری، فقط برای خودتان)</label>
    <input class="input" id="title" name="title" value="<?= e($reel['title'] ?? '') ?>" placeholder="مثال: نکات تغذیه در رمضان">
  </div>
  <div class="field">
    <label>منبع ویدیو</label>
    <div class="seg">
      <span><input type="radio" name="source_type" value="file" id="rt-file" data-reel-type <?= $reelType === 'file' ? 'checked' : '' ?>><label for="rt-file"><span class="material-symbols-rounded" style="font-size:18px">upload_file</span>آپلود فایل</label></span>
      <span><input type="radio" name="source_type" value="url" id="rt-url" data-reel-type <?= $reelType === 'url' ? 'checked' : '' ?>><label for="rt-url"><span class="material-symbols-rounded" style="font-size:18px">link</span>لینک مستقیم</label></span>
      <span><input type="radio" name="source_type" value="embed" id="rt-embed" data-reel-type <?= $reelType === 'embed' ? 'checked' : '' ?>><label for="rt-embed"><span class="material-symbols-rounded" style="font-size:18px">code</span>اسکریپت آپارات</label></span>
    </div>
  </div>

  <div data-reel-panel="file">
    <div class="field">
      <label for="file">فایل ویدیو</label>
      <?php if ($isEdit && $reelType === 'file'): ?>
      <video class="reel-frame" style="width:140px" src="<?= e('../' . $reel['source']) ?>" controls preload="metadata"></video>
      <p class="hint">اگر فایل جدیدی انتخاب کنید، فایل قبلی جایگزین می‌شود.</p>
      <?php endif; ?>
      <input class="input" type="file" id="file" name="file" accept="video/mp4,video/webm,video/quicktime" <?= ($isEdit && $reelType === 'file') ? '' : 'required' ?>>
      <p class="hint">فرمت‌های مجاز: MP4، WebM، MOV — حجم زیاد؟ از لینک مستقیم یا آپارات استفاده کنید.</p>
    </div>
  </div>

  <div data-reel-panel="url" <?= $reelType === 'url' ? '' : 'hidden' ?>>
    <div class="field">
      <label for="url">لینک مستقیم ویدیو</label>
      <input class="input" type="url" dir="ltr" style="text-align:left" id="url" name="url" value="<?= e($sourceUrl) ?>" placeholder="https://example.com/video.mp4">
      <p class="hint">لینک باید مستقیم به فایل ویدیو (mp4/webm) ختم شود.</p>
    </div>
  </div>

  <div data-reel-panel="embed" <?= $reelType === 'embed' ? '' : 'hidden' ?>>
    <div class="field">
      <label for="embed">کد embed آپارات</label>
      <textarea class="textarea" dir="ltr" style="text-align:left" id="embed" name="embed" placeholder='<iframe src="https://www.aparat.com/video/video/embed/videohash/xxxx/vt/frame" ...></iframe>'><?= e($sourceEmbed) ?></textarea>
      <p class="hint">کد کامل embed را از صفحه ویدیو در آپارات کپی کنید (iframe یا script).</p>
    </div>
  </div>

  <div class="field">
    <label class="switch">
      <input type="checkbox" name="is_active" <?= (!$reel || $reel['is_active']) ? 'checked' : '' ?>>
      <span class="track"></span>
      <span class="switch-label">نمایش در سایت</span>
    </label>
  </div>

  <div class="form-actions">
    <button class="btn btn-primary" type="submit">
      <span class="material-symbols-rounded">save</span>
      <?= $isEdit ? 'ذخیره ویرایش' : 'افزودن ریلز' ?>
    </button>
    <a class="btn btn-ghost" href="reels.php">انصراف</a>
  </div>
</form>
