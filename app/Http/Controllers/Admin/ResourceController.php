<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Concerns\Sortable;
use App\Models\Media;
use App\Support\Activity;
use App\Support\Jalali;
use App\Support\JalaliDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorInstance;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Shared list/create/edit/delete flow for admin content modules.
 *
 * A module declares its model, labels, list columns and form fields; this
 * class does input normalisation (Persian digits, prices, Jalali dates, line
 * lists), validation with Persian attribute names, saving, ordering, trash
 * and activity logging. Field definition keys:
 *
 *   name, label, type, required, rules (overrides the type default), hint,
 *   options (select/checklist), precision (jdate: precision column),
 *   accept (media: "image" to list images only), dir ("ltr"), wide (bool)
 *
 * Types: text, email, url, textarea, markdown, number, money, checkbox,
 * select, media, jdate, jdatetime, lines, links, checklist (many-to-many,
 * saved in afterSave()).
 */
abstract class ResourceController extends Controller
{
    protected int $perPage = 25;

    /** @return class-string<Model> */
    abstract protected function model(): string;

    /** Route name prefix, e.g. "admin.experiences". */
    abstract protected function routeBase(): string;

    /** @return array{plural: string, singular: string, create: string} */
    abstract protected function labels(): array;

    /** @return list<array{label: string, value: callable(Model): (string|null), primary?: bool, ltr?: bool}> */
    abstract protected function columns(): array;

    /** @return list<array<string, mixed>> */
    abstract protected function fields(Model $item): array;

    /** Columns searched by the list's search box. */
    protected function searchable(): array
    {
        return [];
    }

    /** @return array<string, array{label: string, options: array<string, string>}> list filters on plain columns */
    protected function filters(): array
    {
        return [];
    }

    protected function with(): array
    {
        return [];
    }

    protected function titleOf(Model $item): string
    {
        // Only read loaded attributes: strict mode throws on missing ones.
        $attributes = $item->getAttributes();
        foreach (['title', 'name', 'organization', 'institution', 'author_name', 'display_name'] as $key) {
            if (filled($attributes[$key] ?? null)) {
                return (string) $attributes[$key];
            }
        }

        return '#'.$item->getKey();
    }

    /** Hook for relations (checklists) and derived columns. */
    protected function afterSave(Model $item, array $data, Request $request): void {}

    /** Extra cross-field validation. */
    protected function validateMore(ValidatorInstance $validator, array $data, Model $item): void {}

    // ------------------------------------------------------------------

