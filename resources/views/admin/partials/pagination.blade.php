@if ($paginator->hasPages())
  <nav class="pagination" aria-label="صفحه‌بندی">
    @if ($paginator->onFirstPage())
      <span class="btn btn--sm" aria-disabled="true">قبلی</span>
    @else
      <a class="btn btn--sm" href="{{ $paginator->previousPageUrl() }}" rel="prev">قبلی</a>
    @endif
    <span class="text-sm text-muted">صفحهٔ {{ \App\Support\Jalali::digits($paginator->currentPage()) }} از {{ \App\Support\Jalali::digits($paginator->lastPage()) }}</span>
    @if ($paginator->hasMorePages())
      <a class="btn btn--sm" href="{{ $paginator->nextPageUrl() }}" rel="next">بعدی</a>
    @else
      <span class="btn btn--sm" aria-disabled="true">بعدی</span>
    @endif
  </nav>
@endif
