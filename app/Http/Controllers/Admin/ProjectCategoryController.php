<?php

namespace App\Http\Controllers\Admin;

use App\Models\ProjectCategory;
use App\Rules\Slug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class ProjectCategoryController extends ResourceController
{
    protected function model(): string
    {
        return ProjectCategory::class;
    }

    protected function routeBase(): string
    {
        return 'admin.project-categories';
    }

    protected function labels(): array
    {
        return ['plural' => 'دسته‌های نمونه‌کار', 'singular' => 'دسته', 'create' => 'دستهٔ جدید'];
    }

    protected function tabs(): array
    {
        return [['admin.projects', 'نمونه‌کارها'], ['admin.project-categories', 'دسته‌ها']];
    }

    protected function columns(): array
    {
        return [
            ['label' => 'نام', 'value' => fn (ProjectCategory $c) => $c->name, 'primary' => true],
            ['label' => 'نامک', 'value' => fn (ProjectCategory $c) => $c->slug, 'ltr' => true, 'muted' => true],
        ];
    }

    protected function fields(Model $item): array
    {
        return [
            ['name' => 'name', 'label' => 'نام', 'type' => 'text', 'required' => true],
            ['name' => 'slug', 'label' => 'نامک', 'type' => 'text', 'required' => true, 'dir' => 'ltr',
                'rules' => ['string', 'max:190', new Slug, Rule::unique('project_categories', 'slug')->ignore($item->getKey())]],
        ];
    }
}
