<?php

namespace App\Services;

use App\Models\AccountNotification;
use App\Models\HostingAccount;
use App\Models\Tenant;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Mail;
use App\Mail\AccountAlertMail;

class AccountNotifier
{
    public function send(
        Tenant $tenant,
        string $type,
        string $title,
        string $message,
        ?HostingAccount $account = null,
        ?string $actionUrl = null,
        array $data = [],
    ): AccountNotification {
        if ($actionUrl !== null && (! str_starts_with($actionUrl, '/') || str_starts_with($actionUrl, '//'))) {
            throw new \InvalidArgumentException('La dirección de la notificación debe ser interna.');
        }

        $notification = AccountNotification::create([
            'tenant_id' => $tenant->id,
            'hosting_account_id' => $account?->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'data' => $data ?: null,
        ]);

        if (SystemSetting::get('notification_email_enabled', '0') === '1' && filled($tenant->user?->email)) {
            try {
                Mail::to($tenant->user->email, $tenant->user->name)->send(new AccountAlertMail($title, $message));
                $notification->update(['email_sent_at' => now(), 'email_error' => null]);
            } catch (\Throwable $exception) {
                report($exception);
                $notification->update(['email_error' => mb_substr($exception->getMessage(), 0, 2000)]);
            }
        }

        return $notification->fresh();
    }
}
