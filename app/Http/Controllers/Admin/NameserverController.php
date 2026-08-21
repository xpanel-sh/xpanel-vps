<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NameserverSetting;
use App\Services\DaemonClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NameserverController extends Controller
{
    public function edit()
    {
        $settings = NameserverSetting::firstOrCreate(['name' => 'default'], [
            'provider' => 'xpanel',
            'is_active' => true,
        ]);

        return view('admin.dns.nameservers', compact('settings'));
    }

    public function update(Request $request, DaemonClient $daemon)
    {
        $validated = $request->validate([
            'ns1' => ['nullable', 'string', 'max:255'],
            'ns2' => ['nullable', 'string', 'max:255'],
            'ns3' => ['nullable', 'string', 'max:255'],
            'ns4' => ['nullable', 'string', 'max:255'],
            'provider' => ['required', 'in:xpanel,cloudflare,external'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $settings = NameserverSetting::firstOrCreate(['name' => 'default']);
        $settings->update([
            ...$validated,
            'is_active' => $request->boolean('is_active'),
        ]);

        try {
            $daemon->applyNameservers($settings->records());
        } catch (\Throwable $e) {
            Log::warning('Nameserver apply failed', ['nameserver_setting_id' => $settings->id, 'exception' => $e]);

            return redirect()->route('admin.dns.nameservers')
                ->withErrors(['nameservers' => 'Nameservers guardados, pero el agente no pudo aplicarlos. Revisa operaciones del agente o los logs.']);
        }

        return redirect()->route('admin.dns.nameservers')->with('success', 'Nameservers actualizados correctamente.');
    }
}
