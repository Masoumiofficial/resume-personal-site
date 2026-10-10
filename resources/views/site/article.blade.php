@extends('layouts.site')
@use('App\Support\JalaliDate')
@use('App\Support\Jalali')
@php
  $category = $article->categories->first();
  // Body images: lazy + async decode (the Markdown renderer emits a bare <img>).
  $body = str_replace('<img ', '<img loading="lazy" decoding="async" ', (string) $article->body_html);
@endphp

@section('content')
<article class="article">
  @if ($preview ?? false)
    <div class="preview-banner" role="status">
      <div class="container container--narrow preview-banner__inner">
        <strong>پیش‌نمایش</strong>
        <span>{{ $article->status === 'published' && $article->published_at?->isPast() ? 'این مقاله منتشر شده است.' : 'این مقاله هنوز منتشر نشده و فقط شما آن را می‌بینید.' }}</span>
        <a href="{{ route('admin.articles.edit', $article->id) }}">بازگشت به ویرایش</a>
      </div>
    </div>
  @endif
  @include('site.partials.page-head', [
    'title' => $article->title,
    'crumbs' => array_filter(['مقالات' => \App\Support\Link::route('blog.index')] + ($category ? [$category->name => $category->url()] : [])) + [$article->title => null],
  ])
  <div class="container container--narrow article__body">
    <p class="article__meta">
      <span>{{ $profile->display_name }}</span>
      <span aria-hidden="true">·</span>
      <time datetime="{{ $article->published_at->toAtomString() }}">{{ JalaliDate::display($article->published_at) }}</time>
      @if ($modified)<span aria-hidden="true">·</span><span>به‌روزرسانی: <time datetime="{{ $modified->toAtomString() }}">{{ JalaliDate::display($modified) }}</time></span>@endif
      @if ($article->reading_minutes)<span aria-hidden="true">·</span><span>{{ Jalali::digits($article->reading_minutes) }} دقیقه مطالعه</span>@endif
    </p>

    @if ($article->cover?->isImage())
      <figure class="article-cover">
        @include('site.partials.image', ['media' => $article->cover, 'sizes' => '(max-width: 48rem) 100vw, 46rem', 'priority' => true])
      </figure>
    @endif

    <div class="prose">{!! $body !!}</div>

    @if ($article->categories->isNotEmpty() || $article->tags->isNotEmpty())
      <ul class="chip-list article__terms" role="list" aria-label="دسته‌ها و برچسب‌ها">
        @foreach ($article->categories as $c)<li><a class="chip" href="{{ $c->url() }}">{{ $c->name }}</a></li>@endforeach
        @foreach ($article->tags as $t)<li><a class="chip" href="{{ $t->url() }}">#{{ $t->name }}</a></li>@endforeach
      </ul>
    @endif

    <aside class="card author-box" aria-label="نویسنده">
      @if ($profile->avatar?->isImage())
        @include('site.partials.image', ['media' => $profile->avatar, 'alt' => '', 'sizes' => '4rem', 'class' => 'author-box__img'])
      @else
        <span class="monogram" aria-hidden="true">س‌م</span>
      @endif
      <div>
        <p class="author-box__name"><a href="{{ \App\Support\Link::route('about') }}">{{ $profile->display_name }}</a></p>
        @if ($profile->headline)<p class="muted text-sm">{{ $profile->headline }}</p>@endif
      </div>
    </aside>
  </div>
</article>

@if ($related->isNotEmpty())
<section class="section" aria-labelledby="related-title">
  <div class="container">
    <div class="section-head"><div><h2 id="related-title">مقاله‌های مرتبط</h2></div></div>
    <div class="grid grid--3">
      @foreach ($related as $item)@include('site.partials.post-card', ['article' => $item])@endforeach
    </div>
  </div>
</section>
@endif
@endsection
