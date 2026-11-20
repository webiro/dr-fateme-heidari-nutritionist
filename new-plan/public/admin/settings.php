<?php
/** Site identity + contact links & social channels. */
$active = 'settings';
$pageTitle = 'اطلاعات و تماس';
require __DIR__ . '/partials/layout_start.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    Csrf::check();
    $action = $_POST['action'] ?? '';

    if ($action === 'identity') {
        $identity = [
            'nameFa' => clean_text($_POST['name_fa'] ?? '', 100),
            'nameEn' => clean_text($_POST['name_en'] ?? '', 100),
            'shortNameFa' => clean_text($_POST['short_name_fa'] ?? '', 100),
            'title' => clean_text($_POST['title'] ?? '', 191),
            'medicalCode' => clean_text($_POST['medical_code'] ?? '', 60),
            'city' => clean_text($_POST['city'] ?? '', 60),
            'province' => clean_text($_POST['province'] ?? '', 60),
            'country' => 'ایران',
            'intro' => clean_text($_POST['intro'] ?? '', 600),
            'footerText' => clean_text($_POST['footer_text'] ?? '', 300),
        ];
        SiteData::putGroup('identity', $identity);
        flash_set('ok', 'اطلاعات اصلی ذخیره شد.');
        redirect('settings.php');
    }

    if ($action === 'links') {
        $links = [];
        foreach (['website', 'instagram', 'telegram', 'tisaAssistant', 'whatsapp', 'medicalCouncil', 'bale', 'rubika', 'eitaa'] as $key) {
            $value = clean_text($_POST[$key] ?? '', 500);
            $links[$key] = ($value === '' || is_http_url($value)) ? $value : '';
        }
        $phone = normalize_digits(clean_text($_POST['phone'] ?? '', 30));
        $links['phone'] = preg_match('/^\+?\d{10,15}$/', preg_replace('/\s/', '', $phone) ?? '') ? $phone : '';
        SiteData::putGroup('links', $links);
        flash_set('ok', 'راه‌های ارتباطی ذخیره شد.');
        redirect('settings.php');
    }
}

$identity = SiteData::group('identity');
$links = SiteData::group('links');
?>
<div class="card">
  <div class="card-head"><span class="material-symbols-rounded icon-lead">person_circle</span><h2>اطلاعات اصلی</h2></div>
  <p class="card-note">این اطلاعات در عنوان‌ها، بخش هیرو، فوتر، کارت QR و دیتای ساختاریافته گوگل (JSON-LD) استفاده می‌شود.</p>
  <form method="post" data-dirty>
    <?= Csrf::field() ?>
    <input type="hidden" name="action" value="identity">
    <div class="form-grid">
      <div class="field">
        <label for="name_fa">نام کامل (فارسی)</label>
        <input class="input" id="name_fa" name="name_fa" value="<?= e($identity['nameFa']) ?>">
      </div>
      <div class="field">
        <label for="short_name_fa">نام کوتاه (فارسی)</label>
        <input class="input" id="short_name_fa" name="short_name_fa" value="<?= e($identity['shortNameFa']) ?>">
      </div>
      <div class="field">
        <label for="name_en">نام (انگلیسی)</label>
        <input class="input" dir="ltr" style="text-align:left" id="name_en" name="name_en" value="<?= e($identity['nameEn']) ?>">
      </div>
      <div class="field">
        <label for="medical_code">کد نظام پزشکی</label>
        <input class="input" id="medical_code" name="medical_code" value="<?= e($identity['medicalCode']) ?>">
      </div>
      <div class="field">
        <label for="title">عنوان شغلی</label>
        <input class="input" id="title" name="title" value="<?= e($identity['title']) ?>">
      </div>
      <div class="field">
        <label for="city">شهر</label>
        <input class="input" id="city" name="city" value="<?= e($identity['city']) ?>">
      </div>
      <div class="field">
        <label for="province">استان</label>
        <input class="input" id="province" name="province" value="<?= e($identity['province']) ?>">
      </div>
      <div class="field full">
        <label for="intro">متن معرفی بخش هیرو</label>
        <textarea class="textarea" id="intro" name="intro" style="min-height:90px"><?= e($identity['intro']) ?></textarea>
      </div>
      <div class="field full">
        <label for="footer_text">متن فوتر</label>
        <input class="input" id="footer_text" name="footer_text" value="<?= e($identity['footerText']) ?>">
      </div>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit"><span class="material-symbols-rounded">save</span>ذخیره اطلاعات اصلی</button>
    </div>
  </form>
</div>

<div class="card">
  <div class="card-head"><span class="material-symbols-rounded icon-lead">share</span><h2>راه‌های ارتباطی و شبکه‌های اجتماعی</h2></div>
  <p class="card-note">هر راه ارتباطی که پر شود در بخش «ارتباط با من»، کارت QR و دیتای ساختاریافته گوگل نمایش داده می‌شود؛ خالی بماند، حذف می‌شود.</p>
  <form method="post" data-dirty>
    <?= Csrf::field() ?>
    <input type="hidden" name="action" value="links">
    <div class="form-grid">
      <div class="field">
        <label for="phone">شماره تماس</label>
        <input class="input" dir="ltr" style="text-align:left" id="phone" name="phone" value="<?= e($links['phone']) ?>" placeholder="+98912...">
      </div>
      <div class="field">
        <label for="whatsapp">واتساپ</label>
        <input class="input" dir="ltr" style="text-align:left" id="whatsapp" name="whatsapp" value="<?= e($links['whatsapp']) ?>" placeholder="https://wa.me/...">
      </div>
      <div class="field">
        <label for="instagram">اینستاگرام</label>
        <input class="input" dir="ltr" style="text-align:left" id="instagram" name="instagram" value="<?= e($links['instagram']) ?>">
      </div>
      <div class="field">
        <label for="telegram">تلگرام</label>
        <input class="input" dir="ltr" style="text-align:left" id="telegram" name="telegram" value="<?= e($links['telegram']) ?>">
      </div>
      <div class="field">
        <label for="tisaAssistant">دستیار هوشمند (ربات)</label>
        <input class="input" dir="ltr" style="text-align:left" id="tisaAssistant" name="tisaAssistant" value="<?= e($links['tisaAssistant']) ?>">
      </div>
      <div class="field">
        <label for="medicalCouncil">صفحه نظام پزشکی</label>
        <input class="input" dir="ltr" style="text-align:left" id="medicalCouncil" name="medicalCouncil" value="<?= e($links['medicalCouncil']) ?>">
      </div>
      <div class="field">
        <label for="bale">بله</label>
        <input class="input" dir="ltr" style="text-align:left" id="bale" name="bale" value="<?= e($links['bale']) ?>">
      </div>
      <div class="field">
        <label for="rubika">روبیکا</label>
        <input class="input" dir="ltr" style="text-align:left" id="rubika" name="rubika" value="<?= e($links['rubika']) ?>">
      </div>
      <div class="field">
        <label for="eitaa">ایتا</label>
        <input class="input" dir="ltr" style="text-align:left" id="eitaa" name="eitaa" value="<?= e($links['eitaa']) ?>">
      </div>
      <div class="field">
        <label for="website">آدرس سایت</label>
        <input class="input" dir="ltr" style="text-align:left" id="website" name="website" value="<?= e($links['website']) ?>">
      </div>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit"><span class="material-symbols-rounded">save</span>ذخیره راه‌های ارتباطی</button>
    </div>
  </form>
</div>
<?php require __DIR__ . '/partials/layout_end.php'; ?>
