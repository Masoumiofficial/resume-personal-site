<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\MediaStore;
use App\Support\Activity;
use App\Support\Jalali;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;
use RuntimeException;

class MediaController extends Controller
{
    public function __construct(private readonly MediaStore $store) {}

    public function index(Request $request): View
    {
        $query = Media::query()->latest('id');
        $type = (string) $request->query('type', '');
        if ($type === 'image') {
            $query->where('mime', 'like', 'image/%');
        } elseif ($type === 'document') {
            $query->where('mime', 'application/pdf');
        }
        if ($request->boolean('unused')) {
            foreach (Media::REFERENCES as [$table, $column]) {
                $query->whereNotIn('id', DB::table($table)->whereNotNull($column)->select($column));
            }
        }
        if (($q = trim((string) $request->query('q', ''))) !== '') {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';
            $query->where(fn ($w) => $w->where('original_name', 'like', $like)->orWhere('alt', 'like', $like));
        }

        return view('admin.media.index', [
            'items' => $query->paginate(36)->withQueryString(),
            'type' => $type,
            'unused' => $request->boolean('unused'),
            'search' => $q,
            'maxMb' => (int) round(MediaStore::MAX_KB / 1024),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'files' => ['required', 'array', 'max:10'],
            'files.*' => ['required', File::types(['jpg', 'jpeg', 'png', 'webp', 'pdf'])->max(MediaStore::MAX_KB)],
            'alt' => ['nullable', 'string', 'max:191'],
        ], [], ['files' => 'فایل', 'files.*' => 'فایل', 'alt' => 'متن جایگزین']);

        $added = 0;
        $duplicates = 0;
        $failed = [];
        foreach ($request->file('files') as $file) {
            try {
                [$media, $duplicate] = $this->store->store($file, $request->user()->id, $request->input('alt') ?: null);
                $duplicate ? $duplicates++ : $added++;
                if (! $duplicate) {
                    Activity::log('media.uploaded', $media, ['title' => $media->original_name]);
                }
            } catch (RuntimeException $e) {
                $failed[] = $file->getClientOriginalName().': '.$e->getMessage();
            }
        }

        $message = trim(($added ? "{$added} فایل بارگذاری شد. " : '').($duplicates ? "{$duplicates} فایل تکراری بود و از نسخهٔ موجود استفاده شد." : ''));
        $redirect = redirect()->route('admin.media.index');
        if ($message !== '') {
            $redirect->with('flash', Jalali::digits($message));
        }

        return $failed ? $redirect->with('flash_error', implode(' — ', $failed)) : $redirect;
    }

    public function edit(int $id): View
    {
        $media = Media::query()->with('uploader:id,name')->findOrFail($id);

        return view('admin.media.edit', ['media' => $media, 'usages' => $this->store->usages($media)]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $media = Media::query()->findOrFail($id);
        $data = $request->validate([
            'alt' => ['nullable', 'string', 'max:191'],
            'caption' => ['nullable', 'string', 'max:1000'],
        ], [], ['alt' => 'متن جایگزین', 'caption' => 'توضیح']);
        $media->update($data);
        Activity::log('media.updated', $media, ['title' => $media->original_name]);

        return redirect()->route('admin.media.edit', $media->id)->with('flash', 'ذخیره شد.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $media = Media::query()->findOrFail($id);
        $usages = $this->store->usages($media);
        if ($usages && ! $request->boolean('confirm_in_use')) {
            return back()->with('flash_error', 'این فایل هنوز استفاده می‌شود. برای حذف، گزینهٔ «با وجود استفاده حذف شود» را تیک بزنید.');
        }
        $name = $media->original_name;
        $this->store->delete($media);
        Activity::log('media.deleted', null, ['title' => $name]);

        return redirect()->route('admin.media.index')->with('flash', "«{$name}» حذف شد.");
    }
}
