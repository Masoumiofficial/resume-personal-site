@extends('layouts.site')
@use('App\Support\Link')
@php
  $icons = ['web-development' => 'i-code', 'data-ai' => 'i-chart'];
  $since = collect($facts)->firstWhere('label', 'شروع فعالیت حرفه‌ای')['value'] ?? null;
  $city = $profile->show_location ? $profile->location : null;
@endphp

@section('content')
<section class="hero" aria-labelledby="hero-title">
  <div class="container hero__grid">
    <div>
      @if ($since || $city)
        <span class="badge badge--accent hero__eyebrow reveal"><span class="badge__dot" aria-hidden="true"></span>{{ collect([$since ? 'فعال از '.$since : null, $city])->filter()->implode(' · ') }}</span>
      @endif
      <h1 id="hero-title">{{ $profile->display_name }}</h1>
      @if ($profile->headline)<p class="hero__headline">{{ $profile->headline }}</p>@endif
      @if ($profile->short_bio)<p class="hero__intro">{{ $profile->short_bio }}</p>@endif
      <div class="cluster hero__actions reveal reveal--2">
        @if ($projects->isNotEmpty())
          <a class="btn btn--primary" href="{{ Link::route('works.index') }}">مشاهدهٔ نمونه‌کارها <svg aria-hidden="true"><use href="#i-arrow"/></svg></a>
        @else
          <a class="btn btn--primary" href="{{ Link::route('services.index') }}">خدمات و تعرفه <svg aria-hidden="true"><use href="#i-arrow"/></svg></a>
        @endif
        <a class="btn" href="{{ Link::route('contact') }}">تماس با من</a>
        <a class="btn btn--ghost" href="{{ Link::route('resume') }}"><svg aria-hidden="true"><use href="#i-file"/></svg> رزومه</a>
      </div>
    </div>
    <div class="hero__visual {{ $profile->avatar?->isImage() ? '' : 'hero__visual--placeholder' }}">
      <div class="portrait">
        @if ($profile->avatar?->isImage())
          @include('site.partials.image', ['media' => $profile->avatar, 'alt' => $profile->display_name, 'sizes' => '(max-width: 52rem) 15rem, 22rem', 'class' => 'portrait__img', 'priority' => true])
        @else
          <span class="monogram" aria-hidden="true">س‌م</span>
        @endif
      </div>
      @if ($profile->availability_note)
        <div class="portrait-card reveal reveal--3">
          <span class="status-dot" aria-hidden="true"></span>
          <span>{{ $profile->availability_note }}</span>
        </div>
      @endif
    </div>
  </div>
</section>

@if ($facts)
<section class="container" aria-label="خلاصه">
  <dl class="facts facts--{{ count($facts) }}">
    @foreach ($facts as $fact)
      <div class="fact"><dt class="fact__label">{{ $fact['label'] }}</dt><dd class="fact__value">{{ $fact['value'] }}</dd></div>
    @endforeach
  </dl>
</section>
@endif

@if ($groups->isNotEmpty())
<section class="section" aria-labelledby="exp-title">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="section-head__eyebrow">حوزه‌های تخصص</span>
        <h2 id="exp-title">از ایده تا رشد</h2>
        <p>ترکیب توسعهٔ وب، تحلیل داده و مدیریت پروژه؛ تا خروجی فقط یک سایت نباشد، بلکه بخشی از سیستم رشد کسب‌وکار باشد.</p>
      </div>
      <a class="btn" href="{{ Link::route('about') }}">بیشتر دربارهٔ من <svg aria-hidden="true"><use href="#i-arrow"/></svg></a>
    </div>
    <div class="grid grid--3">
      @foreach ($groups as $group)
        <article class="card expertise-card">
          <span class="icon-tile"><svg aria-hidden="true"><use href="#{{ $icons[$group->slug] ?? 'i-target' }}"/></svg></span>
          <h3>{{ $group->title }}</h3>
          @if ($group->summary)<p class="muted">{{ $group->summary }}</p>@endif
          <ul class="expertise-card__list">
            @foreach ($group->services->take(4) as $service)<li>{{ $service->title }}</li>@endforeach
          </ul>
        </article>
      @endforeach
    </div>
  </div>
</section>
@endif

