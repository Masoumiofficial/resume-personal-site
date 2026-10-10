@php use App\Support\Jalali; @endphp
@extends('layouts.admin')
@section('title', 'رسانه')

@section('content')
  <section class="card" aria-labelledby="upload-heading">
    <div class="panel__head"><h2 id="upload-heading">بارگذاری فایل</h2></div>
    <form class="form-grid" method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" novalidate>
      @csrf
      <div class="field">
        <label class="field__label" for="files">فایل‌ها<span class="required-mark" aria-hidden="true">*</span></label>
        <input class="input" id="files" name="files[]" type="file" multiple required accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf"
               aria-describedby="files-hint @if ($errors->has('files') || $errors->has('files.*')) files-error @endif">
        <span class="field__hint" id="files-hint">JPEG، PNG، WebP یا PDF؛ هر فایل حداکثر {{ Jalali::digits($maxMb) }} مگابایت، حداکثر ۱۰ فایل. تصاویر بازسازی می‌شوند و اطلاعات EXIF/موقعیت حذف می‌شود.</span>
        @if ($errors->has('files') || $errors->has('files.*'))<span class="field__error" id="files-error">{{ $errors->first('files') ?: $errors->first('files.*') }}</span>@endif
      </div>
      <div class="field">
        <label class="field__label" for="alt">متن جایگزین (اختیاری)</label>
        <input class="input" id="alt" name="alt" type="text" maxlength="191" value="{{ old('alt') }}" aria-describedby="alt-hint">
        <span class="field__hint" id="alt-hint">برای همهٔ فایل‌های این بارگذاری؛ بعداً برای هر فایل قابل ویرایش است.</span>
      </div>
      <div class="form-grid__actions"><button class="btn btn--primary" type="submit"><svg aria-hidden="true"><use href="#i-upload"/></svg>بارگذاری</button></div>
    </form>
  </section>

  <div class="toolbar">
    <form class="toolbar__filters" method="GET" action="{{ route('admin.media.index') }}" role="search">
      <label class="visually-hidden" for="q">جست‌وجو</label>
      <input class="input input--sm" id="q" name="q" type="search" value="{{ $search }}" placeholder="جست‌وجو در نام یا متن جایگزین…">
      <label class="visually-hidden" for="type">نوع</label>
      <select class="input input--sm" id="type" name="type">
        <option value="">همهٔ انواع</option>
        <option value="image" @selected($type === 'image')>تصویر</option>
        <option value="document" @selected($type === 'document')>PDF</option>
      </select>
      <label class="checkbox"><input type="checkbox" name="unused" value="1" @checked($unused)><span>فقط فایل‌های بی‌استفاده</span></label>
      <button class="btn btn--sm" type="submit"><svg aria-hidden="true"><use href="#i-search"/></svg>اعمال</button>
    </form>
  </div>

  @if ($items->isEmpty())
    <div class="card empty py-6"><svg aria-hidden="true"><use href="#i-image"/></svg><h2 class="text-lg">فایلی پیدا نشد</h2></div>
  @else
    <ul class="media-grid" role="list">
      @foreach ($items as $media)
        <li class="media-card">
          <a class="media-card__link" href="{{ route('admin.media.edit', $media->id) }}">
            @if ($media->isImage())
              <img class="media-card__thumb" src="{{ $media->urlFor(480) }}" alt="{{ $media->alt ?? '' }}" loading="lazy" decoding="async" width="{{ $media->width }}" height="{{ $media->height }}">
            @else
              <span class="media-card__thumb media-card__thumb--doc" aria-hidden="true"><svg><use href="#i-file"/></svg>PDF</span>
            @endif
            <span class="media-card__name">{{ $media->original_name }}</span>
            <span class="media-card__meta">
              {{ Jalali::digits(number_format($media->size / 1024, 0)) }} کیلوبایت
              @if ($media->width) · <span class="ltr">{{ Jalali::digits($media->width.'×'.$media->height) }}</span>@endif
              @unless ($media->alt) · <span class="text-danger">بدون متن جایگزین</span>@endunless
            </span>
          </a>
        </li>
      @endforeach
    </ul>
    {{ $items->links('admin.partials.pagination') }}
  @endif
@endsection
