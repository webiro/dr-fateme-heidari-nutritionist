# راهنمای ورکر رزرو مشاوره

ورکر `nutrition-appointments` روی Cloudflare با اتصال به دیتابیس D1 (هم‌نام) اجرا می‌شود و روی دامنه‌ی اختصاصی سرو می‌شود:

- آدرس API: `https://api.drfatemeheidari.ir`
- پنل ادمین: `https://api.drfatemeheidari.ir/admin`

> نکته: subdomain ورکر این اکانت (`*.webiro-ir.workers.dev`) در edge کلادفلر خطای 1101 می‌دهد؛ به همین دلیل ورکر روی custom domain سرو می‌شود.

## API

| متد | مسیر | توضیح |
|---|---|---|
| POST | `/api/appointments` | ثبت فرم (name و phone الزامی، اعتبارسنجی تلفن، محدودیت ۱۰ ثبت در دقیقه برای هر IP) |
| GET | `/api/admin/appointments` | لیست رزروها (نیازمند هدر `X-Admin-Password`) |
| PATCH | `/api/admin/appointments/:id` | تغییر وضعیت (`new` / `contacted` / `done`) |
| DELETE | `/api/admin/appointments/:id` | حذف رزرو |
| GET | `/admin` | صفحه‌ی پنل ادمین (noindex) |

## دیتابیس

- Binding: `DB` → D1 database با نام `nutrition-appointments`
- اسکیمای جدول در `schema.sql` (اعمال‌شده روی remote)

## رمز ادمین

رمز فعلی به‌صورت secret با نام `ADMIN_TOKEN` ست شده است. برای تغییر:

```bash
cd workers
npx wrangler secret put ADMIN_TOKEN
npx wrangler deploy
```

## دپلوی

```bash
cd workers
npx wrangler deploy
```

اتصال دامنه‌ی `api.drfatemeheidari.ir` از طریق `routes` در `wrangler.toml` انجام می‌شود (custom_domain، DNS خودکار).

## اتصال سایت

در `config.json` ریشه‌ی پروژه:

```json
"workerUrl": "https://api.drfatemeheidari.ir"
```

فرانت‌اند خودش به `workerUrl + /api/appointments` پست می‌کند.
