<?php /** Section: clinic location + map */ ?>
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
