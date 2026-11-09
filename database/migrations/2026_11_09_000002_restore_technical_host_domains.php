<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Older admin forms stored a customer address in panel_domain. Keep
        // that address as the optional alias while restoring the stable
        // technical name. Existing TLS snippets stay in place until renewal.
        foreach (DB::table('host_instances')->orderBy('id')->get() as $instance) {
            $prefix = 'h-'.substr(str_replace('-', '', $instance->uuid), 0, 12).'.';
            if (str_starts_with($instance->panel_domain, $prefix) || ! $instance->hosting_account_id) {
                continue;
            }
            $account = DB::table('hosting_accounts')->where('id', $instance->hosting_account_id)->first();
            if (! $account) {
                continue;
            }
            $base = strtolower(trim((string) config('xpanel.host_instances.cloud_domain'), '.'));
            if (! filter_var($base, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
                continue;
            }
            $technical = $prefix.$base;
            if (DB::table('host_instances')->where('panel_domain', $technical)->exists()) {
                continue;
            }
            if (! $account->custom_panel_domain) {
                DB::table('hosting_accounts')->where('id', $account->id)->update([
                    'custom_panel_domain' => $instance->panel_domain,
                    'custom_domain_status' => $instance->ssl_status === 'active' ? 'active' : 'waiting_dns',
                ]);
            } elseif ($account->custom_panel_domain !== $instance->panel_domain) {
                DB::table('hosting_accounts')->where('id', $account->id)->update(['custom_domain_status' => 'waiting_dns']);
            }
            DB::table('host_instances')->where('id', $instance->id)->update([
                'panel_domain' => $technical,
                'ssl_status' => 'pending',
                'ssl_last_error' => null,
                'ssl_attempted_at' => null,
            ]);
        }
    }

    public function down(): void
    {
        // Never rewrite customer addresses during a rollback.
    }
};
