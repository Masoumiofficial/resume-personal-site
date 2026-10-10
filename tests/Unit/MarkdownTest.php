<?php

namespace Tests\Unit;

use App\Support\Markdown;
use Tests\TestCase;

class MarkdownTest extends TestCase
{
    public function test_raw_html_is_escaped_not_executed(): void
    {
        $html = Markdown::toHtml("سلام <script>alert(1)</script>\n\n<img src=x onerror=alert(1)>");

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_dangerous_link_schemes_are_dropped(): void
    {
        $html = Markdown::toHtml('[a](javascript:alert(1)) [b](data:text/html;base64,PHN2Zz4=) [c](vbscript:x)');

        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('data:text', $html);
        $this->assertStringNotContainsString('vbscript:', $html);
    }

    public function test_external_links_get_noopener_and_internal_links_do_not(): void
    {
        config(['app.url' => 'https://sajadmasoumi.com']);
        $html = Markdown::toHtml('[ext](https://example.org) [int](https://sajadmasoumi.com/about/)');

        $this->assertMatchesRegularExpression('#<a (?=[^>]*rel="noopener")[^>]*href="https://example.org"[^>]*>ext</a>#', $html);
        $this->assertStringContainsString('<a href="https://sajadmasoumi.com/about/">int</a>', $html);
    }

    public function test_markdown_formatting_and_reading_time(): void
    {
        $html = Markdown::toHtml("## تیتر\n\n**پررنگ** و `کد`\n\n- یک\n- دو");
        $this->assertStringContainsString('<h2>تیتر</h2>', $html);
        $this->assertStringContainsString('<strong>پررنگ</strong>', $html);
        $this->assertStringContainsString('<li>یک</li>', $html);

        $this->assertSame('', Markdown::toHtml(null));
        $this->assertSame(1, Markdown::readingMinutes('<p>کوتاه</p>'));
        $this->assertSame(3, Markdown::readingMinutes('<p>'.str_repeat('کلمه ', 600).'</p>'));
    }
}
