# فاز ۱: معماری اطلاعات، ساختار صفحات و مدل محتوایی

**وضعیت:** پیشنهاد برای تأیید کاربر
**وابستگی به هاست:** ندارد. ساختار دیتابیس در فاز ۲ به Migration تبدیل می‌شود.

---

## ۱. نقشهٔ سایت عمومی

| مسیر | صفحه | منبع داده | یادداشت |
|---|---|---|---|
| `/` | خانه | پروفایل، برندها، خدمات، پروژه‌های منتخب، مقالات اخیر، تنظیمات | |
| `/about/` | درباره | پروفایل (معرفی کامل)، سوابق کلیدی | URL فعلی حفظ می‌شود. |
| `/resume/` | رزومه | سوابق، تحصیلات، مهارت‌ها، گواهی/دوره، زبان‌ها | **نسخهٔ چاپی با CSS `@media print`** و دکمهٔ «دانلود PDF» (چاپ مرورگر) |
| `/works/` | فهرست نمونه‌کارها | پروژه‌ها (منتشرشده) + فیلتر دسته | URL فعلی حفظ می‌شود. |
| `/works/{slug}/` | جزئیات پروژه | پروژه | Schema: `CreativeWork` |
| `/services/` | خدمات و تعرفه | گروه‌ها و آیتم‌های خدمت | جدید (D4، D7) |
| `/services/{slug}/` | صفحهٔ خدمت | آیتم خدمت دارای صفحهٔ اختصاصی | اولین مورد: `samandehi-license` |
| `/blog/` و `/blog/page/{n}/` | فهرست مقالات | مقالات | |
| `/blog/category/{slug}/` | آرشیو دسته | دسته + مقالات | 301 از مسیرهای قدیمی |
| `/blog/tag/{slug}/` | آرشیو برچسب | برچسب + مقالات | 301 از `/tag/…` |
| `/{article-slug}/` | مقاله | مقاله | **URL ریشه حفظ می‌شود** تا ریدایرکت لازم نباشد. Schema: `Article` |
| `/contact/` | تماس | تنظیمات تماس + فرم | URL فعلی حفظ می‌شود. |
| `/feed/` | RSS مقالات | مقالات | |
| `/sitemap.xml`، `/robots.txt` | فنی | پویا | |
| 404 / 410 | خطا | — | فارسی، با جست‌وجو و لینک‌های اصلی |

**اسلاگ‌های رزرو:** چون مقالات در ریشه هستند، اسلاگ مقاله نمی‌تواند یکی از این‌ها باشد (در Form Request اعتبارسنجی می‌شود):

`admin, about, resume, works, services, blog, contact, feed, sitemap.xml, robots.txt, media, build, storage, login, logout, password, two-factor, up, api, wp-admin, wp-content, wp-login.php, xmlrpc.php`

## ۲. نقشهٔ پنل مدیریت

پیشوند `/admin`، قابل تغییر با `ADMIN_PATH` در `.env`. این تغییر فقط نویز ربات‌ها را کم می‌کند و جایگزین کنترل دسترسی **نیست**.

- **ورود:** ورود، 2FA، فراموشی رمز، بازنشانی رمز. **ثبت‌نام عمومی وجود ندارد.**
- **داشبورد:**
  - شمارش واقعی مقالات و پروژه‌ها (منتشر/پیش‌نویس)
  - پیام‌های جدید
  - ۱۰ فعالیت اخیر
  - هشدارها: نسخهٔ پشتیبان قدیمی، `APP_DEBUG` روشن، 2FA غیرفعال
- **پروفایل:** هویت، معرفی، تماس، شبکه‌های اجتماعی، تنظیمات نمایش
- **رزومه:** سوابق کاری، تحصیلات، مهارت‌ها و زبان‌ها، گواهی‌ها و دوره‌ها و جوایز. همه با مرتب‌سازی (کشیدن یا دکمهٔ بالا/پایین).
- **نمونه‌کارها:** پروژه‌ها، دسته‌ها
- **مقالات:** مقالات، دسته‌ها، برچسب‌ها
- **خدمات:** گروه‌ها، آیتم‌ها و قیمت‌ها
- **نظرات مشتریان:** فقط نظر واقعی با تیک «رضایت انتشار»
- **رسانه:** آپلود، متن جایگزین، فایل‌های بی‌استفاده
- **پیام‌ها:** صندوق، وضعیت رسیدگی
- **سئو:** ریدایرکت‌ها (301/302/410 + آمار برخورد)، تنظیمات پیش‌فرض متا
- **تنظیمات:** عمومی، برند، فوتر، صفحهٔ اصلی
- **حساب کاربری:** تغییر رمز، 2FA، نشست‌ها
- **گزارش فعالیت**

