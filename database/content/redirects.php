<?php

/*
| Redirect rules for the old WordPress URLs (decision D31).
|
| Generated from docs/phase-0/url-inventory.csv (Phase 0 inventory), with the
| Phase 5 adjustments recorded in docs/phase-0/decisions.md (D31):
|  - old tag archives → the closest article category (tags were not migrated);
|  - /product/samandehi-license/ → /services/ while that service is a draft;
|  - /wp-admin/ and /wp-json/ are prefix rules (trailing *).
| Nothing is redirected to the home page. Paths are written decoded; they are
| normalised on save. Imported create-only by `php artisan app:import-site-content`,
| so edits made in the admin panel are never overwritten.
|
| Format: [from, to (null for 410), status, note]
*/

return [
    ['/shop/', '/services/', 301, 'برگهٔ ووکامرس؛ فروشگاه در نسخهٔ ۱ نیست (D4)'],
    ['/cart/', null, 410, 'برگهٔ ووکامرس؛ فروشگاه در نسخهٔ ۱ نیست (D4)'],
    ['/checkout/', null, 410, 'برگهٔ ووکامرس؛ فروشگاه در نسخهٔ ۱ نیست (D4)'],
    ['/checkout-3/', null, 410, 'برگهٔ ووکامرس؛ فروشگاه در نسخهٔ ۱ نیست (D4)'],
    ['/my-account/', null, 410, 'برگهٔ ووکامرس؛ فروشگاه در نسخهٔ ۱ نیست (D4)'],
    ['/product/samandehi-license/', '/services/', 301, 'خدمت «مجوز ساماندهی» پیش‌نویس است؛ پس از انتشار، مقصد را به /services/samandehi-license/ تغییر دهید'],
    ['/product-category/خدمات-ثبتی-و-مجوزهای-کسب-و-کار-مجازی/', '/services/', 301, 'دستهٔ محصول ووکامرس (D4)'],
    ['/software-development/', '/blog/category/software-development/', 301, 'دستهٔ قدیمی وردپرس'],
    ['/software-development/programming-tutorials/', '/blog/category/programming-tutorials/', 301, 'دستهٔ قدیمی وردپرس'],
    ['/software-development/software-testing-optimization/', '/blog/category/software-testing-optimization/', 301, 'دستهٔ قدیمی وردپرس'],
    ['/wp/', '/blog/category/wp/', 301, 'دستهٔ قدیمی وردپرس'],
    ['/wp/wordpress-tutorials/', '/blog/category/wordpress-tutorials/', 301, 'دستهٔ قدیمی وردپرس'],
    ['/software-development/developer-tools/', null, 410, 'دستهٔ خالی وردپرس'],
    ['/software-development/new-technologies/', null, 410, 'دستهٔ خالی وردپرس'],
    ['/wp/wordpress-plugins/', null, 410, 'دستهٔ خالی وردپرس'],
    ['/wp/wordpress-themes/', null, 410, 'دستهٔ خالی وردپرس'],
    ['/wp/wordpress-seo-optimization/', null, 410, 'دستهٔ خالی وردپرس'],
    ['/wp/wordpress-troubleshooting/', null, 410, 'دستهٔ خالی وردپرس'],
    ['/data-science-ai/', null, 410, 'دستهٔ خالی وردپرس'],
    ['/data-science-ai/data-science-basics/', null, 410, 'دستهٔ خالی وردپرس'],
    ['/data-science-ai/machine-learning-modeling/', null, 410, 'دستهٔ خالی وردپرس'],
    ['/data-science-ai/big-data-processing/', null, 410, 'دستهٔ خالی وردپرس'],
    ['/data-science-ai/data-visualization/', null, 410, 'دستهٔ خالی وردپرس'],
    ['/office-software/', null, 410, 'دستهٔ خالی وردپرس'],
    ['/office-software/word/', null, 410, 'دستهٔ خالی وردپرس'],
    ['/office-software/excel/', null, 410, 'دستهٔ خالی وردپرس'],
    ['/office-software/powerpoint/', null, 410, 'دستهٔ خالی وردپرس'],
    ['/office-software/outlook/', null, 410, 'دستهٔ خالی وردپرس'],
    ['/office-software/other-office-software/', null, 410, 'دستهٔ خالی وردپرس'],
    ['/دستهبندی-نشده/', null, 410, 'دستهٔ خالی وردپرس'],
    ['/tag/آموزش-برنامهنویسی/', '/blog/category/programming-tutorials/', 301, 'برچسب قدیمی؛ برچسب‌ها منتقل نشدند → دستهٔ مرتبط'],
    ['/tag/آموزش-توسعهدهندگان/', '/blog/category/programming-tutorials/', 301, 'برچسب قدیمی؛ برچسب‌ها منتقل نشدند → دستهٔ مرتبط'],
    ['/tag/ابزارهای-تست/', '/blog/category/software-testing-optimization/', 301, 'برچسب قدیمی؛ برچسب‌ها منتقل نشدند → دستهٔ مرتبط'],
    ['/tag/افزایش-کیفیت-نرمافزار/', '/blog/category/software-testing-optimization/', 301, 'برچسب قدیمی؛ برچسب‌ها منتقل نشدند → دستهٔ مرتبط'],
    ['/tag/انتخاب-ابزارهای-برنامهنویسی/', '/blog/category/software-development/', 301, 'برچسب قدیمی؛ برچسب‌ها منتقل نشدند → دستهٔ مرتبط'],
    ['/tag/برنامهنویسی-برای-مبتدیان/', '/blog/category/software-development/', 301, 'برچسب قدیمی؛ برچسب‌ها منتقل نشدند → دستهٔ مرتبط'],
    ['/tag/بهینهسازی-نرمافزار/', '/blog/category/software-testing-optimization/', 301, 'برچسب قدیمی؛ برچسب‌ها منتقل نشدند → دستهٔ مرتبط'],
    ['/tag/تحلیل-نیازهای-پروژه-agile-و-توسعه-پروژه/', '/blog/category/software-development/', 301, 'برچسب قدیمی؛ برچسب‌ها منتقل نشدند → دستهٔ مرتبط'],
    ['/tag/تست-نرمافزار/', '/blog/category/software-testing-optimization/', 301, 'برچسب قدیمی؛ برچسب‌ها منتقل نشدند → دستهٔ مرتبط'],
    ['/tag/مدیریت-پروژههای-برنامهنویسی/', '/blog/category/software-development/', 301, 'برچسب قدیمی؛ برچسب‌ها منتقل نشدند → دستهٔ مرتبط'],
    ['/tag/یادگیری-کدنویسی-پایتون-برای-مبتدیان/', '/blog/category/programming-tutorials/', 301, 'برچسب قدیمی؛ برچسب‌ها منتقل نشدند → دستهٔ مرتبط'],
    ['/tag/download-new-music/', null, 410, 'برچسب خالی'],
    ['/tag/majid-razavi/', null, 410, 'برچسب خالی'],
    ['/tag/دانلود-آهنگ-ایرانی/', null, 410, 'برچسب خالی'],
    ['/tag/دانلود-آهنگ-جدید/', null, 410, 'برچسب خالی'],
    ['/tag/مجید-رضوی/', null, 410, 'برچسب خالی'],
    ['/tag/زبانهای-برنامهنویسی/', null, 410, 'برچسب خالی'],
    ['/author/smj/', '/about/', 301, 'بایگانی نویسنده؛ سایت تک‌نویسنده است'],
    ['/comments/feed/', null, 410, 'خوراک دیدگاه‌ها؛ دیدگاه در نسخهٔ ۱ نیست'],
    ['/sitemap_index.xml', '/sitemap.xml', 301, 'نقشهٔ سایت رنک‌مث'],
    ['/post-sitemap.xml', '/sitemap.xml', 301, 'نقشهٔ سایت رنک‌مث'],
    ['/page-sitemap.xml', '/sitemap.xml', 301, 'نقشهٔ سایت رنک‌مث'],
    ['/product-sitemap.xml', '/sitemap.xml', 301, 'نقشهٔ سایت رنک‌مث'],
    ['/category-sitemap.xml', '/sitemap.xml', 301, 'نقشهٔ سایت رنک‌مث'],
    ['/product_cat-sitemap.xml', '/sitemap.xml', 301, 'نقشهٔ سایت رنک‌مث'],
    ['/wp-admin/*', null, 410, 'پیشخوان وردپرس'],
    ['/wp-login.php', null, 410, 'ورود وردپرس'],
    ['/xmlrpc.php', null, 410, 'XML-RPC وردپرس'],
    ['/wp-json/*', null, 410, 'REST API وردپرس'],
];
