{{-- Params: $eyebrow (optional), $title, $lead (optional), $crumbs (optional: [label => url|null]) --}}
<header class="page-head">
  <div class="container">
    @if (! empty($crumbs))
      <nav class="breadcrumbs" aria-label="مسیر صفحه">
        <ol role="list">
          <li><a href="{{ \App\Support\Link::route('home') }}">خانه</a></li>
          @foreach ($crumbs as $label => $url)
            <li>@if ($url)<a href="{{ $url }}">{{ $label }}</a>@else<span aria-current="page">{{ $label }}</span>@endif</li>
          @endforeach
        </ol>
      </nav>
    @endif
    @isset($eyebrow)<span class="section-head__eyebrow">{{ $eyebrow }}</span>@endisset
    <h1 class="page-head__title">{{ $title }}</h1>
    @if (! empty($lead))<p class="page-head__lead">{{ $lead }}</p>@endif
  </div>
</header>
