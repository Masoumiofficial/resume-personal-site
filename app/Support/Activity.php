<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

/** Writes content events to activity_logs. Never pass secrets in $properties. */
final class Activity
{
    public static function log(string $event, ?Model $subject = null, array $properties = []): void
    {
        $request = request();

        ActivityLog::create([
            'user_id' => $request->user()?->getAuthIdentifier(),
            'event' => $event,
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id' => $subject?->getKey(),
            'ip_hash' => IpHash::make($request->ip()),
            'properties' => $properties ?: null,
        ]);
    }
}
