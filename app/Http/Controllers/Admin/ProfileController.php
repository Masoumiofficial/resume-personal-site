<?php

namespace App\Http\Controllers\Admin;

use App\Models\Profile;
use App\Support\Activity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** The single public identity row. */
class ProfileController extends ResourceController
{
    protected function model(): string
    {
        return Profile::class;
    }

    protected function routeBase(): string
    {
        return 'admin.profile';
    }

    protected function labels(): array
    {
        return ['plural' => 'پروفایل', 'singular' => 'پروفایل', 'create' => 'پروفایل'];
    }

    protected function singleton(): bool
    {
        return true;
    }

    protected function titleOf(Model $item): string
    {
        return 'پروفایل';
    }

    protected function columns(): array
    {
        return [];
    }

    protected function fields(Model $item): array
    {
        return [
            ['name' => 'display_name', 'label' => 'نام نمایشی', 'type' => 'text', 'required' => true],
            ['name' => 'headline', 'label' => 'عنوان حرفه‌ای', 'type' => 'text', 'hint' => 'در Hero، عنوان صفحه و رزومه استفاده می‌شود.'],
            ['name' => 'short_bio', 'label' => 'معرفی کوتاه', 'type' => 'textarea', 'rows' => 3, 'rules' => ['string', 'max:600'], 'hint' => 'یک یا دو جمله؛ برای صفحهٔ اصلی و توضیح متای پیش‌فرض.'],
            ['name' => 'long_bio', 'label' => 'معرفی کامل (صفحهٔ درباره)', 'type' => 'markdown', 'rows' => 12],
            ['name' => 'resume_summary', 'label' => 'خلاصهٔ رزومه', 'type' => 'textarea', 'rows' => 4],
            ['name' => 'avatar_id', 'label' => 'عکس پروفایل', 'type' => 'media', 'accept' => 'image'],
            ['name' => 'availability_note', 'label' => 'وضعیت پذیرش پروژه', 'type' => 'text', 'hint' => 'مثل «پذیرش پروژهٔ جدید». خالی = نمایش داده نمی‌شود.'],
            ['name' => 'email', 'label' => 'ایمیل عمومی', 'type' => 'email'],
            ['name' => 'phone', 'label' => 'تلفن', 'type' => 'text', 'dir' => 'ltr', 'rules' => ['string', 'max:32', 'regex:/^[0-9+()\-\s۰-۹]{5,32}$/u']],
            ['name' => 'show_phone', 'label' => 'تلفن در سایت نمایش داده شود', 'type' => 'checkbox', 'default' => true],
            ['name' => 'location', 'label' => 'محل', 'type' => 'text'],
            ['name' => 'show_location', 'label' => 'محل در سایت نمایش داده شود', 'type' => 'checkbox', 'default' => true],
            ['name' => 'social_links', 'label' => 'شبکه‌های اجتماعی', 'type' => 'links'],
        ];
    }

    public function edit(int $id = 0): View
    {
        return $this->form(Profile::query()->first() ?? new Profile);
    }

    public function update(Request $request, int $id = 0): RedirectResponse
    {
        $profile = Profile::query()->first() ?? new Profile;
        $this->save($profile, $request);
        Activity::log('content.updated', $profile, ['type' => 'پروفایل', 'title' => $profile->display_name]);

        return redirect()->route('admin.profile.edit')->with('flash', 'پروفایل ذخیره شد.');
    }
}
