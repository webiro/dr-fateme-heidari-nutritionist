<?php
/** SEO — meta tags & social sharing (professional OG/Twitter/JSON-LD). */
$active = 'seo';
$pageTitle = 'متاتگ‌ها و سوشال';
require __DIR__ . '/partials/layout_start.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    Csrf::check();
    $action = $_POST['action'] ?? '';

    if ($action === 'meta') {
        $old = SiteData::group('seo');
        $seo = [
            'title' => clean_text($_POST['seo_title'] ?? '', 191),
            'description' => clean_text($_POST['seo_description'] ?? '', 320),
            'robots' => in_array($_POST['robots'] ?? '', ['index,follow', 'index,nofollow', 'noindex,follow', 'noindex,nofollow'], true) ? $_POST['robots'] : 'index,follow',
            'ogTitle' => clean_text($_POST['og_title'] ?? '', 191),
            'ogDescription' => clean_text($_POST['og_description'] ?? '', 320),
            'ogImage' => $old['ogImage'] ?? 'assets/images/og-image.jpg',
            'ogSiteName' => clean_text($_POST['og_site_name'] ?? '', 100),
            'ogImageAlt' => clean_text($_POST['og_image_alt'] ?? '', 191),
            'twitterCard' => in_array($_POST['twitter_card'] ?? '', ['summary', 'summary_large_image'], true) ? $_POST['twitter_card'] : 'summary_large_image',
            'ratingValue' => normalize_digits(clean_text($_POST['rating_value'] ?? '', 5)),
            'ratingCount' => normalize_digits(clean_text($_POST['rating_count'] ?? '', 6)),
        ];
        SiteData::putGroup('seo', $seo);
        flash_set('ok', 'متاتگ‌ها ذخیره شد.');
        redirect('seo.php');
    }

    if ($action === 'og_image') {
        try {
            $old = SiteData::group('seo');
            if (!empty($_FILES['og_image']['name'])) {
                $path = Uploader::store($_FILES['og_image'], 'image');
                if (!empty($old['ogImage']) && str_starts_with($old['ogImage'], 'uploads/')) {
                    Uploader::delete($old['ogImage']);
                }
                $seo = $old;
                $seo['ogImage'] = $path;
                SiteData::putGroup('seo', $seo);
                flash_set('ok', 'تصویر اشتراک‌گذاری به‌روزرسانی شد.');
            } else {
                flash_set('err', 'فایلی انتخاب نشده است.');
            }
        } catch (RuntimeException $e) {
            flash_set('err', $e->getMessage());
        }
        redirect('seo.php');
    }
}

