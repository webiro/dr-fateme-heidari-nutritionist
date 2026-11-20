<?php
/** Public landing page — same frontend as the static site, data from the database. */

require_once __DIR__ . '/bootstrap.php';

// If the site is not installed yet, hand over to the installer.
try {
    Database::pdo();
} catch (Throwable $e) {
    require __DIR__ . '/install.php';
    exit;
}

$cacheKey = 'landing';
if (!isset($_GET['nocache'])) {
    $cached = SiteData::pageCache($cacheKey);
    if ($cached !== null) {
        echo $cached;
        exit;
    }
}

$config = SiteData::config();
$headings = $config['headings'];
$sections = $config['sections'];
$sectionOrder = SiteData::sectionOrder();
$identity = $config['doctor'];
$seo = $config['seo'];
$links = $config['links'];
$location = $config['location'];
$articleHtml = SiteData::articleHtml();
// intro paragraph: admin-authored inline HTML (strip every attribute for safety)
$introParagraphHtml = preg_replace('/<([a-z0-9]+)[^>]*>/i', '<$1>', (string) ($headings['introParagraph'] ?? ''));
$V = SiteData::version(); // cache-bust version

$domain = (string) $config['domain'];
$siteUrl = 'https://' . $domain . '/';
$ogImageUrl = $siteUrl . ltrim((string) ($seo['ogImage'] ?: 'assets/images/og-image.jpg'), '/');
$sameAs = array_values(array_filter([
    $links['instagram'] ?? '', $links['telegram'] ?? '', $links['whatsapp'] ?? '',
    $links['bale'] ?? '', $links['rubika'] ?? '', $links['eitaa'] ?? '', $links['website'] ?? '',
]));
$contactPoints = [];
if (!empty($links['phone'])) {
    $contactPoints[] = [
        '@type' => 'ContactPoint',
        'telephone' => $links['phone'],
        'contactType' => 'customer support',
        'availableLanguage' => 'Persian',
    ];
}
if (!empty($links['whatsapp'])) {
    $contactPoints[] = ['@type' => 'ContactPoint', 'url' => $links['whatsapp'], 'contactType' => 'WhatsApp'];
}
if (!empty($links['tisaAssistant'])) {
    $contactPoints[] = ['@type' => 'ContactPoint', 'url' => $links['tisaAssistant'], 'contactType' => 'AI nutrition assistant', 'availableLanguage' => 'Persian'];
}

$ldGraph = [
    [
        '@type' => 'Physician',
        '@id' => $siteUrl . '#physician',
        'name' => 'دکتر ' . $identity['nameFa'],
        'alternateName' => array_values(array_filter([$identity['nameEn'], 'Dr. ' . $identity['nameEn'], $identity['nameFa'] . ' نائیج'])),
        'url' => $siteUrl,
        'description' => $identity['title'] . ' در ' . $identity['city'] . '، ' . $identity['province'] . '، ایران.',
        'image' => $siteUrl . 'assets/images/doctor.jpg',
        'identifier' => $identity['medicalCode'],
        'address' => [
            '@type' => 'PostalAddress',
            'addressLocality' => $identity['city'],
            'addressRegion' => $identity['province'],
            'addressCountry' => 'IR',
        ],
        'areaServed' => [
            '@type' => 'City',
            'name' => $identity['city'],
            'containedInPlace' => ['@type' => 'AdministrativeArea', 'name' => 'استان ' . $identity['province']],
        ],
        'availableLanguage' => ['Persian'],
        'medicalSpecialty' => 'https://schema.org/DietNutrition',
        'sameAs' => $sameAs,
    ],
    [
        '@type' => 'Person',
        '@id' => $siteUrl . '#person',
        'name' => $identity['nameFa'] . ' نائیج',
        'alternateName' => array_values(array_filter([$identity['nameEn'], $identity['nameFa']])),
        'jobTitle' => $identity['title'],
        'description' => $seo['description'],
        'image' => $siteUrl . 'assets/images/doctor.jpg',
        'url' => $siteUrl,
        'identifier' => $identity['medicalCode'],
        'knowsAbout' => ['تغذیه', 'رژیم غذایی', 'تغذیه ورزشی', 'تغذیه درمانی', 'دکتر تغذیه در نوشهر', 'متخصص تغذیه در نوشهر', 'دکتر فاطمه حیدری'],
    ],
    [
        '@type' => 'WebSite',
        '@id' => $siteUrl . '#website',
        'url' => $siteUrl,
        'name' => 'وب‌سایت دکتر ' . $identity['nameFa'],
        'inLanguage' => 'fa-IR',
        'publisher' => ['@id' => $siteUrl . '#person'],
    ],
];
if (!empty($seo['ratingValue']) && !empty($seo['ratingCount'])) {
    $ldGraph[] = [
        '@type' => 'Physician',
        '@id' => $siteUrl . '#physician',
        'name' => 'دکتر ' . $identity['nameFa'],
        'url' => $siteUrl,
        'aggregateRating' => [
            '@type' => 'AggregateRating',
            'ratingValue' => (string) $seo['ratingValue'],
            'bestRating' => '5',
            'worstRating' => '1',
            'ratingCount' => (string) $seo['ratingCount'],
        ],
    ];
}
if ($contactPoints) {
    $ldGraph[1]['contactPoint'] = $contactPoints;
}

