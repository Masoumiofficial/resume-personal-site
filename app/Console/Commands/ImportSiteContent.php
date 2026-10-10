<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Certification;
use App\Models\Experience;
use App\Models\Profile;
use App\Models\Service;
use App\Models\ServiceGroup;
use App\Models\Skill;
use App\Support\Activity;
use App\Support\JalaliDate;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * One-time (but safely repeatable) import of the old site's content from
 * database/content/sajadmasoumi-com.php, plus the migrated blog posts in
 * database/content/articles/*.md (front matter + Markdown body).
 *
 * Idempotent: rows are matched by a natural key (organization, title, slug,
 * name) and only created when missing. Existing rows are never changed, so
 * edits made in the admin panel survive a second run.
 */
class ImportSiteContent extends Command
{
    protected $signature = 'app:import-site-content {--file= : Path to the content file} {--articles= : Directory of article Markdown files} {--dry-run : Show what would be created without writing}';

    protected $description = 'Import the profile, resume, services, categories and articles of the old WordPress site (create-only, idempotent)';

    /** @var array<string, array{0:int,1:int}> section => [created, existing] */
    private array $counts = [];

    public function handle(): int
    {
        $this->counts = [];
        $file = $this->option('file') ?: database_path('content/sajadmasoumi-com.php');
        if (! is_file($file)) {
            $this->error("Content file not found: {$file}");

            return self::FAILURE;
        }
        $data = require $file;
        $dry = (bool) $this->option('dry-run');

        DB::beginTransaction();
        try {
            $this->profile($data['profile'] ?? null);
            $this->experiences($data['experiences'] ?? []);
            $this->certifications($data['certifications'] ?? []);
            $this->skills($data['skills'] ?? []);
            $this->services($data['service_groups'] ?? []);
            $this->categories($data['article_categories'] ?? []);
            $this->articles($this->option('articles') ?: database_path('content/articles'));
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Import failed, nothing was written: '.$e->getMessage());

            return self::FAILURE;
        }

        $created = array_sum(array_column($this->counts, 0));
        if ($dry) {
            DB::rollBack();
        } else {
            if ($created > 0) {
                Activity::log('content.imported', null, ['created' => $created]);
            }
            DB::commit();
        }

        $this->table(['Section', 'Created', 'Already present'], collect($this->counts)
            ->map(fn ($c, $section) => [$section, $c[0], $c[1]])->values()->all());
        $this->info(($dry ? '[dry run] would create ' : 'Created ')."{$created} row(s). Existing rows were left unchanged.");
        if ($created > 0) {
            $this->line('Review hidden skills/courses and draft services in the admin panel before publishing.');
        }

        return self::SUCCESS;
    }

    private function count(string $section, bool $created): void
    {
        $this->counts[$section] ??= [0, 0];
        $this->counts[$section][$created ? 0 : 1]++;
    }

    private function profile(?array $profile): void
    {
        if ($profile === null) {
            return;
        }
        $exists = Profile::query()->exists();
        if (! $exists) {
            Profile::create($profile);
        }
        $this->count('profile', ! $exists);
    }

    private function experiences(array $items): void
    {
        foreach ($items as $i => $item) {
            [$start, $precision] = JalaliDate::parse($item['start'] ?? null) ?? [null, 'year'];
            $row = Experience::query()->firstOrCreate(['organization' => $item['organization']], [
                'organization_url' => $item['organization_url'] ?? null,
                'role' => $item['role'],
                'employment_type' => $item['employment_type'] ?? null,
                'start_date' => $start?->toDateString(),
                'date_precision' => $precision,
                'summary' => $item['summary'] ?? null,
                'is_visible' => $item['is_visible'] ?? false,
                'sort_order' => $i + 1,
            ]);
            $this->count('experiences', $row->wasRecentlyCreated);
        }
    }

    private function certifications(array $items): void
    {
        foreach ($items as $i => $item) {
            [$start, $precision] = JalaliDate::parse($item['start'] ?? null) ?? [null, 'month'];
            $end = JalaliDate::parse($item['end'] ?? null)[0] ?? null;
            $row = Certification::query()->firstOrCreate(['title' => $item['title'], 'issuer' => $item['issuer'] ?? null], [
                'kind' => $item['kind'] ?? 'course',
                'issued_at' => $start?->toDateString(),
                'end_at' => $end?->toDateString(),
                'date_precision' => $precision,
                'is_visible' => $item['is_visible'] ?? false,
                'sort_order' => $i + 1,
            ]);
            $this->count('certifications', $row->wasRecentlyCreated);
        }
    }

