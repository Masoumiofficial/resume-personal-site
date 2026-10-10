<?php

namespace App\Models\Concerns;

use App\Models\Redirect;
use App\Support\Activity;
use Illuminate\Database\Eloquent\Model;

/**
 * When the slug of an item that was publicly reachable changes, keep the old
 * URL alive with a 301 to the new one (D31), so links and search results
 * that point at the old address do not break.
 *
 * Models provide slugPath() (public path for a slug) and may override
 * wasPublic() (based on the original, pre-update attributes).
 */
trait RedirectsOldSlug
{
    abstract public static function slugPath(string $slug): string;

    public static function bootRedirectsOldSlug(): void
    {
        // In "updated" the original attributes are still the pre-save values.
        static::updated(function (Model $model): void {
            $old = (string) $model->getOriginal('slug');
            if (! $model->wasChanged('slug') || $old === '' || ! $model->wasPublic()) {
                return;
            }

            $redirect = Redirect::rememberMove(static::slugPath($old), static::slugPath((string) $model->slug), mb_substr("خودکار: تغییر نامک «{$old}» به «{$model->slug}»", 0, 190));
            if ($redirect) {
                Activity::log('redirect.auto_created', $redirect, ['from' => $redirect->from_path, 'to' => $redirect->to_url]);
            }
        });
    }

    protected function wasPublic(): bool
    {
        return true;
    }
}
