@extends('layouts.site')
@php $crumbs = $heading === 'مقالات' ? ['مقالات' => null] : ['مقالات' => \App\Support\Link::route('blog.index'), $heading => null]; @endphp

@section('content')
@include('site.partials.page-head', ['eyebrow' => 'وبلاگ', 'title' => $heading, 'lead' => $intro, 'crumbs' => $crumbs])

<section class="section section--flush-top">
  <div class="container stack stack--6">
    @if ($categories->isNotEmpty())
      <nav aria-label="دسته‌بندی مقالات">
        <ul class="chip-list" role="list">
          <li><a class="chip" href="{{ \App\Support\Link::route('blog.index') }}" @if ($currentUrl === \App\Support\Link::route('blog.index')) aria-current="page" @endif>همه</a></li>
          @foreach ($categories as $category)
            <li><a class="chip" href="{{ $category->url() }}" @if ($currentUrl === $category->url()) aria-current="page" @endif>{{ $category->name }}</a></li>
          @endforeach
        </ul>
      </nav>
    @endif

    @if ($articles->isEmpty())
      <div class="empty card"><p>هنوز مقاله‌ای در این بخش منتشر نشده است.</p></div>
    @else
      <div class="grid grid--3">
        @foreach ($articles as $article)@include('site.partials.post-card', ['withExcerpt' => true, 'headingLevel' => 2])@endforeach
      </div>
    @endif

    @include('site.partials.pagination')
  </div>
</section>
@endsection

@push('head')
@if ($prevUrl)<link rel="prev" href="{{ $prevUrl }}">@endif
@if ($nextUrl)<link rel="next" href="{{ $nextUrl }}">@endif
@endpush
