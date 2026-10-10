@php use App\Support\Jalali; @endphp
@extends('layouts.admin')
@section('title', 'گزارش فعالیت')

@section('content')
  @if ($items->isEmpty())
    <div class="card empty py-6"><svg aria-hidden="true"><use href="#i-list"/></svg><h2 class="text-lg">هنوز فعالیتی ثبت نشده</h2></div>
  @else
    <div class="table-wrap">
      <table class="table">
        <caption class="visually-hidden">گزارش فعالیت</caption>
        <thead><tr><th scope="col">رویداد</th><th scope="col">جزئیات</th><th scope="col">زمان</th></tr></thead>
        <tbody>
          @foreach ($items as $item)
            <tr>
              <td>{{ __('activity.'.$item->event) }}</td>
              <td class="text-muted">{{ collect([$item->properties['type'] ?? null, isset($item->properties['title']) ? '«'.$item->properties['title'].'»' : null, isset($item->properties['status']) ? (\App\Models\ContactMessage::STATUSES[$item->properties['status']] ?? null) : null])->filter()->implode(' ') ?: '—' }}</td>
              <td class="tabular text-muted">{{ Jalali::format($item->created_at, 'Y/m/d H:i') }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    {{ $items->links('admin.partials.pagination') }}
  @endif
@endsection
