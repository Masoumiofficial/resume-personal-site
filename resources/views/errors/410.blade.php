@extends('layouts.site')
@php
  $seo = \App\Support\Seo::make('این صفحه حذف شده است');
  $seo->noindex = true;
@endphp

@section('content')
<section class="section">
  <div class="container container--narrow not-found">
    <p class="not-found__code tabular" aria-hidden="true">۴۱۰</p>
    <h1>این صفحه حذف شده است</h1>
    <p class="muted">این نشانی مربوط به نسخهٔ قبلی سایت بود و دیگر در دسترس نیست. شاید این پیوندها کمک کنند:</p>
    <ul class="chip-list" role="list">
      <li><a class="chip" href="{{ \App\Support\Link::route('home') }}">صفحهٔ اصلی</a></li>
      <li><a class="chip" href="{{ \App\Support\Link::route('blog.index') }}">مقالات</a></li>
      <li><a class="chip" href="{{ \App\Support\Link::route('services.index') }}">خدمات</a></li>
      <li><a class="chip" href="{{ \App\Support\Link::route('contact') }}">تماس</a></li>
    </ul>
  </div>
</section>
@endsection
