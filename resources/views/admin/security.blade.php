@extends('layouts.admin')
@section('title', 'امنیت حساب')

@section('content')
  @php $status = session('status'); @endphp
  @if ($status === 'password-updated')
    <div class="alert alert--success" role="status"><span>رمز عبور تغییر کرد. نشست‌های دیگر این حساب بسته شدند.</span></div>
  @elseif ($status === 'two-factor-authentication-confirmed')
    <div class="alert alert--success" role="status"><span>ورود دومرحله‌ای فعال شد. کدهای بازیابی زیر را در جای امنی نگه دارید.</span></div>
  @elseif ($status === 'two-factor-authentication-disabled')
    <div class="alert alert--warning" role="status"><span>ورود دومرحله‌ای غیرفعال شد.</span></div>
  @elseif ($status === 'recovery-codes-generated')
    <div class="alert alert--success" role="status"><span>کدهای بازیابی جدید ساخته شد؛ کدهای قبلی دیگر کار نمی‌کنند.</span></div>
  @endif

  <section class="card measure-44" aria-labelledby="two-factor">
    <div class="panel__head"><h2 id="two-factor">ورود دومرحله‌ای (TOTP)</h2></div>

    @if ($twoFactorEnabled)
      <p><span class="badge badge--success"><span class="badge__dot" aria-hidden="true"></span>فعال</span></p>

      @if ($recoveryCodes)
        <p class="text-sm mt-5">هر کد فقط یک بار قابل استفاده است. این فهرست دوباره نمایش داده نمی‌شود.</p>
        <ul class="recovery-codes" role="list">@foreach ($recoveryCodes as $code)<li>{{ $code }}</li>@endforeach</ul>
      @endif

      <div class="cluster mt-5">
        <form method="POST" action="{{ route('two-factor.regenerate-recovery-codes') }}">@csrf<button class="btn btn--sm" type="submit">ساخت کدهای بازیابی جدید</button></form>
        <form method="POST" action="{{ route('two-factor.disable') }}" data-confirm="ورود دومرحله‌ای غیرفعال شود؟ امنیت حساب کاهش می‌یابد.">@csrf @method('DELETE')<button class="btn btn--ghost btn--sm text-danger" type="submit">غیرفعال‌سازی</button></form>
      </div>
    @elseif ($twoFactorPending)
      <p class="text-sm">این کد QR را با برنامهٔ احراز هویت (مثل Google Authenticator یا Aegis) اسکن کنید، سپس کد ۶ رقمی را وارد کنید.</p>
      <div class="qr mt-5">{!! $qrSvg !!}</div>
      <form class="stack stack--5 mt-5" method="POST" action="{{ route('two-factor.confirm') }}" novalidate>
        @csrf
        <div class="field">
          <label class="field__label" for="code">کد ۶ رقمی</label>
          <input class="input input--ltr" id="code" name="code" type="text" inputmode="numeric" maxlength="6" autocomplete="one-time-code" required
                 @error('code', 'confirmTwoFactorAuthentication') aria-invalid="true" aria-describedby="code-error" @enderror>
          @include('partials.field-error', ['name' => 'code', 'bag' => 'confirmTwoFactorAuthentication'])
        </div>
        <div class="cluster"><button class="btn btn--primary" type="submit">تأیید و فعال‌سازی</button></div>
      </form>
      <form class="mt-5" method="POST" action="{{ route('two-factor.disable') }}">@csrf @method('DELETE')<button class="btn btn--ghost btn--sm" type="submit">انصراف</button></form>
    @else
      <p><span class="badge badge--warning">غیرفعال</span></p>
      <p class="text-sm mt-5">با فعال‌سازی، ورود علاوه بر رمز عبور به کد برنامهٔ احراز هویت نیاز دارد.</p>
      <form class="mt-5" method="POST" action="{{ route('two-factor.enable') }}">@csrf<button class="btn btn--primary" type="submit">فعال‌سازی</button></form>
    @endif
  </section>

  <section class="card measure-44" aria-labelledby="password-heading">
    <div class="panel__head"><h2 id="password-heading">تغییر رمز عبور</h2></div>
    <form class="stack stack--5" method="POST" action="{{ route('user-password.update') }}" novalidate>
      @csrf @method('PUT')
      <div class="field">
        <label class="field__label" for="current_password">رمز عبور فعلی</label>
        <input class="input input--ltr" id="current_password" name="current_password" type="password" autocomplete="current-password" required
               @error('current_password', 'updatePassword') aria-invalid="true" aria-describedby="current_password-error" @enderror>
        @include('partials.field-error', ['name' => 'current_password', 'bag' => 'updatePassword'])
      </div>
      <div class="field">
        <label class="field__label" for="password">رمز عبور جدید</label>
        <input class="input input--ltr" id="password" name="password" type="password" autocomplete="new-password" minlength="12" required aria-describedby="password-hint"
               @error('password', 'updatePassword') aria-invalid="true" @enderror>
        <span class="field__hint" id="password-hint">دست‌کم ۱۲ نویسه.</span>
        @include('partials.field-error', ['name' => 'password', 'bag' => 'updatePassword'])
      </div>
      <div class="field">
        <label class="field__label" for="password_confirmation">تکرار رمز عبور جدید</label>
        <input class="input input--ltr" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
      </div>
      <div class="cluster"><button class="btn btn--primary" type="submit">ذخیره</button></div>
    </form>
  </section>
@endsection
