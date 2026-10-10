@extends('layouts.auth')
@section('title', 'تعیین رمز عبور جدید')
@section('lead', 'دست‌کم ۱۲ نویسه. پس از تغییر، همهٔ نشست‌های فعال حساب بسته می‌شوند.')

@section('content')
<form class="stack stack--5" method="POST" action="{{ route('password.update') }}" novalidate>
  @csrf
  <input type="hidden" name="token" value="{{ $request->route('token') }}">
  <div class="field">
    <label class="field__label" for="email">ایمیل</label>
    <input class="input input--ltr" id="email" name="email" type="email" value="{{ old('email', $request->email) }}" autocomplete="username" required
           @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
    @include('partials.field-error', ['name' => 'email'])
  </div>
  <div class="field">
    <label class="field__label" for="password">رمز عبور جدید</label>
    <input class="input input--ltr" id="password" name="password" type="password" autocomplete="new-password" minlength="12" required
           @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
    @include('partials.field-error', ['name' => 'password'])
  </div>
  <div class="field">
    <label class="field__label" for="password_confirmation">تکرار رمز عبور</label>
    <input class="input input--ltr" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
  </div>
  <button class="btn btn--primary" type="submit">ذخیرهٔ رمز عبور</button>
</form>
@endsection
