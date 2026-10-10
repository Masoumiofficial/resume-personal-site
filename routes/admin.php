<?php

use App\Http\Controllers\Admin;
use Illuminate\Support\Facades\Route;

/*
| Admin panel. Loaded in bootstrap/app.php with:
|   prefix  = config('admin.path')   middleware = web, auth   name = admin.*
*/
Route::get('/', Admin\DashboardController::class)->name('dashboard');

// Content modules: list / create / edit / delete + ordering and trash.
$resource = function (string $uri, string $controller): void {
    Route::get($uri, [$controller, 'index'])->name("{$uri}.index");
    Route::get("{$uri}/create", [$controller, 'create'])->name("{$uri}.create");
    Route::post($uri, [$controller, 'store'])->name("{$uri}.store");
    Route::get("{$uri}/{id}/edit", [$controller, 'edit'])->whereNumber('id')->name("{$uri}.edit");
    Route::put("{$uri}/{id}", [$controller, 'update'])->whereNumber('id')->name("{$uri}.update");
    Route::delete("{$uri}/{id}", [$controller, 'destroy'])->whereNumber('id')->name("{$uri}.destroy");
    Route::post("{$uri}/{id}/move/{direction}", [$controller, 'move'])->whereNumber('id')->whereIn('direction', ['up', 'down'])->name("{$uri}.move");
    Route::post("{$uri}/{id}/restore", [$controller, 'restore'])->whereNumber('id')->name("{$uri}.restore");
    Route::delete("{$uri}/{id}/force", [$controller, 'forceDelete'])->whereNumber('id')->name("{$uri}.force");
};

$resource('articles', Admin\ArticleController::class);
Route::get('articles/{id}/preview', [Admin\ArticleController::class, 'preview'])->whereNumber('id')->name('articles.preview');
$resource('article-categories', Admin\ArticleCategoryController::class);
$resource('tags', Admin\TagController::class);
$resource('projects', Admin\ProjectController::class);
$resource('project-categories', Admin\ProjectCategoryController::class);
$resource('services', Admin\ServiceController::class);
$resource('service-groups', Admin\ServiceGroupController::class);
$resource('testimonials', Admin\TestimonialController::class);
$resource('experiences', Admin\ExperienceController::class);
$resource('educations', Admin\EducationController::class);
$resource('skills', Admin\SkillController::class);
$resource('certifications', Admin\CertificationController::class);
$resource('redirects', Admin\RedirectController::class);

// Single-record pages.
Route::get('profile', [Admin\ProfileController::class, 'edit'])->name('profile.edit');
Route::put('profile', [Admin\ProfileController::class, 'update'])->name('profile.update');
Route::get('settings', [Admin\SettingsController::class, 'edit'])->name('settings.edit');
Route::put('settings', [Admin\SettingsController::class, 'update'])->name('settings.update');
Route::get('resume/print', Admin\ResumePreviewController::class)->name('resume.print');

// Media library.
Route::get('media', [Admin\MediaController::class, 'index'])->name('media.index');
Route::post('media', [Admin\MediaController::class, 'store'])->name('media.store');
Route::get('media/{id}/edit', [Admin\MediaController::class, 'edit'])->whereNumber('id')->name('media.edit');
Route::put('media/{id}', [Admin\MediaController::class, 'update'])->whereNumber('id')->name('media.update');
Route::delete('media/{id}', [Admin\MediaController::class, 'destroy'])->whereNumber('id')->name('media.destroy');

// Contact messages.
Route::get('messages', [Admin\MessageController::class, 'index'])->name('messages.index');
Route::get('messages/{id}', [Admin\MessageController::class, 'show'])->whereNumber('id')->name('messages.show');
Route::patch('messages/{id}', [Admin\MessageController::class, 'update'])->whereNumber('id')->name('messages.update');
Route::delete('messages/{id}', [Admin\MessageController::class, 'destroy'])->whereNumber('id')->name('messages.destroy');

Route::get('activity', Admin\ActivityController::class)->name('activity');

// "Sudo mode": the page asks for the password again (valid 3h, auth.password_timeout),
// so the 2FA buttons on it work directly instead of bouncing through a
// confirmation that would GET a POST-only endpoint.
Route::get('/security', Admin\SecurityController::class)->middleware('password.confirm')->name('security');
