@extends('layouts.auth')
@section('title', 'ورود دومرحله‌ای')
@section('lead', 'کد ۶ رقمی برنامهٔ احراز هویت را وارد کنید. اگر به گوشی دسترسی ندارید، یکی از کدهای بازیابی را استفاده کنید.')

@section('content')
<form class="stack stack--5" method="POST" action="{{ route('two-factor.login.store') }}" novalidate>
  @csrf
  <div class="field">
    <label class="field__label" for="code">کد احراز هویت</label>
    <input class="input input--ltr" id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="one-time-code" autofocus
           @error('code') aria-invalid="true" aria-describedby="code-error" @enderror>
    @include('partials.field-error', ['name' => 'code'])
  </div>
  <details @if ($errors->has('recovery_code')) open @endif>
    <summary class="text-sm">استفاده از کد بازیابی</summary>
    <div class="field mt-5">
      <label class="field__label" for="recovery_code">کد بازیابی</label>
      <input class="input input--ltr" id="recovery_code" name="recovery_code" type="text" autocomplete="off"
             @error('recovery_code') aria-invalid="true" aria-describedby="recovery_code-error" @enderror>
      @include('partials.field-error', ['name' => 'recovery_code'])
    </div>
  </details>
  <button class="btn btn--primary" type="submit">تأیید</button>
</form>
@endsection
