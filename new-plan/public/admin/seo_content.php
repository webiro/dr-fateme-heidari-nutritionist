<?php
/** SEO article content — full rich text editor. */
$active = 'seo_content';
$pageTitle = 'محتوای سئو';
$useEditor = true;
require __DIR__ . '/partials/layout_start.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    Csrf::check();
    $html = trim((string) ($_POST['html'] ?? ''));
    $allowed = '<h2><h3><h4><p><ul><ol><li><strong><b><em><i><u><br><a><blockquote><span>';
    $html = strip_tags($html, $allowed);
    // only safe <a> tags with http(s) href and no inline handlers
    if (preg_match_all('#<a\b[^>]*>.*?</a>#is', $html, $matches)) {
        foreach ($matches[0] as $tag) {
            if (preg_match('#\son\w+\s*=#i', $tag)) {
                $html = str_replace($tag, '', $html);
            }
        }
    }
    $html = preg_replace('#<a\b(?![^>]*href="https?://)[^>]*>#i', '<p>', $html) ?? $html;
    SiteData::putGroup('article', ['html' => $html]);
    flash_set('ok', 'محتوای سئو ذخیره شد.');
    redirect('seo_content.php');
}

$article = SiteData::group('article');
?>
<div class="card">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead">article</span>
    <h2>مقاله سئوی پایین صفحه اصلی</h2>
    <div class="spacer"></div>
    <a class="btn btn-ghost btn-sm" href="../index.php#article" target="_blank" rel="noopener">
      <span class="material-symbols-rounded" style="font-size:17px">open_in_new</span>
      در سایت
    </a>
  </div>
  <p class="card-note">این متن با دکمه «نمایش بیشتر» در سایت باز می‌شود. از ادیتور برای هدینگ، لیست، نقل‌قول و لینک استفاده کنید — لینک‌ها فقط با https مجازند.</p>

  <form method="post" data-dirty>
    <?= Csrf::field() ?>
    <input type="hidden" name="html" id="article-html" value="<?= e($article['html']) ?>">
    <div data-editor data-target="#article-html">
      <div class="rte-area" contenteditable="true"
           data-empty="1" data-placeholder="متن مقاله سئو را اینجا بنویسید…"></div>
    </div>
    <div class="form-actions" style="margin-top:16px">
      <button class="btn btn-primary" type="submit">
        <span class="material-symbols-rounded">save</span>
        ذخیره محتوای سئو
      </button>
    </div>
  </form>
</div>
<?php require __DIR__ . '/partials/layout_end.php'; ?>
