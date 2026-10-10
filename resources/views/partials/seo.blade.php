@php /** @var \App\Support\Seo $seo */ @endphp
<title>{{ $seo->fullTitle() }}</title>
@if ($description = $seo->metaDescription())
<meta name="description" content="{{ $description }}">
@endif
<meta name="robots" content="{{ $seo->robots() }}">
@if ($seo->canonical)
<link rel="canonical" href="{{ $seo->canonical }}">
@endif
<meta property="og:locale" content="fa_IR">
<meta property="og:type" content="{{ $seo->type }}">
<meta property="og:site_name" content="{{ \App\Models\Setting::get('site_title') }}">
<meta property="og:title" content="{{ $seo->title ?: \App\Models\Setting::get('site_title') }}">
@if ($description)
<meta property="og:description" content="{{ $description }}">
@endif
@if ($seo->canonical)
<meta property="og:url" content="{{ $seo->canonical }}">
@endif
@if ($seo->image)
<meta property="og:image" content="{{ $seo->image }}">
<meta name="twitter:card" content="summary_large_image">
@else
<meta name="twitter:card" content="summary">
@endif
@if ($seo->publishedTime)
<meta property="article:published_time" content="{{ $seo->publishedTime }}">
<meta property="article:modified_time" content="{{ $seo->modifiedTime }}">
@endif
@foreach ($seo->schema as $schema)
<script type="application/ld+json" nonce="{{ $cspNonce ?? '' }}">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endforeach
