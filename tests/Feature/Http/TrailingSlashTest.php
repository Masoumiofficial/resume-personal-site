<?php

namespace Tests\Feature\Http;

use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Laravel's test client trims trailing slashes from request URIs
 * (MakesHttpRequests::prepareUrlForRequest), so these tests send raw
 * requests straight to the HTTP kernel to keep the exact path.
 */
class TrailingSlashTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->group(function () {
            Route::get('sample-page', fn () => 'page');
            Route::get('wordpress-چیست', fn () => 'fa');
            Route::get('sitemap.xml', fn () => 'xml');
            Route::post('sample-form', fn () => 'posted');
        });
    }

    private function raw(string $uri, string $method = 'GET'): TestResponse
    {
        $response = $this->app->make(HttpKernel::class)->handle(Request::create('http://localhost'.$uri, $method));

        return TestResponse::fromBaseResponse($response);
    }

    public function test_slashed_url_is_served_directly(): void
    {
        $this->raw('/sample-page/')->assertOk()->assertSeeText('page');
    }

    public function test_unslashed_url_gets_one_301_to_the_slashed_form(): void
    {
        $this->raw('/sample-page')->assertStatus(301)->assertRedirect('http://localhost/sample-page/');
    }

    public function test_query_string_is_preserved(): void
    {
        $this->raw('/sample-page?utm_source=x&a=1')->assertStatus(301)
            ->assertRedirect('http://localhost/sample-page/?utm_source=x&a=1');
    }

    public function test_percent_encoded_persian_path_stays_encoded(): void
    {
        $encoded = '/wordpress-'.rawurlencode('چیست');

        $this->raw($encoded)->assertStatus(301)->assertRedirect('http://localhost'.$encoded.'/');
        $this->raw($encoded.'/')->assertOk()->assertSeeText('fa');
    }

    public function test_files_and_non_get_requests_are_not_redirected(): void
    {
        $this->raw('/sitemap.xml')->assertOk();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->raw('/sample-form', 'POST')->assertOk();
    }

    public function test_admin_home_and_health_routes_are_not_redirected(): void
    {
        $this->raw('/admin/login')->assertOk();
        $this->raw('/up')->assertOk();
        $this->raw('/')->assertOk();
    }
}
