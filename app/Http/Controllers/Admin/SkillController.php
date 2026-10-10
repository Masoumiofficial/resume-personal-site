<?php

namespace App\Http\Controllers\Admin;

use App\Models\Skill;
use App\Support\Jalali;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Validator;

class SkillController extends ResourceController
{
    protected function model(): string
    {
        return Skill::class;
    }

    protected function routeBase(): string
    {
        return 'admin.skills';
    }

    protected function labels(): array
    {
        return ['plural' => 'مهارت‌ها و زبان‌ها', 'singular' => 'مهارت', 'create' => 'مهارت جدید'];
    }

    protected function searchable(): array
    {
        return ['name'];
    }

    protected function filters(): array
    {
        return ['group' => ['label' => 'گروه', 'options' => Skill::GROUPS]];
    }

    protected function columns(): array
    {
        return [
            ['label' => 'نام', 'value' => fn (Skill $s) => $s->name, 'primary' => true],
            ['label' => 'گروه', 'value' => fn (Skill $s) => Skill::GROUPS[$s->group] ?? $s->group],
            ['label' => 'سطح', 'value' => fn (Skill $s) => trim(($s->level_label ?? '').($s->years ? ' · '.Jalali::digits($s->years).' سال' : ''), ' ·') ?: '—', 'muted' => true],
            ['label' => 'نمایش', 'value' => fn (Skill $s) => self::visibleBadge($s->is_visible), 'badge' => true],
        ];
    }

    protected function fields(Model $item): array
    {
        return [
            ['name' => 'name', 'label' => 'نام', 'type' => 'text', 'required' => true],
            ['name' => 'group', 'label' => 'گروه', 'type' => 'select', 'required' => true, 'options' => Skill::GROUPS],
            ['name' => 'level_label', 'label' => 'سطح (متنی)', 'type' => 'text', 'rules' => ['string', 'max:64'], 'hint' => 'اختیاری، مثل «زبان مادری»، «B2» یا «کار روزمره». درصد پذیرفته نمی‌شود.'],
            ['name' => 'years', 'label' => 'سال‌های تجربه', 'type' => 'number', 'rules' => ['integer', 'min:0', 'max:60']],
            ['name' => 'is_visible', 'label' => 'نمایش در سایت', 'type' => 'checkbox', 'default' => true],
        ];
    }

    /** D6: no undocumented percentages — not even typed into the label. */
    protected function validateMore(Validator $validator, array $data, Model $item): void
    {
        if (! empty($data['level_label']) && preg_match('/[%٪]|\d+\s*(درصد|percent)/u', Jalali::asciiDigits($data['level_label']))) {
            $validator->errors()->add('level_label', 'سطح مهارت به‌صورت درصد ثبت نمی‌شود؛ از توصیف متنی استفاده کنید.');
        }
    }
}
