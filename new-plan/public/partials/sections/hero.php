<?php /** Section: hero (always first, not reorderable) */ ?>
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
