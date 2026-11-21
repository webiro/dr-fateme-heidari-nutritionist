<?php
/** Diet records archive — finals & drafts synced from the platform. */
$active = 'diet_history';
$pageTitle = 'سابقه رژیم‌ها';
require __DIR__ . '/partials/layout_start.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    Csrf::check();
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    if ($action === 'delete' && $id) {
        Database::q('DELETE FROM fh_diet_records WHERE id = ?', [$id]);
        flash_set('ok', 'رکورد حذف شد.');
        redirect('diet_history.php' . (($_POST['filter'] ?? '') ? '?filter=' . urlencode($_POST['filter']) : ''));
    }
}

$filter = in_array($_GET['filter'] ?? '', ['final', 'draft'], true) ? $_GET['filter'] : '';
$sourceLabels = ['manual' => 'دستی', 'ai' => 'هوش مصنوعی', 'word' => 'ورود از Word'];
$typeLabels = ['final' => 'رژیم نهایی', 'draft' => 'پیش‌نویس'];

if ($filter) {
    $items = Database::all('SELECT * FROM fh_diet_records WHERE type = ? ORDER BY COALESCE(updated_at, created_at) DESC LIMIT 200', [$filter]);
} else {
    $items = Database::all('SELECT * FROM fh_diet_records ORDER BY COALESCE(updated_at, created_at) DESC LIMIT 200');
}
$stats = [
    'final' => (int) Database::val("SELECT COUNT(*) FROM fh_diet_records WHERE type = 'final'"),
    'draft' => (int) Database::val("SELECT COUNT(*) FROM fh_diet_records WHERE type = 'draft'"),
];
$exportsTotal = 0;
foreach ($items as $it) {
    $ex = json_decode((string) $it['exports'], true);
    if (is_array($ex)) {
        $exportsTotal += array_sum($ex);
    }
}
?>
<div class="stat-grid">
  <div class="stat-card">
    <span class="stat-ico"><span class="material-symbols-rounded">history</span></span>
    <div><b><?= e(fa_digits((string) ($stats['final'] + $stats['draft']))) ?></b><span>کل رکوردها</span></div>
  </div>
  <div class="stat-card ok">
    <span class="stat-ico"><span class="material-symbols-rounded">task_alt</span></span>
    <div><b><?= e(fa_digits((string) $stats['final'])) ?></b><span>رژیم نهایی</span></div>
  </div>
  <div class="stat-card warn">
    <span class="stat-ico"><span class="material-symbols-rounded">edit_square</span></span>
    <div><b><?= e(fa_digits((string) $stats['draft'])) ?></b><span>پیش‌نویس</span></div>
  </div>
  <div class="stat-card">
    <span class="stat-ico"><span class="material-symbols-rounded">download</span></span>
    <div><b><?= e(fa_digits((string) $exportsTotal)) ?></b><span>خروجی گرفته‌شده</span></div>
  </div>
</div>

<div class="card">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead">history</span>
    <h2>سابقه رژیم‌های پلتفرم</h2>
    <span class="badge"><?= e(fa_digits((string) count($items))) ?></span>
    <div class="spacer"></div>
    <div class="seg">
      <span><input type="radio" name="hfilter" id="hf-all" <?= !$filter ? 'checked' : '' ?>><label for="hf-all" onclick="location='diet_history.php'">همه</label></span>
      <span><input type="radio" name="hfilter" id="hf-final" <?= $filter === 'final' ? 'checked' : '' ?>><label for="hf-final" onclick="location='diet_history.php?filter=final'">نهایی</label></span>
      <span><input type="radio" name="hfilter" id="hf-draft" <?= $filter === 'draft' ? 'checked' : '' ?>><label for="hf-draft" onclick="location='diet_history.php?filter=draft'">پیش‌نویس</label></span>
    </div>
  </div>
  <p class="card-note">هر رژیم نهایی (هنگام خروجی گرفتن) و هر پیش‌نویس ذخیره‌شده در پلتفرم، به‌صورت خودکار همین‌جا ثبت می‌شود.</p>
  <?php if (!$items): ?>
  <div class="empty"><span class="material-symbols-rounded">history_toggle_off</span>هنوز رکوردی ثبت نشده است. با هر خروجی یا پیش‌نویس در پلتفرم، اینجا پر می‌شود.</div>
  <?php else: ?>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th>نوع</th>
          <th>مراجع</th>
          <th>مشخصات</th>
          <th>روش</th>
          <th>قالب</th>
          <th>خروجی‌ها</th>
          <th>آخرین به‌روزرسانی</th>
          <th>عملیات</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $it): $meta = json_decode((string) $it['patient_meta'], true) ?: []; $ex = json_decode((string) $it['exports'], true) ?: []; ?>
        <tr>
          <td><span class="badge <?= $it['type'] === 'final' ? 'badge-ok' : 'badge-warn' ?>"><?= e($typeLabels[$it['type']]) ?></span></td>
          <td class="cell-title"><?= e($it['patient_name'] ?: '—') ?></td>
          <td class="cell-muted"><?= e(fa_digits((string) ($meta['age'] ?? ''))) ?> ساله · <?= e(fa_digits((string) ($meta['weight'] ?? ''))) ?> kg · <?= e($meta['goal'] ?? '') ?><?= $meta['date'] ? ' · ' . e(fa_digits($meta['date'])) : '' ?></td>
          <td><?= $it['source'] ? '<span class="badge badge-muted">' . e($sourceLabels[$it['source']] ?? $it['source']) . '</span>' : '—' ?></td>
          <td class="cell-muted"><?= e($it['template']) ?></td>
          <td class="cell-muted"><?= $ex ? e(implode(' / ', array_map(fn($k, $v) => fa_digits((string) $v) . ' ' . strtoupper($k), array_keys($ex), $ex))) : '—' ?></td>
          <td class="cell-muted"><?= e(jalali_datetime($it['updated_at'] ?: $it['created_at'])) ?></td>
          <td class="cell-actions">
            <a class="icon-btn" href="../platform/index.php?record=<?= (int) $it['id'] ?>" target="_blank" rel="noopener" title="بازکردن در پلتفرم و خروجی‌گرفتن"><span class="material-symbols-rounded" style="font-size:19px">open_in_new</span></a>
            <details style="display:inline-block">
              <summary class="icon-btn" style="list-style:none;cursor:pointer" aria-label="جزئیات"><span class="material-symbols-rounded" style="font-size:19px">visibility</span></summary>
              <div style="position:absolute;z-index:60;max-width:420px;max-height:300px;overflow:auto;background:var(--card);border:1px solid var(--border);border-radius:12px;padding:12px;box-shadow:var(--shadow);font-size:11.5px;line-height:1.9;white-space:pre-wrap;direction:ltr;text-align:left"><?= e(mb_substr(json_encode(json_decode((string) $it['data'], true), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: (string) $it['data'], 0, 4000)) ?></div>
            </details>
            <form method="post" data-confirm="این رکورد حذف شود؟">
              <?= Csrf::field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int) $it['id'] ?>">
              <input type="hidden" name="filter" value="<?= e($filter) ?>">
              <button class="icon-btn" type="submit" aria-label="حذف" style="color:var(--danger)">
                <span class="material-symbols-rounded" style="font-size:19px">delete</span>
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/partials/layout_end.php'; ?>
