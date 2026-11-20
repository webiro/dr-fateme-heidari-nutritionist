<?php
/** Landing texts: hero, intro (rich editor), headings, empty states + English slogans. */
$active = 'texts';
$pageTitle = 'متن‌ها و معرفی';
$useEditor = true;
require __DIR__ . '/partials/layout_start.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    Csrf::check();
    $action = $_POST['action'] ?? '';

    if ($action === 'headings') {
        $headings = [
            'heroKicker' => clean_text($_POST['hero_kicker'] ?? '', 100),
            'intro' => clean_text($_POST['intro_heading'] ?? '', 191),
            'introParagraph' => trim((string) ($_POST['intro_paragraph'] ?? '')),
            'introPills' => implode('|', array_filter(array_map('trim', explode('|', (string) ($_POST['intro_pills'] ?? ''))))),
            'services' => clean_text($_POST['services_heading'] ?? '', 191),
            'servicesSub' => clean_text($_POST['services_sub'] ?? '', 191),
            'faq' => clean_text($_POST['faq_heading'] ?? '', 191),
            'videos' => clean_text($_POST['videos_heading'] ?? '', 191),
            'posts' => clean_text($_POST['posts_heading'] ?? '', 191),
            'contact' => clean_text($_POST['contact_heading'] ?? '', 191),
            'appoint' => clean_text($_POST['appoint_heading'] ?? '', 191),
            'formNote' => clean_text($_POST['form_note'] ?? '', 300),
            'videosEmpty' => clean_text($_POST['videos_empty'] ?? '', 191),
            'postsEmpty' => clean_text($_POST['posts_empty'] ?? '', 191),
            'appointmentCta' => clean_text($_POST['appointment_cta'] ?? '', 60),
        ];
        // intro paragraph is a plain-text paragraph; keep only inline-safe content
        $headings['introParagraph'] = strip_tags($headings['introParagraph'], '<strong><b><em><i><br><u>');
        SiteData::putGroup('headings', $headings);
        flash_set('ok', 'متن‌ها ذخیره شد و بلافاصله در سایت نمایش داده می‌شود.');
        redirect('texts.php');
    }

    if ($action === 'slogans') {
        $slogans = array_values(array_filter(array_map('trim', explode("\n", (string) ($_POST['slogans'] ?? '')))));
        SiteData::putGroup('slogans', $slogans);
        flash_set('ok', 'شعارهای انگلیسی ذخیره شد.');
        redirect('texts.php');
    }
}

