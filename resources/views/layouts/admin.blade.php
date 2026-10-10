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
      {{-- Only sections that exist are listed; content modules are added in Phase 3. --}}
      <div class="admin-nav__group">
        <a href="{{ route('admin.dashboard') }}" @if (request()->routeIs('admin.dashboard')) aria-current="page" @endif><svg aria-hidden="true"><use href="#i-home"/></svg>پیشخوان</a>
      </div>
      <div class="admin-nav__group mt-5">
        <span class="admin-nav__label">سیستم</span>
        <a href="{{ route('admin.security') }}" @if (request()->routeIs('admin.security')) aria-current="page" @endif><svg aria-hidden="true"><use href="#i-shield"/></svg>امنیت حساب</a>
      </div>
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
      @yield('content')
    </main>
  </div>
</div>
<script src="{{ \App\Support\Asset::url('js/app.js') }}" defer></script>
</body>
</html>
