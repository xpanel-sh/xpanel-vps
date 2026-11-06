<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\HostInstanceDomainMigrator;
use App\Services\ServerCommandRunner;
use Illuminate\Http\Request;
use RuntimeException;

class SettingsController extends Controller
{
    public function index()
    {
        $appName = SystemSetting::get('app_name', 'XPanel');
        $hostName = SystemSetting::get('host_name', $appName.' Hosting');
        $panelDomain = SystemSetting::get('panel_domain', (string) env('XPANEL_PANEL_DOMAIN', ''));
        $panelDomainStatus = SystemSetting::get('panel_domain_status', $panelDomain ? 'active' : 'not_configured');
        $recoveryUrl = 'https://'.config('xpanel.server_ip').':'.config('xpanel.panel_port');

        $graceDays = (int) SystemSetting::get('service_grace_days', '7');
        $retentionDays = (int) SystemSetting::get('cancelled_data_retention_days', '30');
        return view('admin.settings.index', compact('appName', 'hostName', 'panelDomain', 'panelDomainStatus', 'recoveryUrl', 'graceDays', 'retentionDays'));
    }

    public function update(Request $request, ServerCommandRunner $commands, HostInstanceDomainMigrator $instances)
    {
        $data = $request->validate([
            'app_name' => 'required|string|max:80',
            'host_name' => ['sometimes', 'required', 'string', 'max:80', 'regex:/^[^\r\n]+$/u'],
            'service_grace_days' => ['sometimes', 'required', 'integer', 'min:0', 'max:90'],
            'cancelled_data_retention_days' => ['sometimes', 'required', 'integer', 'min:0', 'max:365'],
            'panel_domain' => ['nullable', 'string', 'max:253', 'regex:/^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/'],
        ]);

        $currentDomain = SystemSetting::get('panel_domain', (string) env('XPANEL_PANEL_DOMAIN', ''));
        $panelDomain = strtolower(trim((string) ($data['panel_domain'] ?? '')));
        if ($currentDomain !== '' && $panelDomain === '') {
            return back()->withErrors(['panel_domain' => 'Configura otro dominio antes de retirar el dominio activo.'])->withInput();
        }
        $migration = ['migrated' => 0, 'failed' => 0];
        if ($panelDomain !== '' && $panelDomain !== $currentDomain) {
            try {
                if (config('xpanel.native_hosting.apply_system_changes')) {
                    $certificateEmail = (string) $request->user('admin')?->email;
                    if ($certificateEmail === '' || str_ends_with($certificateEmail, '@xpanel.local')) {
                        $certificateEmail = 'admin@'.$panelDomain;
                    }
                    $commands->run([
                        'sudo', '-n', config('xpanel.control_plane_helper'), 'set-domain',
                        $panelDomain,
                        $certificateEmail,
                        (string) config('xpanel.server_ip'),
                        (string) config('xpanel.panel_port'),
                        PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION,
                    ], null, 600);
                }
            } catch (RuntimeException $exception) {
                return back()->withErrors(['panel_domain' => $exception->getMessage()])->withInput();
            }

            SystemSetting::set('panel_domain', $panelDomain);
            SystemSetting::set('panel_domain_status', config('xpanel.native_hosting.apply_system_changes') ? 'active' : 'staged');
            $migration = $instances->migrateTechnicalDomains($panelDomain);
        }

        SystemSetting::set('app_name', trim($data['app_name']));
        if (array_key_exists('host_name', $data)) SystemSetting::set('host_name', trim($data['host_name']));
        if (array_key_exists('service_grace_days', $data)) SystemSetting::set('service_grace_days', (string) $data['service_grace_days']);
        if (array_key_exists('cancelled_data_retention_days', $data)) SystemSetting::set('cancelled_data_retention_days', (string) $data['cancelled_data_retention_days']);

        return back()->with('status', $panelDomain !== '' && $panelDomain !== $currentDomain
            ? 'Dominio configurado y '.$migration['migrated'].' instancia(s) técnica(s) actualizada(s).'
                .($migration['failed'] ? ' '.$migration['failed'].' requieren revisión.' : '')
                .' El acceso por IP y puerto permanece disponible para recuperación.'
            : 'Configuración guardada.');
    }
}
