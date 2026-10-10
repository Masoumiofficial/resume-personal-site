<?php

namespace App\Support;

use App\Models\Media;
use App\Models\Setting;
use Illuminate\Support\Str;

/**
 * Per-page SEO data rendered by partials/seo.blade.php: title, description,
 * canonical, robots, Open Graph and JSON-LD.
 */
final class Seo
{
    public ?string $title = null;

    public ?string $description = null;

    public ?string $canonical = null;

    public bool $noindex = false;

    public string $type = 'website';

    public ?string $image = null;

    public ?string $publishedTime = null;

    public ?string $modifiedTime = null;

    /** @var list<array<string, mixed>> */
    public array $schema = [];

    public static function make(?string $title = null, ?string $description = null): self
    {
        $seo = new self;
        $seo->title = $title;
        $seo->description = $description;

        return $seo;
    }

    /** Copy SEO fields from an article/project/service (meta_title, meta_description, og_image, canonical_url, noindex). */
    public function fromModel(object $model, ?string $fallbackTitle = null, ?string $fallbackDescription = null): self
    {
        $this->title = $model->meta_title ?: $fallbackTitle;
        $this->description = $model->meta_description ?: $fallbackDescription;
        $this->canonical = $model->canonical_url ?: $this->canonical;
        $this->noindex = (bool) $model->noindex;

        return $this;
    }

    public function canonical(string $url): self
    {
        $this->canonical ??= $url;

        return $this;
    }

    public function image(?Media $media): self
    {
        if ($media && $media->isImage()) {
            $this->image = url($media->urlFor(1200));
        }

        return $this;
    }

    public function schema(array $data): self
    {
        $this->schema[] = ['@context' => 'https://schema.org'] + $data;

        return $this;
    }

    /** «Page title | Site title»; the home page uses the site title (or its own) alone. */
    public function fullTitle(): string
    {
        $site = (string) Setting::get('site_title');
        if ($this->title === null || $this->title === '' || $this->title === $site) {
            return $site;
        }

        return $this->title.' '.Setting::get('title_separator').' '.$site;
    }

    public function metaDescription(): ?string
    {
        $text = $this->description ?: Setting::get('default_meta_description');

        return $text ? Str::limit(trim((string) preg_replace('/\s+/u', ' ', strip_tags($text))), 159, '…') : null;
    }

    /** Search engines are kept out of non-production copies and when indexing is switched off. */
    public function robots(): string
    {
        $indexable = app()->isProduction() && Setting::get('search_indexing') && ! $this->noindex;

        return $indexable ? 'index, follow, max-image-preview:large' : 'noindex, nofollow';
    }
}