$seo = SiteData::group('seo');
$domain = Env::get('SITE_DOMAIN', 'drfatemeheidari.ir');
$ogImageHref = str_starts_with((string) $seo['ogImage'], 'uploads/') ? '../' . $seo['ogImage'] : '../' . $seo['ogImage'];
?>
<form method="post" data-dirty>
  <?= Csrf::field() ?>
  <input type="hidden" name="action" value="meta">

  <div class="card">
    <div class="card-head"><span class="material-symbols-rounded icon-lead">search</span><h2>موتورهای جستجو</h2></div>
    <div class="field">
      <label for="seo_title">عنوان صفحه (تب مرورگر و نتیجه گوگل)</label>
      <input class="input" id="seo_title" name="seo_title" value="<?= e($seo['title']) ?>">
      <p class="hint" id="title-count"></p>
    </div>
    <div class="field">
      <label for="seo_description">توضیحات متا</label>
      <textarea class="textarea" id="seo_description" name="seo_description" style="min-height:90px"><?= e($seo['description']) ?></textarea>
      <p class="hint" id="desc-count"></p>
    </div>
    <div class="form-grid">
      <div class="field">
        <label for="robots">دسترسی ربات‌ها</label>
        <select class="select" id="robots" name="robots">
          <?php foreach (['index,follow' => 'عادی (index, follow)', 'noindex,nofollow' => 'بی‌ایندکس', 'index,nofollow' => 'index فقط', 'noindex,follow' => 'follow فقط'] as $val => $label): ?>
          <option value="<?= e($val) ?>" <?= ($seo['robots'] ?? '') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>پیش‌نمایش گوگل</label>
        <div style="background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:12px;font-size:12.5px">
          <div style="color:var(--muted);font-size:11px" dir="ltr"><?= e($domain) ?></div>
          <div style="color:#1a0dab;font-size:14px;font-weight:700" class="g-title"><?= e(mb_substr($seo['title'], 0, 60)) ?></div>
          <div style="color:var(--muted)"><?= e(mb_substr($seo['description'], 0, 120)) ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-head"><span class="material-symbols-rounded icon-lead">share</span><h2>اشتراک‌گذاری در شبکه‌های اجتماعی (Open Graph)</h2></div>
    <p class="card-note">این چیزی است وقتی لینک سایت در تلگرام، واتساپ، فیسبوک یا لینکدین پست می‌شود دیده می‌شود.</p>
    <div class="form-grid">
      <div class="field">
        <label for="og_site_name">نام سایت</label>
        <input class="input" id="og_site_name" name="og_site_name" value="<?= e($seo['ogSiteName']) ?>">
      </div>
      <div class="field">
        <label for="og_title">عنوان اشتراک‌گذاری</label>
        <input class="input" id="og_title" name="og_title" value="<?= e($seo['ogTitle']) ?>">
      </div>
      <div class="field full">
        <label for="og_description">توضیح اشتراک‌گذاری</label>
        <input class="input" id="og_description" name="og_description" value="<?= e($seo['ogDescription']) ?>">
      </div>
      <div class="field">
        <label for="og_image_alt">متن جایگزین تصویر</label>
        <input class="input" id="og_image_alt" name="og_image_alt" value="<?= e($seo['ogImageAlt']) ?>">
      </div>
      <div class="field">
        <label for="twitter_card">نوع کارت توییتر</label>
        <select class="select" id="twitter_card" name="twitter_card">
          <option value="summary_large_image" <?= ($seo['twitterCard'] ?? '') === 'summary_large_image' ? 'selected' : '' ?>>عکس بزرگ (summary_large_image)</option>
          <option value="summary" <?= ($seo['twitterCard'] ?? '') === 'summary' ? 'selected' : '' ?>>خلاصه کوچک (summary)</option>
        </select>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-head"><span class="material-symbols-rounded icon-lead">star</span><h2>امتیاز ویژه گوگل (Rich Result)</h2></div>
    <p class="card-note">امتیاز ستاره‌دار در نتایج گوگل (AggregateRating). عدد اعتباری بین ۱ تا ۵ و تعداد رأی واقعی بگذارید؛ خالی بگذارید تا نمایش داده نشود.</p>
    <div class="form-grid">
      <div class="field">
        <label for="rating_value">امتیاز (مثال: 4.9)</label>
        <input class="input" dir="ltr" style="text-align:left" id="rating_value" name="rating_value" value="<?= e((string) $seo['ratingValue']) ?>">
      </div>
      <div class="field">
        <label for="rating_count">تعداد رأی (مثال: 271)</label>
        <input class="input" dir="ltr" style="text-align:left" id="rating_count" name="rating_count" value="<?= e((string) $seo['ratingCount']) ?>">
      </div>
    </div>
  </div>

  <div class="form-actions">
    <button class="btn btn-primary" type="submit">
      <span class="material-symbols-rounded">save</span>
      ذخیره متاتگ‌ها
    </button>
  </div>
</form>

<div class="card">
  <div class="card-head"><span class="material-symbols-rounded icon-lead">image</span><h2>تصویر اشتراک‌گذاری (og:image)</h2></div>
  <p class="card-note">اندازه پیشنهادی: 1200×630 پیکسل (نسبت ۱٫۹:۱) — این تصویر در پیش‌نمایش لینک همه شبکه‌ها دیده می‌شود.</p>
  <div style="display:flex;gap:16px;align-items:flex-start;flex-wrap:wrap">
    <img src="<?= e($ogImageHref) ?>" alt="og:image" style="width:280px;border-radius:12px;border:1px solid var(--border)">
    <form method="post" enctype="multipart/form-data" style="flex:1;min-width:240px">
      <?= Csrf::field() ?>
      <input type="hidden" name="action" value="og_image">
      <div class="field">
        <label for="og_image">تصویر جدید</label>
        <input class="input" type="file" id="og_image" name="og_image" accept="image/jpeg,image/png,image/webp">
      </div>
      <button class="btn btn-primary" type="submit">
        <span class="material-symbols-rounded">upload</span>
        آپلود تصویر جدید
      </button>
    </form>
  </div>
</div>
<script>
  (function () {
    function bindCount(inputId, hintId, max) {
      var el = document.getElementById(inputId), hint = document.getElementById(hintId);
      if (!el || !hint) return;
      var update = function () {
        var n = el.value.length;
        hint.textContent = n + " کاراکتر" + (n > max ? " — طولانی است، ممکن است در گوگل بریده شود" : " — مناسب");
        hint.style.color = n > max ? "var(--danger)" : "var(--muted)";
      };
      el.addEventListener("input", update);
      update();
    }
    bindCount("seo_title", "title-count", 60);
    bindCount("seo_description", "desc-count", 160);
  })();
</script>
<?php require __DIR__ . '/partials/layout_end.php'; ?>
