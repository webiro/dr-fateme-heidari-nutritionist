<?php
/** All editable texts & headings of the landing page + section visibility. */
$active = 'texts';
$pageTitle = 'متن‌ها و عنوان‌ها';
require __DIR__ . '/partials/layout_start.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $seo = [
        'title' => clean_text($_POST['seo_title'] ?? '', 191),
        'description' => clean_text($_POST['seo_description'] ?? '', 320),
        'ogTitle' => clean_text($_POST['og_title'] ?? '', 191),
        'ogDescription' => clean_text($_POST['og_description'] ?? '', 320),
        'ogImage' => SiteData::group('seo')['ogImage'] ?? 'assets/images/og-image.jpg',
    ];
    $headings = [
        'heroKicker' => clean_text($_POST['hero_kicker'] ?? '', 100),
        'intro' => clean_text($_POST['intro_heading'] ?? '', 191),
        'introParagraph' => clean_text($_POST['intro_paragraph'] ?? '', 1000),
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
    $sections = SiteData::group('sections');
    foreach (array_keys($sections) as $key) {
        $sections[$key] = isset($_POST['section_' . $key]);
    }
    SiteData::putGroup('seo', $seo);
    SiteData::putGroup('headings', $headings);
    SiteData::putGroup('sections', $sections);
    flash_set('ok', 'متن‌ها ذخیره شد و بلافاصله در سایت نمایش داده می‌شود.');
    redirect('texts.php');
}

$seo = SiteData::group('seo');
$headings = SiteData::group('headings');
$sections = SiteData::group('sections');

$sectionLabels = [
    'intro' => 'بخش معرفی',
    'services' => 'بخش خدمات',
    'faq' => 'سوالات متداول',
    'videos' => 'بخش ریلز / ویدیوها',
    'posts' => 'بخش پست‌های اسلایدی',
    'contact' => 'راه‌های ارتباطی',
    'appointment' => 'فرم رزرو مشاوره',
    'article' => 'متن سئو',
    'partners' => 'همکاران',
    'location' => 'آدرس مطب و نقشه',
];
?>
<form method="post" data-dirty>
  <?= Csrf::field() ?>

  <div class="card">
    <div class="card-head"><span class="material-symbols-rounded icon-lead">search</span><h2>عنوان و توضیحات سایت (سئو)</h2></div>
    <div class="field">
      <label for="seo_title">عنوان صفحه (تب مرورگر و نتیجه گوگل)</label>
      <input class="input" id="seo_title" name="seo_title" value="<?= e($seo['title']) ?>">
    </div>
    <div class="field">
      <label for="seo_description">توضیحات متا</label>
      <textarea class="textarea" id="seo_description" name="seo_description"><?= e($seo['description']) ?></textarea>
      <p class="hint">حدود ۱۵۰ کاراکتر؛ همین متن در نتایج جستجوی گوگل نمایش داده می‌شود.</p>
    </div>
    <div class="form-grid">
      <div class="field">
        <label for="og_title">عنوان اشتراک‌گذاری (شبکه‌های اجتماعی)</label>
        <input class="input" id="og_title" name="og_title" value="<?= e($seo['ogTitle']) ?>">
      </div>
      <div class="field">
        <label for="og_description">توضیح اشتراک‌گذاری</label>
        <input class="input" id="og_description" name="og_description" value="<?= e($seo['ogDescription']) ?>">
      </div>
    </div>
  </div>

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
    <p class="card-note">نام دکتر، عنوان شغلی، کد نظام پزشکی و متن معرفی در بخش «اطلاعات و تنظیمات» ویرایش می‌شود.</p>
  </div>

  <div class="card">
    <div class="card-head"><span class="material-symbols-rounded icon-lead">short_text</span><h2>بخش معرفی</h2></div>
    <div class="field">
      <label for="intro_heading">عنوان بخش</label>
      <input class="input" id="intro_heading" name="intro_heading" value="<?= e($headings['intro']) ?>">
    </div>
    <div class="field">
      <label for="intro_paragraph">متن بخش</label>
      <textarea class="textarea" id="intro_paragraph" name="intro_paragraph"><?= e($headings['introParagraph']) ?></textarea>
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

  <div class="card">
    <div class="card-head"><span class="material-symbols-rounded icon-lead">toggle_on</span><h2>نمایش بخش‌های سایت</h2></div>
    <p class="card-note">بخش‌هایی که خاموش شوند، از صفحه اصلی و منو حذف می‌شوند.</p>
    <?php foreach ($sectionLabels as $key => $label): ?>
    <label class="switch">
      <input type="checkbox" name="section_<?= e($key) ?>" <?= !empty($sections[$key]) ? 'checked' : '' ?>>
      <span class="track"></span>
      <span class="switch-label"><?= e($label) ?></span>
    </label>
    <?php endforeach; ?>
  </div>

  <div class="form-actions">
    <button class="btn btn-primary" type="submit">
      <span class="material-symbols-rounded">save</span>
      ذخیره همه تغییرات
    </button>
  </div>
</form>
<?php require __DIR__ . '/partials/layout_end.php'; ?>
