<?php

namespace App\Http\Controllers\Admin;

use App\Models\Tag;
use App\Rules\Slug;
use App\Support\Jalali;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class TagController extends ResourceController
{
    protected function model(): string
    {
        return Tag::class;
    }

    protected function routeBase(): string
    {
        return 'admin.tags';
    }

    protected function labels(): array
    {
        return ['plural' => 'برچسب‌ها', 'singular' => 'برچسب', 'create' => 'برچسب جدید'];
    }

    protected function tabs(): array
    {
        return ArticleController::TABS;
    }

    protected function searchable(): array
    {
        return ['name', 'slug'];
    }

    protected function baseQuery(): Builder
    {
        return Tag::query()->withCount('articles');
    }

    protected function columns(): array
    {
        return [
            ['label' => 'نام', 'value' => fn (Tag $t) => $t->name, 'primary' => true],
            ['label' => 'نامک', 'value' => fn (Tag $t) => $t->slug, 'muted' => true],
            ['label' => 'مقاله', 'value' => fn (Tag $t) => Jalali::digits($t->articles_count)],
        ];
    }

    protected function fields(Model $item): array
    {
        return [
            ['name' => 'name', 'label' => 'نام', 'type' => 'text', 'required' => true],
            ['name' => 'slug', 'label' => 'نامک', 'type' => 'text', 'required' => true, 'hint' => 'نشانی: /blog/tag/نامک/ — حروف فارسی مجاز است.',
                'rules' => ['string', 'max:190', new Slug, Rule::unique('tags', 'slug')->ignore($item->getKey())]],
        ];
    }
}
