<!doctype html>
<html lang="fa" dir="rtl">
<head>
  @include('partials.admin-head')
  <title>@yield('title') | پنل مدیریت</title>
</head>
<body>
<main class="auth" id="main">
  <div class="auth__card card">
    <div class="auth__brand"><span class="monogram" aria-hidden="true">س‌م</span><span>پنل مدیریت</span></div>
    <h1 class="auth__title">@yield('title')</h1>
    @hasSection('lead')<p class="auth__lead">@yield('lead')</p>@endif

    @if (session('status'))
      <div class="alert alert--success" role="status"><span>{{ session('status') }}</span></div>
    @endif

    @yield('content')
  </div>
</main>
<script src="{{ \App\Support\Asset::url('js/app.js') }}" defer></script>
</body>
</html>