## ۳. ساختار صفحات اصلی

### خانه (به ترتیب)

1. **Hero:**
   - نام و عنوان حرفه‌ای
   - معرفی یک‌جمله‌ای
   - دکمه‌ها: «مشاهده نمونه‌کارها»، «تماس با من»، «رزومه»
   - عکس پروفایل (تصویر LCP، **بدون** lazy-load)
2. **نوار حقایق قابل دفاع:** فعالیت از ۱۳۹۲ · بنیان‌گذار ۳ برند · شیراز
3. **حوزه‌های تخصص:** ۳ کارت (وب و وردپرس / داده و هوش مصنوعی / مدیریت پروژه و رشد)
4. **برندهایی که ساخته‌ام:** کاکو موزیک، مکتب گرافیک، اتحاد وردپرس. نقش، سال و لینک.
5. **نمونه‌کارهای منتخب:** ۳ مورد. اگر خالی باشد، **کل بخش پنهان** می‌شود.
6. **خدمات و تعرفه (خلاصه):** «شروع از …» + لینک `/services/`
7. **نظرات مشتریان:** فقط وقتی حداقل یک نظر واقعی منتشر شده باشد.
8. **مقالات اخیر:** ۳ مورد
9. **CTA تماس**
10. **فوتر**

**حذف‌شده نسبت به سایت فعلی:** متن کامل بیوگرافی و رزومه (محتوای تکراری)، درصدها، «حقایق جالب»، نظرات ساختگی، لوگوی Google، نقشهٔ لندن.

### مقاله

Breadcrumb · H1 · تاریخ شمسی انتشار و به‌روزرسانی · زمان مطالعه · تصویر شاخص · فهرست مطالب (برای مقالات بلند) · متن · دسته‌ها و برچسب‌ها · مقالات مرتبط · CTA

### پروژه

H1 · خلاصه · نقش من · فناوری‌ها · **مسئله → راهکار → نتیجه** (نتیجه فقط با مدرک) · گالری · لینک پروژه

### رزومه

سربرگ (نام، عنوان، تماس) · خلاصه · سوابق · تحصیلات · مهارت‌ها (گروه‌بندی‌شده، بدون درصد) · زبان‌ها (سطح توصیفی یا CEFR) · گواهی‌ها و دوره‌ها

نسخهٔ چاپی: تک‌ستونه، A4، بدون منو و فوتر، لینک‌ها با URL.

---

## ۴. مدل محتوایی (Content Model)

**قواعد مشترک:**

- `utf8mb4_unicode_ci`
- تاریخ‌ها به **UTC** ذخیره و به `Asia/Tehran` و شمسی نمایش داده می‌شوند.
- `id` از نوع BIGINT
- `timestamps` در همهٔ جداول
- `sort_order` (INT، ایندکس‌دار) برای همهٔ موارد قابل مرتب‌سازی

**وضعیت انتشار** (مقاله، پروژه، صفحهٔ خدمت):

| فیلد | مقادیر |
|---|---|
| `status` | `draft` \| `published` |
| `published_at` | نال‌پذیر |

یک مورد در سایت عمومی نمایش داده می‌شود اگر و فقط اگر: `status = published` **و** `published_at <= now()`. به این ترتیب انتشار زمان‌بندی‌شده بدون نیاز به Cron کار می‌کند.

**فیلدهای SEO** (مقاله، پروژه، صفحهٔ خدمت):

`meta_title` (≤ 70)، `meta_description` (≤ 160)، `og_image_id` (FK media)، `canonical_url` (نال‌پذیر)، `noindex` (bool)

**SoftDeletes:** فقط برای مقاله و پروژه، برای بازیابی حذف تصادفی. پیام‌های تماس حذف واقعی می‌شوند (حریم خصوصی).

