<?php

use App\Http\Middleware\CanonicalTrailingSlash;
use App\Http\Middleware\SecurityHeaders;
use App\Support\RedirectResolver;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware(['web', 'auth'])
                ->prefix(config('admin.path'))
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global: headers on every response, including 404/500 pages.
        $middleware->append(SecurityHeaders::class);
        $middleware->web(append: [CanonicalTrailingSlash::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Old WordPress URLs and renamed slugs (D31): consulted only for would-be 404s.
        $exceptions->render(fn (NotFoundHttpException $e, Request $request) => RedirectResolver::respond($request));

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

// Split layout (document root cannot point at public/, D36): the deploy
// creates bootstrap/public-path.php returning the absolute path of the web
// root (e.g. ~/domains/example.com/public_html). Read here — before config is
// loaded — so web requests, artisan/cron and the cached config all agree.
if (is_file(__DIR__.'/public-path.php')) {
    $app->usePublicPath(require __DIR__.'/public-path.php');
}

return $app;
