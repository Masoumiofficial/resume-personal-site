<?php

namespace Tests\Unit;

use App\Support\Link;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LinkTest extends TestCase
{
    public static function urls(): array
    {
        return [
            'page' => ['https://example.com/about', 'https://example.com/about/'],
            'already slashed' => ['https://example.com/about/', 'https://example.com/about/'],
            'root' => ['https://example.com', 'https://example.com/'],
            'file' => ['https://example.com/sitemap.xml', 'https://example.com/sitemap.xml'],
            'query kept' => ['https://example.com/contact?subject=a/b', 'https://example.com/contact/?subject=a/b'],
            'encoded persian' => ['https://example.com/%D9%88%D8%B1%D8%AF', 'https://example.com/%D9%88%D8%B1%D8%AF/'],
        ];
    }

    #[DataProvider('urls')]
    public function test_slash(string $input, string $expected): void
    {
        $this->assertSame($expected, Link::slash($input));
    }
}
