@php /** @var \App\Models\Project $project */ @endphp
<article class="card card--interactive post-card">
  @if ($project->cover?->isImage())
    <div class="post-card__cover post-card__cover--media">
      @include('site.partials.image', ['media' => $project->cover, 'alt' => '', 'sizes' => '(max-width: 40rem) 100vw, (max-width: 72rem) 50vw, 24rem'])
    </div>
  @else
    <div class="post-card__cover" aria-hidden="true"></div>
  @endif
  @if ($project->categories->isNotEmpty())
    <div class="post-card__meta">@foreach ($project->categories as $c)<span class="badge">{{ $c->name }}</span>@endforeach</div>
  @endif
  @php $h = 'h'.($headingLevel ?? 3); @endphp
  <{{ $h }} class="card__title"><a class="card__link" href="{{ $project->url() }}">{{ $project->title }}</a></{{ $h }}>
  @if ($project->summary)<p class="muted text-sm">{{ $project->summary }}</p>@endif
</article>
