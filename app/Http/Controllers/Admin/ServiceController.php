<?php

namespace App\Http\Controllers\Admin;

use App\Models\Service;
use App\Models\ServiceGroup;
use App\Rules\Slug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ServiceController extends ResourceController
{
    protected function model(): string
    {
        return Service::class;
    }

    protected function routeBase(): string
    {
        return 'admin.services';
    }

    protected function labels(): array
    {
        return ['plural' => 'خدمات و تعرفه', 'singular' => 'خدمت', 'create' => 'خدمت جدید'];
    }

    protected function tabs(): array
    {
        return [['admin.services', 'خدمات'], ['admin.service-groups', 'گروه‌ها']];
    }

    protected function with(): array
    {
        return ['group'];
    }

    protected function searchable(): array
    {
        return ['title'];
    }

    protected function filters(): array
    {
        return [
            'service_group_id' => ['label' => 'گروه', 'options' => ServiceGroup::query()->ordered()->pluck('title', 'id')->mapWithKeys(fn ($t, $id) => [(string) $id => $t])->all()],
            'status' => ['label' => 'وضعیت', 'options' => Service::STATUSES],
        ];
    }

    protected function columns(): array
    {
        return [
            ['label' => 'عنوان', 'value' => fn (Service $s) => $s->title, 'primary' => true],
            ['label' => 'گروه', 'value' => fn (Service $s) => $s->group?->title, 'muted' => true],
            ['label' => 'قیمت', 'value' => fn (Service $s) => $s->priceLabel()],
            ['label' => 'وضعیت', 'value' => fn (Service $s) => self::stateBadge($s), 'badge' => true],
        ];
    }

    protected function fields(Model $item): array
    {
        return array_merge([
            ['name' => 'title', 'label' => 'عنوان', 'type' => 'text', 'required' => true],
            ['name' => 'service_group_id', 'label' => 'گروه', 'type' => 'select', 'required' => true,
                'options' => ServiceGroup::query()->ordered()->pluck('title', 'id')->all(), 'rules' => ['integer', 'exists:service_groups,id']],
            ['name' => 'price_from', 'label' => 'قیمت پایه (تومان)', 'type' => 'money', 'hint' => 'در سایت «شروع از …» نمایش داده می‌شود. خالی = «تماس بگیرید».'],
            ['name' => 'price_unit', 'label' => 'واحد قیمت', 'type' => 'select', 'required' => true, 'options' => Service::UNITS],
            ['name' => 'price_original', 'label' => 'قیمت قبل از تخفیف (تومان)', 'type' => 'money', 'hint' => 'اختیاری؛ خط‌خورده نمایش داده می‌شود.'],
            ['name' => 'price_note', 'label' => 'یادداشت قیمت', 'type' => 'text'],
            ['name' => 'summary', 'label' => 'خلاصه', 'type' => 'textarea', 'rows' => 3],
            ['name' => 'cta_type', 'label' => 'دکمهٔ اقدام', 'type' => 'select', 'required' => true, 'options' => Service::CTA_TYPES],
            ['name' => 'cta_url', 'label' => 'لینک بیرونی دکمه', 'type' => 'url', 'hint' => 'فقط برای «لینک بیرونی»؛ مثلاً صفحهٔ خدمت در اتحاد وردپرس.'],
            ['name' => 'has_page', 'label' => 'صفحهٔ اختصاصی دارد (/services/نامک/)', 'type' => 'checkbox'],
            ['name' => 'slug', 'label' => 'نامک صفحه', 'type' => 'text', 'dir' => 'ltr',
                'rules' => ['string', 'max:190', new Slug, Rule::unique('services', 'slug')->ignore($item->getKey())]],
            ['name' => 'body', 'label' => 'متن صفحهٔ اختصاصی', 'type' => 'markdown', 'rows' => 10],
            ['name' => 'is_visible', 'label' => 'در جدول تعرفه نمایش داده شود', 'type' => 'checkbox', 'default' => true],
        ], $this->publishingFields(), $this->seoFields());
    }

    protected function validateMore(Validator $validator, array $data, Model $item): void
    {
        if (! empty($data['has_page']) && empty($data['slug'])) {
            $validator->errors()->add('slug', 'برای صفحهٔ اختصاصی، نامک لازم است.');
        }
        if (($data['cta_type'] ?? null) === 'external' && empty($data['cta_url'])) {
            $validator->errors()->add('cta_url', 'برای دکمهٔ «لینک بیرونی»، نشانی لازم است.');
        }
        if (! empty($data['price_original']) && ! empty($data['price_from']) && $data['price_original'] <= $data['price_from']) {
            $validator->errors()->add('price_original', 'قیمت قبل از تخفیف باید از قیمت پایه بیشتر باشد.');
        }
    }

    protected function aside(Model $item): ?array
    {
        return $this->publicationAside($item);
    }
}
