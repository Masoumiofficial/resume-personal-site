<?php

namespace App\Http\Controllers\Admin;

use App\Models\Setting;
use App\Support\Activity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/** Site-wide key-value settings, edited as one form. */
class SettingsController extends ResourceController
{
    protected function model(): string
    {
        return Setting::class;
    }

    protected function routeBase(): string
    {
        return 'admin.settings';
    }

    protected function labels(): array
    {
        return ['plural' => 'تنظیمات', 'singular' => 'تنظیمات', 'create' => 'تنظیمات'];
    }

    protected function singleton(): bool
    {
        return true;
    }

    protected function columns(): array
    {
        return [];
    }

    protected function fields(Model $item): array
    {
        return [
            ['name' => 'site_title', 'label' => 'عنوان سایت', 'type' => 'text', 'required' => true],
            ['name' => 'title_separator', 'label' => 'جداکنندهٔ عنوان صفحه', 'type' => 'text', 'required' => true, 'rules' => ['string', 'max:3']],
            ['name' => 'default_meta_description', 'label' => 'توضیح متای پیش‌فرض', 'type' => 'textarea', 'rows' => 2, 'rules' => ['string', 'max:170']],
            ['name' => 'search_indexing', 'label' => 'اجازهٔ ایندکس در موتورهای جست‌وجو', 'type' => 'checkbox', 'hint' => 'فقط در محیط production اثر دارد؛ نسخه‌های آزمایشی همیشه noindex هستند.'],
            ['name' => 'footer_note', 'label' => 'متن فوتر', 'type' => 'text'],
            ['name' => 'home_show_services', 'label' => 'صفحهٔ اصلی: بخش خدمات', 'type' => 'checkbox'],
            ['name' => 'home_show_projects', 'label' => 'صفحهٔ اصلی: نمونه‌کارهای منتخب (اگر خالی باشد خودکار پنهان است)', 'type' => 'checkbox'],
            ['name' => 'home_show_articles', 'label' => 'صفحهٔ اصلی: مقالات اخیر', 'type' => 'checkbox'],
            ['name' => 'home_show_testimonials', 'label' => 'صفحهٔ اصلی: نظرات مشتریان (فقط نظرات دارای رضایت)', 'type' => 'checkbox'],
            ['name' => 'contact_form_enabled', 'label' => 'فرم تماس فعال باشد', 'type' => 'checkbox'],
            ['name' => 'message_retention_days', 'label' => 'نگهداری پیام‌ها (روز)', 'type' => 'number', 'required' => true, 'rules' => ['integer', 'min:30', 'max:3650'], 'hint' => 'پیام‌های قدیمی‌تر خودکار حذف می‌شوند (نیازمند Cron). هرزنامه پس از ۳۰ روز.'],
        ];
    }

    public function edit(int $id = 0): View
    {
        $fields = $this->fields(new Setting);
        $values = [];
        foreach ($fields as $field) {
            $values[$field['name']] = Setting::get($field['name']);
        }

        return view('admin.crud.form', [
            'item' => new Setting, 'fields' => $fields, 'values' => $values, 'labels' => $this->labels(),
            'route' => $this->routeBase(), 'media' => [], 'title' => 'تنظیمات', 'aside' => null, 'tabs' => [], 'singleton' => true,
        ]);
    }

    public function update(Request $request, int $id = 0): RedirectResponse
    {
        $fields = $this->fields(new Setting);
        [$input, $errors] = $this->normalize($fields, $request);
        $validator = Validator::make($input, $this->rulesFor($fields, new Setting), [], $this->attributeNames($fields));
        $validator->after(fn ($v) => collect($errors)->each(fn ($m, $f) => $v->errors()->add($f, $m)));
        $data = $validator->validate();

        Setting::put(collect($fields)->mapWithKeys(fn ($f) => [$f['name'] => $data[$f['name']] ?? null])->all());
        Activity::log('settings.updated');

        return redirect()->route('admin.settings.edit')->with('flash', 'تنظیمات ذخیره شد.');
    }
}
