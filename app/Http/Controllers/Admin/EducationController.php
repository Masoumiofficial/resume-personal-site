<?php

namespace App\Http\Controllers\Admin;

use App\Models\Education;
use App\Support\JalaliDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Validator;

class EducationController extends ResourceController
{
    protected function model(): string
    {
        return Education::class;
    }

    protected function routeBase(): string
    {
        return 'admin.educations';
    }

    protected function labels(): array
    {
        return ['plural' => 'تحصیلات', 'singular' => 'سابقهٔ تحصیلی', 'create' => 'سابقهٔ تحصیلی جدید'];
    }

    protected function titleOf(Model $item): string
    {
        return trim($item->institution.' — '.$item->degree, ' —');
    }

    protected function columns(): array
    {
        return [
            ['label' => 'مؤسسه', 'value' => fn (Education $e) => $e->institution, 'primary' => true],
            ['label' => 'مقطع و رشته', 'value' => fn (Education $e) => trim($e->degree.' '.$e->field) ?: '—'],
            ['label' => 'بازه', 'value' => fn (Education $e) => JalaliDate::range($e->start_date, $e->end_date, $e->date_precision) ?: '—', 'muted' => true],
            ['label' => 'نمایش', 'value' => fn (Education $e) => self::visibleBadge($e->is_visible), 'badge' => true],
        ];
    }

    protected function fields(Model $item): array
    {
        return [
            ['name' => 'institution', 'label' => 'مؤسسه / دانشگاه', 'type' => 'text', 'required' => true],
            ['name' => 'degree', 'label' => 'مقطع', 'type' => 'text'],
            ['name' => 'field', 'label' => 'رشته', 'type' => 'text'],
            ['name' => 'start_date', 'label' => 'شروع', 'type' => 'jdate'],
            ['name' => 'end_date', 'label' => 'پایان', 'type' => 'jdate', 'hint' => 'خالی = در حال تحصیل.'],
            ['name' => 'description', 'label' => 'توضیح', 'type' => 'textarea'],
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
