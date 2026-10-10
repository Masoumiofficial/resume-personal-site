<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MessageController extends Controller
{
    /** Inbox = new + read; other statuses have their own tab. */
    private const INBOX = ['new', 'read'];

    public function index(Request $request): View
    {
        $status = (string) $request->query('status', 'inbox');
        if ($status !== 'inbox' && ! array_key_exists($status, ContactMessage::STATUSES)) {
            $status = 'inbox';
        }

        $messages = ContactMessage::query()
            ->when($status === 'inbox', fn ($q) => $q->whereIn('status', self::INBOX), fn ($q) => $q->where('status', $status))
            ->latest('id')
            ->paginate(30, ['id', 'name', 'email', 'subject', 'status', 'created_at'])
            ->withQueryString();

        $counts = DB::table('contact_messages')->select('status', DB::raw('count(*) as n'))->groupBy('status')->pluck('n', 'status');

        return view('admin.messages.index', compact('messages', 'status', 'counts'));
    }

    public function show(int $id): View
    {
        $message = ContactMessage::query()->findOrFail($id);
        if ($message->status === 'new') {
            $message->update(['status' => 'read']);
        }

        return view('admin.messages.show', compact('message'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $message = ContactMessage::query()->findOrFail($id);
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(ContactMessage::STATUSES))]]);
        $message->update([
            'status' => $data['status'],
            'handled_at' => in_array($data['status'], ['replied', 'archived', 'spam'], true) ? now() : null,
        ]);
        Activity::log('message.status_changed', $message, ['status' => $data['status']]);

        return redirect()->route('admin.messages.index')->with('flash', 'وضعیت پیام: «'.ContactMessage::STATUSES[$data['status']].'».');
    }

    public function destroy(int $id): RedirectResponse
    {
        $message = ContactMessage::query()->findOrFail($id);
        $message->delete();
        Activity::log('message.deleted');

        return redirect()->route('admin.messages.index')->with('flash', 'پیام حذف شد.');
    }
}