    private function skills(array $groups): void
    {
        foreach ($groups as $group => $items) {
            if (! array_key_exists($group, Skill::GROUPS)) {
                throw new \InvalidArgumentException("Unknown skill group [{$group}].");
            }
            foreach (array_values($items) as $i => [$name, $visible]) {
                $row = Skill::query()->firstOrCreate(['name' => $name, 'group' => $group], [
                    'is_visible' => $visible,
                    'sort_order' => $i + 1,
                ]);
                $this->count('skills', $row->wasRecentlyCreated);
            }
        }
    }

    private function services(array $groups): void
    {
        foreach ($groups as $g => $groupData) {
            $group = ServiceGroup::query()->firstOrCreate(['slug' => $groupData['slug']], [
                'title' => $groupData['title'],
                'sort_order' => $g + 1,
            ]);
            $this->count('service groups', $group->wasRecentlyCreated);

            foreach ($groupData['services'] ?? [] as $i => $item) {
                if (! array_key_exists($item['unit'], Service::UNITS)) {
                    throw new \InvalidArgumentException("Unknown price unit [{$item['unit']}].");
                }
                $status = $item['status'] ?? 'published';
                $row = Service::query()->firstOrCreate(['service_group_id' => $group->id, 'title' => $item['title']], [
                    'slug' => $item['slug'] ?? null,
                    'has_page' => $item['has_page'] ?? false,
                    'price_from' => $item['price'] ?? null,
                    'price_unit' => $item['unit'],
                    'cta_type' => 'contact',
                    'status' => $status,
                    'published_at' => $status === 'published' ? now() : null,
                    'is_visible' => true,
                    'sort_order' => $i + 1,
                ]);
                $this->count('services', $row->wasRecentlyCreated);
            }
        }
    }

    private function categories(array $items, ?int $parentId = null): void
    {
        foreach ($items as $i => $item) {
            $row = ArticleCategory::query()->firstOrCreate(['slug' => $item['slug']], [
                'name' => $item['name'],
                'parent_id' => $parentId,
                'sort_order' => $i + 1,
            ]);
            $this->count('article categories', $row->wasRecentlyCreated);
            $this->categories($item['children'] ?? [], $row->id);
        }
    }

    /**
     * Articles from Markdown files with a simple «key: value» front matter:
     * title, slug, published_at («Y-m-d H:i», display timezone), categories
     * (comma-separated slugs), meta_title, meta_description, excerpt, status.
     */
    private function articles(string $directory): void
    {
        $files = is_dir($directory) ? glob(rtrim($directory, '/').'/*.md') : [];
        sort($files);

        foreach ($files as $file) {
            [$meta, $body] = $this->parseFrontMatter((string) file_get_contents($file), basename($file));

            $existing = Article::query()->where('slug', $meta['slug'])->exists();
            if ($existing) {
                $this->count('articles', false);

                continue;
            }

            $status = ($meta['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
            $publishedAt = isset($meta['published_at'])
                ? Carbon::createFromFormat('Y-m-d H:i', $meta['published_at'], (string) config('app.display_timezone', 'Asia/Tehran'))->utc()
                : null;
            if ($status === 'published' && $publishedAt === null) {
                throw new RuntimeException(basename($file).': published articles need published_at');
            }

            $article = Article::query()->create([
                'title' => $meta['title'],
                'slug' => $meta['slug'],
                'excerpt' => $meta['excerpt'] ?? null,
                'body' => $body,
                'status' => $status,
                'published_at' => $publishedAt,
                'meta_title' => $meta['meta_title'] ?? null,
                'meta_description' => $meta['meta_description'] ?? null,
            ]);

            $slugs = array_filter(array_map('trim', explode(',', $meta['categories'] ?? '')));
            $categories = ArticleCategory::query()->whereIn('slug', $slugs)->pluck('id', 'slug');
            if ($missing = array_diff($slugs, $categories->keys()->all())) {
                throw new RuntimeException(basename($file).': unknown categories '.implode(', ', $missing));
            }
            $article->categories()->sync($categories->values()->all());

            $this->count('articles', true);
        }
    }

    /** @return array{0: array<string, string>, 1: string} */
    private function parseFrontMatter(string $content, string $name): array
    {
        $content = str_replace("\r\n", "\n", $content);
        if (! preg_match('/\A---\n(.*?)\n---\n(.*)\z/s', $content, $m)) {
            throw new RuntimeException("{$name}: missing front matter");
        }

        $meta = [];
        foreach (explode("\n", $m[1]) as $line) {
            if (trim($line) === '') {
                continue;
            }
            [$key, $value] = array_pad(explode(':', $line, 2), 2, null);
            if ($value === null) {
                throw new RuntimeException("{$name}: invalid front matter line «{$line}»");
            }
            $meta[trim($key)] = trim($value);
        }

        foreach (['title', 'slug'] as $required) {
            if (empty($meta[$required])) {
                throw new RuntimeException("{$name}: front matter needs {$required}");
            }
        }

        return [$meta, trim($m[2])."\n"];
    }
}
