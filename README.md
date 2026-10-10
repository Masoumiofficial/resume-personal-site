# resume-personal-site

پلتفرم شخصی اختصاصی **سجاد معصومی**: جایگزین وردپرس فعلی `sajadmasoumi.com`، با CMS و پنل مدیریت مستقل، قابل اجرا روی هاست اشتراکی DirectAdmin.

## وضعیت

- **فاز ۰ (Discovery):** کامل شد. تصمیم‌ها در `decisions.md` ثبت شده‌اند.
- **فاز ۱ (محصول و طراحی):** معماری اطلاعات، مدل محتوا، سیستم طراحی و نمونهٔ اولیهٔ استاتیک آماده و **منتظر تأیید** است.
- **فاز ۲ (زیرساخت):** مسدود تا ارتقای PHP هاست از 8.1.34 (پایان پشتیبانی) به 8.4 (حداقل 8.3).

هنوز کد برنامهٔ Laravel نوشته نشده است.

## مستندات

| فایل | محتوا |
|---|---|
| [docs/phase-0/discovery-report.md](docs/phase-0/discovery-report.md) | گزارش Discovery: زیرساخت، تکنولوژی پیشنهادی، معماری، ریسک‌ها، برنامهٔ اجرا، پرسش‌های باز |
| [docs/phase-0/content-audit.md](docs/phase-0/content-audit.md) | ممیزی محتوای سایت فعلی (حفظ / تأیید / حذف) |
| [docs/phase-0/url-inventory.csv](docs/phase-0/url-inventory.csv) | فهرست URLهای فعلی و مقصد پیشنهادی برای مهاجرت سئو |
| [docs/phase-0/hosting-checklist.md](docs/phase-0/hosting-checklist.md) | راهنمای جمع‌آوری اطلاعات هاست |
| [docs/phase-0/decisions.md](docs/phase-0/decisions.md) | مشخصات تأییدشدهٔ هاست، تصمیم‌های D1–D12، راهنمای ارتقای PHP، پرسش‌های باز |
| [docs/phase-1/information-architecture.md](docs/phase-1/information-architecture.md) | نقشهٔ سایت و پنل، ساختار صفحات، مدل محتوا (جداول و فیلدها) |
| [docs/phase-1/design-system.md](docs/phase-1/design-system.md) | توکن‌ها، کنتراست، کامپوننت‌ها، قواعد RTL، دسترسی‌پذیری، بودجهٔ کارایی، آزمون‌ها |
| [design/prototype/](design/prototype/) | نمونهٔ اولیهٔ استاتیک: خانه، پیشخوان، راهنمای طراحی |
| [tools/host-check/host-check.php](tools/host-check/host-check.php) | اسکریپت تشخیصی یک‌بارمصرف هاست (محافظت‌شده با توکن) |

## قواعد مخزن

- این مخزن **عمومی** است. هرگز `.env`، رمز، توکن، Dump دیتابیس، فایل پشتیبان یا دادهٔ واقعی کاربران را Commit نکنید.
- همهٔ تغییرات ساختار دیتابیس از طریق Migration انجام می‌شوند.
- هیچ تستی بدون اجرای واقعی «موفق» گزارش نمی‌شود.
