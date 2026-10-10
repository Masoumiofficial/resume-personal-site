@php /** @var \App\Models\Article $article */ $category = $article->categories->first(); @endphp
<article class="card card--interactive post-card">
  @if ($article->cover?->isImage())
    <div class="post-card__cover post-card__cover--media">
      @include('site.partials.image', ['media' => $article->cover, 'alt' => '', 'sizes' => '(max-width: 40rem) 100vw, (max-width: 72rem) 50vw, 24rem'])
    </div>
  @else
    <div class="post-card__cover" aria-hidden="true"></div>
  @endif
  <div class="post-card__meta">
    @if ($category)<span>{{ $category->name }}</span><span aria-hidden="true">·</span>@endif
    <time datetime="{{ $article->published_at->toDateString() }}">{{ \App\Support\JalaliDate::display($article->published_at) }}</time>
    @if ($article->reading_minutes)<span aria-hidden="true">·</span><span>{{ \App\Support\Jalali::digits($article->reading_minutes) }} دقیقه مطالعه</span>@endif
  </div>
  @php $h = 'h'.($headingLevel ?? 3); @endphp
  <{{ $h }} class="card__title"><a class="card__link" href="{{ $article->url() }}">{{ $article->title }}</a></{{ $h }}>
  @if ($article->excerpt && ($withExcerpt ?? false))<p class="muted text-sm">{{ $article->excerpt }}</p>@endif
</article>
