@php use App\Support\JalaliDate; use App\Models\Skill; use App\Models\Certification; use App\Models\Experience; @endphp
<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>رزومهٔ {{ $profile->display_name }}</title>
  <link rel="preload" href="{{ asset('assets/fonts/Vazirmatn-wght.woff2') }}" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="{{ \App\Support\Asset::url('css/tokens.css') }}">
  <link rel="stylesheet" href="{{ \App\Support\Asset::url('css/resume.css') }}">
</head>
<body class="resume-page">
  @if ($preview ?? false)
    <div class="resume-toolbar no-print" role="region" aria-label="ابزار پیش‌نمایش">
      <a href="{{ route('admin.dashboard') }}">→ پنل مدیریت</a>
      <span>پیش‌نمایش رزومه؛ فقط موارد «نمایش» دیده می‌شوند.</span>
      <button type="button" data-print>چاپ / ذخیره به PDF</button>
    </div>
  @endif

  <main class="resume">
    <header class="resume__header">
      <h1>{{ $profile->display_name }}</h1>
      @if ($profile->headline)<p class="resume__headline">{{ $profile->headline }}</p>@endif
      <ul class="resume__contact" role="list">
        @if ($profile->email)<li><span class="ltr">{{ $profile->email }}</span></li>@endif
        @if ($profile->phone && $profile->show_phone)<li><span class="ltr">{{ $profile->phone }}</span></li>@endif
        @if ($profile->location && $profile->show_location)<li>{{ $profile->location }}</li>@endif
        <li><span class="ltr">{{ preg_replace('#^https?://#', '', rtrim(config('app.url'), '/')) }}</span></li>
      </ul>
    </header>

    @if ($profile->resume_summary || $profile->short_bio)
      <section class="resume__section"><h2>خلاصه</h2><p>{{ $profile->resume_summary ?: $profile->short_bio }}</p></section>
    @endif

    @if ($experiences->isNotEmpty())
      <section class="resume__section">
        <h2>سوابق کاری</h2>
        @foreach ($experiences as $e)
          <article class="resume__item">
            <div class="resume__item-head">
              <h3>{{ $e->role }} — {{ $e->organization }}</h3>
              <span class="resume__date">{{ JalaliDate::range($e->start_date, $e->end_date, $e->date_precision) }}</span>
            </div>
            @if ($e->summary)<p>{{ $e->summary }}</p>@endif
            @if ($e->highlights)<ul>@foreach ($e->highlights as $h)<li>{{ $h }}</li>@endforeach</ul>@endif
          </article>
        @endforeach
      </section>
    @endif

    @if ($educations->isNotEmpty())
      <section class="resume__section">
        <h2>تحصیلات</h2>
        @foreach ($educations as $ed)
          <article class="resume__item">
            <div class="resume__item-head">
              <h3>{{ trim($ed->degree.' '.$ed->field) ?: $ed->institution }}@if ($ed->degree || $ed->field) — {{ $ed->institution }}@endif</h3>
              <span class="resume__date">{{ JalaliDate::range($ed->start_date, $ed->end_date, $ed->date_precision, 'در حال تحصیل') }}</span>
            </div>
            @if ($ed->description)<p>{{ $ed->description }}</p>@endif
          </article>
        @endforeach
      </section>
    @endif

    @if ($skillGroups->isNotEmpty())
      <section class="resume__section">
        <h2>مهارت‌ها</h2>
        <dl class="resume__skills">
          @foreach ($skillGroups as $group => $items)
            <dt>{{ Skill::GROUPS[$group] ?? $group }}</dt>
            <dd>{{ $items->map(fn ($s) => $s->name.($s->level_label ? ' ('.$s->level_label.')' : ''))->implode('، ') }}</dd>
          @endforeach
        </dl>
      </section>
    @endif

    @if ($certifications->isNotEmpty())
      <section class="resume__section">
        <h2>دوره‌ها و گواهی‌ها</h2>
        <ul class="resume__list">
          @foreach ($certifications as $c)
            <li><strong>{{ $c->title }}</strong>@if ($c->issuer) — {{ $c->issuer }}@endif <span class="resume__date">{{ JalaliDate::range($c->issued_at, $c->end_at, $c->date_precision, 'در جریان') }}</span></li>
          @endforeach
        </ul>
      </section>
    @endif

    @if ($languages->isNotEmpty())
      <section class="resume__section">
        <h2>زبان‌ها</h2>
        <p>{{ $languages->map(fn ($s) => $s->name.($s->level_label ? ' ('.$s->level_label.')' : ''))->implode('، ') }}</p>
      </section>
    @endif
  </main>
  <script src="{{ \App\Support\Asset::url('js/app.js') }}" defer></script>
</body>
</html>
