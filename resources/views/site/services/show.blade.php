@extends('layouts.site')
@php
  $ctaUrl = $service->cta_type === 'external' && $service->cta_url ? $service->cta_url : \App\Support\Link::route('contact').'?subject='.rawurlencode($service->title);
@endphp

@section('content')
@include('site.partials.page-head', ['eyebrow' => $service->group?->title ?? 'خدمات', 'title' => $service->title, 'lead' => $service->summary, 'crumbs' => ['خدمات' => \App\Support\Link::route('services.index'), $service->title => null]])

<div class="container article-layout">
  <div class="article-layout__main">
    @if ($bodyHtml)<div class="prose">{!! $bodyHtml !!}</div>@endif
  </div>
  <aside class="article-layout__side">
    <div class="card stack stack--5">
      <ul class="price-list" role="list">@include('site.partials.price-row')</ul>
      @if ($service->price_note)<p class="text-sm muted">{{ $service->price_note }}</p>@endif
      <a class="btn btn--primary" href="{{ $ctaUrl }}" @if ($service->cta_type === 'external') rel="noopener" target="_blank" @endif>ثبت درخواست <svg aria-hidden="true"><use href="#i-arrow"/></svg></a>
    </div>
  </aside>
</div>
@endsection
