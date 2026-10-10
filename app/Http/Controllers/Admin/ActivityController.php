<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.activity', [
            'items' => ActivityLog::query()->latest('id')->paginate(50, ['id', 'event', 'properties', 'created_at']),
        ]);
    }
}
