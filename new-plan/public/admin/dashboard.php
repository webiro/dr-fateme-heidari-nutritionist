<?php
/** Admin dashboard — overview, AI idea generator, latest submissions. */
$active = 'dashboard';
$pageTitle = 'پیشخوان';
require __DIR__ . '/partials/layout_start.php';

$stats = [
    'unread' => (int) Database::val('SELECT COUNT(*) FROM fh_submissions WHERE is_read = 0'),
    'posts' => (int) Database::val('SELECT COUNT(*) FROM fh_posts WHERE is_active = 1'),
    'reels' => (int) Database::val('SELECT COUNT(*) FROM fh_reels WHERE is_active = 1'),
    'services' => (int) Database::val('SELECT COUNT(*) FROM fh_services WHERE is_active = 1'),
];
$latest = Database::all('SELECT id, name, goal, phone, is_read, created_at FROM fh_submissions ORDER BY id DESC LIMIT 5');
$lastLogin = $adminUser['last_login_at'];
$aiConfigured = AiService::isConfigured();
$aiBatchCount = (int) Database::val('SELECT COUNT(*) FROM fh_ai_batches');
?>
<div class="ai-card">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead" style="color:var(--accent)">auto_awesome</span>
    <h2>دستیار محتوایی هوشمند</h2>
    <div class="spacer"></div>
    <a class="btn btn-ghost btn-sm" href="ai_ideas.php">
      <span class="material-symbols-rounded" style="font-size:17px">history</span>
      آرشیو ایده‌ها
      <?php if ($aiBatchCount): ?><span class="badge badge-muted"><?= e(fa_digits((string) $aiBatchCount)) ?></span><?php endif; ?>
    </a>
    <a class="btn btn-ghost btn-sm" href="ai.php">
      <span class="material-symbols-rounded" style="font-size:17px">settings</span>
      تنظیمات
    </a>
  </div>
  <p class="card-note">۱۰ خبر و موضوع ترند حوزه تغذیه را از هوش مصنوعی (پاستا کلاد، مدل DeepSeek) بگیرید؛ همه چیز آرشیو می‌شود.</p>
  <div id="ai-result" class="idea-list" style="margin-top:12px"></div>
  <div id="ai-error" class="login-error" style="display:none;margin-top:12px">
    <span class="material-symbols-rounded">error</span><span id="ai-error-text"></span>
  </div>
  <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-top:8px">
    <button class="btn btn-accent" type="button" id="ai-generate" <?= $aiConfigured ? '' : 'disabled' ?>>
      <span class="material-symbols-rounded">auto_awesome</span>
      تولید ۱۰ خبر جدید
    </button>
    <?php if (!$aiConfigured): ?>
    <span style="font-size:13px;color:var(--muted)">برای فعال شدن، اول از <a href="ai.php" style="color:var(--primary);font-weight:700">تنظیمات هوشمند</a> کلید API پاستا را ذخیره کنید.</span>
    <?php endif; ?>
  </div>
</div>

<div class="stat-grid">
  <a class="stat-card<?= $stats['unread'] ? ' warn' : '' ?>" href="messages.php">
    <span class="stat-ico"><span class="material-symbols-rounded">mark_email_unread</span></span>
    <div><b><?= e(fa_digits((string) $stats['unread'])) ?></b><span>درخواست جدید</span></div>
  </a>
  <a class="stat-card" href="posts.php">
    <span class="stat-ico"><span class="material-symbols-rounded">photo_library</span></span>
    <div><b><?= e(fa_digits((string) $stats['posts'])) ?></b><span>پست اسلایدی فعال</span></div>
  </a>
  <a class="stat-card" href="reels.php">
    <span class="stat-ico"><span class="material-symbols-rounded">movie</span></span>
    <div><b><?= e(fa_digits((string) $stats['reels'])) ?></b><span>ریلز فعال</span></div>
  </a>
  <a class="stat-card ok" href="texts.php">
    <span class="stat-ico"><span class="material-symbols-rounded">edit_document</span></span>
    <div><b><?= e(fa_digits((string) $stats['services'])) ?></b><span>خدمات فعال</span></div>
  </a>
</div>

