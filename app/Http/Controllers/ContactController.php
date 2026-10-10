<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\Setting;
use App\Support\ContactForm;
use App\Support\IpHash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Public contact form submission. The form itself is rendered by the public
 * site (Phase 4). Bots get the same "sent" response as humans, so the
 * filters cannot be probed.
 */
class ContactController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        if (! Setting::get('contact_form_enabled')) {
            return back()->with('contact_error', 'فرم تماس موقتاً غیرفعال است. لطفاً از ایمیل استفاده کنید.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:191'],
            'subject' => ['nullable', 'string', 'max:191'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $sent = back()->with('contact_status', 'sent');

        // Honeypot filled or token missing/forged: almost certainly automated — drop silently.
        $elapsed = ContactForm::elapsed($request->input(ContactForm::TOKEN));
        if (filled($request->input(ContactForm::HONEYPOT)) || $elapsed === null) {
            return $sent;
        }

        // Too fast, expired form, or link-stuffed: keep for review, but in the spam folder.
        $suspicious = $elapsed < ContactForm::MIN_SECONDS
            || $elapsed > ContactForm::MAX_SECONDS
            || ContactForm::linkCount($data['message']) > 3;

        ContactMessage::create([
            'name' => $data['name'],
            'email' => Str::lower($data['email']),
            'subject' => $data['subject'] ?? null,
            'message' => $data['message'],
            'status' => $suspicious ? 'spam' : 'new',
            'ip_hash' => IpHash::make($request->ip()),
            'user_agent' => Str::limit((string) $request->userAgent(), 180, ''),
        ]);

        return $sent;
    }
}
