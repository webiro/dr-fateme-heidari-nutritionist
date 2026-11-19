<?php
/** Services CRUD + reorder + icon picker. */
$active = 'services';
$pageTitle = 'خدمات';
require __DIR__ . '/partials/layout_start.php';

$icons = [
    'monitor_weight', 'restaurant', 'balance', 'fitness_center', 'nutrition',
    'monitor_heart', 'medical_services', 'favorite', 'self_improvement', 'eco',
    'medication', 'vaccines', 'psychology', 'bedtime', 'water_drop',
    'local_fire_department', 'directions_run', 'spa', 'eco', 'science',
];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $icon = clean_text($_POST['icon'] ?? 'nutrition', 64);
        $title = clean_text($_POST['title'] ?? '', 191);
        $text = clean_text($_POST['text'] ?? '', 1000);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        if ($title === '') {
            flash_set('err', 'عنوان خدمت نمی‌تواند خالی باشد.');
            redirect('services.php');
        }
        if (!in_array($icon, $icons, true)) {
            $icon = 'nutrition';
        }
        if ($action === 'create') {
            $max = (int) Database::val('SELECT COALESCE(MAX(sort), 0) FROM fh_services');
            Database::insert(
                'INSERT INTO fh_services (icon, title, text, sort, is_active) VALUES (?, ?, ?, ?, ?)',
                [$icon, $title, $text, $max + 1, $isActive]
            );
            flash_set('ok', 'خدمت جدید اضافه شد.');
        } else {
            Database::q(
                'UPDATE fh_services SET icon = ?, title = ?, text = ?, is_active = ? WHERE id = ?',
                [$icon, $title, $text, $isActive, $id]
            );
            flash_set('ok', 'خدمت ویرایش شد.');
        }
        SiteData::clearCache();
        redirect('services.php');
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        Database::q('DELETE FROM fh_services WHERE id = ?', [$id]);
        SiteData::clearCache();
        flash_set('ok', 'خدمت حذف شد.');
        redirect('services.php');
    }

    if ($action === 'move') {
        $id = (int) ($_POST['move_id'] ?? 0);
        $direction = $_POST['direction'] === 'up' ? -1 : 1;
        $row = Database::one('SELECT id, sort FROM fh_services WHERE id = ?', [$id]);
        if ($row) {
            $neighbor = Database::one(
                'SELECT id, sort FROM fh_services WHERE sort ' . ($direction < 0 ? '<' : '>') . ' ? ORDER BY sort ' . ($direction < 0 ? 'DESC' : 'ASC') . ' LIMIT 1',
                [$row['sort']]
            );
            if ($neighbor) {
                Database::q('UPDATE fh_services SET sort = ? WHERE id = ?', [$neighbor['sort'], $row['id']]);
                Database::q('UPDATE fh_services SET sort = ? WHERE id = ?', [$row['sort'], $neighbor['id']]);
                SiteData::clearCache();
            }
        }
        redirect('services.php');
    }
}

$items = Database::all('SELECT * FROM fh_services ORDER BY sort ASC, id ASC');
$editing = null;
if (($_GET['edit'] ?? '') !== '') {
    $editing = Database::one('SELECT * FROM fh_services WHERE id = ?', [(int) $_GET['edit']]);
}
?>
<div class="card">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead">medical_information</span>
    <h2><?= $editing ? 'ویرایش خدمت' : 'افزودن خدمت جدید' ?></h2>
    <?php if ($editing): ?>
    <div class="spacer"></div>
    <a class="btn btn-ghost btn-sm" href="services.php">انصراف از ویرایش</a>
    <?php endif; ?>
  </div>
  <form method="post" data-dirty>
    <?= Csrf::field() ?>
    <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
    <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
    <div class="form-grid">
      <div class="field">
        <label for="title">عنوان خدمت</label>
        <input class="input" id="title" name="title" required value="<?= e($editing['title'] ?? '') ?>">
      </div>
      <div class="field">
        <label>فعال در سایت</label>
        <label class="switch">
          <input type="checkbox" name="is_active" <?= (!$editing || $editing['is_active']) ? 'checked' : '' ?>>
          <span class="track"></span>
          <span class="switch-label">نمایش در صفحه اصلی</span>
        </label>
      </div>
      <div class="field full">
        <label for="text">توضیح کوتاه</label>
        <textarea class="textarea" id="text" name="text" style="min-height:80px"><?= e($editing['text'] ?? '') ?></textarea>
      </div>
    </div>

    <div class="field" data-icon-picker>
      <label>آیکون خدمت</label>
      <div class="icon-grid">
        <?php foreach ($icons as $icon): ?>
        <button type="button" class="icon-opt<?= ($editing['icon'] ?? 'monitor_weight') === $icon ? ' is-selected' : '' ?>" data-icon="<?= e($icon) ?>">
          <span class="material-symbols-rounded"><?= e($icon) ?></span>
          <span><?= e($icon) ?></span>
        </button>
        <?php endforeach; ?>
      </div>
      <input type="hidden" name="icon" value="<?= e($editing['icon'] ?? 'monitor_weight') ?>">
    </div>

    <div class="form-actions">
      <button class="btn btn-primary" type="submit">
        <span class="material-symbols-rounded">save</span>
        <?= $editing ? 'ذخیره ویرایش' : 'افزودن خدمت' ?>
      </button>
    </div>
  </form>
</div>

<div class="card">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead">list</span>
    <h2>خدمات فعلی</h2>
    <span class="badge"><?= e(fa_digits((string) count($items))) ?></span>
  </div>
  <?php if (!$items): ?>
  <div class="empty"><span class="material-symbols-rounded">note_stack</span>هنوز خدمتی ثبت نشده است.</div>
  <?php else: ?>
  <div class="rows">
    <?php foreach ($items as $i => $item): ?>
    <div class="row-item">
      <span class="thumb" style="display:grid;place-items:center;font-size:0">
        <span class="material-symbols-rounded" style="font-size:26px;color:var(--primary)"><?= e($item['icon']) ?></span>
      </span>
      <div class="row-body">
        <h3><?= e($item['title']) ?> <?= !$item['is_active'] ? '<span class="badge badge-muted">غیرفعال</span>' : '' ?></h3>
        <p class="multi"><?= e($item['text']) ?></p>
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
        <a class="icon-btn" href="services.php?edit=<?= (int) $item['id'] ?>" aria-label="ویرایش">
          <span class="material-symbols-rounded">edit</span>
        </a>
        <form method="post" data-confirm="این خدمت حذف شود؟">
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
