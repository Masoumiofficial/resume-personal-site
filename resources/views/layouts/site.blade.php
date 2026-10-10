@use('App\Support\Link')
@use('App\Support\Asset')
@php
  $seo ??= \App\Support\Seo::make(trim($__env->yieldContent('title')) ?: null);
  $siteTitle = \App\Models\Setting::get('site_title');
  $nav = [
    ['home', 'home', 'خانه'],
    ['about', 'about', 'درباره'],
    ['works.index', 'works.*', 'نمونه‌کارها'],
    ['services.index', 'services.*', 'خدمات'],
    ['blog.index', 'blog.*', 'مقالات'],
    ['contact', 'contact', 'تماس'],
  ];
@endphp
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
@include('partials.seo')
<meta name="theme-color" content="#F8F9FB" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#0A0D14" media="(prefers-color-scheme: dark)">
@include('partials.theme-script')
{{-- Same URL as the @font-face src in tokens.css (no ?v=), otherwise the preload is wasted. --}}
<link rel="preload" href="{{ asset('assets/fonts/Vazirmatn-wght.woff2') }}" as="font" type="font/woff2" crossorigin>
@foreach (['tokens', 'base', 'components', 'site'] as $sheet)
<link rel="stylesheet" href="{{ Asset::url("css/{$sheet}.css") }}">
@endforeach
<link rel="alternate" type="application/rss+xml" title="{{ $siteTitle }}" href="{{ Link::route('feed') }}">
@stack('head')
</head>
<body>
<a class="skip-link" href="#main">پرش به محتوای اصلی</a>
@include('partials.icons')

<header class="site-header">
  <div class="container site-header__inner">
    <a class="brand" href="{{ Link::route('home') }}" @if (request()->routeIs('home')) aria-current="page" @endif>
      <span class="monogram" aria-hidden="true">س‌م</span>
      <span>{{ $siteTitle }}</span>
    </a>
    <nav class="site-nav" id="site-nav" aria-label="منوی اصلی">
      <ul class="site-nav__list" role="list">
        @foreach ($nav as [$route, $pattern, $label])
          <li><a href="{{ Link::route($route) }}" @if (request()->routeIs($pattern)) aria-current="page" @endif>{{ $label }}</a></li>
        @endforeach
      </ul>
    </nav>
    <div class="header-actions">
      <button class="btn btn--ghost btn--icon" type="button" data-theme-toggle aria-pressed="false" aria-label="تغییر حالت تیره و روشن">
        <svg aria-hidden="true"><use href="#i-moon"/></svg>
      </button>
      <button class="btn btn--ghost btn--icon nav-toggle" type="button" data-nav-toggle aria-controls="site-nav" aria-expanded="false" aria-label="منو">
        <svg aria-hidden="true"><use href="#i-menu"/></svg>
      </button>
      <a class="btn btn--primary btn--sm" href="{{ Link::route('contact') }}">شروع همکاری</a>
    </div>
  </div>
</header>

<main id="main" tabindex="-1">
@yield('content')
</main>

<footer class="site-footer">
  <div class="container site-footer__inner">
    <p>© {{ \App\Support\Jalali::format(now(), 'Y') }} {{ $siteTitle }}. @if ($note = \App\Models\Setting::get('footer_note')){{ $note }}@endif</p>
    <nav aria-label="پیوندهای پایانی">
      <ul role="list" class="cluster">
        <li><a href="{{ Link::route('resume') }}">رزومه</a></li>
        <li><a href="{{ Link::route('blog.index') }}">مقالات</a></li>
        <li><a href="{{ Link::route('services.index') }}">خدمات</a></li>
        <li><a href="{{ Link::route('contact') }}">تماس</a></li>
        <li><a href="{{ Link::route('feed') }}">خوراک RSS</a></li>
      </ul>
    </nav>
  </div>
</footer>
<script src="{{ Asset::url('js/app.js') }}" defer></script>
</body>
</html>
