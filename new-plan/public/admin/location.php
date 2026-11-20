<?php
/** Clinic address, map coordinates and routing. */
$active = 'location';
$pageTitle = 'آدرس مطب';
require __DIR__ . '/partials/layout_start.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    Csrf::check();
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
    redirect('location.php');
}

$location = SiteData::group('location');
?>
<div class="card">
  <div class="card-head"><span class="material-symbols-rounded icon-lead">location_on</span><h2>آدرس مطب و نقشه</h2></div>
  <form method="post" data-dirty>
    <?= Csrf::field() ?>
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
    <?php if (!empty($location['lat']) && !empty($location['lng'])): ?>
    <iframe class="preview-frame" style="height:320px"
      src="https://maps.google.com/maps?q=<?= e((string) $location['lat']) ?>,<?= e((string) $location['lng']) ?>&z=16&output=embed"
      title="پیش‌نمایش نقشه" loading="lazy"></iframe>
    <?php endif; ?>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit"><span class="material-symbols-rounded">save</span>ذخیره آدرس</button>
    </div>
  </form>
</div>
<?php require __DIR__ . '/partials/layout_end.php'; ?>
