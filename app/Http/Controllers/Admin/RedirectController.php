<?php

namespace App\Http\Controllers\Admin;

use App\Models\Article;
use App\Models\Redirect;
use App\Support\Jalali;
use App\Support\JalaliDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Validator as ValidatorInstance;
use Throwable;

/** Old-URL rules (D31): 301/302 to a new address, or 410 for removed pages. */
class RedirectController extends ResourceController
{
    protected int $perPage = 50;

    protected function model(): string
    {
        return Redirect::class;
    }

    protected function routeBase(): string
    {
        return 'admin.redirects';
    }

    protected function labels(): array
    {
        return ['plural' => 'تغییرمسیرها', 'singular' => 'تغییرمسیر', 'create' => 'تغییرمسیر جدید'];
    }

    protected function searchable(): array
    {
        return ['from_path', 'to_url', 'note'];
    }

    protected function filters(): array
    {
        return [
            'status_code' => ['label' => 'نوع', 'options' => ['301' => '۳۰۱', '302' => '۳۰۲', '410' => '۴۱۰']],
            'is_active' => ['label' => 'وضعیت', 'options' => ['1' => 'فعال', '0' => 'غیرفعال']],
        ];
    }

    protected function baseQuery(): Builder
    {
        return Redirect::query();
    }

    protected function titleOf(Model $item): string
    {
        return (string) ($item->getAttributes()['from_path'] ?? '#'.$item->getKey());
    }

    protected function columns(): array
    {
        return [
            ['label' => 'نشانی قدیمی', 'value' => fn (Redirect $r) => $r->from_path, 'primary' => true, 'ltr' => true],
            ['label' => 'نوع', 'value' => fn (Redirect $r) => [match (true) {
                ! $r->is_active => 'warning',
                $r->status_code === 410 => 'danger',
                default => 'accent',
            }, Jalali::digits($r->status_code).($r->is_active ? '' : ' · غیرفعال')], 'badge' => true],
            ['label' => 'مقصد', 'value' => fn (Redirect $r) => $r->to_url === null ? '—' : rawurldecode($r->to_url), 'ltr' => true, 'muted' => true],
            ['label' => 'بازدید', 'value' => fn (Redirect $r) => Jalali::number($r->hits)],
            ['label' => 'آخرین بازدید', 'value' => fn (Redirect $r) => $r->last_hit_at ? JalaliDate::display($r->last_hit_at) : '—', 'muted' => true],
        ];
    }

    protected function fields(Model $item): array
    {
        return [
            ['name' => 'from_path', 'label' => 'نشانی قدیمی', 'type' => 'text', 'required' => true, 'dir' => 'ltr', 'wide' => true,
                'rules' => ['string', 'max:2000'],
                'hint' => 'مسیر نسبی مثل /old-page/ (حروف فارسی مجاز است). با * در انتها همهٔ زیرمسیرها را می‌گیرد، مثل /wp-json/* . این قانون فقط وقتی اجرا می‌شود که نشانی در سایت جدید صفحه‌ای نداشته باشد (۴۰۴).'],
            ['name' => 'status_code', 'label' => 'نوع', 'type' => 'select', 'required' => true, 'options' => array_map(fn ($l) => $l, Redirect::STATUSES),
                'hint' => '۳۰۱ برای جابه‌جایی همیشگی (حفظ اعتبار سئو)، ۳۰۲ فقط برای موقت، ۴۱۰ برای صفحه‌ای که عمداً حذف شده و جایگزین ندارد.'],
            ['name' => 'to_url', 'label' => 'مقصد', 'type' => 'text', 'dir' => 'ltr', 'wide' => true, 'rules' => ['string', 'max:2000'],
                'hint' => 'مسیر داخلی مثل /blog/ یا نشانی کامل https://… — برای ۴۱۰ خالی بماند. همه‌چیز را به صفحهٔ اصلی نفرستید؛ مرتبط‌ترین صفحه را انتخاب کنید.'],
            ['name' => 'note', 'label' => 'یادداشت', 'type' => 'text'],
            ['name' => 'is_active', 'label' => 'فعال', 'type' => 'checkbox'],
        ];
    }

    protected function formValues(Model $item, array $fields): array
    {
        $values = parent::formValues($item, $fields);
        $values['status_code'] = (string) ($item->status_code ?? 301);
        if (isset($values['to_url']) && is_string($values['to_url'])) {
            $values['to_url'] = rawurldecode($values['to_url']);
        }

        return $values;
    }

    protected function validateMore(ValidatorInstance $validator, array $data, Model $item): void
    {
        $raw = (string) ($data['from_path'] ?? '');
        if ($raw === '') {
            return;
        }
        $from = Redirect::normalize($raw);
        $errors = $validator->errors();

        $admin = '/'.trim((string) config('admin.path'), '/').'/';
        if ($from === '/' || $from === '/*' || str_starts_with($from, $admin) || $from === rtrim($admin, '/').'*') {
            $errors->add('from_path', 'صفحهٔ اصلی و مسیر پنل مدیریت را نمی‌توان تغییرمسیر داد.');

            return;
        }

        $duplicate = Redirect::query()->where('from_hash', Redirect::hash($from))->whereKeyNot($item->getKey())->exists();
        if ($duplicate) {
            $errors->add('from_path', 'برای این نشانی قبلاً تغییرمسیر ثبت شده است.');
        }

        if (! str_ends_with($from, '*') && $this->servesContent($from)) {
            $errors->add('from_path', 'این نشانی در سایت جدید صفحهٔ فعال دارد، پس تغییرمسیر هرگز اجرا نمی‌شود. اگر صفحه جابه‌جا شده، نامک آن را عوض کنید (تغییرمسیر خودکار ساخته می‌شود).');
        }

        $status = (int) ($data['status_code'] ?? 301);
        $to = trim((string) ($data['to_url'] ?? ''));
        if ($status === 410) {
            return;
        }
        if ($to === '') {
            $errors->add('to_url', 'برای ۳۰۱ و ۳۰۲ مقصد لازم است.');
        } elseif (str_starts_with($to, '//') || (! str_starts_with($to, '/') && ! preg_match('~^https?://[^\s/?#]+[^\s]*$~i', $to))) {
            $errors->add('to_url', 'مقصد باید با / شروع شود (مسیر داخلی) یا نشانی کامل http(s) باشد.');
        } elseif (str_starts_with($to, '/') && Redirect::normalize($to) === $from) {
            $errors->add('to_url', 'مقصد نمی‌تواند همان نشانی قدیمی باشد.');
        }
    }

    /** True when the path is answered by a fixed route or a published article (the redirect would never run). */
    private function servesContent(string $path): bool
    {
        try {
            $route = Route::getRoutes()->match(Request::create($path, 'GET'));
        } catch (Throwable) {
            return false;
        }

        if (! $route->isFallback) {
            return $route->parameterNames() === [];
        }

        $slug = trim($path, '/');

        return ! str_contains($slug, '/') && Article::query()->published()->where('slug', $slug)->exists();
    }
}
