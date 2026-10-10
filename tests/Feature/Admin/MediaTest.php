<?php

namespace Tests\Feature\Admin;

use App\Models\Media;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('media');
    }

    private function png(int $w = 40, int $h = 30): UploadedFile
    {
        $img = imagecreatetruecolor($w, $h);
        imagefill($img, 0, 0, imagecolorallocate($img, 29, 78, 216));
        ob_start();
        imagepng($img);
        $bytes = ob_get_clean();

        return UploadedFile::fake()->createWithContent('Photo Name.PNG', $bytes);
    }

    public function test_image_upload_is_stored_with_a_random_name_and_metadata(): void
    {
        $this->actingAs(User::factory()->create())->post('/admin/media', ['files' => [$this->png()], 'alt' => 'لوگو'])
            ->assertRedirect()->assertSessionHasNoErrors();

        $media = Media::query()->sole();
        $this->assertSame('image/png', $media->mime);
        $this->assertSame([40, 30], [$media->width, $media->height]);
        $this->assertSame('لوگو', $media->alt);
        $this->assertSame('Photo Name.PNG', $media->original_name);
        $this->assertMatchesRegularExpression('#^\d{4}/\d{2}/[A-Za-z0-9]{26,}\.png$#', $media->path); // never the client file name
        Storage::disk('media')->assertExists($media->path);
        $this->assertDatabaseHas('activity_logs', ['event' => 'media.uploaded']);
    }

    public function test_identical_upload_is_deduplicated(): void
    {
        $admin = User::factory()->create();
        $file = $this->png();
        $this->actingAs($admin)->post('/admin/media', ['files' => [$file]]);
        $this->post('/admin/media', ['files' => [$file]])->assertSessionHas('flash');

        $this->assertSame(1, Media::query()->count());
    }

    public function test_dangerous_files_are_rejected(): void
    {
        $admin = User::factory()->create();
        $cases = [
            'php disguised as jpg' => UploadedFile::fake()->createWithContent('shell.jpg', "<?php system(\$_GET['c']); ?>"),
            'php file' => UploadedFile::fake()->createWithContent('shell.php', '<?php echo 1; ?>'),
            'svg with script' => UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            'html' => UploadedFile::fake()->createWithContent('page.html', '<html><script>alert(1)</script></html>'),
            'double extension' => UploadedFile::fake()->createWithContent('a.php.png', '<?php echo 1; ?>'),
        ];
        foreach ($cases as $label => $file) {
            $this->actingAs($admin)->post('/admin/media', ['files' => [$file]])->assertRedirect();
            // Rejected by validation, or — when the client-declared type lies (fake test files report the
            // extension's MIME) — by MediaStore's own content sniffing. Either way nothing is stored.
            $this->assertTrue(session()->has('errors') || str_contains((string) session('flash_error'), 'نوع فایل مجاز نیست'), $label);
            $this->assertSame(0, Media::query()->count(), $label);
        }
        $this->assertSame([], Storage::disk('media')->allFiles());
    }

    public function test_real_php_file_named_jpg_fails_content_sniffing(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'up');
        file_put_contents($path, "<?php system(\$_GET['c']); ?>");
        $file = new UploadedFile($path, 'shell.jpg', 'image/jpeg', null, true); // client claims image/jpeg

        $this->actingAs(User::factory()->create())->post('/admin/media', ['files' => [$file]])->assertSessionHasErrors('files.0');
        $this->assertSame(0, Media::query()->count());
    }

    public function test_jpeg_metadata_is_removed_by_re_encoding(): void
    {
        $img = imagecreatetruecolor(20, 20);
        ob_start();
        imagejpeg($img);
        $jpeg = ob_get_clean();
        // Insert a comment segment (COM, 0xFFFE) carrying "private" metadata right after SOI.
        $secret = 'GPS 29.59N 52.58E secret';
        $jpeg = substr($jpeg, 0, 2)."\xFF\xFE".pack('n', strlen($secret) + 2).$secret.substr($jpeg, 2);

        $this->actingAs(User::factory()->create())
            ->post('/admin/media', ['files' => [UploadedFile::fake()->createWithContent('cam.jpg', $jpeg)]])
            ->assertSessionHasNoErrors();

        $stored = Storage::disk('media')->get(Media::query()->sole()->path);
        $this->assertStringStartsWith("\xFF\xD8", $stored);
        $this->assertStringNotContainsString($secret, $stored);
    }

    public function test_oversized_upload_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/admin/media', ['files' => [UploadedFile::fake()->create('big.pdf', 10241, 'application/pdf')]])
            ->assertSessionHasErrors('files.0');
    }

    public function test_media_in_use_needs_confirmation_before_delete(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin)->post('/admin/media', ['files' => [$this->png()]]);
        $media = Media::query()->sole();
        Profile::create(['display_name' => 'x', 'avatar_id' => $media->id]);

        $this->get("/admin/media/{$media->id}/edit")->assertOk()->assertSee('پروفایل');
        $this->delete("/admin/media/{$media->id}")->assertSessionHas('flash_error');
        $this->assertModelExists($media);

        $this->delete("/admin/media/{$media->id}", ['confirm_in_use' => '1'])->assertRedirect('/admin/media');
        $this->assertModelMissing($media);
        $this->assertNull(Profile::query()->value('avatar_id'));
        Storage::disk('media')->assertMissing($media->path);
    }
}
