@php use App\Support\Jalali; use App\Models\ContactMessage; @endphp
@extends('layouts.admin')
@section('title', 'پیام از '.$message->name)

@section('content')
  <p><a class="text-sm" href="{{ route('admin.messages.index') }}">→ بازگشت به پیام‌ها</a></p>
  <div class="form-layout">
    <article class="card stack stack--5">
      <header>
        <h2 class="text-lg">{{ $message->subject ?: 'بدون موضوع' }}</h2>
        <p class="text-sm text-muted">{{ $message->name }} · <a class="ltr" href="mailto:{{ $message->email }}?subject={{ rawurlencode('پاسخ: '.($message->subject ?? 'پیام شما')) }}">{{ $message->email }}</a> · {{ Jalali::format($message->created_at, 'j F Y، H:i') }}</p>
      </header>
      {{-- Visitor input: escaped, line breaks kept. Never rendered as HTML/Markdown. --}}
      <div class="message-body">{{ $message->message }}</div>
    </article>

    <aside class="form-aside">
      <div class="card stack stack--5">
        <a class="btn btn--primary" href="mailto:{{ $message->email }}?subject={{ rawurlencode('پاسخ: '.($message->subject ?? 'پیام شما')) }}"><svg aria-hidden="true"><use href="#i-mail"/></svg>پاسخ با ایمیل</a>
        <form class="stack stack--5" method="POST" action="{{ route('admin.messages.update', $message->id) }}">
          @csrf @method('PATCH')
          <div class="field">
            <label class="field__label" for="status">وضعیت</label>
            <select class="input" id="status" name="status">
              @foreach (ContactMessage::STATUSES as $value => $label)<option value="{{ $value }}" @selected($message->status === $value)>{{ $label }}</option>@endforeach
            </select>
          </div>
          <button class="btn btn--sm" type="submit">ثبت وضعیت</button>
        </form>
        <form method="POST" action="{{ route('admin.messages.destroy', $message->id) }}" data-confirm="این پیام برای همیشه حذف شود؟">
          @csrf @method('DELETE')
          <button class="btn btn--ghost btn--sm text-danger" type="submit"><svg aria-hidden="true"><use href="#i-trash"/></svg>حذف</button>
        </form>
        @if ($message->handled_at)<p class="text-sm text-muted">رسیدگی: {{ Jalali::format($message->handled_at, 'Y/m/d H:i') }}</p>@endif
      </div>
    </aside>
  </div>
@endsection
