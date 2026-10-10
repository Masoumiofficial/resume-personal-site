<?php

namespace Tests\Feature\Site;

use App\Support\BodyImages;
use Tests\TestCase;

class BodyImagesTest extends TestCase
{
    public function test_local_images_get_their_real_size_and_lazy_loading(): void
    {
        $dir = public_path('media/tests-body-images');
        @mkdir($dir, 0775, true);
        $image = imagecreatetruecolor(40, 25);
        imagepng($image, $dir.'/تصویر نمونه.png');

        try {
            $html = BodyImages::prepare('<p><img src="/media/tests-body-images/%D8%AA%D8%B5%D9%88%DB%8C%D8%B1%20%D9%86%D9%85%D9%88%D9%86%D9%87.png" alt="a"></p>'
                .'<img src="/wp-content/uploads/missing.png" alt="b"><img src="https://example.com/x.png" alt="c">'
                .'<img src="/../.env" alt="d"><img loading="eager" width="10" src="/media/tests-body-images/x.png" alt="e">');
        } finally {
            @unlink($dir.'/تصویر نمونه.png');
            @rmdir($dir);
        }

        $this->assertStringContainsString('<img loading="lazy" decoding="async" width="40" height="25" src="/media/tests-body-images/', $html);
        $this->assertStringContainsString('<img loading="lazy" decoding="async" src="/wp-content/uploads/missing.png" alt="b">', $html);
        $this->assertStringContainsString('<img loading="lazy" decoding="async" src="https://example.com/x.png" alt="c">', $html);
        $this->assertStringContainsString('<img loading="lazy" decoding="async" src="/../.env" alt="d">', $html);   // outside public/: untouched
        $this->assertStringContainsString('<img decoding="async" loading="eager" width="10"', $html);
    }
}
