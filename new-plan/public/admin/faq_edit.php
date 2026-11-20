<?php
/** Edit an existing FAQ item. */
$active = 'faq';
$pageTitle = 'ویرایش سوال';
require __DIR__ . '/partials/layout_start.php';

$id = (int) ($_GET['id'] ?? 0);
$item = $id ? Database::one('SELECT * FROM fh_faq WHERE id = ?', [$id]) : null;
if (!$item) {
    flash_set('err', 'سوال مورد نظر پیدا نشد.');
    redirect('faq.php');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    Csrf::check();
    $question = clean_text($_POST['question'] ?? '', 255);
    $answer = clean_text($_POST['answer'] ?? '', 4000);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    if ($question === '' || $answer === '') {
        flash_set('err', 'سوال و پاسخ هر دو لازم است.');
        redirect('faq_edit.php?id=' . $id);
    }
    Database::q(
        'UPDATE fh_faq SET question = ?, answer = ?, is_active = ? WHERE id = ?',
        [$question, $answer, $isActive, $id]
    );
    SiteData::clearCache();
    flash_set('ok', 'سوال ویرایش شد.');
    redirect('faq.php');
}
?>
<div class="card">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead">edit_circle</span>
    <h2>ویرایش سوال</h2>
    <div class="spacer"></div>
    <a class="btn btn-ghost btn-sm" href="faq.php">
      <span class="material-symbols-rounded">arrow_forward</span>
      بازگشت به آرشیو
    </a>
  </div>
  <form method="post" data-dirty>
    <?= Csrf::field() ?>
    <div class="field">
      <label for="question">سوال</label>
      <input class="input" id="question" name="question" required value="<?= e($item['question']) ?>">
    </div>
    <div class="field">
      <label for="answer">پاسخ</label>
      <textarea class="textarea" id="answer" name="answer" required style="min-height:160px"><?= e($item['answer']) ?></textarea>
    </div>
    <div class="field">
      <label class="switch">
        <input type="checkbox" name="is_active" <?= $item['is_active'] ? 'checked' : '' ?>>
        <span class="track"></span>
        <span class="switch-label">نمایش در سایت</span>
      </label>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit">
        <span class="material-symbols-rounded">save</span>
        ذخیره ویرایش
      </button>
      <a class="btn btn-ghost" href="faq.php">انصراف</a>
    </div>
  </form>
</div>
<?php require __DIR__ . '/partials/layout_end.php'; ?>
