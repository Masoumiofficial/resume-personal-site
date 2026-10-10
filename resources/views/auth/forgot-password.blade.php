@extends('layouts.auth')
@section('title', 'بازیابی رمز عبور')
@section('lead', 'ایمیل حساب را وارد کنید تا پیوند بازنشانی (معتبر تا ۳۰ دقیقه) ارسال شود.')

@section('content')
<form class="stack stack--5" method="POST" action="{{ route('password.email') }}" novalidate>
  @csrf
  <div class="field">
    <label class="field__label" for="email">ایمیل</label>
    <input class="input input--ltr" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus
           @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
    @include('partials.field-error', ['name' => 'email'])
  </div>
  <button class="btn btn--primary" type="submit">ارسال پیوند</button>
</form>
<div class="auth__links"><a href="{{ route('login') }}">بازگشت به ورود</a></div>
@endsection
