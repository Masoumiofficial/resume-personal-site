<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Support\Link;
use App\Support\Markdown;
use App\Support\Seo;
use Illuminate\View\View;

class WorkController extends Controller
{
    public function index(): View
    {
        $projects = Project::query()->published()->with('cover', 'categories')->orderByDesc('is_featured')->ordered()->get();

        $seo = Seo::make('نمونه‌کارها', 'نمونه‌کارهای منتخب؛ هر پروژه با مسئله، راهکار، نقش و نتیجه.')->canonical(Link::route('works.index'));
        // An empty portfolio page has nothing to rank for.
        $seo->noindex = $projects->isEmpty();

        return view('site.works.index', compact('projects', 'seo'));
    }

    public function show(string $slug): View
    {
        $project = Project::query()->published()->where('slug', $slug)->with('cover', 'categories', 'gallery.media')->firstOrFail();

        $seo = Seo::make()->fromModel($project, $project->title, $project->summary)
            ->canonical($project->url())->image($project->cover);
        $seo->type = 'article';
        $seo->schema(array_filter([
            '@type' => 'CreativeWork',
            'name' => $project->title,
            'description' => $project->summary,
            'url' => $project->url(),
            'image' => $seo->image,
            'dateCreated' => $project->year ? (string) $project->year : null,
        ]));

        return view('site.works.show', [
            'project' => $project,
            'seo' => $seo,
            'sections' => array_filter([
                'مسئله' => Markdown::toHtml($project->problem),
                'راهکار' => Markdown::toHtml($project->solution),
                'نقش من' => Markdown::toHtml($project->role),
            ]),
            'bodyHtml' => Markdown::toHtml($project->body),
            'outcomeHtml' => $project->showsOutcome() ? Markdown::toHtml($project->outcome) : '',
        ]);
    }
}
