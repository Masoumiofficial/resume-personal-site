<?php

namespace App\Http\Controllers\Admin;

use App\Models\Project;
use App\Models\ProjectCategory;
use App\Rules\Slug;
use App\Support\Jalali;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends ResourceController
{
    protected function model(): string
    {
        return Project::class;
    }

    protected function routeBase(): string
    {
        return 'admin.projects';
    }

    protected function labels(): array
    {
        return ['plural' => 'نمونه‌کارها', 'singular' => 'نمونه‌کار', 'create' => 'نمونه‌کار جدید'];
    }

    protected function tabs(): array
    {
        return [['admin.projects', 'نمونه‌کارها'], ['admin.project-categories', 'دسته‌ها']];
    }

    protected function searchable(): array
    {
        return ['title', 'slug'];
    }

    protected function filters(): array
    {
        return ['status' => ['label' => 'وضعیت', 'options' => Project::STATUSES]];
    }

    protected function columns(): array
    {
        return [
            ['label' => 'عنوان', 'value' => fn (Project $p) => $p->title, 'primary' => true],
            ['label' => 'نامک', 'value' => fn (Project $p) => $p->slug, 'ltr' => true, 'muted' => true],
            ['label' => 'سال', 'value' => fn (Project $p) => $p->year ? Jalali::digits($p->year) : '—'],
            ['label' => 'وضعیت', 'value' => fn (Project $p) => self::stateBadge($p), 'badge' => true],
        ];
    }

    protected function fields(Model $item): array
    {
        return array_merge([
            ['name' => 'title', 'label' => 'عنوان', 'type' => 'text', 'required' => true],
            ['name' => 'slug', 'label' => 'نامک', 'type' => 'text', 'required' => true, 'dir' => 'ltr', 'hint' => 'نشانی: /works/نامک/',
                'rules' => ['string', 'max:190', new Slug, Rule::unique('projects', 'slug')->ignore($item->getKey())]],
            ['name' => 'summary', 'label' => 'خلاصه', 'type' => 'textarea', 'rows' => 2, 'rules' => ['string', 'max:500']],
            ['name' => 'role', 'label' => 'نقش من', 'type' => 'text'],
            ['name' => 'year', 'label' => 'سال (شمسی)', 'type' => 'number', 'rules' => ['integer', 'min:1300', 'max:1500']],
            ['name' => 'client_name', 'label' => 'نام کارفرما', 'type' => 'text', 'hint' => 'فقط با اجازهٔ کارفرما.'],
            ['name' => 'project_url', 'label' => 'لینک پروژه', 'type' => 'url'],
            ['name' => 'problem', 'label' => 'مسئله', 'type' => 'textarea'],
            ['name' => 'solution', 'label' => 'راه‌حل', 'type' => 'textarea'],
            ['name' => 'technologies', 'label' => 'فناوری‌ها', 'type' => 'lines'],
            ['name' => 'outcome', 'label' => 'نتیجه', 'type' => 'textarea', 'hint' => 'فقط وقتی در سایت نمایش داده می‌شود که مدرک داشته باشد یا تأیید شود.'],
            ['name' => 'outcome_evidence_url', 'label' => 'لینک مدرک نتیجه', 'type' => 'url'],
            ['name' => 'outcome_verified', 'label' => 'نتیجه را خودم تأیید می‌کنم (قابل اثبات است)', 'type' => 'checkbox'],
            ['name' => 'body', 'label' => 'شرح کامل', 'type' => 'markdown', 'rows' => 12],
            ['name' => 'cover_id', 'label' => 'تصویر شاخص', 'type' => 'media', 'accept' => 'image'],
            ['name' => 'categories', 'label' => 'دسته‌ها', 'type' => 'checklist', 'empty' => 'هنوز دسته‌ای ساخته نشده.',
                'options' => ProjectCategory::query()->ordered()->pluck('name', 'id')->all()],
            ['name' => 'is_featured', 'label' => 'منتخب (نمایش در صفحهٔ اصلی)', 'type' => 'checkbox'],
        ], $this->publishingFields(), $this->seoFields());
    }

    protected function afterSave(Model $item, array $data, Request $request): void
    {
        $item->categories()->sync($data['categories'] ?? []);
    }

    protected function aside(Model $item): ?array
    {
        $lines = $this->publicationAside($item);
        if (filled($item->outcome) && ! $item->showsOutcome()) {
            $lines[] = 'نتیجه ثبت شده ولی بدون مدرک یا تأیید در سایت نمایش داده نمی‌شود.';
        }

        return $lines;
    }
}
