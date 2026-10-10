@php echo '<?xml version="1.0" encoding="UTF-8"?>'."\n"; @endphp
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/">
<channel>
  <title>{{ \App\Models\Setting::get('site_title') }}</title>
  <link>{{ \App\Support\Link::route('home') }}</link>
  <atom:link href="{{ \App\Support\Link::route('feed') }}" rel="self" type="application/rss+xml"/>
  <description>{{ \App\Models\Setting::get('default_meta_description') ?: $profile->headline }}</description>
  <language>fa-IR</language>
@if ($articles->isNotEmpty())
  <lastBuildDate>{{ $articles->first()->published_at->toRssString() }}</lastBuildDate>
@endif
@foreach ($articles as $article)
  <item>
    <title>{{ $article->title }}</title>
    <link>{{ $article->url() }}</link>
    <guid isPermaLink="true">{{ $article->url() }}</guid>
    <pubDate>{{ $article->published_at->toRssString() }}</pubDate>
    <dc:creator>{{ $profile->display_name }}</dc:creator>
@foreach ($article->categories as $category)
    <category>{{ $category->name }}</category>
@endforeach
    <description>{{ $article->excerpt ?: \Illuminate\Support\Str::limit(strip_tags((string) $article->body_html), 300) }}</description>
  </item>
@endforeach
</channel>
</rss>
