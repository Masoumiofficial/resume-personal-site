<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <title>@yield('title')</title>
  @include('partials.theme-script')
  <link rel="stylesheet" href="{{ \App\Support\Asset::url('css/tokens.css') }}">
  <link rel="stylesheet" href="{{ \App\Support\Asset::url('css/base.css') }}">
  <link rel="stylesheet" href="{{ \App\Support\Asset::url('css/components.css') }}">
  <link rel="stylesheet" href="{{ \App\Support\Asset::url('css/admin.css') }}">
</head>
<body>
<main class="auth" id="main">
  <div class="auth__card card">
    <p class="text-muted tabular">@yield('code')</p>
    <h1 class="auth__title">@yield('title')</h1>
    <p class="auth__lead">@yield('message')</p>
    <a class="btn" href="{{ url('/') }}">بازگشت به صفحهٔ اصلی</a>
  </div>
</main>
</body>
</html>
