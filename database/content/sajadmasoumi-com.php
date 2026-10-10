<?php

/*
| Content carried over from the WordPress site sajadmasoumi.com.
|
| Source: the live site (Phase 0 audit, docs/phase-0/content-audit.md) and the
| Web Archive capture of 2026-02-26 (the live homepage returned HTTP 500 on
| 2026-10-10). Imported by `php artisan app:import-site-content`.
|
| Rules applied (docs/phase-0/decisions.md):
|  - D6: no fake testimonials, fun facts, skill percentages or Google logo.
|  - Claims without evidence are not imported («چند برابر» sales claim).
|  - Birth date is personal data and stays private.
|  - Items that are unconfirmed or incomplete are imported hidden (is_visible
|    false) or as drafts, so they are only published after review.
|  - Prices are the published tariffs, unchanged (D7).
*/

return [
    'profile' => [
        'display_name' => 'سجاد معصومی',
        // Current headline of the old site; the final professional title still needs the owner's confirmation.
        'headline' => 'توسعه‌دهنده وردپرس و طراح سایت، متخصص داده و هوش مصنوعی و مشاور رشد کسب‌وکار اینترنتی',
        'short_bio' => 'درود، من سجاد معصومی هستم. از سال ۱۳۹۲ مسیر کاری‌ام را شروع کردم و امروز تمرکزم روی طراحی و توسعهٔ وب‌سایت‌های حرفه‌ای با وردپرس، بهینه‌سازی سرعت و سئو، و ساخت سیستم‌هایی است که به رشد کسب‌وکارها کمک می‌کند.',
        'long_bio' => "درود، من سجاد معصومی هستم. از سال ۱۳۹۲ مسیر کاری‌ام را شروع کردم و امروز تمرکزم روی طراحی و توسعهٔ وب‌سایت‌های حرفه‌ای با وردپرس، بهینه‌سازی سرعت و سئو، و ساخت سیستم‌هایی است که به رشد کسب‌وکارها کمک می‌کند.\n\nاعتقاد دارم وب‌سایت فقط یک ظاهر زیبا نیست؛ وب‌سایت باید **فروش بسازد، اعتماد بسازد و رشد ایجاد کند.**\n\n> همیشه همان تغییری باش که می‌خواهی در دنیا ببینی.",
        'location' => 'ایران، شیراز',
        'show_location' => true,
        'email' => 'info@sajadmasoumi.com', // D8
        'phone' => '09910011146',           // D9: shown publicly
        'show_phone' => true,
        'availability_note' => null,        // old "Freelance: Available" is not carried over
    ],

    'experiences' => [
        [
            'organization' => 'کاکو موزیک',
            'organization_url' => 'https://kako-music.ir/about/',
            'role' => 'مدیر مسئول و بنیان‌گذار',
            'employment_type' => 'founder',
            'start' => '1392',
            'summary' => 'رسانه و سرویس انتشار موسیقی فارسی برای هنرمندان و کاربران.',
            'is_visible' => true,
        ],
        [
            'organization' => 'مکتب گرافیک',
            'organization_url' => 'https://maktabgraphic.com/',
            'role' => 'مدرس، مدیر مسئول و بنیان‌گذار',
            'employment_type' => 'founder',
            'start' => '1400',
            'summary' => 'پلتفرم بومی آموزش آنلاین طراحی گرافیک.',
            'is_visible' => true,
        ],
        [
            // D12: role from etehadwp.com/about-us; no founding year is published, so no date.
            'organization' => 'اتحاد وردپرس',
            'organization_url' => 'https://etehadwp.com/',
            'role' => 'بنیان‌گذار و مدیر پروژه',
            'employment_type' => 'founder',
            'start' => null,
            'summary' => null,
            'is_visible' => true,
        ],
        [
            // D13: confirmed by the owner; period unknown, so no date.
            'organization' => 'آماد پارت',
            'organization_url' => 'https://amadpart.com/',
            'role' => 'مدیر دیجیتال مارکتینگ',
            'employment_type' => null,
            'start' => null,
            'summary' => null,
            'is_visible' => true,
        ],
    ],

    'certifications' => [
        ['title' => 'دورهٔ سئو', 'issuer' => 'محسن حبیبی', 'start' => '1402/01', 'end' => '1402/02', 'is_visible' => true],
        // Listed as «در حال برگزاری» since 1401 — status unclear, hidden until reviewed.
        ['title' => 'دورهٔ اینستاگرام', 'issuer' => 'احمد کلاته', 'start' => '1401/01', 'end' => null, 'is_visible' => false],
        // «دوره مسترکلاس سئو» (repeated three times, no date or issuer) looks like theme demo content: not imported.
    ],

    // Percentages dropped (D6). Visible = the short list on the old homepage; the rest is hidden for review.
    'skills' => [
        'development' => [
            ['WordPress', true], ['PHP', true], ['HTML', true], ['CSS', true], ['React', true], ['Java', true],
            ['JavaScript', false], ['jQuery', false], ['TypeScript', false], ['C#', false], ['Visual Basic', false],
            ['برنامه‌نویسی شیءگرا (OOP)', false], ['ساختار داده‌ها و الگوریتم‌ها', false],
        ],
        'data_ai' => [
            ['هوش مصنوعی (AI)', true], ['علم داده و یادگیری ماشین', false], ['SQL و پایگاه داده', false],
        ],
        'design' => [
            ['Photoshop', true], ['Illustrator', true],
            ['Premiere', false], ['After Effects', false], ['InDesign', false], ['Dreamweaver', false],
        ],
        'management' => [
            ['مدیریت و توسعهٔ پروژه', false], ['مدیریت کمپین تبلیغاتی', false], ['ارتباط و همکاری', false],
        ],
        'tools' => [
            ['مدیریت وردپرس', false], ['مدیریت لینوکس و ویندوز', false],
            ['Visual Studio', false], ['Android Studio', false], ['Xcode', false], ['OutSystems', false],
        ],
        // The old homepage lists «فارسی، انگلیسی»; levels are not documented, so none are set.
        'language' => [
            ['فارسی', true], ['انگلیسی', true], ['اسپانیایی', false], ['فرانسوی', false], ['ایتالیایی', false],
        ],
    ],

    'service_groups' => [
        [
            'title' => 'توسعه وردپرس و وب', 'slug' => 'web-development',
            'services' => [
                ['title' => 'طراحی سایت شرکتی / ساده', 'price' => 7500000, 'unit' => 'once'],
                ['title' => 'طراحی فروشگاه آنلاین ووکامرس', 'price' => 15000000, 'unit' => 'once'],
                ['title' => 'طراحی و توسعه قالب اختصاصی', 'price' => 10000000, 'unit' => 'once'],
                ['title' => 'طراحی و توسعه افزونه اختصاصی', 'price' => 12000000, 'unit' => 'once'],
                ['title' => 'بهینه‌سازی سرعت و سئو سایت', 'price' => 5000000, 'unit' => 'once'],
                ['title' => 'امنیت و پشتیبانی سایت', 'price' => 1500000, 'unit' => 'monthly'],
                [
                    // D4: simple service page, no online payment in v1. The discounted price (1,500,000) is the
                    // same in both places; the original price conflicts (2,000,000 vs 2,900,000), so none is set
                    // and the service stays a draft until the owner confirms.
                    'title' => 'مجوز ساماندهی', 'slug' => 'samandehi-license', 'has_page' => true,
                    'price' => 1500000, 'unit' => 'once', 'status' => 'draft',
                ],
            ],
        ],
        [
            'title' => 'تحلیل داده و هوش مصنوعی', 'slug' => 'data-ai',
            'services' => [
                ['title' => 'تحلیل داده و گزارش‌گیری', 'price' => 3500000, 'unit' => 'once'],
                ['title' => 'مدل‌سازی و پیش‌بینی روندها', 'price' => 8500000, 'unit' => 'once'],
                ['title' => 'بهینه‌سازی فرایندها با AI', 'price' => 12500000, 'unit' => 'once'],
                ['title' => 'اتوماسیون و هوش تجاری', 'price' => 9000000, 'unit' => 'once'],
            ],
        ],
        [
            'title' => 'مدیریت پروژه و رشد کسب‌وکار', 'slug' => 'project-growth',
            'services' => [
                ['title' => 'مدیریت کامل پروژهٔ دیجیتال', 'price' => 18000000, 'unit' => 'monthly'],
                ['title' => 'استراتژی رشد و توسعهٔ کسب‌وکار', 'price' => 1000000, 'unit' => 'hourly'],
                ['title' => 'مشاورهٔ بهبود عملکرد تیم و فرایندها', 'price' => 1000000, 'unit' => 'hourly'],
                ['title' => 'مشاوره برای افزایش فروش و بهره‌وری', 'price' => 1500000, 'unit' => 'hourly'],
            ],
        ],
    ],

    // Legacy WordPress category slugs are kept so old category URLs can map 1:1 (redirects: phase 5).
    'article_categories' => [
        ['name' => 'توسعه نرم‌افزار', 'slug' => 'software-development', 'children' => [
            ['name' => 'آموزش برنامه‌نویسی', 'slug' => 'programming-tutorials'],
            ['name' => 'تست و بهینه‌سازی نرم‌افزار', 'slug' => 'software-testing-optimization'],
        ]],
        ['name' => 'وردپرس', 'slug' => 'wp', 'children' => [
            ['name' => 'آموزش وردپرس', 'slug' => 'wordpress-tutorials'],
        ]],
    ],
];
