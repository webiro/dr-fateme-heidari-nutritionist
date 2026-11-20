<?php
/** Diet platform settings — brand, AI, master prompt (body), meal defaults. */
$active = 'platform_settings';
$pageTitle = 'تنظیمات پلتفرم تغذیه';
require __DIR__ . '/partials/layout_start.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    Csrf::check();
    $action = $_POST['action'] ?? '';
    $platform = SiteData::group('platform');

    if ($action === 'brand') {
        $platform['clinician'] = clean_text($_POST['clinician'] ?? '', 100);
        $platform['lic'] = clean_text($_POST['lic'] ?? '', 60);
        $platform['brand'] = clean_text($_POST['brand'] ?? '', 150);
        $platform['addr'] = clean_text($_POST['addr'] ?? '', 300);
        $phone = normalize_digits(clean_text($_POST['phone'] ?? '', 30));
        $platform['phone'] = preg_match('/^\+?\d{10,15}$/', preg_replace('/\s/', '', $phone) ?? '') ? $phone : '';
        $platform['instagram'] = clean_text($_POST['instagram'] ?? '', 100);
        SiteData::putGroup('platform', $platform);
        flash_set('ok', 'اطلاعات سربرگ پلتفرم ذخیره شد.');
        redirect('platform.php');
    }

    if ($action === 'ai') {
        $platform['aiModel'] = clean_text($_POST['ai_model'] ?? '', 120);
        $key = trim((string) ($_POST['api_key'] ?? ''));
        if ($key !== '' && $key !== '••••••••••••') {
            $platform['apiKey'] = $key; // empty/masked = keep old
        }
        SiteData::putGroup('platform', $platform);
        flash_set('ok', 'تنظیمات هوش مصنوعی پلتفرم ذخیره شد.');
        redirect('platform.php');
    }

    if ($action === 'prompt') {
        $body = trim((string) ($_POST['master_prompt'] ?? ''));
        if ($body === '') {
            flash_set('err', 'مسترپرامپت نمی‌تواند خالی باشد.');
            redirect('platform.php');
        }
        $platform['masterPrompt'] = $body;
        SiteData::putGroup('platform', $platform);
        flash_set('ok', 'مسترپرامپت ذخیره شد؛ پلتفرم از این به بعد همین را استفاده می‌کند.');
        redirect('platform.php');
    }

    if ($action === 'reset_prompt') {
        $defaults = SiteData::defaults();
        $platform['masterPrompt'] = $defaults['platform']['masterPrompt'];
        SiteData::putGroup('platform', $platform);
        flash_set('ok', 'مسترپرامپت به حالت پیش‌فرض برگشت.');
        redirect('platform.php');
    }

    if ($action === 'meals') {
        foreach ($platform['meals'] as $key => $_) {
            $platform['meals'][$key] = isset($_POST['meal_' . $key]);
        }
        SiteData::putGroup('platform', $platform);
        flash_set('ok', 'پیش‌فرض وعده‌ها ذخیره شد.');
        redirect('platform.php');
    }
}

$platform = SiteData::group('platform');
$ai = SiteData::group('ai');
$hasOwnKey = (string) $platform['apiKey'] !== '';
$effectiveKey = $platform['apiKey'] ?: $ai['apiKey'];
$mealLabels = [
    'fasting' => 'ناشتا',
    'breakfast' => 'صبحانه',
    'morning' => 'میان‌وعده صبح',
    'lunch' => 'ناهار',
    'afternoon' => 'میان‌وعده عصر',
    'dinner' => 'شام',
    'latenight' => 'وعده آخر شب',
    'supplements' => 'مکمل‌ها',
];
?>
<div class="card">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead">restaurant_menu</span>
    <h2>پلتفرم تغذیه‌ساز</h2>
    <div class="spacer"></div>
    <a class="btn btn-primary" href="../platform/index.php" target="_blank" rel="noopener">
      <span class="material-symbols-rounded">launch</span>
      ورود به پلتفرم
    </a>
  </div>
  <p class="card-note">پلتفرم فقط با همین لاگین پنل باز می‌شود. تنظیمات زیر، مقادیر پیش‌فرض و رفتار پلتفرم را تعیین می‌کنند.</p>
</div>

<form method="post" data-dirty>
  <?= Csrf::field() ?>
  <input type="hidden" name="action" value="brand">
  <div class="card">
    <div class="card-head"><span class="material-symbols-rounded icon-lead">badge</span><h2>سربرگ خروجی رژیم</h2></div>
    <p class="card-note">این اطلاعات در سربرگ برنامه غذایی خروجی چاپ می‌شود.</p>
    <div class="form-grid">
      <div class="field">
        <label for="clinician">نام رژیم درمانگر</label>
        <input class="input" id="clinician" name="clinician" value="<?= e($platform['clinician']) ?>">
      </div>
      <div class="field">
        <label for="lic">شماره نظام پزشکی</label>
        <input class="input" id="lic" name="lic" value="<?= e($platform['lic']) ?>">
      </div>
      <div class="field">
        <label for="brand">اسم مجموعه</label>
        <input class="input" id="brand" name="brand" value="<?= e($platform['brand']) ?>">
      </div>
      <div class="field">
        <label for="instagram">اینستاگرام</label>
        <input class="input" dir="ltr" style="text-align:left" id="instagram" name="instagram" value="<?= e($platform['instagram']) ?>">
      </div>
      <div class="field full">
        <label for="addr">آدرس</label>
        <input class="input" id="addr" name="addr" value="<?= e($platform['addr']) ?>">
      </div>
      <div class="field">
        <label for="phone">شماره تماس</label>
        <input class="input" dir="ltr" style="text-align:left" id="phone" name="phone" value="<?= e($platform['phone']) ?>">
      </div>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit"><span class="material-symbols-rounded">save</span>ذخیره سربرگ</button>
    </div>
  </div>
