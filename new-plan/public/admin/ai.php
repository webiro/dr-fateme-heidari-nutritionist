<?php
/** AI settings — Paasta Cloud API key, model, master prompt. */
$active = 'ai';
$pageTitle = 'تنظیمات هوشمند';
require __DIR__ . '/partials/layout_start.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    Csrf::check();
    $action = $_POST['action'] ?? '';
    $ai = SiteData::group('ai');

    if ($action === 'save') {
        $ai['apiKey'] = trim((string) ($_POST['api_key'] ?? ''));
        $ai['model'] = clean_text($_POST['model'] ?? $ai['model'], 120);
        $ai['masterPrompt'] = (string) ($_POST['master_prompt'] ?? $ai['masterPrompt']);
        if ($ai['apiKey'] === '' && isset($_POST['api_key'])) {
            // empty submission = keep old key if a masked value came back
        }
        if (isset($_POST['api_key']) && ($_POST['api_key'] === '••••••••••••' || $_POST['api_key'] === '')) {
            $ai['apiKey'] = $ai['apiKey']; // unchanged
        }
        if ($ai['masterPrompt'] === '') {
            flash_set('err', 'مسترپرامپت نمی‌تواند خالی باشد.');
            redirect('ai.php');
        }
        SiteData::putGroup('ai', $ai);
        flash_set('ok', 'تنظیمات هوش مصنوعی ذخیره شد.');
        redirect('ai.php');
    }

    if ($action === 'reset_prompt') {
        $ai['masterPrompt'] = SiteData::group('ai')['masterPrompt'] ?: '';
        // force-default: rebuild from defaults
        $defaults = SiteData::defaults();
        $ai['masterPrompt'] = $defaults['ai']['masterPrompt'];
        SiteData::putGroup('ai', $ai);
        flash_set('ok', 'مسترپرامپت به حالت پیش‌فرض برگشت.');
        redirect('ai.php');
    }
}

$ai = SiteData::group('ai');
$hasKey = $ai['apiKey'] !== '';
$maskedKey = $hasKey ? '••••••••••••' : '';
?>
<div class="card">
  <div class="card-head"><span class="material-symbols-rounded icon-lead">neurology</span><h2>اتصال به پاستا کلاد</h2></div>
  <p class="card-note">
    کلید API را از <b dir="ltr">console.paasta.cloud/ai/keys</b> بگیرید و اینجا ذخیره کنید.
    با این اتصال، پیشخوان می‌تواند ایده محتوایی و خبرهای ترند حوزه تغذیه تولید کند.
  </p>
  <form method="post" data-dirty>
    <?= Csrf::field() ?>
    <input type="hidden" name="action" value="save">
    <div class="field">
      <label for="api_key">کلید API پاستا <?= $hasKey ? '<span class="badge badge-ok">ذخیره شده ✓</span>' : '<span class="badge badge-warn">تنظیم نشده</span>' ?></label>
      <input class="input" dir="ltr" style="text-align:left" id="api_key" name="api_key"
             value="<?= e($maskedKey) ?>" placeholder="کلید API پاستا کلاد" autocomplete="off">
      <p class="hint">اگر فیلد را دست نزنید، کلید فعلی حفظ می‌شود.</p>
    </div>
    <div class="field">
      <label for="model">مدل</label>
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <input class="input" dir="ltr" style="text-align:left;flex:1;min-width:220px" id="model" name="model"
               value="<?= e($ai['model']) ?>" placeholder="deepseek/deepseek-v4-flash" autocomplete="off">
        <button class="btn btn-ghost" type="button" id="fetch-models">
          <span class="material-symbols-rounded" style="font-size:19px">cloud_download</span>
          دریافت لیست مدل‌ها
        </button>
      </div>
      <div class="model-list" id="model-list" hidden></div>
      <p class="hint">پیش‌فرض اقتصادی: <b dir="ltr">deepseek/deepseek-v4-flash</b> · مدل رایگان تست: <b dir="ltr">paasta/deepseek-v4-flash-0731:free</b></p>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit">
        <span class="material-symbols-rounded">save</span>
        ذخیره تنظیمات
      </button>
    </div>
  </form>
</div>

<div class="card">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead">note_stack</span>
    <h2>مسترپرامپت</h2>
    <div class="spacer"></div>
    <form method="post" style="display:inline">
      <?= Csrf::field() ?>
      <input type="hidden" name="action" value="reset_prompt">
      <button class="btn btn-ghost btn-sm" type="submit" data-confirm="مسترپرامپت به پیش‌فرض برگردد؟">بازگشت به پیش‌فرض</button>
    </form>
  </div>
  <p class="card-note">این دستور هر بار که در پیشخوان «تولید ایده جدید» می‌زنید، همراه با درخواست به مدل ارسال می‌شود. خروجی باید JSON لیست ایده‌ها باشد.</p>
  <form method="post" data-dirty>
    <?= Csrf::field() ?>
    <input type="hidden" name="action" value="save">
    <div class="field">
      <textarea class="textarea" dir="rtl" name="master_prompt" style="min-height:220px;font-size:13.5px"><?= e($ai['masterPrompt']) ?></textarea>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit">
        <span class="material-symbols-rounded">save</span>
        ذخیره مسترپرامپت
      </button>
    </div>
  </form>
</div>
<script>
  document.getElementById("fetch-models")?.addEventListener("click", function () {
    var btn = this, list = document.getElementById("model-list"), form = btn.closest("form");
    btn.disabled = true;
    btn.textContent = "در حال دریافت…";
    var fd = new FormData(form);
    fetch("ai_models.php", { method: "POST", body: fd })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        list.innerHTML = "";
        if (!data.ok) {
          list.hidden = false;
          list.innerHTML = '<button type="button" style="color:var(--danger)">' + (data.error || "خطا") + '</button>';
        } else if (!data.models.length) {
          list.hidden = false;
          list.innerHTML = '<button type="button">مدلی یافت نشد</button>';
        } else {
          list.hidden = false;
          data.models.forEach(function (id) {
            var b = document.createElement("button");
            b.type = "button";
            b.textContent = id;
            b.addEventListener("click", function () {
              document.getElementById("model").value = id;
              list.hidden = true;
            });
            list.appendChild(b);
          });
        }
      })
      .catch(function () {
        list.hidden = false;
        list.innerHTML = '<button type="button" style="color:var(--danger)">ارتباط با سرور برقرار نشد</button>';
      })
      .finally(function () {
        btn.disabled = false;
        btn.innerHTML = '<span class="material-symbols-rounded" style="font-size:19px">cloud_download</span>دریافت لیست مدل‌ها';
      });
  });
</script>
<?php require __DIR__ . '/partials/layout_end.php'; ?>
