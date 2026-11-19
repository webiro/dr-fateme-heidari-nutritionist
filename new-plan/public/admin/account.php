<?php
/** Admin account — change password. */
$active = 'account';
$pageTitle = 'حساب کاربری';
require __DIR__ . '/partials/layout_start.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $current = (string) ($_POST['current_password'] ?? '');
    $new = (string) ($_POST['new_password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    $hash = Database::val('SELECT password_hash FROM fh_admins WHERE id = ?', [(int) $adminUser['id']]);
    if (!$hash || !password_verify($current, $hash)) {
        flash_set('err', 'گذرواژه فعلی اشتباه است.');
        redirect('account.php');
    }
    if (strlen($new) < 8) {
        flash_set('err', 'گذرواژه جدید باید حداقل ۸ کاراکتر باشد.');
        redirect('account.php');
    }
    if ($new !== $confirm) {
        flash_set('err', 'تکرار گذرواژه جدید مطابقت ندارد.');
        redirect('account.php');
    }
    Auth::changePassword((int) $adminUser['id'], $new);
    flash_set('ok', 'گذرواژه با موفقیت تغییر کرد.');
    redirect('account.php');
}
?>
<div class="card" style="max-width:520px">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead">lock_reset</span>
    <h2>تغییر گذرواژه</h2>
  </div>
  <p class="card-note">نام کاربری شما: <b dir="ltr"><?= e($adminUser['username']) ?></b></p>
  <form method="post" data-dirty>
    <?= Csrf::field() ?>
    <div class="field">
      <label for="current_password">گذرواژه فعلی</label>
      <input class="input" type="password" id="current_password" name="current_password" required
             autocomplete="current-password" dir="ltr" style="text-align:left">
    </div>
    <div class="field">
      <label for="new_password">گذرواژه جدید (حداقل ۸ کاراکتر)</label>
      <input class="input" type="password" id="new_password" name="new_password" required minlength="8"
             autocomplete="new-password" dir="ltr" style="text-align:left">
    </div>
    <div class="field">
      <label for="confirm_password">تکرار گذرواژه جدید</label>
      <input class="input" type="password" id="confirm_password" name="confirm_password" required minlength="8"
             autocomplete="new-password" dir="ltr" style="text-align:left">
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit">
        <span class="material-symbols-rounded">save</span>
        تغییر گذرواژه
      </button>
    </div>
  </form>
</div>

<div class="card" style="max-width:520px">
  <div class="card-head">
    <span class="material-symbols-rounded icon-lead">shield</span>
    <h2>امنیت</h2>
  </div>
  <ul style="margin:0;padding-inline-start:20px;color:var(--muted);font-size:13px;line-height:2">
    <li>ورود با گذرواژه‌های هش‌شده (Argon2id) ذخیره می‌شود.</li>
    <li>بعد از ۵ تلاش ناموفق، ورود برای آن نام‌کاربری ۱۰ دقیقه قفل می‌شود.</li>
    <li>همه فرم‌ها با توکن CSRF محافظت می‌شوند.</li>
    <li>پیشنهاد: همین حالا گذرواژه اولیه نصب را عوض کنید.</li>
  </ul>
</div>
<?php require __DIR__ . '/partials/layout_end.php'; ?>
