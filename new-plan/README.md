# نسخه داینامیک سایت دکتر فاطمه حیدری — PHP + MySQL

نسخه داینامیک سایت با **پنل ادمین فارسی RTL** که همه‌چیز را از دیتابیس می‌خواند.
فرانت‌اند عمقی **دقیقاً همان فرانت قبلی** است (همان HTML/CSS/JS)، فقط داده‌ها داینامیک شده و تمام CDNها حذف شده‌اند.

## ساختار

```
new-plan/
├── public/            ← ریشه وب (روی cPanel: public_html)
│   ├── index.php      ← لندینگ (همان فرانت + داده از DB + کش)
│   ├── qrcode.php     ← کارت QR
│   ├── api/appointment.php ← دریافت فرم رزرو (جایگزین ورکر)
│   ├── admin/         ← پنل ادمین (فارسی، ریسپانسیو، تم روشن/تیره)
│   ├── assets/        ← app.css/app.js + images + vendor (فونت/jQuery/Owl/Plyr — همه لوکال)
│   ├── uploads/       ← آپلودهای ادمین (اجرای PHP غیرفعال)
│   ├── install.php    ← نصب‌کننده یک‌باره
│   └── sw.js / .htaccess / .user.ini / robots.txt / sitemap.xml
├── app/               ← کد اپلیکیشن — بیرون از ریشه وب (روی cPanel کنار public_html)
│   ├── bootstrap.php  ← سشن امن + هدرها + لود کلاس‌ها
│   ├── Env.php        ← خواندن config.env
│   ├── Database.php   ← PDO + prepared statements
│   ├── Auth.php       ← Argon2id + قفل بعد از ۵ تلاش ناموفق
│   ├── Csrf.php / Uploader.php / SiteData.php / helpers.php
│   ├── schema.sql     ← جداول MySQL
│   └── seed.php       ← محتوای اولیه = متن‌های فعلی سایت
├── config.env         ← تنظیمات (دیتابیس، ادمین، FTP)
├── deploy.sh          ← دیپلوی FTP با lftp
├── docker-compose.yml ← تست لوکال (PHP 8.5-apache + MySQL 8)
└── docker/Dockerfile
```

## اجرای لوکال (داکر)

```bash
cd new-plan
docker compose up -d --build
# یک بار نصب: کلید INSTALL_KEY از config.env (پیش‌فرض dev-install-key)
curl -s -X POST "http://localhost:8080/install.php" -d "key=dev-install-key"
# سایت:   http://localhost:8080
# پنل:    http://localhost:8080/admin  (drfateme / DrFateme!2024)
```

## دیپلوی روی cPanel

1. یک دیتابیس MySQL + کاربر بساز (cPanel → MySQL Databases).
2. `config.env` را از `config.env.example` بساز و مقادیر دیتابیس/ادمین/INSTALL_KEY/FTP را پر کن.
3. `./deploy.sh` — فایل‌ها با FTPS آپلود می‌شوند:
   - `public/*` → `public_html`
   - `app/*` و `config.env` → پوشه `app` کنار `public_html` (عمومی نیست)
4. یک بار در مرورگر `https://دامنه/install.php` و کلید نصب → جداول + محتوا + ادمین ساخته می‌شود و صفحه قفل می‌شود.
5. وارد پنل شو و از «حساب کاربری» گذرواژه را عوض کن.

> اگر هاست اجازه داد، در cPanel → MultiPHP INI Editor مقدار `upload_max_filesize` را برای آپلود ریلز بالا ببر (`.user.ini` هم ضمیمه است).

## پنل ادمین — چه چیزهایی قابل مدیریت است؟

| بخش | توضیح |
|---|---|
| پیشخوان | آمار + آخرین درخواست‌ها |
| درخواست‌های مشاوره | صندوق ورودی فرم سایت، خوانده‌شده، تماس، حذف |
| متن‌ها و عنوان‌ها | سئو، هیرو، معرفی، همه هدینگ‌ها، پیام‌های خالی، کلید خاموش/روشن هر بخش |
| خدمات | CRUD + ترتیب + انتخاب آیکون |
| سوالات متداول | CRUD + ترتیب |
| منوی سایت | CRUD + ترتیب + نمایش در دسکتاپ/موبایل |
| پست‌های اسلایدی | ساخت پست + آپلود چند عکس یا لینک مستقیم هر اسلاید + ترتیب |
| ریلزها | آپلود فایل / لینک مستقیم / اسکریپت آپارات + ترتیب + فعال/غیرفعال |
| اطلاعات و تنظیمات | اطلاعات دکتر، لینک‌ها و شبکه‌های اجتماعی، آدرس مطب و مختصات نقشه، متن سئو، شعارها |
| حساب کاربری | تغییر گذرواژه |

## امنیت

- PDO + prepared statements (بدون SQL injection)
- خروجی همه‌جا `htmlspecialchars` (بدون XSS)
- توکن CSRF روی همه فرم‌ها
- Argon2id برای گذرواژه + قفل ورود بعد از ۵ تلاش ناموفق
- سشن HttpOnly + SameSite + regenerate روی لاگین
- آپلود با finfo + وایت‌لیست + نام رندوم + غیرفعال‌سازی اجرای PHP در uploads/
- کد اپلیکیشن (`app/`) و `config.env` خارج از ریشه وب
- هدرهای امنیتی + بستن دسترسی به `.env/*.sql/storage`

## نکات فنی

- صفحه اصلی با **کش HTML ۱۵ دقیقه‌ای** رندر می‌شود؛ هر ذخیره در پنل کش را بی‌درنگ خالی می‌کند (`?nocache` برای دیدن بدون کش).
- فرانت `app.js` فقط با تغییرات کمینه و سازگار: منوی داینامیک، هدینگ‌های قابل ویرایش، پشتیبانی embed آپارات در کاروسل، اسلاید با URL مستقیم.
- `sw.js` برای ناوبری network-first است تا تغییرات پنل فوراً دیده شود؛ `/admin` و `/api` هرگز کش نمی‌شوند.
- تاریخ‌های پنل شمسی (تبدیل گرگوری→جلالی بدون وابستگی).
