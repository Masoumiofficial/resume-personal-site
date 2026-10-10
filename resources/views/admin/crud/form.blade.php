@extends('layouts.admin')
@section('title', $title)

@section('content')
  @include('admin.partials.tabs')

  @unless ($singleton)
    <p><a class="text-sm" href="{{ route($route.'.index') }}">→ بازگشت به {{ $labels['plural'] }}</a></p>
  @endunless

  @if ($errors->any())
    <div class="alert alert--danger" role="alert"><span>فرم خطا دارد؛ موارد مشخص‌شده را اصلاح کنید.</span></div>
  @endif

  <div class="form-layout">
    <form class="card form-grid" method="POST" novalidate
          action="{{ $singleton ? route($route.'.update') : ($item->exists ? route($route.'.update', $item->getKey()) : route($route.'.store')) }}">
      @csrf
      @if ($singleton || $item->exists) @method('PUT') @endif

      @foreach ($fields as $field)
        @include('admin.partials.field', ['field' => $field, 'value' => $values[$field['name']] ?? null])
      @endforeach

      <div class="form-grid__actions">
        <button class="btn btn--primary" type="submit">{{ $item->exists ? 'ذخیرهٔ تغییرات' : 'ذخیره' }}</button>
        @unless ($singleton)<a class="btn btn--ghost" href="{{ route($route.'.index') }}">انصراف</a>@endunless
      </div>
    </form>

    @if ($item->exists && ! $singleton)
      <aside class="form-aside">
        @if ($links)
          <div class="card cluster">@foreach ($links as [$label, $url])<a class="btn btn--ghost btn--sm" href="{{ $url }}" target="_blank" rel="noopener"><svg aria-hidden="true"><use href="#i-external"/></svg>{{ $label }}</a>@endforeach</div>
        @endif
        @if ($aside)
          <div class="card stack stack--5">@foreach ($aside as $line)<p class="text-sm">{{ $line }}</p>@endforeach</div>
        @endif
        <div class="card">
          <p class="text-sm text-muted">ایجاد: {{ \App\Support\Jalali::format($item->created_at, 'Y/m/d H:i') }}<br>آخرین ویرایش: {{ \App\Support\Jalali::format($item->updated_at, 'Y/m/d H:i') }}</p>
          <form class="mt-5" method="POST" action="{{ route($route.'.destroy', $item->getKey()) }}" data-confirm="«{{ $title }}» حذف شود؟">
            @csrf @method('DELETE')
            <button class="btn btn--ghost btn--sm text-danger" type="submit"><svg aria-hidden="true"><use href="#i-trash"/></svg>حذف</button>
          </form>
        </div>
      </aside>
    @endif
  </div>
@endsection
