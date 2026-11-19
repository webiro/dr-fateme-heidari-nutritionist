<?php
/** Appointment submissions inbox. */
$active = 'messages';
$pageTitle = 'درخواست‌های مشاوره';
require __DIR__ . '/partials/layout_start.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'read' && $id) {
        Database::q('UPDATE fh_submissions SET is_read = 1 WHERE id = ?', [$id]);
        redirect('messages.php#msg-' . $id);
    }
    if ($action === 'unread' && $id) {
        Database::q('UPDATE fh_submissions SET is_read = 0 WHERE id = ?', [$id]);
        redirect('messages.php');
    }
    if ($action === 'delete' && $id) {
        Database::q('DELETE FROM fh_submissions WHERE id = ?', [$id]);
        flash_set('ok', 'درخواست حذف شد.');
        redirect('messages.php');
    }
}

$items = Database::all('SELECT * FROM fh_submissions ORDER BY is_read ASC, id DESC');
$unread = (int) Database::val('SELECT COUNT(*) FROM fh_submissions WHERE is_read = 0');
?>
<div class="card">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead">inbox</span>
    <h2>درخواست‌های ثبت‌شده در فرم سایت</h2>
    <?php if ($unread): ?><span class="badge"><?= e(fa_digits((string) $unread)) ?> جدید</span><?php endif; ?>
  </div>
  <?php if (!$items): ?>
  <div class="empty"><span class="material-symbols-rounded">inbox</span>هنوز درخواستی ثبت نشده است. وقتی بازدیدکننده‌ای فرم «رزرو مشاوره» را پر کند، همین‌جا نمایش داده می‌شود.</div>
  <?php else: ?>
  <div class="rows">
    <?php foreach ($items as $item): ?>
    <div class="row-item" id="msg-<?= (int) $item['id'] ?>" style="<?= $item['is_read'] ? '' : 'border-inline-start:4px solid var(--primary)' ?>">
      <div class="row-body">
        <h3>
          <?= e($item['name']) ?>
          <?= !$item['is_read'] ? '<span class="badge">جدید</span>' : '' ?>
        </h3>
        <p>
          <span class="badge badge-muted"><?= e($item['goal']) ?></span>
          <?= e(fa_digits($item['age'])) ?> ساله · قد <?= e(fa_digits($item['height'])) ?> سانتی‌متر
        </p>
        <p>تلفن: <b dir="ltr"><?= e(fa_digits($item['phone'])) ?></b> · <?= e(jalali_datetime($item['created_at'])) ?></p>
        <?php if ($item['message']): ?>
        <p class="multi"><?= e($item['message']) ?></p>
        <?php endif; ?>
      </div>
      <div class="row-tools">
        <?php if (!$item['is_read']): ?>
        <form method="post" style="display:contents">
          <?= Csrf::field() ?>
          <input type="hidden" name="action" value="read">
          <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
          <button class="btn btn-ghost btn-sm" type="submit">
            <span class="material-symbols-rounded">done_all</span>
            خوانده شد
          </button>
        </form>
        <?php else: ?>
        <form method="post" style="display:contents">
          <?= Csrf::field() ?>
          <input type="hidden" name="action" value="unread">
          <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
          <button class="btn btn-ghost btn-sm" type="submit">
            <span class="material-symbols-rounded">mark_email_unread</span>
            علامت‌گذاری جدید
          </button>
        </form>
        <?php endif; ?>
        <a class="btn btn-accent btn-sm" href="tel:<?= e($item['phone']) ?>">
          <span class="material-symbols-rounded">call</span>
          تماس
        </a>
        <form method="post" data-confirm="این درخواست برای همیشه حذف شود؟">
          <?= Csrf::field() ?>
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
          <button class="icon-btn" type="submit" aria-label="حذف" style="color:var(--danger)">
            <span class="material-symbols-rounded">delete</span>
          </button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/partials/layout_end.php'; ?>
