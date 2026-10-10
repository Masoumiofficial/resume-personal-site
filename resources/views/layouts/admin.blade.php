<!doctype html>
<html lang="fa" dir="rtl">
<head>
  @include('partials.admin-head')
  <title>@yield('title') | پنل مدیریت</title>
</head>
<body>
<a class="skip-link" href="#admin-main">پرش به محتوا</a>
@include('partials.icons')

<div class="admin">
  <aside class="admin-sidebar" id="admin-sidebar" aria-label="منوی مدیریت">
    <a class="brand" href="{{ route('admin.dashboard') }}"><span class="monogram" aria-hidden="true">س‌م</span><span>پنل مدیریت</span></a>
    <nav class="admin-nav">
      @php
        $navGroups = [
          null => [['admin.dashboard', 'admin.dashboard', 'i-home', 'پیشخوان']],
          'محتوا' => [
            ['admin.articles.index', 'admin.articles.*|admin.article-categories.*|admin.tags.*', 'i-file', 'مقالات'],
            ['admin.projects.index', 'admin.projects.*|admin.project-categories.*', 'i-briefcase', 'نمونه‌کارها'],
            ['admin.services.index', 'admin.services.*|admin.service-groups.*', 'i-layers', 'خدمات و تعرفه'],
            ['admin.testimonials.index', 'admin.testimonials.*', 'i-quote', 'نظرات مشتریان'],
          ],
          'رزومه' => [
            ['admin.profile.edit', 'admin.profile.*', 'i-user', 'پروفایل'],
            ['admin.experiences.index', 'admin.experiences.*', 'i-briefcase', 'سوابق کاری'],
            ['admin.educations.index', 'admin.educations.*', 'i-cap', 'تحصیلات'],
            ['admin.skills.index', 'admin.skills.*', 'i-code', 'مهارت‌ها و زبان‌ها'],
            ['admin.certifications.index', 'admin.certifications.*', 'i-award', 'دوره‌ها و گواهی‌ها'],
            ['admin.resume.print', 'admin.resume.print', 'i-printer', 'نسخهٔ چاپی رزومه'],
          ],
          'رسانه و پیام' => [
            ['admin.media.index', 'admin.media.*', 'i-image', 'رسانه'],
            ['admin.messages.index', 'admin.messages.*', 'i-mail', 'پیام‌ها'],
          ],
          'سیستم' => [
            ['admin.settings.edit', 'admin.settings.*', 'i-settings', 'تنظیمات'],
            ['admin.redirects.index', 'admin.redirects.*', 'i-redirect', 'تغییرمسیرها'],
            ['admin.activity', 'admin.activity', 'i-list', 'گزارش فعالیت'],
            ['admin.security', 'admin.security', 'i-shield', 'امنیت حساب'],
          ],
        ];
        $newMessages = \Illuminate\Support\Facades\DB::table('contact_messages')->where('status', 'new')->count();
      @endphp
      @foreach ($navGroups as $group => $links)
        <div class="admin-nav__group @unless ($loop->first) mt-5 @endunless">
          @if ($group)<span class="admin-nav__label">{{ $group }}</span>@endif
          @foreach ($links as [$routeName, $pattern, $icon, $label])
            <a href="{{ route($routeName) }}" @if (request()->routeIs(...explode('|', $pattern))) aria-current="page" @endif><svg aria-hidden="true"><use href="#{{ $icon }}"/></svg>{{ $label }}@if ($routeName === 'admin.messages.index' && $newMessages > 0)<span class="badge badge--accent admin-nav__count">{{ \App\Support\Jalali::number($newMessages) }}<span class="visually-hidden"> پیام جدید</span></span>@endif</a>
          @endforeach
        </div>
      @endforeach
    </nav>
  </aside>

  <div class="admin-main">
    <header class="admin-topbar">
      <button class="btn btn--ghost btn--icon admin-menu-btn" type="button" data-nav-toggle aria-controls="admin-sidebar" aria-expanded="false" aria-label="منو"><svg aria-hidden="true"><use href="#i-menu"/></svg></button>
      <h1 class="admin-topbar__title">@yield('title')</h1>
      <div class="admin-topbar__actions">
        <a class="btn btn--ghost btn--sm hide-sm" href="{{ url('/') }}" target="_blank" rel="noopener">مشاهدهٔ سایت <svg aria-hidden="true"><use href="#i-external"/></svg></a>
        <button class="btn btn--ghost btn--icon" type="button" data-theme-toggle aria-pressed="false" aria-label="تغییر حالت نمایش"><svg aria-hidden="true"><use href="#i-moon"/></svg></button>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button class="btn btn--ghost btn--icon" type="submit" aria-label="خروج" title="خروج"><svg aria-hidden="true"><use href="#i-logout"/></svg></button>
        </form>
      </div>
    </header>

    <main class="admin-content" id="admin-main">
      @if (session('flash'))
        <div class="alert alert--success" role="status"><span>{{ session('flash') }}</span></div>
      @endif
      @if (session('flash_error'))
        <div class="alert alert--danger" role="alert"><span>{{ session('flash_error') }}</span></div>
      @endif
      @yield('content')
    </main>
  </div>
</div>
<script src="{{ \App\Support\Asset::url('js/app.js') }}" defer></script>
</body>
</html>
