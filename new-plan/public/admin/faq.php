<?php
/** FAQ CRUD + reorder. */
$active = 'faq';
$pageTitle = 'سوالات متداول';
require __DIR__ . '/partials/layout_start.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $question = clean_text($_POST['question'] ?? '', 255);
        $answer = clean_text($_POST['answer'] ?? '', 4000);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        if ($question === '' || $answer === '') {
            flash_set('err', 'سوال و پاسخ هر دو لازم است.');
            redirect('faq.php');
        }
        if ($action === 'create') {
            $max = (int) Database::val('SELECT COALESCE(MAX(sort), 0) FROM fh_faq');
            Database::insert(
                'INSERT INTO fh_faq (question, answer, sort, is_active) VALUES (?, ?, ?, ?)',
                [$question, $answer, $max + 1, $isActive]
            );
            flash_set('ok', 'سوال جدید اضافه شد.');
        } else {
            Database::q(
                'UPDATE fh_faq SET question = ?, answer = ?, is_active = ? WHERE id = ?',
                [$question, $answer, $isActive, $id]
            );
            flash_set('ok', 'سوال ویرایش شد.');
        }
        SiteData::clearCache();
        redirect('faq.php');
    }

    if ($action === 'delete') {
        Database::q('DELETE FROM fh_faq WHERE id = ?', [(int) ($_POST['id'] ?? 0)]);
        SiteData::clearCache();
        flash_set('ok', 'سوال حذف شد.');
        redirect('faq.php');
    }

    if ($action === 'move') {
        $id = (int) ($_POST['move_id'] ?? 0);
        $direction = $_POST['direction'] === 'up' ? -1 : 1;
        $row = Database::one('SELECT id, sort FROM fh_faq WHERE id = ?', [$id]);
        if ($row) {
            $neighbor = Database::one(
                'SELECT id, sort FROM fh_faq WHERE sort ' . ($direction < 0 ? '<' : '>') . ' ? ORDER BY sort ' . ($direction < 0 ? 'DESC' : 'ASC') . ' LIMIT 1',
                [$row['sort']]
            );
            if ($neighbor) {
                Database::q('UPDATE fh_faq SET sort = ? WHERE id = ?', [$neighbor['sort'], $row['id']]);
                Database::q('UPDATE fh_faq SET sort = ? WHERE id = ?', [$row['sort'], $neighbor['id']]);
                SiteData::clearCache();
            }
        }
        redirect('faq.php');
    }
}

$items = Database::all('SELECT * FROM fh_faq ORDER BY sort ASC, id ASC');
$editing = null;
if (($_GET['edit'] ?? '') !== '') {
    $editing = Database::one('SELECT * FROM fh_faq WHERE id = ?', [(int) $_GET['edit']]);
}
?>
<div class="card">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead">quiz</span>
    <h2><?= $editing ? 'ویرایش سوال' : 'افزودن سوال جدید' ?></h2>
    <?php if ($editing): ?>
    <div class="spacer"></div>
    <a class="btn btn-ghost btn-sm" href="faq.php">انصراف از ویرایش</a>
    <?php endif; ?>
  </div>
  <form method="post" data-dirty>
    <?= Csrf::field() ?>
    <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
    <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
    <div class="field">
      <label for="question">سوال</label>
      <input class="input" id="question" name="question" required value="<?= e($editing['question'] ?? '') ?>">
    </div>
    <div class="field">
      <label for="answer">پاسخ</label>
      <textarea class="textarea" id="answer" name="answer" required><?= e($editing['answer'] ?? '') ?></textarea>
    </div>
    <div class="field">
      <label class="switch">
        <input type="checkbox" name="is_active" <?= (!$editing || $editing['is_active']) ? 'checked' : '' ?>>
        <span class="track"></span>
        <span class="switch-label">نمایش در سایت</span>
      </label>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit">
        <span class="material-symbols-rounded">save</span>
        <?= $editing ? 'ذخیره ویرایش' : 'افزودن سوال' ?>
      </button>
    </div>
  </form>
</div>

<div class="card">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead">list</span>
    <h2>سوالات فعلی</h2>
    <span class="badge"><?= e(fa_digits((string) count($items))) ?></span>
  </div>
  <?php if (!$items): ?>
  <div class="empty"><span class="material-symbols-rounded">help</span>هنوز سوالی ثبت نشده است.</div>
  <?php else: ?>
  <div class="rows">
    <?php foreach ($items as $i => $item): ?>
    <div class="row-item">
      <div class="row-body">
        <h3><?= e($item['question']) ?> <?= !$item['is_active'] ? '<span class="badge badge-muted">غیرفعال</span>' : '' ?></h3>
        <p class="multi"><?= e($item['answer']) ?></p>
      </div>
      <div class="row-tools">
        <form method="post" style="display:contents">
          <?= Csrf::field() ?>
          <input type="hidden" name="action" value="move">
          <input type="hidden" name="move_id" value="<?= (int) $item['id'] ?>">
          <input type="hidden" name="direction" value="">
          <button class="icon-btn" type="button" data-move="up" data-move-id="<?= (int) $item['id'] ?>" aria-label="انتقال به بالا" <?= $i === 0 ? 'disabled' : '' ?>>
            <span class="material-symbols-rounded">arrow_upward</span>
          </button>
          <button class="icon-btn" type="button" data-move="down" data-move-id="<?= (int) $item['id'] ?>" aria-label="انتقال به پایین" <?= $i === count($items) - 1 ? 'disabled' : '' ?>>
            <span class="material-symbols-rounded">arrow_downward</span>
          </button>
        </form>
        <a class="icon-btn" href="faq.php?edit=<?= (int) $item['id'] ?>" aria-label="ویرایش">
          <span class="material-symbols-rounded">edit</span>
        </a>
        <form method="post" data-confirm="این سوال حذف شود؟">
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