    public function index(Request $request): View
    {
        $query = $this->baseQuery()->with($this->with());
        $trashed = $this->usesSoftDeletes() && $request->boolean('trashed');
        if ($trashed) {
            $query->onlyTrashed();
        }

        $search = trim((string) $request->query('q', ''));
        if ($search !== '' && $this->searchable()) {
            $query->where(function (Builder $q) use ($search) {
                foreach ($this->searchable() as $column) {
                    $q->orWhere($column, 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%');
                }
            });
        }

        $activeFilters = [];
        foreach ($this->filters() as $column => $filter) {
            $value = (string) $request->query($column, '');
            if ($value !== '' && array_key_exists($value, $filter['options'])) {
                $query->where($column, $value);
                $activeFilters[$column] = $value;
            }
        }

        if ($this->isSortable()) {
            $scope = (new ($this->model()))->sortScope();
            foreach ($scope as $column) {
                $query->orderBy($column);
            }
            $query->orderBy('sort_order')->orderBy('id');
        } else {
            $query->latest('id');
        }

        return view('admin.crud.index', [
            'items' => $query->paginate($this->perPage)->withQueryString(),
            'columns' => $this->columns(),
            'labels' => $this->labels(),
            'route' => $this->routeBase(),
            'sortable' => $this->isSortable() && $search === '' && ! $activeFilters && ! $trashed,
            'softDeletes' => $this->usesSoftDeletes(),
            'trashed' => $trashed,
            'search' => $search,
            'searchable' => (bool) $this->searchable(),
            'filters' => $this->filters(),
            'activeFilters' => $activeFilters,
            'titleOf' => fn (Model $m) => $this->titleOf($m),
            'tabs' => $this->tabs(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new ($this->model()));
    }

    public function store(Request $request): RedirectResponse
    {
        $item = new ($this->model());
        $this->save($item, $request);
        Activity::log('content.created', $item, ['type' => $this->labels()['singular'], 'title' => $this->titleOf($item)]);

        return redirect()->route($this->routeBase().'.edit', $item->getKey())
            ->with('flash', $this->labels()['singular'].' ذخیره شد.');
    }

    public function edit(int $id): View
    {
        return $this->form($this->find($id));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $item = $this->find($id);
        $this->save($item, $request);
        Activity::log('content.updated', $item, ['type' => $this->labels()['singular'], 'title' => $this->titleOf($item)]);

        return redirect()->route($this->routeBase().'.edit', $item->getKey())->with('flash', 'تغییرات ذخیره شد.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $item = $this->find($id);
        $title = $this->titleOf($item);
        DB::transaction(fn () => $this->deleting($item));
        Activity::log('content.deleted', $item, ['type' => $this->labels()['singular'], 'title' => $title]);

        $message = $this->usesSoftDeletes() ? "«{$title}» به سطل زباله رفت." : "«{$title}» حذف شد.";

        return redirect()->route($this->routeBase().'.index')->with('flash', $message);
    }

    public function restore(int $id): RedirectResponse
    {
        abort_unless($this->usesSoftDeletes(), 404);
        $item = $this->baseQuery()->onlyTrashed()->findOrFail($id);
        $item->restore();
        Activity::log('content.restored', $item, ['type' => $this->labels()['singular'], 'title' => $this->titleOf($item)]);

        return redirect()->route($this->routeBase().'.index', ['trashed' => 1])->with('flash', "«{$this->titleOf($item)}» بازیابی شد.");
    }

    public function forceDelete(int $id): RedirectResponse
    {
        abort_unless($this->usesSoftDeletes(), 404);
        $item = $this->baseQuery()->onlyTrashed()->findOrFail($id);
        $title = $this->titleOf($item);
        $item->forceDelete();
        Activity::log('content.force_deleted', null, ['type' => $this->labels()['singular'], 'title' => $title]);

        return redirect()->route($this->routeBase().'.index', ['trashed' => 1])->with('flash', "«{$title}» برای همیشه حذف شد.");
    }

    /** Swap with the neighbour inside the same sort scope (e.g. same skill group). */
    public function move(int $id, string $direction): RedirectResponse
    {
        abort_unless($this->isSortable(), 404);
        $item = $this->find($id);

        DB::transaction(function () use ($item, $direction) {
            $siblings = $this->baseQuery()
                ->where(fn (Builder $q) => collect($item->sortScope())->each(fn ($c) => $q->where($c, $item->getAttribute($c))))
                ->orderBy('sort_order')->orderBy('id')->get();

            $ids = $siblings->pluck('id')->all();
            $pos = array_search($item->getKey(), $ids, true);
            $swap = $direction === 'up' ? $pos - 1 : $pos + 1;
            if ($pos !== false && isset($ids[$swap])) {
                [$ids[$pos], $ids[$swap]] = [$ids[$swap], $ids[$pos]];
            }
            foreach ($ids as $i => $siblingId) {
                $sibling = $siblings->firstWhere('id', $siblingId);
                if ((int) $sibling->sort_order !== ($i + 1) * 10) {
                    $sibling->forceFill(['sort_order' => ($i + 1) * 10])->saveQuietly();
                }
            }
        });

        return back()->with('flash', 'ترتیب به‌روز شد.');
    }

    // ------------------------------------------------------------------

    protected function deleting(Model $item): void
    {
        $item->delete();
    }

    protected function form(Model $item): View
    {
        $fields = $this->fields($item);

        return view('admin.crud.form', [
            'item' => $item,
            'fields' => $fields,
            'values' => $this->formValues($item, $fields),
            'labels' => $this->labels(),
            'route' => $this->routeBase(),
            'media' => $this->mediaOptions($fields),
            'title' => $item->exists ? $this->titleOf($item) : $this->labels()['create'],
            'aside' => $item->exists ? $this->aside($item) : null,
            'tabs' => $this->tabs(),
            'singleton' => $this->singleton(),
        ]);
    }

    /** Optional info lines next to the form (e.g. publication state). */
    protected function aside(Model $item): ?array
    {
        return null;
    }

    /** Single-record pages (profile, settings): no list, no delete. */
    protected function singleton(): bool
    {
        return false;
    }

    /** Sibling sections shown as tabs, e.g. articles | categories | tags. @return list<array{0: string, 1: string}> */
    protected function tabs(): array
    {
        return [];
    }

    protected function save(Model $item, Request $request): void
    {
        $fields = $this->fields($item);
        [$input, $parseErrors] = $this->normalize($fields, $request);

        $validator = Validator::make($input, $this->rulesFor($fields, $item), [], $this->attributeNames($fields));
        $validator->after(function (ValidatorInstance $v) use ($parseErrors, $input, $item) {
            foreach ($parseErrors as $field => $message) {
                $v->errors()->add($field, $message);
            }
            $this->validateMore($v, $input, $item);
        });
        $data = $validator->validate();

        DB::transaction(function () use ($item, $fields, $data, $input, $request) {
            foreach ($fields as $field) {
                $name = $field['name'];
                if (in_array($field['type'], ['checklist'], true) || ($field['virtual'] ?? false)) {
                    continue;
                }
                $item->setAttribute($name, $data[$name] ?? null);
            }
            foreach ($this->precisionColumns($fields) as $column => $names) {
                $item->setAttribute($column, $this->coarsestPrecision(array_map(fn ($n) => $input['__precision'][$n] ?? null, $names)));
            }
            $this->beforeSave($item, $data);
            $item->save();
            $this->afterSave($item, $data, $request);
        });
    }

    protected function beforeSave(Model $item, array $data): void
    {
        // Publishing without a date publishes now.
        if ($item->isFillable('status') && $item->getAttribute('status') === 'published' && $item->getAttribute('published_at') === null) {
            $item->setAttribute('published_at', now());
        }
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, string>}
     */
    protected function normalize(array $fields, Request $request): array
    {
        $input = ['__precision' => []];
        $errors = [];

        foreach ($fields as $field) {
            $name = $field['name'];
            $raw = $request->input($name);
            $text = is_string($raw) ? trim($raw) : $raw;

            try {
                $input[$name] = match ($field['type']) {
                    'checkbox' => $request->boolean($name),
                    'number', 'money' => $this->toInteger($text, $field['type'] === 'money'),
                    'media', 'select' => $text === '' ? null : $text,
                    'jdate' => (function () use ($text, $name, &$input) {
                        $parsed = JalaliDate::parse(is_string($text) ? $text : null);
                        $input['__precision'][$name] = $parsed[1] ?? null;

                        return $parsed ? $parsed[0]->toDateString() : null;
                    })(),
                    'jdatetime' => JalaliDate::parseDateTime(is_string($text) ? $text : null)?->toDateTimeString(),
                    'lines' => $this->toLines($text),
                    'links' => $this->toLinks($text),
                    'checklist' => array_values(array_filter((array) $raw, 'is_scalar')),
                    default => $text === '' ? null : $text,
                };
            } catch (InvalidArgumentException $e) {
                $input[$name] = null;
                $errors[$name] = match ($field['type']) {
                    'jdate' => "{$field['label']}: تاریخ شمسی را به شکل ۱۴۰۲ یا ۱۴۰۲/۰۱ یا ۱۴۰۲/۰۱/۱۵ وارد کنید.",
                    'jdatetime' => "{$field['label']}: به شکل ۱۴۰۵/۰۷/۱۸ ۱۴:۳۰ وارد کنید (ساعت اختیاری است).",
                    default => "{$field['label']} معتبر نیست.",
                };
            }
        }

        return [$input, $errors];
    }

    protected function rulesFor(array $fields, Model $item): array
    {
        $rules = [];
        foreach ($fields as $field) {
            $base = $field['rules'] ?? match ($field['type']) {
                'email' => ['email:rfc', 'max:191'],
                'url' => ['url:http,https', 'max:500'],
                'textarea' => ['string', 'max:5000'],
                'markdown' => ['string', 'max:200000'],
                'number' => ['integer', 'min:0', 'max:65535'],
                'money' => ['integer', 'min:0', 'max:100000000000'],
                'checkbox' => ['boolean'],
                'select' => ['string', 'in:'.implode(',', array_keys($field['options'] ?? []))],
                'media' => ['integer', 'exists:media,id'],
                'jdate', 'jdatetime' => ['date'],
                'lines' => ['array', 'max:50'],
                'links' => ['array', 'max:20'],
                'checklist' => ['array'],
                default => ['string', 'max:191'],
            };
            $presence = ($field['required'] ?? false) ? 'required' : 'nullable';
            $rules[$field['name']] = array_merge([$presence], $base);

            if ($field['type'] === 'lines') {
                $rules[$field['name'].'.*'] = ['string', 'max:300'];
            }
            if ($field['type'] === 'links') {
                $rules[$field['name'].'.*.label'] = ['required', 'string', 'max:50'];
                $rules[$field['name'].'.*.url'] = ['required', 'url:http,https', 'max:500'];
            }
            if ($field['type'] === 'checklist') {
                $rules[$field['name'].'.*'] = ['integer', 'in:'.implode(',', array_keys($field['options'] ?? []))];
            }
        }

        return $rules;
    }

    protected function attributeNames(array $fields): array
    {
        return collect($fields)->mapWithKeys(fn ($f) => [
            $f['name'] => $f['label'],
            $f['name'].'.*' => $f['label'],
            $f['name'].'.*.label' => 'عنوان پیوند در «'.$f['label'].'»',
            $f['name'].'.*.url' => 'نشانی پیوند در «'.$f['label'].'»',
        ])->all();
    }

    protected function formValues(Model $item, array $fields): array
    {
        $values = [];
        foreach ($fields as $field) {
            $name = $field['name'];
            $value = $item->getAttribute($name);
            $values[$name] = match ($field['type']) {
                'jdate' => JalaliDate::toInput($value, ($field['precision'] ?? 'date_precision') === false ? 'day' : ($item->getAttribute($field['precision'] ?? 'date_precision') ?? 'day')),
                'jdatetime' => JalaliDate::toDateTimeInput($value),
                'lines' => implode("\n", (array) ($value ?? [])),
                'links' => collect($value ?? [])->map(fn ($l) => ($l['label'] ?? '').' | '.($l['url'] ?? ''))->implode("\n"),
                'checklist' => $item->exists ? $item->{$name}()->pluck($item->{$name}()->getRelated()->getQualifiedKeyName())->map(fn ($v) => (string) $v)->all() : [],
                'checkbox' => (bool) ($value ?? ($field['default'] ?? false)),
                default => $value ?? ($field['default'] ?? null),
            };
        }

        return $values;
    }

    /** @return array<int, string> */
    protected function mediaOptions(array $fields): array
    {
        $mediaFields = array_filter($fields, fn ($f) => $f['type'] === 'media');
        if (! $mediaFields) {
            return [];
        }
        $imagesOnly = collect($mediaFields)->every(fn ($f) => ($f['accept'] ?? null) === 'image');

        return Media::query()
            ->when($imagesOnly, fn ($q) => $q->where('mime', 'like', 'image/%'))
            ->latest('id')->limit(500)->get(['id', 'alt', 'original_name', 'mime'])
            ->mapWithKeys(fn (Media $m) => [$m->id => $m->label()])->all();
    }

    protected function find(int $id): Model
    {
        return $this->baseQuery()->findOrFail($id);
    }

    protected function baseQuery(): Builder
    {
        return ($this->model())::query();
    }

    protected function isSortable(): bool
    {
        return in_array(Sortable::class, class_uses_recursive($this->model()), true);
    }

    protected function usesSoftDeletes(): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($this->model()), true);
    }

    /** @return array<string, list<string>> precision column => jdate field names */
    private function precisionColumns(array $fields): array
    {
        $map = [];
        foreach ($fields as $field) {
            if ($field['type'] === 'jdate' && ($field['precision'] ?? null) !== false) {
                $map[$field['precision'] ?? 'date_precision'][] = $field['name'];
            }
        }

        return $map;
    }

    /** Never claim more precision than every date in the range has. */
    private function coarsestPrecision(array $precisions): string
    {
        $precisions = array_filter($precisions);
        foreach (['year', 'month', 'day'] as $p) {
            if (in_array($p, $precisions, true)) {
                return $p;
            }
        }

        return 'year';
    }

    private function toInteger(mixed $value, bool $money): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $clean = Jalali::asciiDigits((string) $value);
        if ($money) {
            $clean = str_replace([',', '٬', '،', ' ', "\u{066B}", '.'], '', $clean);
        }
        if (! preg_match('/^\d{1,15}$/', $clean)) {
            throw new InvalidArgumentException('integer');
        }

        return (int) $clean;
    }

    private function toLines(mixed $value): ?array
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', $value) ?: []), fn ($l) => $l !== ''));

        return $lines ?: null;
    }

