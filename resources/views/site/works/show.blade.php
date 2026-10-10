@extends('layouts.site')

@section('content')
@include('site.partials.page-head', ['eyebrow' => 'نمونه‌کار', 'title' => $project->title, 'lead' => $project->summary, 'crumbs' => ['نمونه‌کارها' => \App\Support\Link::route('works.index'), $project->title => null]])

<div class="container article-layout">
  <article class="article-layout__main stack stack--6">
    @if ($project->cover?->isImage())
      <figure class="article-cover">
        @include('site.partials.image', ['media' => $project->cover, 'sizes' => '(max-width: 48rem) 100vw, 46rem', 'priority' => true])
      </figure>
    @endif
    @foreach ($sections as $heading => $html)
      <section class="prose"><h2>{{ $heading }}</h2>{!! $html !!}</section>
    @endforeach
    @if ($bodyHtml)<div class="prose">{!! $bodyHtml !!}</div>@endif
    @if ($outcomeHtml)
      <section class="card outcome">
        <h2 class="about__heading">نتیجه</h2>
        <div class="prose">{!! $outcomeHtml !!}</div>
        @if ($project->outcome_evidence_url)<a class="venture__link" href="{{ $project->outcome_evidence_url }}" rel="noopener" target="_blank">مستندات نتیجه <svg width="14" height="14" aria-hidden="true"><use href="#i-external"/></svg></a>@endif
      </section>
    @endif
    @if ($project->gallery->isNotEmpty())
      <section aria-label="تصاویر پروژه" class="gallery">
        @foreach ($project->gallery as $item)
          @if ($item->media?->isImage())
            <figure>
              @include('site.partials.image', ['media' => $item->media, 'sizes' => '(max-width: 48rem) 100vw, 46rem'])
              @if ($item->caption)<figcaption class="muted text-sm">{{ $item->caption }}</figcaption>@endif
            </figure>
          @endif
        @endforeach
      </section>
    @endif
  </article>
  <aside class="article-layout__side">
    <dl class="card meta-list">
      @if ($project->client_name)<div><dt>کارفرما</dt><dd>{{ $project->client_name }}</dd></div>@endif
      @if ($project->year)<div><dt>سال</dt><dd>{{ \App\Support\Jalali::digits($project->year) }}</dd></div>@endif
      @if ($project->technologies)<div><dt>فناوری‌ها</dt><dd><ul class="tag-list" role="list">@foreach ($project->technologies as $t)<li class="badge">{{ $t }}</li>@endforeach</ul></dd></div>@endif
      @if ($project->project_url)<div><dt>نشانی</dt><dd><a href="{{ $project->project_url }}" rel="noopener" target="_blank"><bdi dir="ltr">{{ parse_url($project->project_url, PHP_URL_HOST) }}</bdi></a></dd></div>@endif
    </dl>
  </aside>
</div>
@include('site.partials.cta')
@endsection
