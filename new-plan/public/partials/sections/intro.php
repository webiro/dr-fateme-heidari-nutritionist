<?php /** Section: intro — paragraph is admin-authored, sanitized (inline tags only) */ ?>
<section class="intro" id="intro">
  <div class="wrap intro-layout">
    <h2><?= e($headings['intro']) ?></h2>
    <div>
      <p><?= $introParagraphHtml ?></p>
      <div class="intro-pills">
        <?php foreach (array_filter(explode('|', (string) $headings['introPills'])) as $pill): ?>
        <span><?= e(trim($pill)) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
