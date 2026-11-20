<?php /** Section: appointment form */ ?>
<section class="appoint" id="appoint">
  <div class="wrap form-shell">
    <div>
      <h2><?= e($headings['appoint']) ?></h2>
      <p class="form-note"><?= e($headings['formNote']) ?></p>
    </div>
    <form class="form-card" id="appointment-form" novalidate>
      <div class="fields">
        <div class="field full">
          <label for="name">نام و نام خانوادگی</label>
          <input id="name" name="name" required autocomplete="name">
        </div>
        <div class="field">
          <label for="age">سن</label>
          <input id="age" name="age" type="decimal" min="1" max="120" required>
        </div>
        <div class="field">
          <label for="height">قد</label>
          <input id="height" name="height" inputmode="decimal" required placeholder="سانتی‌متر">
        </div>
        <div class="field full">
          <label for="goal">هدف مشاوره</label>
          <select id="goal" name="goal" required>
            <option value="">انتخاب کنید</option>
            <option value="افزایش وزن">افزایش وزن</option>
            <option value="کاهش وزن">کاهش وزن</option>
            <option value="پایداری وزن">پایداری وزن</option>
          </select>
        </div>
        <div class="field full">
          <label for="phone">شماره تماس</label>
          <input id="phone" name="phone" type="tel" required autocomplete="tel" dir="ltr">
        </div>
        <div class="field full">
          <label for="message">پیام</label>
          <textarea id="message" name="message" placeholder="اگر نکته‌ای هست، همین‌جا بنویسید."></textarea>
        </div>
        <!-- Honeypot: hidden from humans; bots that fill it get rejected. -->
        <div class="field hp-field" aria-hidden="true">
          <label for="website_url">وب‌سایت</label>
          <input id="website_url" name="website_url" type="text" tabindex="-1" autocomplete="off">
        </div>
      </div>
      <button class="btn btn-primary" type="submit" style="margin-top:18px;width:100%">ارسال درخواست</button>
      <div class="form-msg" id="form-msg" role="status"></div>
    </form>
  </div>
</section>
