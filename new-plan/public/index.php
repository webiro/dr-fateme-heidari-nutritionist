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
$identity = $config['doctor'];
$seo = $config['seo'];
$location = $config['location'];
$articleHtml = SiteData::articleHtml();
$V = SiteData::version(); // cache-bust version

function json_block(string $id, array $value): string
{
    $body = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    $body = str_replace('</', '<\\/', (string) $body);
    return "  <script type=\"application/json\" id=\"{$id}\">\n{$body}\n  </script>";
}

ob_start();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($seo['title']) ?></title>
  <meta name="description" content="<?= e($seo['description']) ?>">
  <meta name="robots" content="index,follow">
  <link rel="canonical" href="https://<?= e($config['domain']) ?>/">
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= e($seo['ogTitle']) ?>">
  <meta property="og:description" content="<?= e($seo['ogDescription']) ?>">
  <meta property="og:image" content="assets/images/og-image.jpg">
  <meta property="og:url" content="https://<?= e($config['domain']) ?>/">
  <meta property="og:locale" content="fa_IR">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= e($seo['ogTitle']) ?>">
  <meta name="twitter:description" content="<?= e($seo['description']) ?>">
  <meta name="twitter:image" content="assets/images/og-image.jpg">
  <link rel="icon" href="assets/images/logo.svg" type="image/svg+xml">
  <link rel="stylesheet" href="assets/vendor/fonts/site-fonts.css?v=<?= e($V) ?>">
  <link rel="stylesheet" href="assets/vendor/owl/owl.carousel.min.css">
  <link rel="stylesheet" href="assets/vendor/plyr/plyr.css">
  <link rel="stylesheet" href="assets/app.css?v=<?= e($V) ?>" id="app-css">

  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Person",
    "name": "فاطمه حیدری نائیج",
    "alternateName": ["فاطمه حیدری", "Fateme Heidari", "Fateme Heidari Naeij", "Dr Fateme Heidari"],
    "jobTitle": "متخصص و کارشناس تغذیه",
    "description": "متخصص و کارشناس تغذیه در نوشهر، استان مازندران. مشاوره تغذیه و طراحی برنامه غذایی متناسب با شرایط فرد.",
    "image": "assets/images/doctor.jpg",
    "url": "/",
    "identifier": "<?= e($identity['medicalCode']) ?>",
    "address": {
      "@type": "PostalAddress",
      "addressLocality": "نوشهر",
      "addressRegion": "مازندران",
      "addressCountry": "IR"
    },
    "knowsAbout": ["تغذیه", "رژیم غذایی", "تغذیه ورزشی", "تغذیه درمانی","دکتر تغذیه در نوشهر","متخصص تغذیه در نوشهر","دکتر فاطمه حیدری"]
  }
  </script>

  <script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "Physician",
      "@id": "https://<?= e($config['domain']) ?>/#physician",
      "name": "دکتر فاطمه حیدری",
      "alternateName": [
        "Fateme Heidari",
        "Dr. Fateme Heidari",
        "دکتر فاطمه حیدری نائیج"
      ],
      "url": "https://<?= e($config['domain']) ?>/",
      "description": "دکتر فاطمه حیدری، متخصص تغذیه و رژیم‌درمانی در نوشهر، استان مازندران، ایران.",
      "address": {
        "@type": "PostalAddress",
        "addressLocality": "نوشهر",
        "addressRegion": "مازندران",
        "addressCountry": "IR"
      },
      "areaServed": {
        "@type": "City",
        "name": "نوشهر",
        "containedInPlace": {
          "@type": "AdministrativeArea",
          "name": "استان مازندران"
        }
      },
      "availableLanguage": [
        "Persian"
      ],
      "medicalSpecialty": "https://schema.org/DietNutrition",
      "sameAs": [
        "<?= e($config['links']['instagram']) ?>",
        "<?= e($config['links']['telegram']) ?>"
      ]
    }
  ]
}
</script>
  <script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "Person",
      "@id": "https://<?= e($config['domain']) ?>/#person",
      "name": "دکتر فاطمه حیدری",
      "alternateName": "Fateme Heidari",
      "url": "https://<?= e($config['domain']) ?>/",
      "sameAs": [
        "<?= e($config['links']['instagram']) ?>",
        "<?= e($config['links']['telegram']) ?>"
      ],
      "mainEntityOfPage": {
        "@id": "https://<?= e($config['domain']) ?>/#website"
      },
      "contactPoint": [
        {
          "@type": "ContactPoint",
          "telephone": "<?= e($config['links']['phone']) ?>",
          "contactType": "customer support",
          "availableLanguage": "Persian"
        },
        {
          "@type": "ContactPoint",
          "url": "<?= e($config['links']['whatsapp']) ?>",
          "contactType": "WhatsApp"
        }
      ]
    },
    {
      "@type": "WebSite",
      "@id": "https://<?= e($config['domain']) ?>/#website",
      "url": "https://<?= e($config['domain']) ?>/",
      "name": "وب‌سایت دکتر فاطمه حیدری",
      "inLanguage": "fa-IR",
      "publisher": {
        "@id": "https://<?= e($config['domain']) ?>/#person"
      }
    }
  ]
}
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
    <section class="hero" id="hero">
      <div class="hero-bg" aria-hidden="true">
        <span class="blob blob-a"></span>
        <span class="blob blob-b"></span>
        <span class="blob blob-c"></span>
      </div>
      <div class="wrap hero-grid">
        <div class="hero-copy">
          <div class="kicker"><i></i> <?= e($headings['heroKicker']) ?></div>
          <h1>دکتر <?= e($identity['nameFa']) ?></h1>
          <p class="hero-role"><?= e($identity['title']) ?></p>
          <div class="hero-meta">
            <span class="chip"><span class="material-symbols-rounded" style="font-size:16px">badge</span> کد نظام پزشکی:
              <?= e($identity['medicalCode']) ?></span>
            <span class="chip"><span class="material-symbols-rounded" style="font-size:16px">location_on</span>
              <?= e($identity['city']) ?>، <?= e($identity['province']) ?></span>
          </div>
          <p class="hero-lead"><?= e($identity['intro']) ?></p>
          <div class="hero-cta">
            <a class="btn btn-primary" href="#appoint">
              <span class="material-symbols-rounded">event_available</span>
              <?= e($headings['appointmentCta']) ?>
            </a>
            <a class="btn btn-ghost" href="#services">مشاوره تغذیه</a>
            <a class="btn btn-line" data-link="medical" href="#" rel="noopener" target="_blank">مشاهده صفحه نظام
              پزشکی</a>
          </div>
        </div>
        <div class="portrait-stage">
          <svg class="deco-apple" viewBox="0 0 64 64" aria-hidden="true">
            <path d="M32 14c2-6 8-8 12-8-1 6-6 10-12 8z" fill="#f5b7c1" />
            <path d="M20 28c0-8 6-14 12-14s8 4 10 4 12-2 12 12-8 24-22 24S20 36 20 28z" fill="none" stroke="#b0c4de"
              stroke-width="1.6" />
          </svg>
          <div class="portrait-frame">
            <img src="assets/images/dr-fateme-heidari.jpeg" alt="پرتره <?= e($identity['nameFa']) ?>" width="784" height="784" fetchpriority="high" decoding="async">
            <div class="portrait-glass"></div>
          </div>
          <div class="badge-med"><?= e($identity['medicalCode']) ?></div>
          <aside class="float-card">
            <small>مشاوره تغذیه</small>
            <strong><?= e($identity['city']) ?>، <?= e($identity['province']) ?></strong>
          </aside>
        </div>
      </div>
    </section>

    <section class="intro" id="intro">
      <div class="wrap intro-layout">
        <h2><?= e($headings['intro']) ?></h2>
        <div>
          <p><?= e($headings['introParagraph']) ?></p>
          <div class="intro-pills">
            <?php foreach (array_filter(explode('|', (string) $headings['introPills'])) as $pill): ?>
            <span><?= e(trim($pill)) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </section>

    <section class="services" id="services">
      <div class="wrap">
        <div class="section-head">
          <h2><?= e($headings['services']) ?></h2>
          <p><?= e($headings['servicesSub']) ?></p>
        </div>
        <div class="orb-wrap" aria-hidden="true">
          <div class="orb-fallback"></div>
          <canvas id="nutrition-orb"></canvas>
        </div>
        <div class="masonry" id="services-grid"></div>
      </div>
    </section>

    <section class="faq" id="faq">
      <div class="wrap">
        <div class="section-head">
          <h2><?= e($headings['faq']) ?></h2>
        </div>
        <div class="faq-list" id="faq-list"></div>
      </div>
    </section>

    <section class="videos" id="videos">
      <div class="wrap">
        <div class="section-head">
          <h2><?= e($headings['videos']) ?></h2>
        </div>
        <div class="owl-carousel" id="video-carousel"></div>
        <div id="videos-empty" class="empty-state"><?= e($headings['videosEmpty']) ?></div>
      </div>
    </section>

    <section class="posts" id="posts">
      <div class="wrap">
        <div class="section-head">
          <h2><?= e($headings['posts']) ?></h2>
        </div>
        <div class="posts-grid" id="posts-grid"></div>
        <div id="posts-empty" class="empty-state"><?= e($headings['postsEmpty']) ?></div>
      </div>
    </section>

    <section class="contact" id="contact">
      <div class="wrap">
        <div class="section-head">
          <h2><?= e($headings['contact']) ?></h2>
        </div>
        <div class="contact-grid" id="contact-grid"></div>
      </div>
    </section>

    <section class="appoint" id="appoint">
      <div class="wrap form-shell">
        <div>
          <h2><?= e($headings['appoint']) ?></h2>
          <p class="form-note"><?= e($headings['formNote']) ?></p>
        </div>
        <form class="form-card" id="appointment-form" novalidate>
          <div class="fields">
            <div class="field full">
              <label for="name">نام و نام خانوادگی</label>
              <input id="name" name="name" required autocomplete="name">
            </div>
            <div class="field">
              <label for="age">سن</label>
              <input id="age" name="age" type="decimal" min="1" max="120" required>
            </div>
            <div class="field">
              <label for="height">قد</label>
              <input id="height" name="height" inputmode="decimal" required placeholder="سانتی‌متر">
            </div>
            <div class="field full">
              <label for="goal">هدف مشاوره</label>
              <select id="goal" name="goal" required>
                <option value="">انتخاب کنید</option>
                <option value="افزایش وزن">افزایش وزن</option>
                <option value="کاهش وزن">کاهش وزن</option>
                <option value="پایداری وزن">پایداری وزن</option>
              </select>
            </div>
            <div class="field full">
              <label for="phone">شماره تماس</label>
              <input id="phone" name="phone" type="tel" required autocomplete="tel" dir="ltr">
            </div>
            <div class="field full">
              <label for="message">پیام</label>
              <textarea id="message" name="message" placeholder="اگر نکته‌ای هست، همین‌جا بنویسید."></textarea>
            </div>
            <!-- Honeypot: hidden from humans; bots that fill it get rejected. -->
            <div class="field hp-field" aria-hidden="true">
              <label for="website_url">وب‌سایت</label>
              <input id="website_url" name="website_url" type="text" tabindex="-1" autocomplete="off">
            </div>
          </div>
          <button class="btn btn-primary" type="submit" style="margin-top:18px;width:100%">ارسال درخواست</button>
          <div class="form-msg" id="form-msg" role="status"></div>
        </form>
      </div>
    </section>

    <section class="seo-article" id="article">
      <div class="wrap">
        <article id="seo-article" data-inline-article="1">
          <?= $articleHtml /* trusted HTML, edited only inside the admin panel */ ?>
        </article>
        <button class="seo-toggle" id="seo-toggle" type="button">نمایش بیشتر</button>
      </div>
    </section>

    <section class="partners hidden" id="partners">
      <div class="wrap section-head">
        <h2>مجموعه‌هایی که با آن‌ها همکاری داشته‌ایم</h2>
      </div>
      <div class="marquee" id="marquee-track"></div>
    </section>

    <section class="location" id="location">
      <div class="wrap location-shell">
        <div>
          <h2 id="location-title"><?= e($location['title']) ?></h2>
          <p class="location-address" id="location-address"><?= e($location['address']) ?></p>
        </div>
        <div class="location-actions" id="location-actions"></div>
        <div class="map-frame" id="map-frame"></div>
      </div>
    </section>
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
