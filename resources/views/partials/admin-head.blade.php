<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="referrer" content="strict-origin-when-cross-origin">
@include('partials.theme-script')
{{-- Same URL as the @font-face src in tokens.css (no ?v=), otherwise the preload is wasted and the font downloads twice. --}}
<link rel="preload" href="{{ asset('assets/fonts/Vazirmatn-wght.woff2') }}" as="font" type="font/woff2" crossorigin>
@foreach (['tokens', 'base', 'components', 'site', 'admin'] as $sheet)
<link rel="stylesheet" href="{{ \App\Support\Asset::url("css/{$sheet}.css") }}">
@endforeach
