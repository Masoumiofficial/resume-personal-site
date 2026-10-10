@php
  $phone = $profile->phone && $profile->show_phone ? $profile->phone : null;
  // 0991… → +98991… for the tel: link; display keeps the local grouping.
  $tel = $phone ? '+98'.ltrim(preg_replace('/\D+/', '', \App\Support\Jalali::asciiDigits($phone)), '0') : null;
@endphp
<ul class="contact-lines" role="list">
  @if ($profile->email)<li><a href="mailto:{{ $profile->email }}"><svg aria-hidden="true"><use href="#i-mail"/></svg><bdi dir="ltr">{{ $profile->email }}</bdi></a></li>@endif
  @if ($phone)<li><a href="tel:{{ $tel }}"><svg aria-hidden="true"><use href="#i-phone"/></svg><bdi dir="ltr">{{ \App\Support\Jalali::digits($phone) }}</bdi></a></li>@endif
  @if ($profile->location && $profile->show_location)<li><span class="contact-lines__item"><svg aria-hidden="true"><use href="#i-pin"/></svg>{{ $profile->location }}</span></li>@endif
</ul>
