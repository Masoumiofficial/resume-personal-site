@extends('layouts.site')
@use('App\Support\JalaliDate')
@use('App\Models\Skill')
@use('App\Models\Certification')

@section('content')
@include('site.partials.page-head', ['eyebrow' => 'درباره من', 'title' => $profile->display_name, 'lead' => $profile->headline, 'crumbs' => ['درباره من' => null]])

<div class="container about">
  <div class="about__main stack stack--6">
    @if ($bioHtml)
      <section aria-labelledby="bio-title">
        <h2 id="bio-title" class="visually-hidden">معرفی</h2>
        <div class="prose">{!! $bioHtml !!}</div>
      </section>
    @endif

    @if ($experiences->isNotEmpty())
      <section aria-labelledby="exp-title">
        <h2 id="exp-title" class="about__heading">سوابق کاری</h2>
        <ol class="timeline" role="list">
          @foreach ($experiences as $e)
            <li class="timeline__item">
              <div class="timeline__head">
                <h3 class="timeline__title">{{ $e->role }}</h3>
                @if ($e->start_date)<span class="timeline__date">{{ JalaliDate::range($e->start_date, $e->end_date, $e->date_precision ?? 'year', 'تاکنون') }}</span>@endif
              </div>
              <p class="timeline__org">
                @if ($e->organization_url)<a href="{{ $e->organization_url }}" rel="noopener" target="_blank">{{ $e->organization }}<span class="visually-hidden"> (در زبانهٔ جدید)</span></a>@else{{ $e->organization }}@endif
                @if ($e->employment_type && isset(\App\Models\Experience::EMPLOYMENT_TYPES[$e->employment_type]))<span class="badge">{{ \App\Models\Experience::EMPLOYMENT_TYPES[$e->employment_type] }}</span>@endif
              </p>
              @if ($e->summary)<p class="muted">{{ $e->summary }}</p>@endif
            </li>
          @endforeach
        </ol>
      </section>
    @endif

    @if ($educations->isNotEmpty())
      <section aria-labelledby="edu-title">
        <h2 id="edu-title" class="about__heading">تحصیلات</h2>
        <ol class="timeline" role="list">
          @foreach ($educations as $ed)
            <li class="timeline__item">
              <div class="timeline__head">
                <h3 class="timeline__title">{{ collect([$ed->degree, $ed->field])->filter()->implode(' — ') }}</h3>
                @if ($ed->start_date)<span class="timeline__date">{{ JalaliDate::range($ed->start_date, $ed->end_date, $ed->date_precision ?? 'year', 'در حال تحصیل') }}</span>@endif
              </div>
              <p class="timeline__org">{{ $ed->institution }}</p>
            </li>
          @endforeach
        </ol>
      </section>
    @endif
  </div>

  <aside class="about__side stack stack--6" aria-label="مهارت‌ها و دوره‌ها">
    @if ($skillGroups->isNotEmpty())
      <section class="card" aria-labelledby="skills-title">
        <h2 id="skills-title" class="about__heading">مهارت‌ها</h2>
        @foreach ($skillGroups as $group => $skills)
          <h3 class="about__subheading">{{ Skill::GROUPS[$group] ?? $group }}</h3>
          <ul class="tag-list" role="list">
            @foreach ($skills as $skill)<li class="badge">{{ $skill->name }}@if ($skill->level_label) <span class="muted">· {{ $skill->level_label }}</span>@endif</li>@endforeach
          </ul>
        @endforeach
        @if ($languages->isNotEmpty())
          <h3 class="about__subheading">زبان‌ها</h3>
          <ul class="tag-list" role="list">
            @foreach ($languages as $lang)<li class="badge">{{ $lang->name }}@if ($lang->level_label) <span class="muted">· {{ $lang->level_label }}</span>@endif</li>@endforeach
          </ul>
        @endif
      </section>
    @endif

    @if ($certifications->isNotEmpty())
      <section class="card" aria-labelledby="cert-title">
        <h2 id="cert-title" class="about__heading">دوره‌ها و گواهی‌ها</h2>
        <ul class="cert-list" role="list">
          @foreach ($certifications as $c)
            <li>
              <strong>@if ($c->credential_url)<a href="{{ $c->credential_url }}" rel="noopener" target="_blank">{{ $c->title }}</a>@else{{ $c->title }}@endif</strong>
              <span class="muted text-sm">{{ collect([$c->issuer, JalaliDate::range($c->issued_at, $c->end_at, $c->date_precision ?? 'month', 'در حال برگزاری')])->filter()->implode(' · ') }}</span>
            </li>
          @endforeach
        </ul>
      </section>
    @endif

    <a class="btn" href="{{ \App\Support\Link::route('resume') }}"><svg aria-hidden="true"><use href="#i-file"/></svg> نسخهٔ قابل چاپ رزومه</a>
  </aside>
</div>

@include('site.partials.cta')
@endsection