function json_block(string $id, array $value): string
{
    $body = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    $body = str_replace('</', '<\\/', (string) $body);
    return "  <script type=\"application/json\" id=\"{$id}\">\n{$body}\n  </script>";
}

$ldJson = json_encode(
    ['@context' => 'https://schema.org', '@graph' => $ldGraph],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
);

ob_start();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($seo['title']) ?></title>
  <meta name="description" content="<?= e($seo['description']) ?>">
  <meta name="robots" content="<?= e($seo['robots'] ?: 'index,follow') ?>">
  <link rel="canonical" href="<?= e($siteUrl) ?>">
  <meta name="author" content="<?= e($identity['nameFa']) ?>">

  <!-- Open Graph (Facebook / Telegram / WhatsApp / LinkedIn) -->
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="<?= e($seo['ogSiteName']) ?>">
  <meta property="og:title" content="<?= e($seo['ogTitle']) ?>">
  <meta property="og:description" content="<?= e($seo['ogDescription']) ?>">
  <meta property="og:image" content="<?= e($ogImageUrl) ?>">
  <meta property="og:image:alt" content="<?= e($seo['ogImageAlt']) ?>">
  <meta property="og:url" content="<?= e($siteUrl) ?>">
  <meta property="og:locale" content="fa_IR">

  <!-- Twitter / X -->
  <meta name="twitter:card" content="<?= e($seo['twitterCard'] ?: 'summary_large_image') ?>">
  <meta name="twitter:title" content="<?= e($seo['ogTitle']) ?>">
  <meta name="twitter:description" content="<?= e($seo['description']) ?>">
  <meta name="twitter:image" content="<?= e($ogImageUrl) ?>">
  <meta name="twitter:image:alt" content="<?= e($seo['ogImageAlt']) ?>">

  <link rel="icon" href="assets/images/logo.svg" type="image/svg+xml">
  <link rel="stylesheet" href="assets/vendor/fonts/site-fonts.css?v=<?= e($V) ?>">
  <link rel="stylesheet" href="assets/vendor/owl/owl.carousel.min.css">
  <link rel="stylesheet" href="assets/vendor/plyr/plyr.css">
  <link rel="stylesheet" href="assets/app.css?v=<?= e($V) ?>" id="app-css">

  <script type="application/ld+json">
<?= $ldJson ?>
  </script>
</head>

