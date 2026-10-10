<?php

namespace App\Http\Controllers\Admin;

use App\Models\Certification;
use App\Support\JalaliDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Validator;

class CertificationController extends ResourceController
{
    protected function model(): string
    {
        return Certification::class;
    }

    protected function routeBase(): string
    {
        return 'admin.certifications';
    }

    protected function labels(): array
    {
        return ['plural' => 'دوره‌ها، گواهی‌ها و جوایز', 'singular' => 'مورد', 'create' => 'مورد جدید'];
    }

    protected function searchable(): array
    {
        return ['title', 'issuer'];
    }

    protected function filters(): array
    {
        return ['kind' => ['label' => 'نوع', 'options' => Certification::KINDS]];
    }

    protected function columns(): array
    {
        return [
            ['label' => 'عنوان', 'value' => fn (Certification $c) => $c->title, 'primary' => true],
            ['label' => 'نوع', 'value' => fn (Certification $c) => Certification::KINDS[$c->kind] ?? $c->kind],
            ['label' => 'برگزارکننده', 'value' => fn (Certification $c) => $c->issuer ?: '—'],
            ['label' => 'تاریخ', 'value' => fn (Certification $c) => JalaliDate::range($c->issued_at, $c->end_at, $c->date_precision, 'در جریان') ?: '—', 'muted' => true],
            ['label' => 'نمایش', 'value' => fn (Certification $c) => self::visibleBadge($c->is_visible), 'badge' => true],
        ];
    }

    protected function fields(Model $item): array
    {
        return [
            ['name' => 'title', 'label' => 'عنوان', 'type' => 'text', 'required' => true],
            ['name' => 'kind', 'label' => 'نوع', 'type' => 'select', 'required' => true, 'options' => Certification::KINDS],
            ['name' => 'issuer', 'label' => 'برگزارکننده / صادرکننده', 'type' => 'text'],
            ['name' => 'credential_url', 'label' => 'لینک تأیید مدرک', 'type' => 'url'],
            ['name' => 'issued_at', 'label' => 'تاریخ (شروع)', 'type' => 'jdate'],
            ['name' => 'end_at', 'label' => 'پایان', 'type' => 'jdate', 'hint' => 'برای دوره‌ها؛ خالی = در جریان.'],
            ['name' => 'evidence_media_id', 'label' => 'فایل مدرک', 'type' => 'media'],
            ['name' => 'description', 'label' => 'توضیح', 'type' => 'textarea'],
            ['name' => 'is_visible', 'label' => 'در رزومه نمایش داده شود', 'type' => 'checkbox', 'default' => true],
        ];
    }

    protected function validateMore(Validator $validator, array $data, Model $item): void
    {
        if (! empty($data['issued_at']) && ! empty($data['end_at']) && $data['end_at'] < $data['issued_at']) {
            $validator->errors()->add('end_at', 'تاریخ پایان نمی‌تواند پیش از شروع باشد.');
        }
    }
}
