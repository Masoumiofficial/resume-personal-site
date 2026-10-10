<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    use MassPrunable;

    public const STATUSES = ['new' => 'جدید', 'read' => 'خوانده‌شده', 'replied' => 'پاسخ داده شد', 'archived' => 'بایگانی', 'spam' => 'هرزنامه'];

    protected $fillable = ['name', 'email', 'subject', 'message', 'status', 'ip_hash', 'user_agent', 'handled_at'];

    protected $attributes = ['status' => 'new', 'subject' => null, 'handled_at' => null];

    protected function casts(): array
    {
        return ['handled_at' => 'datetime'];
    }

    /** Retention: spam after 30 days, everything else after settings.message_retention_days. */
    public function prunable(): Builder
    {
        $days = max(30, (int) Setting::get('message_retention_days'));

        return static::query()->where(fn (Builder $q) => $q
            ->where(fn ($s) => $s->where('status', 'spam')->where('created_at', '<', now()->subDays(30)))
            ->orWhere('created_at', '<', now()->subDays($days)));
    }
}
