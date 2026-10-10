# resume-personal-site

پلتفرم شخصی اختصاصی **سجاد معصومی**: جایگزین وردپرس فعلی `sajadmasoumi.com`، با CMS و پنل مدیریت مستقل، قابل اجرا روی هاست اشتراکی DirectAdmin.

## وضعیت

**فاز ۰ (Discovery) انجام شده است.** هنوز کد برنامه‌ای نوشته نشده است.

شروع پیاده‌سازی (فاز ۲) منتظر تأیید مشخصات هاست است (نسخهٔ PHP ≥ 8.3 و روش امن استقرار).

## مستندات

| فایل | محتوا |
|---|---|
| [docs/phase-0/discovery-report.md](docs/phase-0/discovery-report.md) | گزارش Discovery: زیرساخت، تکنولوژی پیشنهادی، معماری، ریسک‌ها، برنامهٔ اجرا، پرسش‌های باز |
| [docs/phase-0/content-audit.md](docs/phase-0/content-audit.md) | ممیزی محتوای سایت فعلی (حفظ / تأیید / حذف) |
| [docs/phase-0/url-inventory.csv](docs/phase-0/url-inventory.csv) | فهرست URLهای فعلی و مقصد پیشنهادی برای مهاجرت سئو |
| [docs/phase-0/hosting-checklist.md](docs/phase-0/hosting-checklist.md) | راهنمای جمع‌آوری اطلاعات هاست |
| [tools/host-check/host-check.php](tools/host-check/host-check.php) | اسکریپت تشخیصی یک‌بارمصرف هاست (محافظت‌شده با توکن) |

## قواعد مخزن

- این مخزن **عمومی** است. هرگز `.env`، رمز، توکن، Dump دیتابیس، فایل پشتیبان یا دادهٔ واقعی کاربران را Commit نکنید.
- همهٔ تغییرات ساختار دیتابیس از طریق Migration انجام می‌شوند.
- هیچ تستی بدون اجرای واقعی «موفق» گزارش نمی‌شود.