<body class="theme-v1">
  <div id="loader" role="status" aria-live="polite">
    <div class="loader-mark">
      <div class="loader-mono">
        <svg class="loader-leaf" viewBox="0 0 48 48" aria-hidden="true">
          <path d="M24 6c10 2 18 12 14 24-3 8-10 12-16 6-2-3 0-12 2-30z" fill="none" stroke="#b0c4de"
            stroke-width="1.4" />
          <path d="M24 14c4 6 6 14 4 20" fill="none" stroke="#f5b7c1" stroke-width="1" />
        </svg>
      </div>
      <div class="loader-name">فاطمه حیدری</div>
    </div>
  </div>

  <div class="cursor-glow" aria-hidden="true"></div>

  <header class="site-header">
    <div class="header-inner">
      <a class="brand" href="#hero" aria-label="صفحه اصلی">
        <img src="assets/images/logo.svg" alt="" width="42" height="42">
        <span class="brand-text">
          <strong><?= e($identity['shortNameFa']) ?></strong>
          <span><?= e($identity['title']) ?></span>
        </span>
      </a>
      <nav class="nav-desktop" aria-label="ناوبری اصلی">
        <?php foreach ($config['menu'] as $item): if (!$item['desktop']) continue; ?>
        <a href="<?= e($item['href']) ?>"><?= e($item['label']) ?></a>
        <?php endforeach; ?>
      </nav>
      <div class="header-actions">
        <button class="theme-btn" type="button" data-theme-toggle aria-label="تغییر زمینه">
          <span class="material-symbols-rounded">dark_mode</span>
        </button>
        <a class="btn btn-primary" href="#appoint">
          <span class="material-symbols-rounded">calendar_month</span>
          <span class="header-cta-text">رزرو مشاوره</span>
        </a>
        <button class="menu-btn" type="button" aria-label="منو" aria-expanded="false">
          <span class="material-symbols-rounded">menu</span>
        </button>
      </div>
    </div>
  </header>

  <div class="mobile-panel" aria-hidden="true">
    <div style="display:flex;justify-content:space-between;align-items:center">
      <strong>منو</strong>
      <button class="theme-btn menu-close" type="button" aria-label="بستن منو">
        <span class="material-symbols-rounded">close</span>
      </button>
    </div>
    <nav aria-label="منوی موبایل">
      <?php foreach ($config['menu'] as $item): if (!$item['mobile']) continue; ?>
      <a href="<?= e($item['href']) ?>"><?= e($item['label']) ?></a>
      <?php endforeach; ?>
    </nav>
  </div>

  <main id="main">
    <?php
    // Hero is always first; the rest follow the admin-configured order.
    require __DIR__ . '/partials/sections/hero.php';
    foreach ($sectionOrder as $sectionKey) {
        $toggleKey = $sectionKey === 'appoint' ? 'appointment' : $sectionKey;
        if (empty($sections[$toggleKey])) {
            continue;
        }
        require __DIR__ . '/partials/sections/' . $sectionKey . '.php';
    }
    ?>
  </main>

  <div class="lightbox" id="lightbox" aria-hidden="true" role="dialog" aria-label="گالری پست">
    <button class="lb-close" id="lightbox-close" type="button" aria-label="بستن"><span
        class="material-symbols-rounded">close</span></button>
    <div class="lightbox-stage">
      <button class="lb-btn lb-prev" id="lightbox-prev" type="button" aria-label="اسلاید قبلی"><span
          class="material-symbols-rounded">chevron_right</span></button>
      <img id="lightbox-img" alt="">
      <button class="lb-btn lb-next" id="lightbox-next" type="button" aria-label="اسلاید بعدی"><span
          class="material-symbols-rounded">chevron_left</span></button>
      <div class="lb-counter" id="lightbox-counter"></div>
    </div>
  </div>

  <footer class="site-footer">
    <div class="wrap footer-grid">
      <div>
        <div class="footer-brand">
          <img src="assets/images/doctor.jpg" alt="" width="36" height="36">
          <strong><?= e($identity['nameFa']) ?> | <?= e($identity['title']) ?></strong>
        </div>
        <p class="footer-copy"><?= e($identity['footerText']) ?></p>
        <div class="copyright"><?= e($identity['shortNameFa']) ?> · <?= e($identity['city']) ?>، <?= e($identity['province']) ?> · <span id="year"></span></div>
      </div>
      <div>
        <div class="slogan" id="asap-slogan" lang="en" dir="ltr"></div>
      </div>
    </div>
  </footer>

  <script>document.getElementById("year").textContent = new Date().getFullYear();</script>
  <script>
    if('serviceWorker' in navigator){
      window.addEventListener('load', function(){
        navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(function(){});
      });
    }
  </script>
<?= json_block('site-config', $config) . "\n" . json_block('site-slogans', SiteData::slogans()) . "\n" . json_block('site-media', SiteData::media()) ?>

  <script src="assets/vendor/jquery/jquery-3.7.1.min.js" defer></script>
  <script src="assets/vendor/owl/owl.carousel.min.js" defer></script>
  <script src="assets/vendor/plyr/plyr.polyfilled.js" defer></script>
  <script src="assets/app.js?v=<?= e($V) ?>" defer></script>
</body>

</html>
<?php
$html = (string) ob_get_clean();
SiteData::savePageCache($cacheKey, $html);
echo $html;
