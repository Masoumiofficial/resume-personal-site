<?php

namespace App\Services;

use App\Models\Media;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * The only way files enter the media library.
 *
 * - Type is detected from the file content (finfo / getimagesize), never from
 *   the client's extension or Content-Type.
 * - Images are decoded and re-encoded with GD: EXIF/GPS metadata and anything
 *   appended to the image (polyglot payloads) are dropped.
 * - Stored under a random ULID name; the original name is display-only.
 * - SVG is rejected (script inside XML); PDFs must start with %PDF-.
 */
class MediaStore
{
    public const IMAGE_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    public const DOCUMENT_TYPES = ['application/pdf' => 'pdf'];

    public const VARIANT_WIDTHS = [480, 960, 1600];

    /** Decompression-bomb guard, checked from the header before decoding. */
    public const MAX_PIXELS = 40_000_000;

    public const MAX_KB = 10240;

    /** @return array{0: Media, 1: bool} [media, wasDuplicate] */
    public function store(UploadedFile $file, ?int $userId = null, ?string $alt = null): array
    {
        $source = $file->getRealPath();
        if ($source === false || ! is_readable($source)) {
            throw new RuntimeException('فایل قابل خواندن نیست.');
        }

        $sha = hash_file('sha256', $source);
        if ($existing = Media::query()->where('sha256', $sha)->first()) {
            return [$existing, true];
        }

        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($source);
        $disk = Storage::disk('media');
        $base = now()->format('Y/m').'/'.Str::lower((string) Str::ulid());

        if (isset(self::IMAGE_TYPES[$mime])) {
            [$bytes, $width, $height, $variants] = $this->processImage($source, $mime, $base);
            $path = $base.'.'.self::IMAGE_TYPES[$mime];
        } elseif (isset(self::DOCUMENT_TYPES[$mime])) {
            $bytes = (string) file_get_contents($source);
            if (! str_starts_with($bytes, '%PDF-')) {
                throw new RuntimeException('فایل PDF معتبر نیست.');
            }
            [$width, $height, $variants] = [null, null, []];
            $path = $base.'.pdf';
        } else {
            throw new RuntimeException('نوع فایل مجاز نیست. فقط JPEG، PNG، WebP و PDF پذیرفته می‌شوند.');
        }

        $disk->put($path, $bytes);

        try {
            $media = Media::create([
                'disk' => 'media',
                'path' => $path,
                'original_name' => Str::limit($this->cleanName($file->getClientOriginalName()), 180, ''),
                'mime' => $mime,
                'size' => strlen($bytes),
                'width' => $width,
                'height' => $height,
                'sha256' => $sha,
                'alt' => $alt,
                'variants' => $variants ?: null,
                'uploaded_by' => $userId,
            ]);
        } catch (\Throwable $e) {
            $disk->delete(array_merge([$path], array_column($variants, 'path')));
            throw $e;
        }

        return [$media, false];
    }

    public function delete(Media $media): void
    {
        $paths = array_merge([$media->path], array_column($media->variants ?? [], 'path'));
        $media->delete();
        Storage::disk($media->disk)->delete($paths);
    }

    /**
     * Where a media row is still referenced (FK columns + Markdown bodies).
     *
     * @return array<string, int> "table.column" => count
     */
    public function usages(Media $media): array
    {
        $found = [];
        foreach (Media::REFERENCES as [$table, $column]) {
            $n = DB::table($table)->where($column, $media->id)->count();
            if ($n > 0) {
                $found["{$table}.{$column}"] = $n;
            }
        }
        foreach ([['articles', 'body'], ['projects', 'body'], ['services', 'body'], ['profiles', 'long_bio']] as [$table, $column]) {
            $n = DB::table($table)->where($column, 'like', '%'.$media->path.'%')->count();
            if ($n > 0) {
                $found["{$table}.{$column}"] = $n;
            }
        }

        return $found;
    }

    /** @return array{0: string, 1: int, 2: int, 3: list<array{width:int,height:int,path:string}>} */
    private function processImage(string $source, string $mime, string $base): array
    {
        $info = @getimagesize($source);
        if ($info === false || ($info['mime'] ?? null) !== $mime) {
            throw new RuntimeException('تصویر معتبر نیست.');
        }
        [$width, $height] = $info;
        if ($width < 1 || $height < 1 || $width * $height > self::MAX_PIXELS) {
            throw new RuntimeException('ابعاد تصویر بیش از حد مجاز است (حداکثر ۴۰ مگاپیکسل).');
        }

        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($source),
            'image/png' => @imagecreatefrompng($source),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($source) : false,
        };
        if (! $image instanceof GdImage) {
            throw new RuntimeException('تصویر قابل پردازش نیست.');
        }

        if ($mime === 'image/jpeg') {
            $image = $this->applyExifOrientation($image, $source);
            [$width, $height] = [imagesx($image), imagesy($image)];
        }

        $bytes = $this->encode($image, $mime);
        $variants = [];
        if ($this->canWriteWebp()) {
            foreach (self::VARIANT_WIDTHS as $w) {
                if ($w >= $width) {
                    break;
                }
                $scaled = imagescale($image, $w, -1, IMG_BICUBIC);
                if ($scaled instanceof GdImage) {
                    $path = "{$base}-{$w}.webp";
                    Storage::disk('media')->put($path, $this->encode($scaled, 'image/webp'));
                    $variants[] = ['width' => $w, 'height' => imagesy($scaled), 'path' => $path];
                }
            }
        }

        return [$bytes, $width, $height, $variants];
    }

    private function encode(GdImage $image, string $mime): string
    {
        ob_start();
        $ok = match ($mime) {
            'image/jpeg' => imagejpeg($image, null, 85),
            'image/png' => (function () use ($image) {
                imagesavealpha($image, true);

                return imagepng($image, null, 6);
            })(),
            'image/webp' => (function () use ($image) {
                imagesavealpha($image, true);

                return imagewebp($image, null, 80);
            })(),
        };
        $bytes = (string) ob_get_clean();
        if (! $ok || $bytes === '') {
            throw new RuntimeException('ذخیرهٔ تصویر ناموفق بود.');
        }

        return $bytes;
    }

    private function canWriteWebp(): bool
    {
        return function_exists('imagewebp') && (imagetypes() & IMG_WEBP);
    }

    /** Re-encoding drops EXIF, so apply the camera rotation first (needs ext-exif; skipped otherwise). */
    private function applyExifOrientation(GdImage $image, string $source): GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }
        $orientation = (int) (@exif_read_data($source)['Orientation'] ?? 1);
        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        return $rotated instanceof GdImage ? $rotated : $image;
    }

    private function cleanName(string $name): string
    {
        $name = preg_replace('/[\x00-\x1F\x7F\/\\\\]+/u', '', $name) ?? '';

        return $name !== '' ? $name : 'file';
    }
}