@if ($ventures->isNotEmpty())
<section class="section section--subtle" aria-labelledby="ventures-title">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="section-head__eyebrow">برندهایی که ساخته‌ام</span>
        <h2 id="ventures-title">تجربهٔ ساختن، نه فقط مشاوره</h2>
      </div>
    </div>
    <div class="grid grid--3">
      @foreach ($ventures as $venture)
        <article class="card venture">
          <span class="monogram" aria-hidden="true">{{ mb_substr($venture->organization, 0, 1) }}</span>
          <div>
            <h3>{{ $venture->organization }}</h3>
            @if ($venture->summary)<p class="muted">{{ $venture->summary }}</p>@endif
            <div class="venture__meta">
              @if ($venture->role)<span class="badge">{{ $venture->role }}</span>@endif
              @if ($venture->start_date)<span class="badge">{{ \App\Support\JalaliDate::range($venture->start_date, $venture->end_date, 'year', 'تاکنون') }}</span>@endif
            </div>
            @if ($venture->organization_url)
              <a class="venture__link" href="{{ $venture->organization_url }}" rel="noopener" target="_blank"><bdi dir="ltr">{{ parse_url($venture->organization_url, PHP_URL_HOST) }}</bdi> <svg width="14" height="14" aria-hidden="true"><use href="#i-external"/></svg><span class="visually-hidden">(در زبانهٔ جدید)</span></a>
            @endif
          </div>
        </article>
      @endforeach
    </div>
  </div>
</section>
@endif

@if ($projects->isNotEmpty())
<section class="section" aria-labelledby="works-title">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="section-head__eyebrow">نمونه‌کارها</span>
        <h2 id="works-title">پروژه‌های منتخب</h2>
      </div>
      <a class="btn" href="{{ Link::route('works.index') }}">همهٔ نمونه‌کارها <svg aria-hidden="true"><use href="#i-arrow"/></svg></a>
    </div>
    <div class="grid grid--3">
      @foreach ($projects as $project)@include('site.partials.project-card')@endforeach
    </div>
  </div>
</section>
@endif

@if ($groups->isNotEmpty())
<section class="section {{ $ventures->isNotEmpty() && $projects->isEmpty() ? '' : 'section--subtle' }}" aria-labelledby="services-title">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="section-head__eyebrow">خدمات و تعرفه</span>
        <h2 id="services-title">قیمت شفاف، قبل از شروع</h2>
        <p>قیمت‌ها «شروع از» هستند؛ مبلغ نهایی پس از بررسی نیاز پروژه اعلام می‌شود.</p>
      </div>
      <a class="btn" href="{{ Link::route('services.index') }}">جزئیات همهٔ خدمات <svg aria-hidden="true"><use href="#i-arrow"/></svg></a>
    </div>
    <div class="grid grid--3">
      @foreach ($groups as $group)
        <article class="card price-group">
          <h3><span class="icon-tile"><svg aria-hidden="true"><use href="#{{ $icons[$group->slug] ?? 'i-target' }}"/></svg></span>{{ $group->title }}</h3>
          <ul class="price-list" role="list">
            @foreach ($group->services as $service)@include('site.partials.price-row')@endforeach
          </ul>
        </article>
      @endforeach
    </div>
  </div>
</section>
@endif

@if ($testimonials->isNotEmpty())
<section class="section" aria-labelledby="testimonials-title">
  <div class="container">
    <div class="section-head"><div><span class="section-head__eyebrow">نظر همکاران و مشتریان</span><h2 id="testimonials-title">تجربهٔ همکاری</h2></div></div>
    <div class="grid grid--3">
      @foreach ($testimonials as $t)
        <figure class="card testimonial">
          <blockquote class="testimonial__quote">{{ $t->quote }}</blockquote>
          <figcaption class="testimonial__author">
            <strong>{{ $t->author_name }}</strong>
            @if ($t->author_role || $t->company)<span class="muted text-sm">{{ collect([$t->author_role, $t->company])->filter()->implode('، ') }}</span>@endif
          </figcaption>
        </figure>
      @endforeach
    </div>
  </div>
</section>
@endif

@if ($articles->isNotEmpty())
<section class="section" aria-labelledby="blog-title">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="section-head__eyebrow">مقالات</span>
        <h2 id="blog-title">تازه‌ترین نوشته‌ها</h2>
      </div>
      <a class="btn" href="{{ Link::route('blog.index') }}">همهٔ مقالات <svg aria-hidden="true"><use href="#i-arrow"/></svg></a>
    </div>
    <div class="grid grid--3">
      @foreach ($articles as $article)@include('site.partials.post-card')@endforeach
    </div>
  </div>
</section>
@endif

@include('site.partials.cta')
@endsection
