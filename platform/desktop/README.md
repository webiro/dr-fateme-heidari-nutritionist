# Tisa Platform — نسخه دسکتاپ (ویندوز + مک سیلیکون)

اپلیکیشن دسکتاپ پلتفرم DIET TISA. **همان `platform/index.htm` نسخه وب** در زمان بیلد داخل اپ کپی می‌شود (منبع حقیقت واحد = نسخه وب).

## معماری
```
platform/index.htm + platform/vendor + platform/images
        │  (sync در بیلد — sync.js)
        ▼
platform/desktop/app/  ──▶  Electron (اسپلش ۵ ثانیه → پنجره تمام‌صفحه)  ──▶  NSIS exe / DMG
```

- **کاملاً آفلاین**: فونت وزیرمتن، آیکون‌های Material Symbols و همه کتابخانه‌ها (jQuery، دیت‌پیکر شمسی، html2canvas، jsPDF، XLSX) از `platform/vendor/` لوکال داخل اپ باندل می‌شوند. فقط بخش AI (OpenRouter/پاستا کلاد) اینترنت می‌خواهد.
- **اسپلش**: همیشه ۵ ثانیه (`splash.html`) — «پلتفرم تیسا» + تقدیم‌نامه، با فونت وزیرمتن آفلاین.
- **پنجره اصلی**: تمام‌صفحه (خروج/ورود با F11).
- **دیتا**: localStorage داخل `userData` اپ — پیش‌نویس‌ها و رژیم‌ها بعد از آپدیت هم می‌مانند.
- **آپدیت داخل برنامه**: electron-updater از GitHub Releases؛ دکمه نسخه/بروزرسانی در نوار بالای اپ (فقط داخل اپ دیده می‌شود، روی وب بی‌اثر).
- **نام اپ**: Tisa Platform — Publisher/Author: Pedram Gholizadeh.

## ورک‌فلو (خودکار، فقط روی GitHub)
`.github/workflows/desktop-release.yml` — روی هر پوش به main که `platform/**` تغییر کرده باشد (یا دستی):
1. نسخه وب + vendor را داخل باندل کپی می‌کند
2. نسخه `1.0.<run_number>` می‌گذارد (همیشه صعودی → آپدیت‌یاب درست کار می‌کند)
3. **job ویندوز**: `windows-latest` → NSIS x64 → منتشر در GitHub Release (`latest.yml` + `TisaPlatform-Setup-1.0.N.exe`)
4. **job مک** (پس از ویندوز): `macos-14` (Apple Silicon) → DMG arm64 → به همان Release اضافه می‌شود

اپ ویندوزی نصب‌شده، از دکمه بروزرسانی، نسخه جدید را دانلود و نصب می‌کند.

## نکات نصب
- **ویندوز ۱۰/۱۱**: نصب‌کننده unsigned است؛ در SmartScreen «More info → Run anyway» (ناشر: Pedram Gholizadeh). نصب عادی Next/Install با امکان انتخاب مسیر.
- **مک سیلیکون**: DMG unsigned است؛ بعد از کپی در Applications، برای اجرای اول `xattr -cr "/Applications/Tisa Platform.app"` یا کلیک راست → Open لازم است. آپدیت خودکار روی مک به‌دلیل unsigned بودن غیرفعال است (دانلود دستی از Releases).
- اگر بعداً گواهی کدساینینگ گرفته شد: ویندوز → `win.certificateFile` در build config؛ مک → `mac.identity`.

## اجرا/بیلد محلی (فقط برای توسعه؛ خروجی نهایی فقط از GitHub)
```bash
cd platform/desktop
npm install
npm start          # اجرا با هم‌گام‌سازی خودکار نسخه وب
```