### ۴.۱ هویت و دسترسی

| جدول | فیلدهای کلیدی | دلیل |
|---|---|---|
| `users` | name، email (unique)، password (hash)، `two_factor_secret`، `two_factor_recovery_codes` (رمزگذاری‌شده)، `two_factor_confirmed_at`، `last_login_at` | Fortify. فعلاً یک مدیر. |
| `password_reset_tokens` | email (PK)، token (hash)، created_at | استاندارد Laravel. انقضا ۳۰ دقیقه. |
| `sessions` | استاندارد | درایور `database`: امکان خروج از نشست‌های دیگر و مستقل از فایل‌سیستم هاست |

### ۴.۲ پروفایل و تنظیمات

| جدول | فیلدهای کلیدی | دلیل |
|---|---|---|
| `profiles` (یک ردیف) | display_name، headline، short_bio، long_bio (Markdown)، avatar_id، location، show_location، email، phone، show_phone، `social_links` (JSON: نوع + URL)، availability_note، resume_summary | هویت یک نفره. JSON برای شبکه‌ها کافی است چون کوئری روی آن‌ها لازم نیست. |
| `settings` | key (unique)، value (JSON) | عنوان سایت، لوگو، favicon، رنگ تأکیدی، فوتر، متای پیش‌فرض، بخش‌های فعال خانه |

### ۴.۳ رزومه

| جدول | فیلدهای کلیدی |
|---|---|
| `experiences` | organization، organization_url، logo_id، role، employment_type، start_date، end_date (نال = تاکنون)، `date_precision` (year\|month)، location، summary، `highlights` (JSON آرایهٔ متن)، is_visible، sort_order |
| `educations` | institution، degree، field، start_date، end_date، date_precision، description، is_visible، sort_order |
| `skills` | name، `group` (development\|data_ai\|design\|management\|language\|tools)، `level_label` (متن اختیاری، مثل «روزمره» یا «B2»)، `years` (نال‌پذیر)، is_visible، sort_order. **بدون درصد.** زبان‌ها با group=language ثبت می‌شوند. |
| `certifications` | `kind` (certificate\|course\|award)، title، issuer، issued_at، end_at، date_precision، credential_url، `evidence_media_id`، description، is_visible، sort_order. دوره‌ها و جوایز هم اینجا ثبت می‌شوند و به جدول جدا نیازی نیست. |

**تاریخ‌ها:** `date` + `date_precision`، چون بیشتر سوابق فقط «ماه/سال» یا «سال» دارند (مثلاً «۱۳۹۲ تاکنون»).

### ۴.۴ نمونه‌کارها

| جدول | فیلدهای کلیدی |
|---|---|
| `projects` | title، slug (unique)، summary، body (Markdown)، problem، solution، `role`، `technologies` (JSON)، `outcome`، `outcome_evidence_url`، project_url، client_name (نال‌پذیر، با اجازه)، year، cover_id، is_featured، sort_order، status، published_at، SEO، deleted_at |
| `project_categories` | name، slug (unique)، sort_order |
| `project_category` | project_id، project_category_id (PK مرکب، FK cascade) |
| `project_media` | project_id، media_id، caption، sort_order: گالری |

**قاعده:** `outcome` فقط وقتی در سایت نمایش داده می‌شود که `outcome_evidence_url` پر باشد یا مدیر تیک «تأییدشده» را زده باشد.

### ۴.۵ مقالات

| جدول | فیلدهای کلیدی |
|---|---|
| `articles` | title، slug (unique، با بررسی اسلاگ‌های رزرو)، excerpt، body (Markdown)، `body_html` (cache رندرشده و پاک‌سازی‌شده)، `legacy_html` (فقط برای محتوای وارداتی، پاک‌سازی‌شده)، cover_id، reading_minutes، status، published_at، updated_content_at، `legacy_wp_id`، SEO، deleted_at |
| `article_categories` | name، slug (unique)، parent_id (نال‌پذیر، یک سطح)، description، sort_order |
| `article_category` | PK مرکب |
| `tags` | name، slug (unique) |
| `article_tag` | PK مرکب |

**ایندکس‌ها:** `(status, published_at)` روی articles و projects.

### ۴.۶ خدمات (جدید، طبق D4 و D7)

