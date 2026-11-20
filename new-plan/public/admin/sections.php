<?php
/** Landing sections manager — order + enable/disable + edit links + live preview. */
$active = 'sections';
$pageTitle = 'ترتیب سکشن‌ها';
require __DIR__ . '/partials/layout_start.php';

$sectionMeta = [
    'intro' => ['label' => 'معرفی', 'desc' => 'متن معرفی و برچسب‌ها', 'edit' => 'texts.php', 'icon' => 'short_text'],
    'services' => ['label' => 'خدمات', 'desc' => 'کارت‌های خدمات تغذیه', 'edit' => 'services.php', 'icon' => 'medical_information'],
    'faq' => ['label' => 'سوالات متداول', 'desc' => 'پرسش و پاسخ‌های سایت', 'edit' => 'faq.php', 'icon' => 'quiz'],
    'videos' => ['label' => 'ریلزها', 'desc' => 'کاروسل ویدیوها', 'edit' => 'reels.php', 'icon' => 'movie'],
    'posts' => ['label' => 'پست‌های اسلایدی', 'desc' => 'گالری پست‌های اینستاگرام', 'edit' => 'posts.php', 'icon' => 'photo_library'],
    'contact' => ['label' => 'راه‌های ارتباطی', 'desc' => 'کارت‌های تماس و شبکه‌ها', 'edit' => 'settings.php', 'icon' => 'share'],
    'appoint' => ['label' => 'فرم رزرو مشاوره', 'desc' => 'فرم ثبت درخواست مشاوره', 'edit' => 'texts.php', 'icon' => 'calendar_month'],
    'article' => ['label' => 'متن سئو', 'desc' => 'مقاله پایین صفحه', 'edit' => 'seo_content.php', 'icon' => 'article'],
    'partners' => ['label' => 'همکاران', 'desc' => 'نوار لوگوی همکاری‌ها', 'edit' => 'texts.php', 'icon' => 'handshake'],
    'location' => ['label' => 'آدرس مطب', 'desc' => 'نقشه و مسیریابی', 'edit' => 'location.php', 'icon' => 'location_on'],
];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'move') {
        $order = SiteData::sectionOrder();
        $key = (string) ($_POST['key'] ?? '');
        $direction = ($_POST['direction'] ?? '') === 'up' ? -1 : 1;
        $idx = array_search($key, $order, true);
        if ($idx !== false) {
            $j = $idx + $direction;
            if ($j >= 0 && $j < count($order)) {
                [$order[$idx], $order[$j]] = [$order[$j], $order[$idx]];
                SiteData::putGroup('sectionOrder', array_values($order));
            }
        }
        redirect('sections.php');
    }
    if ($action === 'toggle') {
        $key = (string) ($_POST['key'] ?? '');
        $toggleKey = $key === 'appoint' ? 'appointment' : $key;
        $sections = SiteData::group('sections');
        if (array_key_exists($toggleKey, $sections)) {
            $sections[$toggleKey] = !empty($sections[$toggleKey]) ? false : true;
            SiteData::putGroup('sections', $sections);
        }
        redirect('sections.php');
    }
}

$order = SiteData::sectionOrder();
$sections = SiteData::group('sections');
?>
<div class="sections-layout">
  <div class="card" style="margin-bottom:0">
    <div class="card-head">
      <span class="material-symbols-rounded icon-lead">view_agenda</span>
      <h2>سکشن‌های صفحه اصلی</h2>
    </div>
    <p class="card-note">روی هر سکشن بزنید تا در پیش‌نمایش سمت چپ دیده شود. با فلش‌ها ترتیب را عوض کنید و با کلید، سکشن را خاموش/روشن کنید. «هیرو» همیشه بالای صفحه است.</p>
    <?php foreach ($order as $i => $key): $meta = $sectionMeta[$key]; $enabled = !empty($sections[$key === 'appoint' ? 'appointment' : $key]); ?>
    <div class="section-row-item<?= $enabled ? '' : ' off' ?>" data-section-row data-section-id="<?= e($key) ?>">
      <span class="sec-num"><?= e(fa_digits((string) ($i + 1))) ?></span>
      <span class="material-symbols-rounded" style="color:var(--primary)"><?= $meta['icon'] ?></span>
      <div style="flex:1;min-width:0">
        <div class="sec-name"><?= e($meta['label']) ?></div>
        <div style="font-size:11.5px;color:var(--muted)"><?= e($meta['desc']) ?></div>
      </div>
      <form method="post" style="display:contents">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="move">
        <input type="hidden" name="key" value="<?= e($key) ?>">
        <input type="hidden" name="direction" value="">
        <button class="icon-btn" type="button" data-move="up" data-move-key="<?= e($key) ?>" aria-label="بالا" <?= $i === 0 ? 'disabled' : '' ?> style="width:36px;height:36px">
          <span class="material-symbols-rounded" style="font-size:18px">arrow_upward</span>
        </button>
        <button class="icon-btn" type="button" data-move="down" data-move-key="<?= e($key) ?>" aria-label="پایین" <?= $i === count($order) - 1 ? 'disabled' : '' ?> style="width:36px;height:36px">
          <span class="material-symbols-rounded" style="font-size:18px">arrow_downward</span>
        </button>
      </form>
      <form method="post" style="display:contents">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="toggle">
        <input type="hidden" name="key" value="<?= e($key) ?>">
        <button class="icon-btn" type="submit" aria-label="فعال/غیرفعال">
          <span class="material-symbols-rounded" style="font-size:19px"><?= $enabled ? 'visibility' : 'visibility_off' ?></span>
        </button>
      </form>
      <a class="icon-btn" href="<?= e($meta['edit']) ?>" aria-label="ویرایش محتوا" title="ویرایش محتوای این سکشن">
        <span class="material-symbols-rounded" style="font-size:19px">edit</span>
      </a>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="card" style="margin-bottom:0">
    <div class="card-head">
      <span class="material-symbols-rounded icon-lead">preview</span>
      <h2>پیش‌نمایش زنده سایت</h2>
      <div class="spacer"></div>
      <a class="btn btn-ghost btn-sm" href="../index.php" target="_blank" rel="noopener">
        <span class="material-symbols-rounded" style="font-size:17px">open_in_new</span>
        نمای تمام‌صفحه
      </a>
    </div>
    <iframe class="preview-frame" data-preview-frame src="../index.php" title="پیش‌نمایش سایت"></iframe>
    <p class="preview-hint">این قاب، نمای واقعی سایت است. تغییر ترتیب یا فعال/غیرفعال کردن سکشن‌ها بلافاصله اینجا هم اعمال می‌شود.</p>
  </div>
</div>
<?php require __DIR__ . '/partials/layout_end.php'; ?>
