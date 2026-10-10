@extends('layouts.auth')
@section('title', 'ورود')

@section('content')
<form class="stack stack--5" method="POST" action="{{ route('login.store') }}" novalidate>
  @csrf
  <div class="field">
    <label class="field__label" for="email">ایمیل</label>
    <input class="input input--ltr" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus
           @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
    @include('partials.field-error', ['name' => 'email'])
  </div>
  <div class="field">
    <label class="field__label" for="password">رمز عبور</label>
    <input class="input input--ltr" id="password" name="password" type="password" autocomplete="current-password" required
           @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
    @include('partials.field-error', ['name' => 'password'])
  </div>
  <label class="checkbox"><input type="checkbox" name="remember" value="1" @checked(old('remember'))> مرا به خاطر بسپار</label>
  <button class="btn btn--primary" type="submit">ورود</button>
</form>
<div class="auth__links"><a href="{{ route('password.request') }}">رمز عبور را فراموش کرده‌ام</a></div>
@endsection
