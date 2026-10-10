@php use App\Support\Jalali; @endphp
@extends('layouts.admin')
@section('title', $media->original_name)

@section('content')
  <p><a class="text-sm" href="{{ route('admin.media.index') }}">→ بازگشت به رسانه</a></p>

  <div class="form-layout">
    <div class="stack stack--6">
      <div class="card media-preview">
        @if ($media->isImage())
          <img src="{{ $media->urlFor(960) }}" alt="{{ $media->alt ?? '' }}" width="{{ $media->width }}" height="{{ $media->height }}" decoding="async">
        @else
          <a class="btn" href="{{ $media->url() }}" target="_blank" rel="noopener">مشاهدهٔ PDF <svg aria-hidden="true"><use href="#i-external"/></svg></a>
        @endif
      </div>

      <form class="card form-grid" method="POST" action="{{ route('admin.media.update', $media->id) }}" novalidate>
        @csrf @method('PUT')
        <div class="field form-grid__wide">
          <label class="field__label" for="alt">متن جایگزین</label>
          <input class="input" id="alt" name="alt" type="text" maxlength="191" value="{{ old('alt', $media->alt) }}" aria-describedby="alt-hint" @error('alt') aria-invalid="true" @enderror>
          <span class="field__hint" id="alt-hint">توصیف کوتاه تصویر برای کاربران صفحه‌خوان و موتورهای جست‌وجو.</span>
          @include('partials.field-error', ['name' => 'alt'])
        </div>
        <div class="field form-grid__wide">
          <label class="field__label" for="caption">توضیح</label>
          <textarea class="textarea" id="caption" name="caption" rows="3">{{ old('caption', $media->caption) }}</textarea>
          @include('partials.field-error', ['name' => 'caption'])
        </div>
        <div class="form-grid__actions"><button class="btn btn--primary" type="submit">ذخیره</button></div>
      </form>
    </div>

    <aside class="form-aside">
      <div class="card stack stack--5 text-sm">
        <div class="field">
          <label class="field__label" for="url">نشانی فایل</label>
          <input class="input input--ltr input--sm" id="url" type="text" readonly value="{{ $media->url() }}">
        </div>
        <p>نوع: <span class="ltr">{{ $media->mime }}</span><br>
           حجم: {{ Jalali::digits(number_format($media->size / 1024, 0)) }} کیلوبایت
           @if ($media->width)<br>ابعاد: <span class="ltr">{{ Jalali::digits($media->width.'×'.$media->height) }}</span>@endif
           @if ($media->variants)<br>نسخه‌های WebP: {{ Jalali::digits(count($media->variants)) }}@endif
           <br>بارگذاری: {{ Jalali::format($media->created_at, 'Y/m/d H:i') }}</p>
        <div>
          <p class="field__label">محل استفاده</p>
          @if ($usages)
            <ul class="text-sm">@foreach ($usages as $where => $count)<li><span class="ltr">{{ $where }}</span> ({{ Jalali::digits($count) }})</li>@endforeach</ul>
          @else
            <p class="text-muted">جایی استفاده نشده است.</p>
          @endif
        </div>
        <form method="POST" action="{{ route('admin.media.destroy', $media->id) }}" data-confirm="این فایل برای همیشه حذف شود؟">
          @csrf @method('DELETE')
          @if ($usages)
            <label class="checkbox mt-5"><input type="checkbox" name="confirm_in_use" value="1"><span>با وجود استفاده حذف شود</span></label>
          @endif
          <button class="btn btn--ghost btn--sm text-danger mt-5" type="submit"><svg aria-hidden="true"><use href="#i-trash"/></svg>حذف فایل</button>
        </form>
      </div>
    </aside>
  </div>
@endsection
