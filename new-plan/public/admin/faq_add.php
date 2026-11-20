<?php
/** Add a new FAQ item. */
$active = 'faq';
$pageTitle = 'افزودن سوال';
require __DIR__ . '/partials/layout_start.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    Csrf::check();
    $question = clean_text($_POST['question'] ?? '', 255);
    $answer = clean_text($_POST['answer'] ?? '', 4000);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    if ($question === '' || $answer === '') {
        flash_set('err', 'سوال و پاسخ هر دو لازم است.');
        redirect('faq_add.php');
    }
    $max = (int) Database::val('SELECT COALESCE(MAX(sort), 0) FROM fh_faq');
    Database::insert(
        'INSERT INTO fh_faq (question, answer, sort, is_active) VALUES (?, ?, ?, ?)',
        [$question, $answer, $max + 1, $isActive]
    );
    SiteData::clearCache();
    flash_set('ok', 'سوال جدید اضافه شد.');
    redirect('faq.php');
}
?>
<div class="card">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead">add_circle</span>
    <h2>افزودن سوال جدید</h2>
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
      <input class="input" id="question" name="question" required placeholder="مثال: برای کاهش وزن از کجا شروع کنم؟">
    </div>
    <div class="field">
      <label for="answer">پاسخ</label>
      <textarea class="textarea" id="answer" name="answer" required style="min-height:160px"></textarea>
      <p class="hint">لحن دوستانه و ساده بهترین نتیجه را دارد؛ ایموجی هم مشکلی ندارد.</p>
    </div>
    <div class="field">
      <label class="switch">
        <input type="checkbox" name="is_active" checked>
        <span class="track"></span>
        <span class="switch-label">نمایش در سایت</span>
      </label>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit">
        <span class="material-symbols-rounded">save</span>
        افزودن سوال
      </button>
      <a class="btn btn-ghost" href="faq.php">انصراف</a>
    </div>
  </form>
</div>
<?php require __DIR__ . '/partials/layout_end.php'; ?>
