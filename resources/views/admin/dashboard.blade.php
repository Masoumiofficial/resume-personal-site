@php use App\Support\Jalali; @endphp
@extends('layouts.admin')
@section('title', 'پیشخوان')

@section('content')
  @foreach ($alerts as $alert)
    <div class="alert alert--{{ $alert['type'] }}" role="status">
      <svg aria-hidden="true"><use href="#i-alert"/></svg>
      <div><strong>{{ $alert['text'] }}</strong>@if ($alert['link']) <a href="{{ $alert['link'] }}">{{ $alert['link_text'] }}</a>@endif</div>
    </div>
  @endforeach

  <section class="stat-grid" aria-label="خلاصهٔ محتوا">
    <div class="card stat"><span class="stat__label">مقالات منتشرشده</span><span class="stat__value tabular" data-stat="articles_published">{{ Jalali::number($stats['articles_published']) }}</span><span class="stat__meta">{{ Jalali::number($stats['articles_draft']) }} پیش‌نویس</span></div>
    <div class="card stat"><span class="stat__label">نمونه‌کارهای منتشرشده</span><span class="stat__value tabular" data-stat="projects_published">{{ Jalali::number($stats['projects_published']) }}</span></div>
    <div class="card stat"><span class="stat__label">پیام‌های جدید</span><span class="stat__value tabular" data-stat="messages_new">{{ Jalali::number($stats['messages_new']) }}</span></div>
    <div class="card stat"><span class="stat__label">آخرین نسخهٔ پشتیبان</span><span class="stat__value text-lg">ثبت نشده</span><span class="stat__meta">پشتیبان‌گیری در فاز ۶ راه‌اندازی می‌شود</span></div>
  </section>

  <section class="card" aria-labelledby="activity">
    <div class="panel__head"><h2 id="activity">فعالیت‌های اخیر</h2></div>
    @if ($activity->isEmpty())
      <div class="empty py-6">
        <svg aria-hidden="true"><use href="#i-inbox"/></svg>
        <h3>هنوز فعالیتی ثبت نشده</h3>
      </div>
    @else
      <ul class="activity-list" role="list">
        @foreach ($activity as $item)
          <li><span>{{ __('activity.'.$item->event) }}</span><time class="tabular text-muted" datetime="{{ $item->created_at->toIso8601String() }}">{{ Jalali::format($item->created_at, 'Y/m/d H:i') }}</time></li>
        @endforeach
      </ul>
    @endif
  </section>
@endsection
