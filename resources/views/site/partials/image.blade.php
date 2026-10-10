{{--
  Responsive image from the media library: WebP variants (480/960/1600) in srcset,
  intrinsic width/height to prevent layout shift (CLS). Above-the-fold images pass
  priority=true: eager + fetchpriority=high, never lazy (LCP).
  Params: $media, $sizes, $class (optional), $priority (optional), $alt (optional override)
--}}
@php
  $variants = collect($media->variants ?? [])->sortBy('width');
  $disk = \Illuminate\Support\Facades\Storage::disk($media->disk);
  $srcset = $variants->map(fn ($v) => $disk->url($v['path']).' '.$v['width'].'w')->implode(', ');
  $priority ??= false;
@endphp
<img src="{{ $media->urlFor(960) }}"
  @if ($srcset !== '') srcset="{{ $srcset }}" sizes="{{ $sizes }}" @endif
  @if ($media->width && $media->height) width="{{ $media->width }}" height="{{ $media->height }}" @endif
  alt="{{ $alt ?? ($media->alt ?? '') }}"
  @isset($class) class="{{ $class }}" @endisset
  @if ($priority) fetchpriority="high" decoding="async" @else loading="lazy" decoding="async" @endif>
