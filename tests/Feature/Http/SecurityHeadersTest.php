<?php

namespace Tests\Feature\Http;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_baseline_headers_are_sent(): void
    {
        $response = $this->get('/admin/login');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertNotNull($response->headers->get('Permissions-Policy'));
        $this->assertStringContainsString("frame-ancestors 'none'", (string) $response->headers->get('Content-Security-Policy'));
        $this->assertStringNotContainsString('unsafe-inline', (string) $response->headers->get('Content-Security-Policy'));
    }

    public function test_inline_theme_script_nonce_matches_the_csp_header(): void
    {
        $response = $this->get('/admin/login')->assertOk();

        preg_match("/'nonce-([A-Za-z0-9+\/=]+)'/", (string) $response->headers->get('Content-Security-Policy'), $header);
        preg_match('/<script nonce="([^"]+)"/', $response->getContent(), $body);

        $this->assertNotEmpty($header[1] ?? null, 'CSP header has no nonce');
        $this->assertSame($header[1], $body[1] ?? null);
    }

    public function test_nonce_changes_per_request(): void
    {
        $a = $this->get('/admin/login')->headers->get('Content-Security-Policy');
        $b = $this->get('/admin/login')->headers->get('Content-Security-Policy');

        $this->assertNotSame($a, $b);
    }

    public function test_no_inline_style_attributes_in_rendered_pages(): void
    {
        $user = User::factory()->create();
        $pages = [
            $this->get('/admin/login')->getContent(),
            $this->get('/admin/forgot-password')->getContent(),
            $this->get('/')->getContent(),
            $this->actingAs($user)->get('/admin')->getContent(),
            $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])->get('/admin/security')->assertOk()->getContent(),
        ];

        foreach ($pages as $html) {
            $this->assertDoesNotMatchRegularExpression('/\sstyle="/', $html, 'Inline style attributes are blocked by the CSP.');
        }
    }

    public function test_admin_pages_are_not_cacheable(): void
    {
        $user = User::factory()->create();

        $cache = (string) $this->actingAs($user)->get('/admin')->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cache);

        $this->assertStringContainsString('no-store', (string) $this->get('/admin/login')->headers->get('Cache-Control'));
    }

    public function test_admin_is_noindex_in_production_too(): void
    {
        // Found by the release smoke test (D38): production dropped the header on /admin/login.
        $this->app->detectEnvironment(fn () => 'production');

        $this->get('/admin/login')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get('/about/')->assertHeaderMissing('X-Robots-Tag');
    }

    public function test_headers_are_present_on_404_pages(): void
    {
        $this->get('/this-page-does-not-exist/')->assertNotFound()->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_placeholder_home_is_noindex(): void
    {
        $this->get('/')->assertOk()->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }
}
