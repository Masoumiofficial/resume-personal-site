@php use App\Support\Jalali; use App\Models\ContactMessage; @endphp
@extends('layouts.admin')
@section('title', 'پیام‌ها')

@section('content')
  @php
    $tabs = ['inbox' => ['صندوق', ($counts['new'] ?? 0) + ($counts['read'] ?? 0)]];
    foreach (['replied', 'archived', 'spam'] as $s) { $tabs[$s] = [ContactMessage::STATUSES[$s], $counts[$s] ?? 0]; }
  @endphp
  <nav class="tabs" aria-label="وضعیت پیام‌ها">
    @foreach ($tabs as $key => [$label, $n])
      <a class="tabs__link" href="{{ route('admin.messages.index', $key === 'inbox' ? [] : ['status' => $key]) }}" @if ($status === $key) aria-current="page" @endif>{{ $label }} <span class="text-muted">({{ Jalali::digits($n) }})</span></a>
    @endforeach
  </nav>

  @if ($messages->isEmpty())
    <div class="card empty py-6"><svg aria-hidden="true"><use href="#i-inbox"/></svg><h2 class="text-lg">پیامی در این بخش نیست</h2></div>
  @else
    <div class="table-wrap">
      <table class="table">
        <caption class="visually-hidden">پیام‌ها</caption>
        <thead><tr><th scope="col">فرستنده</th><th scope="col">موضوع</th><th scope="col">زمان</th><th scope="col">وضعیت</th></tr></thead>
        <tbody>
          @foreach ($messages as $message)
            <tr @class(['is-unread' => $message->status === 'new'])>
              <th scope="row" class="table__primary"><a href="{{ route('admin.messages.show', $message->id) }}">{{ $message->name }}</a><br><span class="text-muted text-sm ltr">{{ $message->email }}</span></th>
              <td>{{ $message->subject ?: '—' }}</td>
              <td class="tabular text-muted">{{ Jalali::format($message->created_at, 'Y/m/d H:i') }}</td>
              <td><span @class(['badge', 'badge--accent' => $message->status === 'new', 'badge--danger' => $message->status === 'spam', 'badge--success' => $message->status === 'replied'])>{{ ContactMessage::STATUSES[$message->status] ?? $message->status }}</span></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    {{ $messages->links('admin.partials.pagination') }}
  @endif
@endsection
