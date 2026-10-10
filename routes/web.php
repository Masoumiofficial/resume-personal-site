<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\Site\ArticleController;
use App\Http\Controllers\Site\BlogController;
use App\Http\Controllers\Site\FeedController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\ServiceController;
use App\Http\Controllers\Site\WorkController;
use Illuminate\Support\Facades\Route;

/*
| Public site. URLs carry WordPress' trailing slash (D15); CanonicalTrailingSlash
| 301s the slashless form. Articles keep their old root permalinks (/{slug}/)
| through the fallback route below, so they never shadow /admin or other routes.
*/
Route::get('/', HomeController::class)->name('home');
Route::get('/about/', [PageController::class, 'about'])->name('about');
Route::get('/resume/', [PageController::class, 'resume'])->name('resume');

Route::get('/works/', [WorkController::class, 'index'])->name('works.index');
Route::get('/works/{slug}/', [WorkController::class, 'show'])->name('works.show');

Route::get('/services/', [ServiceController::class, 'index'])->name('services.index');
Route::get('/services/{slug}/', [ServiceController::class, 'show'])->name('services.show');

Route::get('/blog/', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/page/{page}/', [BlogController::class, 'index'])->whereNumber('page')->name('blog.page');
Route::get('/blog/category/{slug}/', [BlogController::class, 'category'])->name('blog.category');
Route::get('/blog/category/{slug}/page/{page}/', [BlogController::class, 'category'])->whereNumber('page')->name('blog.category.page');
Route::get('/blog/tag/{slug}/', [BlogController::class, 'tag'])->name('blog.tag');
Route::get('/blog/tag/{slug}/page/{page}/', [BlogController::class, 'tag'])->whereNumber('page')->name('blog.tag.page');

Route::get('/contact/', [ContactController::class, 'create'])->name('contact');
Route::post('/contact/', [ContactController::class, 'store'])->middleware('throttle:contact')->name('contact.store');

Route::get('/feed/', [FeedController::class, 'rss'])->name('feed');
Route::get('/sitemap.xml', [FeedController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [FeedController::class, 'robots'])->name('robots');

// Must stay last: single-segment article permalinks; everything else is a 404.
// Registered for every method so unknown POST/PUT paths are a 404, not a 405.
Route::any('{fallbackPlaceholder}', [ArticleController::class, 'show'])->where('fallbackPlaceholder', '.*')->fallback();
