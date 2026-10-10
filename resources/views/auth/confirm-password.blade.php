@extends('layouts.auth')
@section('title', 'تأیید رمز عبور')
@section('lead', 'برای ادامهٔ این عملیات حساس، رمز عبور خود را دوباره وارد کنید.')

@section('content')
<form class="stack stack--5" method="POST" action="{{ route('password.confirm.store') }}" novalidate>
  @csrf
  <div class="field">
    <label class="field__label" for="password">رمز عبور</label>
    <input class="input input--ltr" id="password" name="password" type="password" autocomplete="current-password" required autofocus
           @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
    @include('partials.field-error', ['name' => 'password'])
  </div>
  <button class="btn btn--primary" type="submit">تأیید</button>
</form>
@endsection
