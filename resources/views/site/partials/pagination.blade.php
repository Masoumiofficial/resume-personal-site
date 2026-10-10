@if (count($pageUrls) > 1)
<nav class="pager" aria-label="صفحه‌بندی">
  @if ($prevUrl)<a class="btn btn--sm" href="{{ $prevUrl }}" rel="prev">→ قبلی</a>@endif
  <ul class="pager__pages" role="list">
    @foreach ($pageUrls as $n => $url)
      <li>
        @if ($n === $page)
          <span class="pager__page" aria-current="page">{{ \App\Support\Jalali::digits($n) }}</span>
        @else
          <a class="pager__page" href="{{ $url }}">{{ \App\Support\Jalali::digits($n) }}</a>
        @endif
      </li>
    @endforeach
  </ul>
  @if ($nextUrl)<a class="btn btn--sm" href="{{ $nextUrl }}" rel="next">بعدی ←</a>@endif
</nav>
@endif
