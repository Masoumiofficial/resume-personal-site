@php $profile ??= \App\Models\Profile::current(); @endphp
<section class="section section--flush-top" aria-labelledby="cta-title">
  <div class="container">
    <div class="cta">
      <div>
        <h2 id="cta-title">پروژه‌ای در ذهن دارید؟</h2>
        <p>چند خط دربارهٔ کسب‌وکار و هدفتان بنویسید؛ در اولین فرصت پاسخ می‌دهم.</p>
        <div class="cluster cta__actions">
          <a class="btn btn--primary" href="{{ \App\Support\Link::route('contact') }}">ارسال پیام <svg aria-hidden="true"><use href="#i-arrow"/></svg></a>
        </div>
      </div>
      @include('site.partials.contact-lines')
    </div>
  </div>
</section>
