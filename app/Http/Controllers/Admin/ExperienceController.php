<?php

namespace App\Http\Controllers\Admin;

use App\Models\Experience;
use App\Support\JalaliDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Validator;

class ExperienceController extends ResourceController
{
    protected function model(): string
    {
        return Experience::class;
    }

    protected function routeBase(): string
    {
        return 'admin.experiences';
    }

    protected function labels(): array
    {
        return ['plural' => 'سوابق کاری', 'singular' => 'سابقهٔ کاری', 'create' => 'سابقهٔ جدید'];
    }

    protected function titleOf(Model $item): string
    {
        return trim($item->organization.' — '.$item->role, ' —');
    }

    protected function searchable(): array
    {
        return ['organization', 'role'];
    }

    protected function columns(): array
    {
        return [
            ['label' => 'مجموعه', 'value' => fn (Experience $e) => $e->organization, 'primary' => true],
            ['label' => 'نقش', 'value' => fn (Experience $e) => $e->role],
            ['label' => 'بازه', 'value' => fn (Experience $e) => JalaliDate::range($e->start_date, $e->end_date, $e->date_precision) ?: '—', 'muted' => true],
            ['label' => 'نمایش', 'value' => fn (Experience $e) => self::visibleBadge($e->is_visible), 'badge' => true],
        ];
    }

    protected function fields(Model $item): array
    {
        return [
            ['name' => 'organization', 'label' => 'مجموعه / شرکت', 'type' => 'text', 'required' => true],
            ['name' => 'role', 'label' => 'نقش', 'type' => 'text', 'required' => true],
            ['name' => 'employment_type', 'label' => 'نوع همکاری', 'type' => 'select', 'options' => Experience::EMPLOYMENT_TYPES],
            ['name' => 'organization_url', 'label' => 'وب‌سایت مجموعه', 'type' => 'url'],
            ['name' => 'start_date', 'label' => 'شروع', 'type' => 'jdate'],
            ['name' => 'end_date', 'label' => 'پایان', 'type' => 'jdate', 'hint' => 'خالی = تاکنون. دقت نمایش، کمترین دقت دو تاریخ است.'],
            ['name' => 'location', 'label' => 'محل', 'type' => 'text'],
            ['name' => 'logo_id', 'label' => 'لوگو', 'type' => 'media', 'accept' => 'image'],
            ['name' => 'summary', 'label' => 'توضیح نقش', 'type' => 'textarea', 'hint' => 'نقش و کارهای انجام‌شده؛ نه معرفی شرکت.'],
            ['name' => 'highlights', 'label' => 'دستاوردها', 'type' => 'lines', 'hint' => 'هر مورد در یک خط. فقط دستاورد قابل اثبات.'],
            ['name' => 'is_visible', 'label' => 'در رزومه نمایش داده شود', 'type' => 'checkbox', 'default' => true],
        ];
    }

    protected function validateMore(Validator $validator, array $data, Model $item): void
    {
        if (! empty($data['start_date']) && ! empty($data['end_date']) && $data['end_date'] < $data['start_date']) {
            $validator->errors()->add('end_date', 'تاریخ پایان نمی‌تواند پیش از شروع باشد.');
        }
    }
}
