<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Real numbers only: every figure is a COUNT on an actual table. No charts or
 * placeholders. Content counts come from the tables directly because the
 * content models arrive in Phase 3.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $now = now();

        $published = fn (string $table) => DB::table($table)
            ->whereNull('deleted_at')
            ->where('status', 'published')
            ->where('published_at', '<=', $now)
            ->count();

        $stats = [
            'articles_published' => $published('articles'),
            'articles_draft' => DB::table('articles')->whereNull('deleted_at')->where('status', 'draft')->count(),
            'projects_published' => $published('projects'),
            'messages_new' => DB::table('contact_messages')->where('status', 'new')->count(),
        ];

        $alerts = [];
        if (! $request->user()->hasEnabledTwoFactorAuthentication()) {
            $alerts[] = ['type' => 'warning', 'text' => 'ورود دومرحله‌ای فعال نیست.', 'link' => route('admin.security'), 'link_text' => 'فعال‌سازی'];
        }
        if (config('app.debug') && app()->isProduction()) {
            $alerts[] = ['type' => 'danger', 'text' => 'حالت دیباگ (APP_DEBUG) در محیط production روشن است و جزئیات خطا را فاش می‌کند.', 'link' => null, 'link_text' => null];
        }

        $activity = ActivityLog::query()->latest('id')->limit(10)->get(['id', 'event', 'created_at']);

        return view('admin.dashboard', compact('stats', 'alerts', 'activity'));
    }
}