$headings = SiteData::group('headings');
$slogans = SiteData::group('slogans');
?>
<form method="post" data-dirty>
  <?= Csrf::field() ?>
  <input type="hidden" name="action" value="headings">

  <div class="card">
    <div class="card-head"><span class="material-symbols-rounded icon-lead">flag</span><h2>بخش ابتدای صفحه (هیرو)</h2></div>
    <div class="form-grid">
      <div class="field">
        <label for="hero_kicker">شعار کوچک بالای نام (لاتین)</label>
        <input class="input" id="hero_kicker" name="hero_kicker" value="<?= e($headings['heroKicker']) ?>">
      </div>
      <div class="field">
        <label for="appointment_cta">متن دکمه اصلی رزرو</label>
        <input class="input" id="appointment_cta" name="appointment_cta" value="<?= e($headings['appointmentCta']) ?>">
      </div>
    </div>
    <p class="card-note">نام دکتر، عنوان شغلی، کد نظام پزشکی و متن معرفی در «اطلاعات و تماس» ویرایش می‌شود.</p>
  </div>

  <div class="card">
    <div class="card-head"><span class="material-symbols-rounded icon-lead">short_text</span><h2>بخش معرفی</h2></div>
    <div class="field">
      <label for="intro_heading">عنوان بخش</label>
      <input class="input" id="intro_heading" name="intro_heading" value="<?= e($headings['intro']) ?>">
    </div>
    <div class="field">
      <label>متن بخش (ادیتور ساده)</label>
      <input type="hidden" name="intro_paragraph" id="intro-paragraph" value="<?= e($headings['introParagraph']) ?>">
      <div data-editor data-target="#intro-paragraph">
        <div class="rte-area" contenteditable="true" style="min-height:140px"
             data-empty="1" data-placeholder="متن معرفی را اینجا بنویسید…"></div>
      </div>
    </div>
    <div class="field">
      <label for="intro_pills">برچسب‌ها (با | جدا کنید)</label>
      <input class="input" id="intro_pills" name="intro_pills" value="<?= e($headings['introPills']) ?>">
      <p class="hint">مثال: هدف شخصی|سبک زندگی|شرایط بدن</p>
    </div>
  </div>

  <div class="card">
    <div class="card-head"><span class="material-symbols-rounded icon-lead">title</span><h2>عنوان بخش‌ها</h2></div>
    <div class="form-grid">
      <div class="field">
        <label for="services_heading">عنوان خدمات</label>
        <input class="input" id="services_heading" name="services_heading" value="<?= e($headings['services']) ?>">
      </div>
      <div class="field">
        <label for="services_sub">زیرعنوان خدمات</label>
        <input class="input" id="services_sub" name="services_sub" value="<?= e($headings['servicesSub']) ?>">
      </div>
      <div class="field">
        <label for="faq_heading">عنوان سوالات متداول</label>
        <input class="input" id="faq_heading" name="faq_heading" value="<?= e($headings['faq']) ?>">
      </div>
      <div class="field">
        <label for="videos_heading">عنوان بخش ریلز / ویدیوها</label>
        <input class="input" id="videos_heading" name="videos_heading" value="<?= e($headings['videos']) ?>">
      </div>
      <div class="field">
        <label for="posts_heading">عنوان پست‌های اسلایدی</label>
        <input class="input" id="posts_heading" name="posts_heading" value="<?= e($headings['posts']) ?>">
      </div>
      <div class="field">
        <label for="contact_heading">عنوان راه‌های ارتباطی</label>
        <input class="input" id="contact_heading" name="contact_heading" value="<?= e($headings['contact']) ?>">
      </div>
      <div class="field">
        <label for="appoint_heading">عنوان فرم رزرو مشاوره</label>
        <input class="input" id="appoint_heading" name="appoint_heading" value="<?= e($headings['appoint']) ?>">
      </div>
      <div class="field">
        <label for="form_note">متن راهنمای فرم</label>
        <input class="input" id="form_note" name="form_note" value="<?= e($headings['formNote']) ?>">
      </div>
      <div class="field">
        <label for="videos_empty">پیام وقتی ویدیویی نیست</label>
        <input class="input" id="videos_empty" name="videos_empty" value="<?= e($headings['videosEmpty']) ?>">
      </div>
      <div class="field">
        <label for="posts_empty">پیام وقتی پستی نیست</label>
        <input class="input" id="posts_empty" name="posts_empty" value="<?= e($headings['postsEmpty']) ?>">
      </div>
    </div>
  </div>

  <div class="form-actions">
    <button class="btn btn-primary" type="submit">
      <span class="material-symbols-rounded">save</span>
      ذخیره همه تغییرات
    </button>
  </div>
</form>

<div class="card">
  <div class="card-head"><span class="material-symbols-rounded icon-lead">format_quote</span><h2>شعارهای انگلیسی فوتر</h2></div>
  <p class="card-note">هر خط یک شعار؛ در هر بازدید یکی به‌صورت تصادفی تایپ می‌شود.</p>
  <form method="post" data-dirty>
    <?= Csrf::field() ?>
    <input type="hidden" name="action" value="slogans">
    <div class="field">
      <textarea class="textarea" dir="ltr" style="text-align:left" name="slogans"><?= e(implode("\n", $slogans)) ?></textarea>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit"><span class="material-symbols-rounded">save</span>ذخیره شعارها</button>
    </div>
  </form>
</div>
<?php require __DIR__ . '/partials/layout_end.php'; ?>
