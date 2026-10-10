<?php

namespace App\Http\Controllers\Admin;

use App\Models\ArticleCategory;
use App\Rules\Slug;
use App\Support\Jalali;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ArticleCategoryController extends ResourceController
{
    protected function model(): string
    {
        return ArticleCategory::class;
    }

    protected function routeBase(): string
    {
        return 'admin.article-categories';
    }

    protected function labels(): array
    {
        return ['plural' => 'دسته‌های مقاله', 'singular' => 'دسته', 'create' => 'دستهٔ جدید'];
    }

    protected function tabs(): array
    {
        return ArticleController::TABS;
    }

    protected function baseQuery(): Builder
    {
        return ArticleCategory::query()->withCount('articles');
    }

    protected function with(): array
    {
        return ['parent'];
    }

    protected function columns(): array
    {
        return [
            ['label' => 'نام', 'value' => fn (ArticleCategory $c) => $c->name, 'primary' => true],
            ['label' => 'والد', 'value' => fn (ArticleCategory $c) => $c->parent?->name ?? '—', 'muted' => true],
            ['label' => 'نامک', 'value' => fn (ArticleCategory $c) => $c->slug, 'ltr' => true, 'muted' => true],
            ['label' => 'مقاله', 'value' => fn (ArticleCategory $c) => Jalali::digits($c->articles_count)],
        ];
    }

    protected function fields(Model $item): array
    {
        return [
            ['name' => 'name', 'label' => 'نام', 'type' => 'text', 'required' => true],
            ['name' => 'slug', 'label' => 'نامک', 'type' => 'text', 'required' => true, 'dir' => 'ltr', 'hint' => 'نشانی: /blog/category/نامک/',
                'rules' => ['string', 'max:190', new Slug, Rule::unique('article_categories', 'slug')->ignore($item->getKey())]],
            ['name' => 'parent_id', 'label' => 'دستهٔ والد', 'type' => 'select', 'rules' => ['integer', 'exists:article_categories,id'],
                // One level only (IA): parents are top-level categories other than this one.
                'options' => ArticleCategory::query()->whereNull('parent_id')->when($item->exists, fn ($q) => $q->whereKeyNot($item->getKey()))->ordered()->pluck('name', 'id')->all()],
            ['name' => 'description', 'label' => 'توضیح', 'type' => 'textarea'],
        ];
    }

    protected function validateMore(Validator $validator, array $data, Model $item): void
    {
        if (! empty($data['parent_id']) && $item->exists && ArticleCategory::query()->where('parent_id', $item->getKey())->exists()) {
            $validator->errors()->add('parent_id', 'این دسته زیردسته دارد و خودش نمی‌تواند زیردسته شود (فقط یک سطح).');
        }
    }
}
