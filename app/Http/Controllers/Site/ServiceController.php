<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceGroup;
use App\Support\Link;
use App\Support\Markdown;
use App\Support\Seo;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        $groups = ServiceGroup::query()->ordered()
            ->with(['services' => fn ($q) => $q->published()->where('is_visible', true)->ordered()])
            ->get()->filter(fn ($g) => $g->services->isNotEmpty())->values();

        $seo = Seo::make('خدمات و تعرفه', 'خدمات طراحی سایت وردپرس، تحلیل داده و هوش مصنوعی و مشاورهٔ رشد کسب‌وکار، با قیمت «شروع از».')
            ->canonical(Link::route('services.index'));

        return view('site.services.index', compact('groups', 'seo'));
    }

    public function show(string $slug): View
    {
        $service = Service::query()->published()->where('is_visible', true)->where('has_page', true)
            ->where('slug', $slug)->with('group')->firstOrFail();

        $seo = Seo::make()->fromModel($service, $service->title, $service->summary)->canonical($service->url());
        $seo->schema(array_filter([
            '@type' => 'Service',
            'name' => $service->title,
            'description' => $service->summary,
            'url' => $service->url(),
            // Prices are stored in toman; ISO 4217 only has the rial (IRR = toman × 10).
            'offers' => $service->price_from ? ['@type' => 'Offer', 'price' => $service->price_from * 10, 'priceCurrency' => 'IRR',
                'description' => 'شروع از '.number_format($service->price_from).' تومان'] : null,
        ]));

        return view('site.services.show', ['service' => $service, 'seo' => $seo, 'bodyHtml' => Markdown::toHtml($service->body)]);
    }
}
