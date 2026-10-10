<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ResumeData;
use Illuminate\View\View;

/** Admin preview of the printable resume (the public /resume/ page arrives in Phase 4). */
class ResumePreviewController extends Controller
{
    public function __invoke(ResumeData $resume): View
    {
        return view('resume.document', $resume->toArray() + ['preview' => true]);
    }
}
