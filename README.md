# resume-personal-site

پلتفرم شخصی اختصاصی **سجاد معصومی**: جایگزین وردپرس فعلی `sajadmasoumi.com`، با CMS و پنل مدیریت مستقل، قابل اجرا روی هاست اشتراکی DirectAdmin.

## وضعیت

- **فاز ۰ (Discovery):** کامل شد.
- **فاز ۱ (محصول و طراحی):** تأیید شد.
- **فاز ۲ (زیرساخت):** کد پیاده شده و CI سبز است. جزئیات در گزارش فاز ۲ آمده است.
  - Laravel 13 و احراز هویت Fortify با ورود دومرحله‌ای TOTP.
  - ۱۲ فایل Migration (۲۷ جدول) مطابق مدل محتوا.
  - هدرهای امنیتی و CSP با nonce.
  - تاریخ شمسی بدون نیاز به intl.
  - اسکلت پنل مدیریت.
- **فاز ۳ (CMS):** بعدی.

> ارتقای PHP هاست از 8.1.34 به 8.4 (حداقل 8.3) برای **استقرار** (فاز ۶) لازم است، نه برای توسعه.

## اجرای محلی

نیازمندی‌ها: PHP 8.3 یا بالاتر با افزونه‌های `pdo_sqlite` یا `pdo_mysql`، `mbstring` و `openssl` (و `gd` از فاز ۳ برای رسانه)، به‌علاوهٔ Composer 2. **Node لازم نیست** (تصمیم D14).

```bash
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite   # یا تنظیم DB_* برای MySQL/MariaDB
php artisan migrate
php artisan app:create-admin     # تنها راه ساخت مدیر؛ ثبت‌نام عمومی وجود ندارد
php artisan serve                # پنل: http://127.0.0.1:8000/admin
php artisan test
vendor/bin/pint --test
```

CI روی هر Push این بررسی‌ها را اجرا می‌کند (`.github/workflows/ci.yml`):
- Pint
- تست‌ها روی PHP 8.3 و 8.4 با SQLite
- تست‌ها روی MariaDB 10.3 و MySQL 8.0
- `composer audit`

## مستندات

| فایل | محتوا |
|---|---|
| [docs/phase-0/discovery-report.md](docs/phase-0/discovery-report.md) | گزارش Discovery: زیرساخت، تکنولوژی پیشنهادی، معماری، ریسک‌ها، برنامهٔ اجرا، پرسش‌های باز |
| [docs/phase-0/content-audit.md](docs/phase-0/content-audit.md) | ممیزی محتوای سایت فعلی (حفظ / تأیید / حذف) |
| [docs/phase-0/url-inventory.csv](docs/phase-0/url-inventory.csv) | فهرست URLهای فعلی و مقصد پیشنهادی برای مهاجرت سئو |
| [docs/phase-0/hosting-checklist.md](docs/phase-0/hosting-checklist.md) | راهنمای جمع‌آوری اطلاعات هاست |
| [docs/phase-0/decisions.md](docs/phase-0/decisions.md) | مشخصات تأییدشدهٔ هاست، تصمیم‌های D1–D15، راهنمای ارتقای PHP، پرسش‌های باز |
| [docs/phase-1/information-architecture.md](docs/phase-1/information-architecture.md) | نقشهٔ سایت و پنل، ساختار صفحات، مدل محتوا (جداول و فیلدها) |
| [docs/phase-1/design-system.md](docs/phase-1/design-system.md) | توکن‌ها، کنتراست، کامپوننت‌ها، قواعد RTL، دسترسی‌پذیری، بودجهٔ کارایی، آزمون‌ها |
| [design/prototype/](design/prototype/) | نمونهٔ اولیهٔ استاتیک: خانه، پیشخوان، راهنمای طراحی |
| [tools/host-check/host-check.php](tools/host-check/host-check.php) | اسکریپت تشخیصی یک‌بارمصرف هاست (محافظت‌شده با توکن) |

## قواعد مخزن

- این مخزن **عمومی** است. هرگز `.env`، رمز، توکن، Dump دیتابیس، فایل پشتیبان یا دادهٔ واقعی کاربران را Commit نکنید.
- همهٔ تغییرات ساختار دیتابیس از طریق Migration انجام می‌شوند.
- هیچ تستی بدون اجرای واقعی «موفق» گزارش نمی‌شود.
