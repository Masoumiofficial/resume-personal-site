@extends('layouts.site')
@use('App\Support\ContactForm')

@section('content')
@include('site.partials.page-head', ['eyebrow' => 'تماس', 'title' => 'تماس با من', 'lead' => 'برای مشاوره، سفارش پروژه یا همکاری پیام بگذارید؛ در اولین فرصت پاسخ می‌دهم.', 'crumbs' => ['تماس' => null]])

<div class="container contact">
  <section class="card contact__form" aria-labelledby="form-title">
    <h2 id="form-title" class="about__heading">ارسال پیام</h2>

    @if (session('contact_status') === 'sent')
      <div class="alert alert--success" role="status">پیام شما دریافت شد. ممنون؛ به‌زودی پاسخ می‌دهم.</div>
    @endif
    @if (session('contact_error'))
      <div class="alert alert--warning" role="alert">{{ session('contact_error') }}</div>
    @endif

    @if ($formEnabled)
      <form method="post" action="{{ \App\Support\Link::route('contact.store') }}" class="stack stack--5" novalidate>
        @csrf
        <input type="hidden" name="{{ ContactForm::TOKEN }}" value="{{ $token }}">
        {{-- Honeypot: hidden from people and assistive tech, tempting to bots. --}}
        <div class="visually-hidden" aria-hidden="true">
          <label for="f-website">وب‌سایت</label>
          <input id="f-website" type="text" name="{{ ContactForm::HONEYPOT }}" tabindex="-1" autocomplete="off">
        </div>

        <div class="form-row">
          <div class="field">
            <label class="field__label" for="f-name">نام</label>
            <input class="input" id="f-name" name="name" value="{{ old('name') }}" required maxlength="120" autocomplete="name" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
            @include('partials.field-error', ['name' => 'name'])
          </div>
          <div class="field">
            <label class="field__label" for="f-email">ایمیل</label>
            <input class="input input--ltr" id="f-email" type="email" name="email" value="{{ old('email') }}" required maxlength="191" autocomplete="email" dir="ltr" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @include('partials.field-error', ['name' => 'email'])
          </div>
        </div>
        <div class="field">
          <label class="field__label" for="f-subject">موضوع <span class="muted">(اختیاری)</span></label>
          <input class="input" id="f-subject" name="subject" value="{{ old('subject', \Illuminate\Support\Str::limit((string) request()->query('subject'), 191, '')) }}" maxlength="191" @error('subject') aria-invalid="true" aria-describedby="subject-error" @enderror>
          @include('partials.field-error', ['name' => 'subject'])
        </div>
        <div class="field">
          <label class="field__label" for="f-message">پیام</label>
          <textarea class="input" id="f-message" name="message" rows="7" required minlength="10" maxlength="5000" @error('message') aria-invalid="true" aria-describedby="message-error" @enderror>{{ old('message') }}</textarea>
          @include('partials.field-error', ['name' => 'message'])
        </div>
        <p class="text-sm muted">اطلاعات شما فقط برای پاسخ به همین پیام استفاده می‌شود.</p>
        <div><button class="btn btn--primary" type="submit">ارسال پیام <svg aria-hidden="true"><use href="#i-arrow"/></svg></button></div>
      </form>
    @else
      <p class="muted">فرم تماس موقتاً غیرفعال است؛ لطفاً از ایمیل یا تلفن استفاده کنید.</p>
    @endif
  </section>

  <aside class="contact__side" aria-label="راه‌های ارتباطی">
    <div class="cta cta--stacked">
      <div>
        <h2>راه‌های ارتباطی</h2>
        <p>از هر راهی که برایتان راحت‌تر است در تماس باشید.</p>
      </div>
      @include('site.partials.contact-lines')
    </div>
  </aside>
</div>
@endsection
