<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Support\Link;
use App\Support\Markdown;
use App\Support\ResumeData;
use App\Support\Seo;
use Illuminate\View\View;

class PageController extends Controller
{
    public function about(ResumeData $resume): View
    {
        $data = $resume->toArray();
        /** @var Profile $profile */
        $profile = $data['profile'];

        $seo = Seo::make('درباره من', $profile->short_bio)->canonical(Link::route('about'))->image($profile->avatar);
        $seo->schema(['@type' => 'AboutPage', 'name' => 'درباره '.$profile->display_name, 'url' => Link::route('about'),
            'mainEntity' => ['@type' => 'Person', 'name' => $profile->display_name, 'jobTitle' => $profile->headline]]);

        return view('site.about', $data + [
            'seo' => $seo,
            'bioHtml' => Markdown::toHtml($profile->long_bio ?: $profile->short_bio),
        ]);
    }

    /** The resume is the same document as the admin preview, printable to PDF from the browser. */
    public function resume(ResumeData $resume): View
    {
        $data = $resume->toArray();
        $seo = Seo::make('رزومه', 'رزومهٔ '.$data['profile']->display_name.': سوابق کاری، مهارت‌ها و دوره‌ها.')
            ->canonical(Link::route('resume'));

        return view('resume.document', $data + ['seo' => $seo, 'preview' => false]);
    }
}