    /** «label | https://…» per line. */
    private function toLinks(mixed $value): ?array
    {
        $lines = $this->toLines($value);
        if ($lines === null) {
            return null;
        }

        return array_map(function (string $line) {
            [$label, $url] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');

            return ['label' => $label, 'url' => $url];
        }, $lines);
    }

    // ---- Helpers shared by modules ------------------------------------

    /** @return array{0: string, 1: string} badge [variant, text] */
    protected static function stateBadge(Model $item): array
    {
        return match ($item->publicationState()) {
            'published' => ['success', 'منتشرشده'],
            'scheduled' => ['accent', 'زمان‌بندی‌شده'],
            default => ['warning', 'پیش‌نویس'],
        };
    }

    /** @return array{0: string, 1: string} */
    protected static function visibleBadge(bool $visible): array
    {
        return $visible ? ['success', 'نمایش'] : ['warning', 'پنهان'];
    }

    protected function publishingFields(): array
    {
        return [
            ['name' => 'status', 'label' => 'وضعیت', 'type' => 'select', 'required' => true, 'options' => Article::STATUSES, 'default' => 'draft'],
            ['name' => 'published_at', 'label' => 'زمان انتشار', 'type' => 'jdatetime', 'hint' => 'خالی = همین حالا (هنگام انتشار). زمان آینده = انتشار زمان‌بندی‌شده.'],
        ];
    }

