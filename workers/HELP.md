# راهنمای ورکر کلادفلر

این ورکر فرم رزرو مشاوره را ذخیره می‌کند و به داشبورد ادمین لیست و حذف می‌دهد.

## پیش‌نیاز
- حساب Cloudflare
- یک Worker
- یک دیتابیس D1

## ساخت دیتابیس
در ترمینال با Wrangler:

```bash
cd workers
npx wrangler d1 create nutrition-appointments
```

`database_id` را در `wrangler.toml` بگذارید.

سپس جدول را بسازید:

```bash
npx wrangler d1 execute nutrition-appointments --file=schema.sql
```

## متغیرها و رمزها
در تنظیمات ورکر:

| نام | نوع | توضیح |
|---|---|---|
| `ALLOWED_ORIGINS` | Var | دامنه سایت، مثلاً `https://drfatemeheidari.ir` |
| `ADMIN_USER` | Var | نام کاربری داشبورد |
| `ADMIN_PASS` | Secret | رمز عبور داشبورد |
| `AUTH_SECRET` | Secret | کلید امضای توکن |

از پنل Cloudflare: Worker → Settings → Variables

## انتشار

```bash
npx wrangler deploy
```

آدرس ورکر شبیه این است:

`https://nutrition-appointments.<ACCOUNT>.workers.dev`

همین آدرس را در `config.json` داخل فیلد `workerUrl` بگذارید.

## مسیرهای API
- `POST /api/appointments` — ثبت فرم (عمومی، فقط از دامنه مجاز)
- `POST /api/login` — ورود ادمین
- `GET /api/appointments` — لیست رکوردها (نیاز به Bearer Token)
- `DELETE /api/appointments/:id` — حذف یک رکورد (نیاز به Bearer Token)

## امنیت
- درخواست‌های تغییر داده فقط از Origin مجاز یا `127.0.0.1` / `localhost` پذیرفته می‌شوند.
- لیست و حذف بدون توکن معتبر جواب `401` می‌گیرند.
- رمز و کلید را هرگز داخل فایل عمومی پروژه نگذارید.

## اتصال به سایت
در `config.json`:

```json
"domain": "drfatemeheidari.ir",
"workerUrl": "https://nutrition-appointments.YOUR_ACCOUNT.workers.dev"
```

فرم صفحه اصلی اگر `appointmentEndpoint` خالی باشد، خودکار به `workerUrl/api/appointments` می‌فرستد.
