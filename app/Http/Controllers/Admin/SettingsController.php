<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HostInstance;
use App\Models\SystemSetting;
use App\Services\ServerCommandRunner;
use Illuminate\Http\Request;
use RuntimeException;

class SettingsController extends Controller
{
    public function index()
    {
        $appName = SystemSetting::get('app_name', 'XPanel');
        $panelDomain = SystemSetting::get('panel_domain', (string) env('XPANEL_PANEL_DOMAIN', ''));
        $panelDomainStatus = SystemSetting::get('panel_domain_status', $panelDomain ? 'active' : 'not_configured');
        $recoveryUrl = 'https://'.config('xpanel.server_ip').':'.config('xpanel.panel_port');

        return view('admin.settings.index', compact('appName', 'panelDomain', 'panelDomainStatus', 'recoveryUrl'));
    }

    public function update(Request $request, ServerCommandRunner $commands)
    {
        $data = $request->validate([
            'app_name' => 'required|string|max:80',
            'panel_domain' => ['nullable', 'string', 'max:253', 'regex:/^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/'],
        ]);

        $currentDomain = SystemSetting::get('panel_domain', (string) env('XPANEL_PANEL_DOMAIN', ''));
        $panelDomain = strtolower(trim((string) ($data['panel_domain'] ?? '')));
        if ($currentDomain !== '' && $panelDomain === '') {
            return back()->withErrors(['panel_domain' => 'Configura otro dominio antes de retirar el dominio activo.'])->withInput();
        }
        if ($panelDomain !== '' && $panelDomain !== $currentDomain && HostInstance::query()->exists()) {
            return back()->withErrors(['panel_domain' => 'No se puede cambiar el dominio base mientras existan cuentas de hosting.'])->withInput();
        }

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
        }

        SystemSetting::set('app_name', trim($data['app_name']));

        return back()->with('status', $panelDomain !== '' && $panelDomain !== $currentDomain
            ? 'Dominio configurado. El acceso por IP y puerto permanece disponible para recuperación.'
            : 'Configuración guardada.');
    }
}
