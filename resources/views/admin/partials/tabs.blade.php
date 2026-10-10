@if (! empty($tabs))
  <nav class="tabs" aria-label="بخش‌ها">
    @foreach ($tabs as [$tabRoute, $tabLabel])
      <a class="tabs__link" href="{{ route($tabRoute.'.index') }}" @if (request()->routeIs($tabRoute.'.*')) aria-current="page" @endif>{{ $tabLabel }}</a>
    @endforeach
  </nav>
@endif
