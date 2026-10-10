@php
  /** @var \App\Models\Service $service */
  $unit = ['monthly' => 'تومان / ماه', 'hourly' => 'تومان / ساعت'][$service->price_unit] ?? 'تومان';
@endphp
<li class="price-row">
  <span>@if ($service->url())<a href="{{ $service->url() }}">{{ $service->title }}</a>@else{{ $service->title }}@endif</span>
  @if ($service->price_from !== null)
    <span class="price-row__value">
      @if ($service->price_original && $service->price_original > $service->price_from)<s class="price-row__old"><span class="visually-hidden">قیمت قبلی </span>{{ \App\Support\Jalali::number($service->price_original) }}</s>@endif
      از {{ \App\Support\Jalali::number($service->price_from) }} <span class="price-row__unit">{{ $unit }}</span>
    </span>
  @else
    <span class="price-row__value price-row__unit">استعلام قیمت</span>
  @endif
</li>