    protected function seoFields(): array
    {
        return [
            ['name' => 'meta_title', 'label' => 'عنوان سئو', 'type' => 'text', 'rules' => ['string', 'max:70'], 'hint' => 'خالی = عنوان اصلی. حداکثر ۷۰ نویسه.'],
            ['name' => 'meta_description', 'label' => 'توضیح متا', 'type' => 'textarea', 'rows' => 2, 'rules' => ['string', 'max:170'], 'hint' => 'حداکثر ۱۷۰ نویسه.'],
            ['name' => 'og_image_id', 'label' => 'تصویر اشتراک‌گذاری (OG)', 'type' => 'media', 'accept' => 'image'],
            ['name' => 'canonical_url', 'label' => 'نشانی Canonical', 'type' => 'url', 'hint' => 'فقط اگر نسخهٔ اصلی این محتوا جای دیگری است.'],
            ['name' => 'noindex', 'label' => 'در نتایج موتور جست‌وجو نمایش داده نشود (noindex)', 'type' => 'checkbox'],
        ];
    }

    /** Info lines for the aside of publishable items. */
    protected function publicationAside(Model $item): array
    {
        $state = static::stateBadge($item)[1];
        $lines = ['وضعیت: '.$state];
        if ($item->published_at) {
            $lines[] = 'انتشار: '.Jalali::format($item->published_at, 'j F Y، H:i');
        }

        return $lines;
    }
}