| جدول | فیلدهای کلیدی | دلیل |
|---|---|---|
| `service_groups` | title، slug، summary، icon، sort_order | ۳ گروه فعلی |
| `services` | service_group_id، title، slug (unique، نال‌پذیر اگر صفحهٔ جدا ندارد)، summary، body (Markdown، برای صفحهٔ اختصاصی)، `price_from` (BIGINT به **تومان**)، `price_original` (نال‌پذیر، برای نمایش تخفیف)، `price_unit` (once\|monthly\|hourly)، `price_note`، `cta_type` (contact\|external)، `cta_url`، `has_page`، is_visible، sort_order، status، published_at، SEO | جدول قیمت قابل ویرایش و صفحهٔ «مجوز ساماندهی». `cta_type=external` امکان اتصال بعدی به زرین‌پال یا اتحاد وردپرس را بدون تغییر ساختار فراهم می‌کند. |

### ۴.۷ نظرات مشتریان (جدید، طبق D6)

| جدول | فیلدهای کلیدی | دلیل |
|---|---|---|
| `testimonials` | author_name، author_role، company، company_url، avatar_id، quote، related_project_id (نال‌پذیر)، **`consent_confirmed` (bool، الزامی برای انتشار)**، received_at، is_visible، sort_order | کاربر بخش نظرات را می‌خواهد. با این قاعده فقط نظر واقعی و با رضایت منتشر می‌شود. |

### ۴.۸ رسانه

| جدول | فیلدهای کلیدی |
|---|---|
| `media` | `disk`، `path` (نام تصادفی ULID)، original_name (فقط نمایش)، mime (از **محتوای فایل**، نه پسوند)، size، width، height، `sha256` (unique: جلوگیری از آپلود تکراری)، alt، caption، `variants` (JSON: مسیر WebP در چند عرض)، uploaded_by، legacy_url (برای ریدایرکت فایل‌های وردپرس) |

- **انواع مجاز:** JPEG، PNG، WebP، (AVIF در صورت پشتیبانی GD)، PDF (فقط برای رزومه و مدارک)
- **SVG:** مجاز نیست (ریسک XSS)
- **تشخیص فایل بی‌استفاده:** بررسی FKها + جست‌وجوی مسیر در `body`
- **حذف:** فقط با تأیید

### ۴.۹ تماس، سئو، فعالیت

| جدول | فیلدهای کلیدی | دلیل |
|---|---|---|
| `contact_messages` | name، email، subject، message، status (new\|read\|replied\|archived\|spam)، `ip_hash` (HMAC با کلید برنامه، **نه IP خام**)، user_agent (کوتاه‌شده)، handled_at | پیشنهاد نگهداری: ۱۲ ماه، سپس حذف با Cron یا دستی |
| `redirects` | from_path (TEXT، نرمال‌شده) + from_hash (SHA-256، unique؛ چون URLهای فارسی کدشده از حد طول ایندکس بیشترند)، to_url (نال‌پذیر برای 410)، status_code (301\|302\|410)، hits، last_hit_at، note، is_active | مهاجرت وردپرس + مدیریت بعدی URLها |
| `activity_logs` | user_id (نال‌پذیر)، event (`auth.login`\|`auth.failed`\|`auth.two_factor_enabled`\|… و در فاز ۳ `article.updated`\|…)، subject_type، subject_id، ip_hash، `properties` (JSON، **بدون رمز یا توکن**)، created_at | ممیزی امنیتی و «فعالیت‌های اخیر» داشبورد |

---

## ۵. معیارهای پذیرش فاز ۱

- [x] کاربر نقشهٔ سایت و پنل را تأیید کرده است (۱۸ مهر ۱۴۰۵، «ادامه بده»).
- [x] کاربر نمونهٔ بصری (`design/prototype/`) و جهت طراحی را تأیید کرده است.
- [x] مدل محتوایی تأیید شده است. هر جدول دلیل نیاز دارد. جداول افزوده نسبت به Spec: `services`، `service_groups`، `testimonials`، `redirects`، `project_media`.
- [ ] عنوان حرفه‌ای نهایی مشخص شده است. (**هنوز باز**؛ مانع فاز ۲ و ۳ نیست، پیش از انتشار فاز ۴ لازم است.)
