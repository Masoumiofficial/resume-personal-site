<?php

namespace App\Http\Controllers\Admin;

use App\Models\ServiceGroup;
use App\Rules\Slug;
use App\Support\Jalali;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

class ServiceGroupController extends ResourceController
{
    protected function model(): string
    {
        return ServiceGroup::class;
    }

    protected function routeBase(): string
    {
        return 'admin.service-groups';
    }

    protected function labels(): array
    {
        return ['plural' => 'گروه‌های خدمت', 'singular' => 'گروه خدمت', 'create' => 'گروه جدید'];
    }

    protected function tabs(): array
    {
        return [['admin.services', 'خدمات'], ['admin.service-groups', 'گروه‌ها']];
    }

    protected function columns(): array
    {
        return [
            ['label' => 'عنوان', 'value' => fn (ServiceGroup $g) => $g->title, 'primary' => true],
            ['label' => 'نامک', 'value' => fn (ServiceGroup $g) => $g->slug, 'ltr' => true, 'muted' => true],
            ['label' => 'تعداد خدمت', 'value' => fn (ServiceGroup $g) => Jalali::digits($g->services_count)],
        ];
    }

    protected function baseQuery(): Builder
    {
        return ServiceGroup::query()->withCount('services');
    }

    protected function fields(Model $item): array
    {
        return [
            ['name' => 'title', 'label' => 'عنوان', 'type' => 'text', 'required' => true],
            ['name' => 'slug', 'label' => 'نامک', 'type' => 'text', 'required' => true, 'dir' => 'ltr',
                'rules' => ['string', 'max:190', new Slug, Rule::unique('service_groups', 'slug')->ignore($item->getKey())]],
            ['name' => 'summary', 'label' => 'خلاصه', 'type' => 'textarea'],
        ];
    }

    /** services.service_group_id is restrictOnDelete: explain instead of failing. */
    public function destroy(int $id): RedirectResponse
    {
        $group = ServiceGroup::query()->withCount('services')->findOrFail($id);
        if ($group->services_count > 0) {
            return back()->with('flash_error', 'این گروه '.Jalali::digits($group->services_count).' خدمت دارد؛ ابتدا خدمات آن را حذف یا به گروه دیگری منتقل کنید.');
        }

        return parent::destroy($id);
    }
}
