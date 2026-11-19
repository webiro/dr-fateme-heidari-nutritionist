<?php
/** Site identity, links, location, SEO article and slogans. */
$active = 'settings';
$pageTitle = 'اطلاعات و تنظیمات';
require __DIR__ . '/partials/layout_start.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
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

    if ($action === 'location') {
        $lat = (float) str_replace(',', '.', (string) ($_POST['lat'] ?? 0));
        $lng = (float) str_replace(',', '.', (string) ($_POST['lng'] ?? 0));
        $location = [
            'enabled' => isset($_POST['enabled']),
            'title' => clean_text($_POST['title'] ?? 'آدرس مطب', 100),
            'address' => clean_text($_POST['address'] ?? '', 300),
            'lat' => $lat ?: 0,
            'lng' => $lng ?: 0,
        ];
        SiteData::putGroup('location', $location);
        flash_set('ok', 'آدرس مطب ذخیره شد.');
        redirect('settings.php');
    }

    if ($action === 'article') {
        $html = trim((string) ($_POST['html'] ?? ''));
        // allow a safe subset of content tags (admin-authored SEO copy)
        $allowed = '<h2><h3><h4><p><ul><ol><li><strong><b><em><i><br><a><span><b>';
        $html = strip_tags($html, $allowed);
        if (preg_match_all('#<a\b[^>]*>#i', $html, $matches)) {
            foreach ($matches[0] as $tag) {
                if (preg_match('#\son\w+\s*=#i', $tag) || !preg_match('#href="https?://[^"]*"#i', $tag)) {
                    $html = str_replace($tag, '', $html);
                }
            }
        }
        SiteData::putGroup('article', ['html' => $html]);
        flash_set('ok', 'متن سئو ذخیره شد.');
        redirect('settings.php');
    }

    if ($action === 'slogans') {
        $slogans = array_values(array_filter(array_map('trim', explode("\n", (string) ($_POST['slogans'] ?? '')))));
        SiteData::putGroup('slogans', $slogans);
        flash_set('ok', 'شعارهای انگلیسی ذخیره شد.');
        redirect('settings.php');
    }
}

$identity = SiteData::group('identity');
$links = SiteData::group('links');
$location = SiteData::group('location');
$article = SiteData::group('article');
$slogans = SiteData::group('slogans');
?>
<div class="card">
  <div class="card-head"><span class="material-symbols-rounded icon-lead">person</span><h2>اطلاعات اصلی</h2></div>
  <p class="card-note">این اطلاعات در عنوان‌ها، بخش هیرو، فوتر و کارت QR سایت استفاده می‌شود.</p>
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
        <textarea class="textarea" id="intro" name="intro"><?= e($identity['intro']) ?></textarea>
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
  <p class="card-note">هر راه ارتباطی که پر شود در بخش «ارتباط با من» و کارت QR نمایش داده می‌شود؛ خالی بماند، حذف می‌شود.</p>
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

<div class="card">
  <div class="card-head"><span class="material-symbols-rounded icon-lead">location_on</span><h2>آدرس مطب و نقشه</h2></div>
  <form method="post" data-dirty>
    <?= Csrf::field() ?>
    <input type="hidden" name="action" value="location">
    <div class="field">
      <label class="switch">
        <input type="checkbox" name="enabled" <?= !empty($location['enabled']) ? 'checked' : '' ?>>
        <span class="track"></span>
        <span class="switch-label">نمایش بخش آدرس در سایت</span>
      </label>
    </div>
    <div class="form-grid">
      <div class="field">
        <label for="loc_title">عنوان بخش</label>
        <input class="input" id="loc_title" name="title" value="<?= e($location['title']) ?>">
      </div>
      <div class="field">
        <label for="loc_address">آدرس کامل</label>
        <input class="input" id="loc_address" name="address" value="<?= e($location['address']) ?>">
      </div>
      <div class="field">
        <label for="lat">عرض جغرافیایی (lat)</label>
        <input class="input" dir="ltr" style="text-align:left" id="lat" name="lat" value="<?= e((string) $location['lat']) ?>">
      </div>
      <div class="field">
        <label for="lng">طول جغرافیایی (lng)</label>
        <input class="input" dir="ltr" style="text-align:left" id="lng" name="lng" value="<?= e((string) $location['lng']) ?>">
      </div>
    </div>
    <p class="card-note">مختصات را از گوگل‌مپ بگیرید: روی محل مطب راست‌کلیک → عدد دوگانه مثل <b dir="ltr">36.6508, 51.4962</b>. با این اطلاعات دکمه‌های مسیریابی (گوگل، Waze، نشان) و نقشه ساخته می‌شود.</p>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit"><span class="material-symbols-rounded">save</span>ذخیره آدرس</button>
    </div>
  </form>
</div>

<div class="card">
  <div class="card-head"><span class="material-symbols-rounded icon-lead">article</span><h2>متن سئوی پایین صفحه</h2></div>
  <p class="card-note">HTML ساده مجاز است: h2، h3، p، ul/li، strong، a. در سایت با دکمه «نمایش بیشتر» باز می‌شود.</p>
  <form method="post" data-dirty>
    <?= Csrf::field() ?>
    <input type="hidden" name="action" value="article">
    <div class="field">
      <textarea class="textarea" name="html" style="min-height:220px;font-size:13px" dir="auto"><?= e($article['html']) ?></textarea>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit"><span class="material-symbols-rounded">save</span>ذخیره متن سئو</button>
    </div>
  </form>
</div>

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
