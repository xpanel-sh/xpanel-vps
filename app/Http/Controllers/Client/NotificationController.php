<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\AccountNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $request->attributes->get('tenant');
        $notifications = $tenant->accountNotifications()->latest()->paginate(20);
        $unreadCount = $tenant->accountNotifications()->whereNull('read_at')->count();

        return view('client.notifications.index', compact('notifications', 'unreadCount'));
    }

    public function read(Request $request, AccountNotification $notification)
    {
        $tenant = $request->attributes->get('tenant');
        abort_unless($notification->tenant_id === $tenant?->id, 404);
        $notification->update(['read_at' => $notification->read_at ?: now()]);

        return $notification->action_url
            ? redirect($notification->action_url)
            : back()->with('success', 'Notificación marcada como leída.');
    }

    public function readAll(Request $request)
    {
        $tenant = $request->attributes->get('tenant');
        $tenant->accountNotifications()->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('success', 'Todas las notificaciones fueron marcadas como leídas.');
    }
}
