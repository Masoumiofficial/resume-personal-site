<?php

namespace App\Http\Controllers\Admin;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Tag;
use App\Rules\NotReservedSlug;
use App\Rules\Slug;
use App\Support\Jalali;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ArticleController extends ResourceController
{
    public const TABS = [['admin.articles', 'مقالات'], ['admin.article-categories', 'دسته‌ها'], ['admin.tags', 'برچسب‌ها']];

    protected function model(): string
    {
        return Article::class;
    }

    protected function routeBase(): string
    {
        return 'admin.articles';
    }

    protected function labels(): array
    {
        return ['plural' => 'مقالات', 'singular' => 'مقاله', 'create' => 'مقالهٔ جدید'];
    }

    protected function tabs(): array
    {
        return self::TABS;
    }

    protected function searchable(): array
    {
        return ['title', 'slug'];
    }

    protected function filters(): array
    {
        return ['status' => ['label' => 'وضعیت', 'options' => Article::STATUSES]];
    }

    protected function columns(): array
    {
        return [
            ['label' => 'عنوان', 'value' => fn (Article $a) => $a->title, 'primary' => true],
            ['label' => 'نامک', 'value' => fn (Article $a) => $a->slug, 'ltr' => true, 'muted' => true],
            ['label' => 'انتشار', 'value' => fn (Article $a) => $a->published_at ? Jalali::format($a->published_at, 'Y/m/d') : '—', 'muted' => true],
            ['label' => 'وضعیت', 'value' => fn (Article $a) => self::stateBadge($a), 'badge' => true],
        ];
    }

    protected function fields(Model $item): array
    {
        return array_merge([
            ['name' => 'title', 'label' => 'عنوان', 'type' => 'text', 'required' => true, 'wide' => true],
            ['name' => 'slug', 'label' => 'نامک', 'type' => 'text', 'required' => true, 'dir' => 'ltr', 'hint' => 'نشانی مقاله: /نامک/ (در ریشهٔ سایت، مثل وردپرس).',
                'rules' => ['string', 'max:190', new Slug, new NotReservedSlug, Rule::unique('articles', 'slug')->ignore($item->getKey())]],
            ['name' => 'cover_id', 'label' => 'تصویر شاخص', 'type' => 'media', 'accept' => 'image'],
            ['name' => 'excerpt', 'label' => 'خلاصه', 'type' => 'textarea', 'rows' => 3, 'rules' => ['string', 'max:600']],
            ['name' => 'body', 'label' => 'متن', 'type' => 'markdown', 'rows' => 22],
            ['name' => 'categories', 'label' => 'دسته‌ها', 'type' => 'checklist', 'empty' => 'هنوز دسته‌ای ساخته نشده.',
                'options' => ArticleCategory::query()->ordered()->pluck('name', 'id')->all()],
            ['name' => 'tags', 'label' => 'برچسب‌ها', 'type' => 'checklist', 'empty' => 'هنوز برچسبی ساخته نشده.',
                'options' => Tag::query()->orderBy('name')->pluck('name', 'id')->all()],
            ['name' => 'updated_content_at', 'label' => 'تاریخ «به‌روزرسانی محتوا»', 'type' => 'jdatetime', 'hint' => 'برای خواننده نمایش داده می‌شود؛ فقط هنگام تغییر واقعی محتوا تنظیم کنید.'],
        ], $this->publishingFields(), $this->seoFields());
    }

    protected function afterSave(Model $item, array $data, Request $request): void
    {
        $item->categories()->sync($data['categories'] ?? []);
        $item->tags()->sync($data['tags'] ?? []);
    }

    protected function aside(Model $item): ?array
    {
        $lines = $this->publicationAside($item);
        if ($item->reading_minutes) {
            $lines[] = 'زمان مطالعه: حدود '.Jalali::digits($item->reading_minutes).' دقیقه';
        }
        if ($item->legacy_wp_id) {
            $lines[] = 'واردشده از وردپرس (شناسهٔ '.Jalali::digits($item->legacy_wp_id).')';
        }

        return $lines;
    }
}