<div class="card">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead">inbox</span>
    <h2>آخرین درخواست‌های مشاوره</h2>
    <div class="spacer"></div>
    <a class="btn btn-ghost btn-sm" href="messages.php">مشاهده همه</a>
  </div>
  <?php if (!$latest): ?>
  <div class="empty"><span class="material-symbols-rounded">inbox</span>هنوز درخواستی ثبت نشده است. اولین پیام‌ها همین‌جا نمایش داده می‌شوند.</div>
  <?php else: ?>
  <div class="rows">
    <?php foreach ($latest as $msg): ?>
    <div class="row-item">
      <div class="row-body">
        <h3><?= e($msg['name']) ?> <?= !$msg['is_read'] ? '<span class="badge">جدید</span>' : '' ?></h3>
        <p><?= e($msg['goal']) ?> · <?= e($msg['phone']) ?> · <?= e(jalali_datetime($msg['created_at'])) ?></p>
      </div>
      <div class="row-tools">
        <a class="btn btn-ghost btn-sm" href="messages.php#msg-<?= (int) $msg['id'] ?>">مشاهده</a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead">tips_and_updates</span>
    <h2>دسترسی سریع</h2>
  </div>
  <div class="rows">
    <div class="row-item">
      <div class="row-body">
        <h3>ترتیب و نمایش سکشن‌ها</h3>
        <p>سکشن‌های صفحه اصلی را جابه‌جا کنید، خاموش/روشن کنید و پیش‌نمایش زنده ببینید.</p>
      </div>
      <div class="row-tools"><a class="btn btn-ghost btn-sm" href="sections.php">مدیریت سکشن‌ها</a></div>
    </div>
    <div class="row-item">
      <div class="row-body">
        <h3>افزودن ریلز جدید</h3>
        <p>آپلود فایل ویدیو، درج لینک مستقیم یا اسکریپت آپارات.</p>
      </div>
      <div class="row-tools"><a class="btn btn-ghost btn-sm" href="reel_add.php">افزودن ریلز</a></div>
    </div>
    <div class="row-item">
      <div class="row-body">
        <h3>آدرس و راه‌های ارتباطی</h3>
        <p>آدرس مطب، موقعیت نقشه، شماره تماس و شبکه‌های اجتماعی.</p>
      </div>
      <div class="row-tools"><a class="btn btn-ghost btn-sm" href="location.php">آدرس مطب</a></div>
    </div>
  </div>
  <?php if ($lastLogin): ?>
  <p class="card-note" style="margin-top:12px">آخرین ورود شما: <?= e(jalali_datetime($lastLogin)) ?></p>
  <?php endif; ?>
</div>

<script>
  (function () {
    var btn = document.getElementById("ai-generate");
    if (!btn) return;
    var result = document.getElementById("ai-result");
    var errBox = document.getElementById("ai-error");
    var errText = document.getElementById("ai-error-text");

    btn.addEventListener("click", function () {
      btn.disabled = true;
      var old = btn.innerHTML;
      btn.innerHTML = '<span class="material-symbols-rounded">progress_activity</span>در حال تولید…';
      errBox.style.display = "none";
      result.innerHTML = "";

      var fd = new FormData();
      fd.append("_csrf", document.querySelector("input[name=_csrf]").value);
      fd.append("action", "generate");

      fetch("ai_generate.php", { method: "POST", body: fd })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (!data.ok) {
            errText.textContent = data.error || "خطای ناشناخته";
            errBox.style.display = "flex";
            return;
          }
          data.items.forEach(function (item, i) {
            var el = document.createElement("div");
            el.className = "idea-item";
            var icon = item.format === "reel" ? "movie" : (item.format === "article" ? "article" : "photo_library");
            var fmt = item.format === "reel" ? "ریلز" : (item.format === "article" ? "مقاله" : "پست");
            el.innerHTML =
              '<span class="idea-badge"><span class="material-symbols-rounded">' + icon + '</span></span>' +
              '<div style="flex:1;min-width:0">' +
              '<h3>' + (i + 1) + '. ' + (item.title || "") + '</h3>' +
              (item.summary ? '<p>' + item.summary + '</p>' : '') +
              '<div class="idea-meta">' +
              '<span class="badge badge-muted">' + fmt + '</span>' +
              (item.hashtag ? '<span class="badge badge-muted" dir="ltr">' + item.hashtag + '</span>' : '') +
              '</div></div>';
            result.appendChild(el);
          });
        })
        .catch(function () {
          errText.textContent = "ارتباط با سرور برقرار نشد؛ دوباره تلاش کنید.";
          errBox.style.display = "flex";
        })
        .finally(function () {
          btn.disabled = false;
          btn.innerHTML = old;
        });
    });
  })();
</script>
<?php require __DIR__ . '/partials/layout_end.php'; ?>
