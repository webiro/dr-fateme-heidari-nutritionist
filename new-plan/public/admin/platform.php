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

    if ($action === 'client_delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            Database::q('DELETE FROM fh_diet_records WHERE id = ?', [$id]);
            flash_set('ok', 'رکورد مراجع حذف شد.');
        }
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
    if ($action === 'logo') {
        try {
            if (!empty($_FILES['logo_file']['name'])) {
                $path = Uploader::store($_FILES['logo_file'], 'image');
                if (!empty($platform['logo']) && str_starts_with($platform['logo'], 'uploads/')) {
                    Uploader::delete($platform['logo']);
                }
                $platform['logo'] = $path;
                SiteData::putGroup('platform', $platform);
                flash_set('ok', 'لوگو ذخیره شد.');
            } else {
                flash_set('err', 'فایلی انتخاب نشده است.');
            }
        } catch (RuntimeException $e) {
            flash_set('err', $e->getMessage());
        }
        redirect('platform.php');
    }

    if ($action === 'logo_reset') {
        if (!empty($platform['logo']) && str_starts_with($platform['logo'], 'uploads/')) {
            Uploader::delete($platform['logo']);
        }
        $platform['logo'] = '';
        SiteData::putGroup('platform', $platform);
        flash_set('ok', 'لوگو حذف شد؛ حرف اول نام مجموعه استفاده می‌شود.');
        redirect('platform.php');
    }

    if ($action === 'mealimg') {
        $key = preg_replace('/[^a-z]/', '', (string) ($_POST['mkey'] ?? ''));
        $allowedKeys = ['breakfast', 'morning', 'lunch', 'afternoon', 'dinner', 'latenight', 'supplement'];
        if (!in_array($key, $allowedKeys, true)) {
            flash_set('err', 'کلید وعده نامعتبر است.');
            redirect('platform.php');
        }
        try {
            if (!empty($_FILES['mimg']['name'])) {
                $path = Uploader::store($_FILES['mimg'], 'image');
                $old = $platform['mealImages'][$key] ?? '';
                if ($old && str_starts_with($old, 'uploads/')) {
                    Uploader::delete($old);
                }
                $platform['mealImages'][$key] = $path;
                SiteData::putGroup('platform', $platform);
                flash_set('ok', 'تصویر وعده ذخیره شد.');
            } else {
                flash_set('err', 'فایلی انتخاب نشده است.');
            }
        } catch (RuntimeException $e) {
            flash_set('err', $e->getMessage());
        }
        redirect('platform.php');
    }

    if ($action === 'mealimg_reset') {
        $key = preg_replace('/[^a-z]/', '', (string) ($_POST['mkey'] ?? ''));
        $allowedKeys = ['breakfast', 'morning', 'lunch', 'afternoon', 'dinner', 'latenight', 'supplement'];
        if (in_array($key, $allowedKeys, true)) {
            $old = $platform['mealImages'][$key] ?? '';
            if ($old && str_starts_with($old, 'uploads/')) {
                Uploader::delete($old);
            }
            unset($platform['mealImages'][$key]);
            SiteData::putGroup('platform', $platform);
            flash_set('ok', 'تصویر به پیش‌فرض برگشت.');
        }
        redirect('platform.php');
    }

    if ($action === 'colors') {
        $colors = [];
        foreach (['accent', 'accent2', 'gold', 'ink', 'bg'] as $c) {
            $v = (string) ($_POST['col_' . $c] ?? '');
            $colors[$c] = preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? strtolower($v) : '#6f8b7a';
        }
        $platform['colors'] = $colors;
        SiteData::putGroup('platform', $platform);
        flash_set('ok', 'رنگ‌های پیش‌فرض پلتفرم ذخیره شد.');
        redirect('platform.php');
    }

    if ($action === 'tpl_add') {
        $key = 'custom_' . time();
        $colors = [];
        foreach (['accent', 'accent2', 'gold', 'ink', 'bg'] as $c) {
            $v = (string) ($_POST['c_' . $c] ?? '');
            $colors[$c] = preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? strtolower($v) : '#6f8b7a';
        }
        $platform['customTemplates'][] = [
            'key' => $key,
            'label' => mb_substr(clean_text($_POST['tpl_label'] ?? 'قالب جدید', 40), 0, 40),
            'dot' => $colors['accent'],
            'colors' => $colors,
            'badge' => '🎨',
        ];
        SiteData::putGroup('platform', $platform);
        flash_set('ok', 'قالب جدید اضافه شد؛ در پلتفرم قابل استفاده است.');
        redirect('platform.php');
    }

    if ($action === 'tpl_delete') {
        $key = preg_replace('/[^a-z0-9_]/i', '', (string) ($_POST['tpl_key'] ?? ''));
        $platform['customTemplates'] = array_values(array_filter(
            (array) ($platform['customTemplates'] ?? []),
            static fn($t) => ($t['key'] ?? '') !== $key
        ));
        SiteData::putGroup('platform', $platform);
        flash_set('ok', 'قالب حذف شد.');
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
$mealImgKeys = [
    'breakfast' => 'صبحانه', 'morning' => 'میان‌وعده صبح', 'lunch' => 'ناهار',
    'afternoon' => 'میان‌وعده عصر', 'dinner' => 'شام', 'latenight' => 'وعده آخر شب',
    'supplement' => 'مکمل‌ها',
];
$colors = (array) ($platform['colors'] ?? []);
$logoHref = !empty($platform['logo']) ? '../' . $platform['logo'] : '';
?>
<div class="card">
  <div class="card-head"><span class="material-symbols-rounded icon-lead">image</span><h2>لوگوی سربرگ رژیم</h2></div>
  <p class="card-note">در سربرگ برنامه غذایی خروجی نمایش داده می‌شود. اگر خالی باشد، حرف اول نام مجموعه استفاده می‌شود.</p>
  <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap">
    <?php if ($logoHref): ?>
    <img src="<?= e($logoHref) ?>" alt="لوگو" style="width:72px;height:72px;object-fit:contain;border-radius:14px;border:1px solid var(--border);background:#fff;padding:6px">
    <?php else: ?>
    <span style="width:72px;height:72px;display:grid;place-items:center;border:1px dashed var(--border);border-radius:14px;font-size:22px;font-weight:800;color:var(--muted)"><?= e(mb_substr($platform['brand'] ?: $platform['clinician'] ?: 'D', 0, 1)) ?></span>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
      <?= Csrf::field() ?>
      <input type="hidden" name="action" value="logo">
      <input class="input" type="file" name="logo_file" accept="image/*" style="max-width:260px">
      <button class="btn btn-primary btn-sm" type="submit"><span class="material-symbols-rounded" style="font-size:17px">upload</span>آپلود</button>
    </form>
    <?php if ($logoHref): ?>
    <form method="post">
      <?= Csrf::field() ?><input type="hidden" name="action" value="logo_reset">
      <button class="btn btn-danger btn-sm" type="submit"><span class="material-symbols-rounded" style="font-size:17px">delete</span>حذف لوگو</button>
    </form>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="card-head"><span class="material-symbols-rounded icon-lead">restaurant</span><h2>تصاویر وعده‌ها</h2></div>
  <p class="card-note">این تصاویر کنار عنوان هر وعده در خروجی چاپ می‌شوند. هر تصویر را آپلود کنید؛ بازنشانی به تصویر پیش‌فرض برمی‌گردد.</p>
  <?php foreach ($mealImgKeys as $mkey => $mlabel): $mimg = $platform['mealImages'][$mkey] ?? ''; ?>
  <div class="row-item" style="margin-bottom:10px;align-items:center">
    <?php if ($mimg): ?><img class="thumb" src="../<?= e($mimg) ?>" alt=""><?php else: ?>
    <span class="thumb" style="display:grid;place-items:center"><span class="material-symbols-rounded" style="color:var(--muted)">image</span></span>
    <?php endif; ?>
    <div class="row-body"><h3><?= e($mlabel) ?></h3><p><?= $mimg ? 'تصویر اختصاصی ✓' : 'پیش‌فرض پلتفرم' ?></p></div>
    <form method="post" enctype="multipart/form-data" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      <?= Csrf::field() ?>
      <input type="hidden" name="action" value="mealimg">
      <input type="hidden" name="mkey" value="<?= e($mkey) ?>">
      <input class="input" type="file" name="mimg" accept="image/*" style="max-width:220px">
      <button class="btn btn-ghost btn-sm" type="submit">آپلود</button>
    </form>
    <?php if ($mimg): ?>
    <form method="post">
      <?= Csrf::field() ?><input type="hidden" name="action" value="mealimg_reset"><input type="hidden" name="mkey" value="<?= e($mkey) ?>">
      <button class="icon-btn" type="submit" aria-label="بازنشانی"><span class="material-symbols-rounded" style="font-size:19px">restart_alt</span></button>
    </form>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>

<div class="card">
  <div class="card-head"><span class="material-symbols-rounded icon-lead">palette</span><h2>رنگ‌های پیش‌فرض صفحه رژیم</h2></div>
  <p class="card-note">پالت پایه پلتفرم؛ انتخاب قالب در مرحله خروجی، رنگ‌ها را نهایی می‌کند.</p>
  <form method="post">
    <?= Csrf::field() ?>
    <input type="hidden" name="action" value="colors">
    <div class="form-grid">
      <div class="field" style="display:flex;gap:14px;align-items:flex-end;flex-wrap:wrap;grid-column:1/-1">
        <?php foreach (['accent' => 'اصلی', 'accent2' => 'مکمل', 'gold' => 'طلایی', 'ink' => 'متن', 'bg' => 'پس‌زمینه'] as $c => $lbl): ?>
        <label style="text-align:center;font-size:12px;color:var(--muted)"><?= e($lbl) ?><input type="color" name="col_<?= e($c) ?>" value="<?= e($colors[$c] ?? ['accent' => '#6f8b7a', 'accent2' => '#8aa89a', 'gold' => '#b08a4a', 'ink' => '#1f2a2e', 'bg' => '#fafaf7'][$c]) ?>" style="width:52px;height:36px;border:0;background:none;display:block;margin-top:4px"></label>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit"><span class="material-symbols-rounded">save</span>ذخیره رنگ‌ها</button>
    </div>
  </form>
</div>
<?php
$builtinTemplates = [
    ['classic', 'کلاسیک', '#6f8b7a'], ['teen', 'نوجوان', '#7c5cbf'], ['young', 'جوان', '#e8603c'],
    ['athlete', 'ورزشکار', '#c0392b'], ['home', 'خانه‌دار', '#c96f4a'], ['royal', 'سلطنتی', '#1f2a44'],
    ['noir', 'نوآر طلایی', '#c9a227'], ['emerald', 'زمرد', '#d4af37'], ['marble', 'مرمر', '#b0895e'],
    ['silk', 'حریر', '#b98c2c'], ['violet', 'بنفش لوکس', '#6d4fa1'], ['minimal', 'مینیمال', '#111111'],
    ['pinstripe', 'شیاری', '#37474f'], ['botanic', 'بوتانیک', '#2e5e4e'], ['rose', 'رزگلد', '#b76e79'],
    ['ocean', 'آبی اقیانوس', '#1e6fb8'],
];
$customTemplates = (array) ($platform['customTemplates'] ?? []);
?>
<div class="card">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead">wallpaper</span>
    <h2>قالب‌های پلتفرم</h2>
    <span class="badge"><?= e(fa_digits((string) (count($builtinTemplates) + count($customTemplates)))) ?></span>
  </div>
  <p class="card-note">این قالب‌ها در مرحله خروجی پلتفرم قابل انتخاب هستند. قالب جدید اضافه کنید یا قالب‌های سفارشی را حذف کنید.</p>
  <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px;align-items:center">
    <?php foreach ($builtinTemplates as [$k, $label, $dot]): ?>
    <span class="badge" style="gap:6px"><span style="width:12px;height:12px;border-radius:50%;background:<?= e($dot) ?>;display:inline-block"></span><?= e($label) ?></span>
    <?php endforeach; ?>
    <?php foreach ($customTemplates as $t): ?>
    <span class="badge badge-ok" style="gap:6px">
      <span style="width:12px;height:12px;border-radius:50%;background:<?= e($t['dot'] ?? '#888') ?>;display:inline-block"></span><?= e($t['label']) ?>
    </span>
    <form method="post" data-confirm="این قالب سفارشی حذف شود؟">
      <?= Csrf::field() ?><input type="hidden" name="action" value="tpl_delete"><input type="hidden" name="tpl_key" value="<?= e($t['key']) ?>">
      <button class="icon-btn" type="submit" aria-label="حذف قالب" style="width:28px;height:28px;color:var(--danger)"><span class="material-symbols-rounded" style="font-size:16px">delete</span></button>
    </form>
    <?php endforeach; ?>
  </div>
  <form method="post">
    <?= Csrf::field() ?>
    <input type="hidden" name="action" value="tpl_add">
    <div class="form-grid">
      <div class="field">
        <label for="tpl_label">نام قالب جدید</label>
        <input class="input" id="tpl_label" name="tpl_label" placeholder="مثال: آبی آرام" required>
      </div>
      <div class="field" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
        <?php foreach (['accent' => 'اصلی', 'accent2' => 'مکمل', 'gold' => 'طلایی', 'ink' => 'متن', 'bg' => 'پس‌زمینه'] as $c => $lbl): ?>
        <label style="text-align:center;font-size:11px;color:var(--muted)"><?= e($lbl) ?><input type="color" name="c_<?= e($c) ?>" value="<?= e(['accent' => '#1e6fb8', 'accent2' => '#5aa9e6', 'gold' => '#c9a227', 'ink' => '#12233a', 'bg' => '#f3f7fc'][$c]) ?>" style="width:44px;height:32px;border:0;background:none;display:block;margin-top:4px"></label>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit"><span class="material-symbols-rounded">add</span>افزودن قالب</button>
    </div>
  </form>
</div>

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

<?php
/* ---------- لیست مراجعین: هر خروجی/پیش‌نویس پلتفرم اینجا ثبت می‌شود ---------- */
$clients = Database::all('SELECT * FROM fh_diet_records ORDER BY COALESCE(updated_at, created_at) DESC LIMIT 300');
$sourceLabels = ['manual' => 'دستی', 'ai' => 'هوش مصنوعی', 'word' => 'ورود از Word'];
$typeLabels = ['final' => 'رژیم نهایی', 'draft' => 'پیش‌نویس'];
?>
<div class="card">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead">groups</span>
    <h2>مراجعین پلتفرم</h2>
    <span class="badge"><?= e(fa_digits((string) count($clients))) ?></span>
  </div>
  <p class="card-note">هر بار برای یک مراجع خروجی یا پیش‌نویس ذخیره شود، همین‌جا ثبت می‌شود. با «بازکردن در پلتفرم» می‌توانید رژیم را ویرایش کنید و دوباره خروجی بگیرید؛ خروجی جدید همان رکورد را به‌روزرسانی می‌کند.</p>
  <?php if (!$clients): ?>
  <div class="empty"><span class="material-symbols-rounded">group_off</span>هنوز مراجعی ثبت نشده است. با اولین خروجی از پلتفرم، این لیست پر می‌شود.</div>
  <?php else: ?>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th>مراجع</th>
          <th>مشخصات</th>
          <th>نوع</th>
          <th>روش</th>
          <th>قالب</th>
          <th>آخرین به‌روزرسانی</th>
          <th>عملیات</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($clients as $c): $cm = json_decode((string) $c['patient_meta'], true) ?: []; ?>
        <tr>
          <td class="cell-title"><?= e($c['patient_name'] ?: '—') ?></td>
          <td class="cell-muted"><?= e(fa_digits((string) ($cm['age'] ?? ''))) ?> ساله · <?= e(fa_digits((string) ($cm['height'] ?? ''))) ?> سانتی‌متر · <?= e(fa_digits((string) ($cm['weight'] ?? ''))) ?> کیلوگرم · <?= e($cm['goal'] ?? '') ?><?= !empty($cm['date']) ? ' · ' . e(fa_digits((string) $cm['date'])) : '' ?></td>
          <td><span class="badge <?= $c['type'] === 'final' ? 'badge-ok' : 'badge-warn' ?>"><?= e($typeLabels[$c['type']] ?? $c['type']) ?></span></td>
          <td class="cell-muted"><?= $c['source'] ? e($sourceLabels[$c['source']] ?? $c['source']) : '—' ?></td>
          <td class="cell-muted"><?= e($c['template'] ?: '—') ?></td>
          <td class="cell-muted"><?= e(jalali_datetime($c['updated_at'] ?: $c['created_at'])) ?></td>
          <td class="cell-actions">
            <a class="icon-btn" href="../platform/index.php?record=<?= (int) $c['id'] ?>" target="_blank" rel="noopener" title="بازکردن در پلتفرم (ویرایش و خروجی)"><span class="material-symbols-rounded" style="font-size:19px">edit_document</span></a>
            <details style="display:inline-block">
              <summary class="icon-btn" style="list-style:none;cursor:pointer" aria-label="جزئیات"><span class="material-symbols-rounded" style="font-size:19px">visibility</span></summary>
              <div style="position:absolute;z-index:60;max-width:420px;max-height:300px;overflow:auto;background:var(--card);border:1px solid var(--border);border-radius:12px;padding:12px;box-shadow:var(--shadow);font-size:11.5px;line-height:1.9;white-space:pre-wrap;direction:ltr;text-align:left"><?= e(mb_substr(json_encode(json_decode((string) $c['data'], true), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: (string) $c['data'], 0, 4000)) ?></div>
            </details>
            <form method="post" data-confirm="این مراجع و رژیمش حذف شود؟">
              <?= Csrf::field() ?>
              <input type="hidden" name="action" value="client_delete">
              <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
              <button class="icon-btn" type="submit" aria-label="حذف" style="color:var(--danger)">
                <span class="material-symbols-rounded" style="font-size:19px">delete</span>
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
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
