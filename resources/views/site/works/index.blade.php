@extends('layouts.site')

@section('content')
@include('site.partials.page-head', ['eyebrow' => 'نمونه‌کارها', 'title' => 'پروژه‌های منتخب', 'lead' => 'هر پروژه با مسئله، راهکار، نقش من و نتیجهٔ قابل اثبات معرفی می‌شود.', 'crumbs' => ['نمونه‌کارها' => null]])

<section class="section section--flush-top">
  <div class="container">
    @if ($projects->isEmpty())
      <div class="empty card">
        <p>نمونه‌کارها به‌زودی در این بخش منتشر می‌شوند.</p>
        <a class="btn" href="{{ \App\Support\Link::route('services.index') }}">مشاهدهٔ خدمات</a>
      </div>
    @else
      <div class="grid grid--3">
        @foreach ($projects as $project)@include('site.partials.project-card')@endforeach
      </div>
    @endif
  </div>
</section>
@endsection
