<?php

namespace App\Http\Controllers\Admin;

use App\Models\Project;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Validator;

/** D6: only real testimonials, published only with the author's consent. */
class TestimonialController extends ResourceController
{
    protected function model(): string
    {
        return Testimonial::class;
    }

    protected function routeBase(): string
    {
        return 'admin.testimonials';
    }

    protected function labels(): array
    {
        return ['plural' => 'نظرات مشتریان', 'singular' => 'نظر', 'create' => 'نظر جدید'];
    }

    protected function titleOf(Model $item): string
    {
        return (string) $item->author_name;
    }

    protected function columns(): array
    {
        return [
            ['label' => 'نویسنده', 'value' => fn (Testimonial $t) => $t->author_name, 'primary' => true],
            ['label' => 'سمت و مجموعه', 'value' => fn (Testimonial $t) => trim($t->author_role.'، '.$t->company, '، ') ?: '—', 'muted' => true],
            ['label' => 'رضایت انتشار', 'value' => fn (Testimonial $t) => $t->consent_confirmed ? ['success', 'تأییدشده'] : ['danger', 'ندارد'], 'badge' => true],
            ['label' => 'نمایش', 'value' => fn (Testimonial $t) => self::visibleBadge($t->consent_confirmed && $t->is_visible), 'badge' => true],
        ];
    }

    protected function fields(Model $item): array
    {
        return [
            ['name' => 'author_name', 'label' => 'نام نویسنده', 'type' => 'text', 'required' => true],
            ['name' => 'author_role', 'label' => 'سمت', 'type' => 'text'],
            ['name' => 'company', 'label' => 'مجموعه', 'type' => 'text'],
            ['name' => 'company_url', 'label' => 'وب‌سایت مجموعه', 'type' => 'url'],
            ['name' => 'quote', 'label' => 'متن نظر', 'type' => 'textarea', 'required' => true, 'hint' => 'عین متن نویسنده؛ بدون ویرایش محتوایی.'],
            ['name' => 'received_at', 'label' => 'تاریخ دریافت', 'type' => 'jdate', 'precision' => false],
            ['name' => 'related_project_id', 'label' => 'پروژهٔ مرتبط', 'type' => 'select', 'rules' => ['integer', 'exists:projects,id'],
                'options' => Project::query()->orderBy('title')->pluck('title', 'id')->all()],
            ['name' => 'avatar_id', 'label' => 'تصویر نویسنده', 'type' => 'media', 'accept' => 'image', 'hint' => 'فقط با اجازهٔ نویسنده.'],
            ['name' => 'consent_confirmed', 'label' => 'نویسنده با انتشار عمومی این نظر موافقت کرده است', 'type' => 'checkbox'],
            ['name' => 'is_visible', 'label' => 'نمایش در سایت', 'type' => 'checkbox'],
        ];
    }

    protected function validateMore(Validator $validator, array $data, Model $item): void
    {
        if (! empty($data['is_visible']) && empty($data['consent_confirmed'])) {
            $validator->errors()->add('is_visible', 'بدون رضایت انتشارِ نویسنده، نظر در سایت نمایش داده نمی‌شود.');
        }
    }
}