</form>

<form method="post" data-dirty>
  <?= Csrf::field() ?>
  <input type="hidden" name="action" value="ai">
  <div class="card">
    <div class="card-head"><span class="material-symbols-rounded icon-lead">smart_toy</span><h2>هوش مصنوعی پلتفرم</h2></div>
    <p class="card-note">پلتفرم از طریق پاستا کلاد کار می‌کند. اگر کلید اختصاصی ندهید، همان کلید «دستیار هوشمند» استفاده می‌شود.</p>
    <div class="form-grid">
      <div class="field">
        <label for="api_key">کلید API پاستا <?= $hasOwnKey ? '<span class="badge badge-ok">کلید اختصاصی ✓</span>' : ($effectiveKey ? '<span class="badge">از دستیار هوشمند ✓</span>' : '<span class="badge badge-warn">تنظیم نشده</span>') ?></label>
        <input class="input" dir="ltr" style="text-align:left" id="api_key" name="api_key"
               value="<?= $hasOwnKey ? '••••••••••••' : '' ?>" placeholder="خالی = استفاده از کلید دستیار هوشمند" autocomplete="off">
      </div>
      <div class="field">
        <label for="ai_model">مدل</label>
        <input class="input" dir="ltr" style="text-align:left" id="ai_model" name="ai_model" value="<?= e($platform['aiModel']) ?>">
        <p class="hint">اقتصادی: <b dir="ltr">deepseek/deepseek-v4-flash</b></p>
      </div>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit"><span class="material-symbols-rounded">save</span>ذخیره تنظیمات AI</button>
    </div>
  </div>
</form>

<form method="post" data-dirty>
  <?= Csrf::field() ?>
  <input type="hidden" name="action" value="meals">
  <div class="card">
    <div class="card-head"><span class="material-symbols-rounded icon-lead">restaurant</span><h2>پیش‌فرض وعده‌ها</h2></div>
    <p class="card-note">وعده‌های غیرفعال در پلتفرم خاموش شروع می‌شوند: نه در مراحل نمایش داده می‌شوند و نه در خروجی. دکتر می‌تواند برای هر مراجع، داخل خود پلتفرم هم تغییرشان دهد.</p>
    <?php foreach ($mealLabels as $key => $label): ?>
    <label class="switch">
      <input type="checkbox" name="meal_<?= e($key) ?>" <?= !empty($platform['meals'][$key]) ? 'checked' : '' ?>>
      <span class="track"></span>
      <span class="switch-label"><?= e($label) ?></span>
    </label>
    <?php endforeach; ?>
    <div class="form-actions" style="margin-top:14px">
      <button class="btn btn-primary" type="submit"><span class="material-symbols-rounded">save</span>ذخیره وعده‌ها</button>
    </div>
  </div>
</form>

<form method="post" data-dirty>
  <?= Csrf::field() ?>
  <input type="hidden" name="action" value="prompt">
  <div class="card">
    <div class="card-head">
      <span class="material-symbols-rounded icon-lead">note_stack</span>
      <h2>مسترپرامپت رژیم‌سازی</h2>
    </div>
    <p class="card-note">متن راهنمای ساخت رژیم توسط هوش مصنوعی. <b>ساختار JSON خروجی به‌صورت خودکار و همیشه اضافه می‌شود</b> — لازم نیست اینجا بنویسید. اگر تغییرش دهید، پلتفرم همیشه همین نسخه ذخیره‌شده را استفاده می‌کند.</p>
    <div class="field">
      <textarea class="textarea" name="master_prompt" style="min-height:260px;font-size:13px"><?= e($platform['masterPrompt']) ?></textarea>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit"><span class="material-symbols-rounded">save</span>ذخیره مسترپرامپت</button>
    </div>
  </div>
</form>

<form method="post">
  <?= Csrf::field() ?>
  <input type="hidden" name="action" value="reset_prompt">
  <div class="card" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
    <span class="material-symbols-rounded" style="color:var(--muted)">restart_alt</span>
    <div style="flex:1;font-size:13px;color:var(--muted)">بازگشت مسترپرامپت به متن پیش‌فرض</div>
    <button class="btn btn-ghost btn-sm" type="submit" data-confirm="مسترپرامپت به پیش‌فرض برگردد؟">بازنشانی</button>
  </div>
</form>
<?php require __DIR__ . '/partials/layout_end.php'; ?>
