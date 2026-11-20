<?php
/** Shared service add/edit form. Expects: $service (null for add), $isEdit (bool). */
?>
<form method="post" data-dirty>
  <?= Csrf::field() ?>
  <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
  <input type="hidden" name="id" value="<?= (int) ($service['id'] ?? 0) ?>">
  <div class="form-grid">
    <div class="field">
      <label for="title">عنوان خدمت</label>
      <input class="input" id="title" name="title" required value="<?= e($service['title'] ?? '') ?>" placeholder="مثال: رژیم کاهش وزن">
    </div>
    <div class="field">
      <label>وضعیت در سایت</label>
      <label class="switch">
        <input type="checkbox" name="is_active" <?= (!$service || $service['is_active']) ? 'checked' : '' ?>>
        <span class="track"></span>
        <span class="switch-label">نمایش در صفحه اصلی</span>
      </label>
    </div>
    <div class="field full">
      <label for="text">توضیح کوتاه</label>
      <textarea class="textarea" id="text" name="text" style="min-height:90px" placeholder="یک تا دو جمله درباره این خدمت"><?= e($service['text'] ?? '') ?></textarea>
    </div>
  </div>
  <?php require __DIR__ . '/icon_picker.php'; ?>
  <div class="form-actions">
    <button class="btn btn-primary" type="submit">
      <span class="material-symbols-rounded">save</span>
      <?= $isEdit ? 'ذخیره ویرایش' : 'افزودن خدمت' ?>
    </button>
    <a class="btn btn-ghost" href="services.php">انصراف</a>
  </div>
</form>
