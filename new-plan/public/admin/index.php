<?php
/** Admin login. */
require_once __DIR__ . '/../bootstrap.php';

if (Auth::id()) {
    redirect('dashboard.php');
}

$error = '';
$locked = false;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    Csrf::check();
    $username = clean_text($_POST['username'] ?? '', 64);
    $password = (string) ($_POST['password'] ?? '');

    $locked = Auth::isLocked($username);
    if ($locked) {
        $error = 'به دلیل تلاش‌های ناموفق زیاد، ورود موقتاً غیرفعال است. چند دقیقه بعد دوباره تلاش کنید.';
    } elseif (Auth::check($username, $password)) {
        redirect('dashboard.php');
    } else {
        $left = Auth::attemptsLeft($username);
        $error = 'نام کاربری یا گذرواژه اشتباه است.'
            . ($left <= 2 ? ' ' . fa_digits((string) $left) . ' تلاش باقی مانده.' : '');
        usleep(300000); // slow down brute force
    }
}

$identity = null;
try {
    $identity = SiteData::group('identity');
} catch (Throwable $e) {
    $identity = ['shortNameFa' => 'فاطمه حیدری'];
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl" data-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <title>ورود — پنل مدیریت</title>
  <link rel="icon" href="../assets/images/logo.svg" type="image/svg+xml">
  <link rel="stylesheet" href="../assets/vendor/fonts/site-fonts.css?v=<?= filemtime(__DIR__ . '/../assets/vendor/fonts/site-fonts.css') ?>">
  <link rel="stylesheet" href="../assets/admin/admin.css?v=<?= filemtime(__DIR__ . '/../assets/admin/admin.css') ?>">
</head>
<body class="admin-body">
  <div class="login-wrap">
    <form class="login-card" method="post" autocomplete="on">
      <?= Csrf::field() ?>
      <div class="login-brand">
        <img src="../assets/images/logo.svg" alt="">
        <h1>پنل مدیریت سایت</h1>
        <p>دکتر <?= e($identity['shortNameFa'] ?: 'فاطمه حیدری') ?></p>
      </div>
      <?php if ($error): ?>
      <div class="login-error"><span class="material-symbols-rounded">error</span><?= e($error) ?></div>
      <?php endif; ?>
      <div class="field">
        <label for="username">نام کاربری</label>
        <input class="input" id="username" name="username" required autocomplete="username"
               autofocus dir="ltr" style="text-align:left">
      </div>
      <div class="field">
        <label for="password">گذرواژه</label>
        <input class="input" id="password" name="password" type="password" required
               autocomplete="current-password" dir="ltr" style="text-align:left">
      </div>
      <button class="btn btn-primary" type="submit" style="width:100%">
        <span class="material-symbols-rounded">login</span>
        ورود به پنل
      </button>
      <p class="hint" style="text-align:center;margin-top:14px;color:var(--muted);font-size:12px">
        ورود فقط برای مدیر سایت است.
      </p>
    </form>
  </div>
  <script>
    (function () {
      if (localStorage.getItem("fh-admin-theme") === "dark" ||
          (!localStorage.getItem("fh-admin-theme") && matchMedia("(prefers-color-scheme: dark)").matches)) {
        document.documentElement.setAttribute("data-theme", "dark");
      }
    })();
  </script>
</body>
</html>
