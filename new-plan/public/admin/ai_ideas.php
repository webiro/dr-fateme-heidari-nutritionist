<?php
/** AI ideas archive — batches and their items. */
$active = 'ai_ideas';
$pageTitle = 'آرشیو ایده‌ها';
require __DIR__ . '/partials/layout_start.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    Csrf::check();
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    if ($action === 'delete_batch' && $id) {
        Database::q('DELETE FROM fh_ai_batches WHERE id = ?', [$id]);
        flash_set('ok', 'دسته ایده‌ها حذف شد.');
        redirect('ai_ideas.php');
    }
    if ($action === 'delete_idea' && $id) {
        Database::q('DELETE FROM fh_ai_ideas WHERE id = ?', [$id]);
        redirect('ai_ideas.php');
    }
}

$batches = Database::all(
    'SELECT b.*, (SELECT COUNT(*) FROM fh_ai_ideas i WHERE i.batch_id = b.id) AS idea_count
     FROM fh_ai_batches b ORDER BY b.id DESC LIMIT 50'
);
$formatLabels = ['reel' => 'ریلز', 'post' => 'پست', 'article' => 'مقاله'];
?>
<div class="card">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead">bulb</span>
    <h2>آرشیو ایده‌های هوش مصنوعی</h2>
    <span class="badge"><?= e(fa_digits((string) count($batches))) ?> دسته</span>
    <div class="spacer"></div>
    <a class="btn btn-primary btn-sm" href="dashboard.php">
      <span class="material-symbols-rounded" style="font-size:17px">auto_awesome</span>
      تولید ایده جدید
    </a>
  </div>
  <?php if (!$batches): ?>
  <div class="empty">
    <span class="material-symbols-rounded">lightbulb</span>
    هنوز ایده‌ای تولید نشده است. از پیشخوان دکمه «تولید ایده جدید» را بزنید تا ۱۰ خبر و موضوع ترند حوزه تغذیه از هوش مصنوعی بگیرید.
  </div>
  <?php else: ?>
  <?php foreach ($batches as $batch): ?>
  <div class="batch-card">
    <div class="batch-head">
      <span class="badge">دسته <?= e(fa_digits((string) $batch['id'])) ?></span>
      <span class="badge badge-muted"><?= e(fa_digits((string) $batch['idea_count'])) ?> ایده</span>
      <span class="badge badge-muted code-mono"><?= e($batch['model'] ?: '—') ?></span>
      <span class="cell-muted"><?= e(jalali_datetime($batch['created_at'])) ?></span>
      <div class="spacer"></div>
      <form method="post" data-confirm="این دسته از ایده‌ها برای همیشه حذف شود؟">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="delete_batch">
        <input type="hidden" name="id" value="<?= (int) $batch['id'] ?>">
        <button class="icon-btn" type="submit" aria-label="حذف دسته" style="color:var(--danger)">
          <span class="material-symbols-rounded" style="font-size:19px">delete</span>
        </button>
      </form>
    </div>
    <?php
    $ideas = Database::all('SELECT * FROM fh_ai_ideas WHERE batch_id = ? ORDER BY id ASC', [$batch['id']]);
    ?>
    <div class="idea-list">
      <?php foreach ($ideas as $idea): ?>
      <div class="idea-item">
        <span class="idea-badge"><span class="material-symbols-rounded"><?= $idea['format'] === 'reel' ? 'movie' : ($idea['format'] === 'article' ? 'article' : 'photo_library') ?></span></span>
        <div style="flex:1;min-width:0">
          <h3><?= e($idea['title']) ?></h3>
          <?php if ($idea['summary']): ?>
          <p><?= e($idea['summary']) ?></p>
          <?php endif; ?>
          <div class="idea-meta">
            <span class="badge badge-muted"><?= e($formatLabels[$idea['format']] ?? $idea['format']) ?></span>
            <?php if ($idea['hashtag']): ?>
            <span class="badge badge-muted" dir="ltr"><?= e($idea['hashtag']) ?></span>
            <?php endif; ?>
          </div>
        </div>
        <form method="post">
          <?= Csrf::field() ?>
          <input type="hidden" name="action" value="delete_idea">
          <input type="hidden" name="id" value="<?= (int) $idea['id'] ?>">
          <button class="icon-btn" type="submit" aria-label="حذف ایده">
            <span class="material-symbols-rounded" style="font-size:18px">close</span>
          </button>
        </form>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/partials/layout_end.php'; ?>
