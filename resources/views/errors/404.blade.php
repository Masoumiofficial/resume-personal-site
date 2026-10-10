@extends('layouts.site')
@php
  $seo = \App\Support\Seo::make('صفحه پیدا نشد');
  $seo->noindex = true;
@endphp

@section('content')
<section class="section">
  <div class="container container--narrow not-found">
    <p class="not-found__code tabular" aria-hidden="true">۴۰۴</p>
    <h1>صفحه پیدا نشد</h1>
    <p class="muted">این صفحه وجود ندارد یا جابه‌جا شده است. شاید این پیوندها کمک کنند:</p>
    <ul class="chip-list" role="list">
      <li><a class="chip" href="{{ \App\Support\Link::route('home') }}">صفحهٔ اصلی</a></li>
      <li><a class="chip" href="{{ \App\Support\Link::route('blog.index') }}">مقالات</a></li>
      <li><a class="chip" href="{{ \App\Support\Link::route('services.index') }}">خدمات</a></li>
      <li><a class="chip" href="{{ \App\Support\Link::route('contact') }}">تماس</a></li>
    </ul>
  </div>
</section>
@endsection
