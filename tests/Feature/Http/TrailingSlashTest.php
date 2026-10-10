<?php

namespace Tests\Feature\Http;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

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

    public function test_slashed_url_is_served_directly(): void
    {
        $this->get('/sample-page/')->assertOk()->assertSeeText('page');
    }

    public function test_unslashed_url_gets_one_301_to_the_slashed_form(): void
    {
        $this->get('/sample-page')->assertStatus(301)->assertRedirect('http://localhost/sample-page/');
    }

    public function test_query_string_is_preserved(): void
    {
        $this->get('/sample-page?utm_source=x&a=1')->assertStatus(301)
            ->assertRedirect('http://localhost/sample-page/?utm_source=x&a=1');
    }

    public function test_percent_encoded_persian_path_stays_encoded(): void
    {
        $encoded = '/wordpress-'.rawurlencode('چیست');

        $this->get($encoded)->assertStatus(301)->assertRedirect('http://localhost'.$encoded.'/');
        $this->get($encoded.'/')->assertOk();
    }

    public function test_files_and_non_get_requests_are_not_redirected(): void
    {
        $this->get('/sitemap.xml')->assertOk();
        $this->withoutMiddleware(ValidateCsrfToken::class)
            ->post('/sample-form')->assertOk();
    }

    public function test_admin_and_health_routes_are_not_redirected(): void
    {
        $this->get('/admin/login')->assertOk();
        $this->get('/up')->assertOk();
    }
}
