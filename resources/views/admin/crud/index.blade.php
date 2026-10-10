@extends('layouts.admin')
@section('title', $labels['plural'])

@section('content')
  @include('admin.partials.tabs')

  <div class="toolbar">
    @if ($searchable || $filters)
      <form class="toolbar__filters" method="GET" action="{{ route($route.'.index') }}" role="search">
        @if ($trashed)<input type="hidden" name="trashed" value="1">@endif
        @if ($searchable)
          <label class="visually-hidden" for="q">جست‌وجو</label>
          <input class="input input--sm" id="q" name="q" type="search" value="{{ $search }}" placeholder="جست‌وجو…">
        @endif
        @foreach ($filters as $column => $filter)
          <label class="visually-hidden" for="f-{{ $column }}">{{ $filter['label'] }}</label>
          <select class="input input--sm" id="f-{{ $column }}" name="{{ $column }}">
            <option value="">{{ $filter['label'] }}: همه</option>
            @foreach ($filter['options'] as $value => $text)
              <option value="{{ $value }}" @selected(($activeFilters[$column] ?? null) === (string) $value)>{{ $text }}</option>
            @endforeach
          </select>
        @endforeach
        <button class="btn btn--sm" type="submit"><svg aria-hidden="true"><use href="#i-search"/></svg>اعمال</button>
        @if ($search !== '' || $activeFilters)<a class="btn btn--ghost btn--sm" href="{{ route($route.'.index', $trashed ? ['trashed' => 1] : []) }}">پاک کردن</a>@endif
      </form>
    @endif
    <div class="toolbar__actions">
      @if ($softDeletes)
        <a class="btn btn--ghost btn--sm" href="{{ route($route.'.index', $trashed ? [] : ['trashed' => 1]) }}">{{ $trashed ? 'بازگشت به فهرست' : 'سطل زباله' }}</a>
      @endif
      @unless ($trashed)
        <a class="btn btn--primary btn--sm" href="{{ route($route.'.create') }}"><svg aria-hidden="true"><use href="#i-plus"/></svg>{{ $labels['create'] }}</a>
      @endunless
    </div>
  </div>

  @if ($items->isEmpty())
    <div class="card empty py-6">
      <svg aria-hidden="true"><use href="#i-inbox"/></svg>
      <h2 class="text-lg">{{ $trashed ? 'سطل زباله خالی است' : ($search !== '' || $activeFilters ? 'موردی با این شرایط پیدا نشد' : 'هنوز موردی ثبت نشده') }}</h2>
    </div>
  @else
    <div class="table-wrap">
      <table class="table">
        <caption class="visually-hidden">{{ $labels['plural'] }}</caption>
        <thead>
          <tr>
            @foreach ($columns as $column)<th scope="col">{{ $column['label'] }}</th>@endforeach
            <th scope="col"><span class="visually-hidden">عملیات</span></th>
          </tr>
        </thead>
        <tbody>
          @foreach ($items as $item)
            <tr>
              @foreach ($columns as $column)
                @php $value = $column['value']($item); @endphp
                @if ($column['primary'] ?? false)
                  <th scope="row" class="table__primary">
                    @if ($column['ltr'] ?? false)@php $value = new \Illuminate\Support\HtmlString('<bdi dir="ltr">'.e($value).'</bdi>'); @endphp@endif
                    @if ($trashed){{ $value }}@else<a href="{{ route($route.'.edit', $item->getKey()) }}">{{ $value }}</a>@endif
                  </th>
                @elseif ($column['badge'] ?? false)
                  <td>@if ($value)<span class="badge badge--{{ $value[0] }}">{{ $value[1] }}</span>@endif</td>
                @else
                  <td @class(['tabular' => $column['ltr'] ?? false, 'text-muted' => $column['muted'] ?? false])>@if ($column['ltr'] ?? false)<bdi dir="ltr">{{ $value }}</bdi>@else{{ $value }}@endif</td>
                @endif
              @endforeach
              <td>
                <div class="row-actions">
                  @if ($trashed)
                    <form method="POST" action="{{ route($route.'.restore', $item->getKey()) }}">@csrf<button class="btn btn--ghost btn--sm" type="submit"><svg aria-hidden="true"><use href="#i-undo"/></svg>بازیابی</button></form>
                    <form method="POST" action="{{ route($route.'.force', $item->getKey()) }}" data-confirm="«{{ $titleOf($item) }}» برای همیشه حذف شود؟ این کار برگشت‌پذیر نیست.">@csrf @method('DELETE')<button class="btn btn--ghost btn--sm text-danger" type="submit">حذف دائمی</button></form>
                  @else
                    @if ($sortable)
                      <form method="POST" action="{{ route($route.'.move', [$item->getKey(), 'up']) }}">@csrf<button class="btn btn--ghost btn--icon btn--sm" type="submit" aria-label="انتقال «{{ $titleOf($item) }}» به بالا" title="بالا"><svg aria-hidden="true"><use href="#i-up"/></svg></button></form>
                      <form method="POST" action="{{ route($route.'.move', [$item->getKey(), 'down']) }}">@csrf<button class="btn btn--ghost btn--icon btn--sm" type="submit" aria-label="انتقال «{{ $titleOf($item) }}» به پایین" title="پایین"><svg aria-hidden="true"><use href="#i-down"/></svg></button></form>
                    @endif
                    <a class="btn btn--ghost btn--icon btn--sm" href="{{ route($route.'.edit', $item->getKey()) }}" aria-label="ویرایش «{{ $titleOf($item) }}»" title="ویرایش"><svg aria-hidden="true"><use href="#i-edit"/></svg></a>
                  @endif
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    {{ $items->links('admin.partials.pagination') }}
  @endif
@endsection
