<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Every admin page: guests are sent to login; the admin gets a 200 with no inline styles (CSP). */
class ModulePagesTest extends TestCase
{
    use RefreshDatabase;

    public static function pages(): array
    {
        $pages = [];
        foreach (['articles', 'article-categories', 'tags', 'projects', 'project-categories', 'services', 'service-groups', 'testimonials', 'experiences', 'educations', 'skills', 'certifications'] as $module) {
            $pages["{$module} list"] = ["/admin/{$module}"];
            $pages["{$module} create"] = ["/admin/{$module}/create"];
        }

        return $pages + [
            'articles trash' => ['/admin/articles?trashed=1'],
            'profile' => ['/admin/profile'],
            'settings' => ['/admin/settings'],
            'media' => ['/admin/media'],
            'messages' => ['/admin/messages'],
            'activity' => ['/admin/activity'],
            'resume preview' => ['/admin/resume/print'],
        ];
    }

    #[DataProvider('pages')]
    public function test_page_requires_login_and_renders_for_the_admin(string $uri): void
    {
        $this->get($uri)->assertRedirect('/admin/login');

        $html = $this->actingAs(User::factory()->create())->get($uri)
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->getContent();

        $this->assertDoesNotMatchRegularExpression('/\sstyle="/i', $html, 'Inline style attributes break the CSP.');
        $this->assertDoesNotMatchRegularExpression('/<script(?![^>]*\s(src|nonce)=)[^>]*>/i', $html, 'Inline scripts need a nonce.');
    }
}
