@extends('layouts.site')
@php $icons = ['web-development' => 'i-code', 'data-ai' => 'i-chart']; @endphp

@section('content')
@include('site.partials.page-head', ['eyebrow' => 'خدمات و تعرفه', 'title' => 'قیمت شفاف، قبل از شروع', 'lead' => 'قیمت‌ها «شروع از» هستند؛ مبلغ نهایی پس از بررسی نیاز پروژه اعلام می‌شود.', 'crumbs' => ['خدمات' => null]])

<section class="section section--flush-top">
  <div class="container stack stack--6">
    @forelse ($groups as $group)
      <article class="card price-group price-group--wide" id="{{ $group->slug }}">
        <h2 class="price-group__title"><span class="icon-tile"><svg aria-hidden="true"><use href="#{{ $icons[$group->slug] ?? 'i-target' }}"/></svg></span>{{ $group->title }}</h2>
        @if ($group->summary)<p class="muted">{{ $group->summary }}</p>@endif
        <ul class="price-list" role="list">
          @foreach ($group->services as $service)@include('site.partials.price-row')@endforeach
        </ul>
      </article>
    @empty
      <div class="empty card"><p>فهرست خدمات به‌زودی منتشر می‌شود.</p></div>
    @endforelse
    <p class="text-sm muted">برای سفارش یا پرسش دربارهٔ هر خدمت، از <a href="{{ \App\Support\Link::route('contact') }}">فرم تماس</a> استفاده کنید.</p>
  </div>
</section>
@include('site.partials.cta')
@endsection
