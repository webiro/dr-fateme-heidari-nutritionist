<?php
/** Site menu CRUD + reorder + desktop/mobile visibility. */
$active = 'menu';
$pageTitle = 'منوی سایت';
require __DIR__ . '/partials/layout_start.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $label = clean_text($_POST['label'] ?? '', 100);
        $href = clean_text($_POST['href'] ?? '', 191);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $showDesktop = isset($_POST['show_desktop']) ? 1 : 0;
        $showMobile = isset($_POST['show_mobile']) ? 1 : 0;
        if ($label === '' || $href === '') {
            flash_set('err', 'عنوان و آدرس هر دو لازم است.');
            redirect('menu.php');
        }
        if ($action === 'create') {
            $max = (int) Database::val('SELECT COALESCE(MAX(sort), 0) FROM fh_menu');
            Database::insert(
                'INSERT INTO fh_menu (label, href, sort, is_active, show_desktop, show_mobile) VALUES (?, ?, ?, ?, ?, ?)',
                [$label, $href, $max + 1, $isActive, $showDesktop, $showMobile]
            );
            flash_set('ok', 'آیتم منو اضافه شد.');
        } else {
            Database::q(
                'UPDATE fh_menu SET label = ?, href = ?, is_active = ?, show_desktop = ?, show_mobile = ? WHERE id = ?',
                [$label, $href, $isActive, $showDesktop, $showMobile, $id]
            );
            flash_set('ok', 'آیتم منو ویرایش شد.');
        }
        SiteData::clearCache();
        redirect('menu.php');
    }

    if ($action === 'delete') {
        Database::q('DELETE FROM fh_menu WHERE id = ?', [(int) ($_POST['id'] ?? 0)]);
        SiteData::clearCache();
        flash_set('ok', 'آیتم منو حذف شد.');
        redirect('menu.php');
    }

    if ($action === 'move') {
        $id = (int) ($_POST['move_id'] ?? 0);
        $direction = $_POST['direction'] === 'up' ? -1 : 1;
        $row = Database::one('SELECT id, sort FROM fh_menu WHERE id = ?', [$id]);
        if ($row) {
            $neighbor = Database::one(
                'SELECT id, sort FROM fh_menu WHERE sort ' . ($direction < 0 ? '<' : '>') . ' ? ORDER BY sort ' . ($direction < 0 ? 'DESC' : 'ASC') . ' LIMIT 1',
                [$row['sort']]
            );
            if ($neighbor) {
                Database::q('UPDATE fh_menu SET sort = ? WHERE id = ?', [$neighbor['sort'], $row['id']]);
                Database::q('UPDATE fh_menu SET sort = ? WHERE id = ?', [$row['sort'], $neighbor['id']]);
                SiteData::clearCache();
            }
        }
        redirect('menu.php');
    }
}

$items = Database::all('SELECT * FROM fh_menu ORDER BY sort ASC, id ASC');
$editing = null;
if (($_GET['edit'] ?? '') !== '') {
    $editing = Database::one('SELECT * FROM fh_menu WHERE id = ?', [(int) $_GET['edit']]);
}
?>
<div class="card">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead">menu_open</span>
    <h2><?= $editing ? 'ویرایش آیتم منو' : 'افزودن آیتم منو' ?></h2>
    <?php if ($editing): ?>
    <div class="spacer"></div>
    <a class="btn btn-ghost btn-sm" href="menu.php">انصراف از ویرایش</a>
    <?php endif; ?>
  </div>
  <form method="post" data-dirty>
    <?= Csrf::field() ?>
    <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
    <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
    <div class="form-grid">
      <div class="field">
        <label for="label">عنوان</label>
        <input class="input" id="label" name="label" required value="<?= e($editing['label'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="href">آدرس</label>
        <input class="input" id="href" name="href" required value="<?= e($editing['href'] ?? '') ?>" placeholder="#services">
        <p class="hint">برای بخش‌های صفحه اصلی با # شروع کنید (مثل #services)؛ یا لینک کامل بدهید.</p>
      </div>
    </div>
    <div class="form-grid">
      <div class="field">
        <label class="switch">
          <input type="checkbox" name="show_desktop" <?= (!$editing || $editing['show_desktop']) ? 'checked' : '' ?>>
          <span class="track"></span>
          <span class="switch-label">نمایش در منوی دسکتاپ</span>
        </label>
      </div>
      <div class="field">
        <label class="switch">
          <input type="checkbox" name="show_mobile" <?= (!$editing || $editing['show_mobile']) ? 'checked' : '' ?>>
          <span class="track"></span>
          <span class="switch-label">نمایش در منوی موبایل</span>
        </label>
      </div>
    </div>
    <div class="field">
      <label class="switch">
        <input type="checkbox" name="is_active" <?= (!$editing || $editing['is_active']) ? 'checked' : '' ?>>
        <span class="track"></span>
        <span class="switch-label">فعال</span>
      </label>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit">
        <span class="material-symbols-rounded">save</span>
        <?= $editing ? 'ذخیره ویرایش' : 'افزودن به منو' ?>
      </button>
    </div>
  </form>
</div>

<div class="card">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead">list</span>
    <h2>آیتم‌های منو</h2>
    <span class="badge"><?= e(fa_digits((string) count($items))) ?></span>
  </div>
  <?php if (!$items): ?>
  <div class="empty"><span class="material-symbols-rounded">menu</span>منو خالی است؛ حداقل چند آیتم اضافه کنید.</div>
  <?php else: ?>
  <div class="rows">
    <?php foreach ($items as $i => $item): ?>
    <div class="row-item">
      <div class="row-body">
        <h3><?= e($item['label']) ?> <?= !$item['is_active'] ? '<span class="badge badge-muted">غیرفعال</span>' : '' ?></h3>
        <p dir="ltr" style="text-align:right"><?= e($item['href']) ?></p>
        <p>
          <?php if ($item['show_desktop']): ?><span class="badge badge-muted">دسکتاپ</span><?php endif; ?>
          <?php if ($item['show_mobile']): ?><span class="badge badge-muted">موبایل</span><?php endif; ?>
        </p>
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
        <a class="icon-btn" href="menu.php?edit=<?= (int) $item['id'] ?>" aria-label="ویرایش">
          <span class="material-symbols-rounded">edit</span>
        </a>
        <form method="post" data-confirm="این آیتم از منو حذف شود؟">
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
