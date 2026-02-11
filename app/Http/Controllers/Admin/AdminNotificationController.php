<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendAdminNotification;
use App\Models\AdminNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminNotificationController extends Controller
{
    public function index(): Response
    {
        $notifications = AdminNotification::with('sender')
            ->latest()
            ->paginate(15)
            ->through(fn (AdminNotification $notification) => [
                'id' => $notification->id,
                'subject' => $notification->subject,
                'audience' => $notification->audience,
                'audience_label' => $notification->audienceLabel(),
                'sent_count' => $notification->sent_count,
                'status' => $notification->status,
                'sent_by_name' => $notification->sender?->name,
                'sent_at' => $notification->sent_at?->toISOString(),
                'created_at' => $notification->created_at->toISOString(),
            ]);

        return Inertia::render('Admin/Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Notifications/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'audience' => ['required', 'string', 'in:all,active,expired'],
        ]);

        $notification = AdminNotification::create([
            ...$validated,
            'sent_by' => $request->user()->id,
        ]);

        SendAdminNotification::dispatch($notification);

        return redirect()
            ->route('admin.notifications.index')
            ->with('success', __('admin.notification_sent'));
    }
}
