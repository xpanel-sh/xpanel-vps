<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServerNode;
use App\Models\SoftwarePackage;
use App\Services\NativePackageManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class SoftwarePackageController extends Controller
{
    public function index(NativePackageManager $packages)
    {
        $node = ServerNode::query()->where('is_active', true)->first();
        $catalog = collect($packages->catalog());
        $webservers = $catalog->where('category', 'webserver')->values();
        $phpVersions = $catalog->where('category', 'php')->values();

        return view('admin.software-packages.index', compact('webservers', 'phpVersions', 'node'));
    }

    public function install(Request $request, NativePackageManager $packages)
    {
        $slugs = collect($packages->catalog())->pluck('slug')->all();
        $validated = $request->validate(['slug' => ['required', Rule::in($slugs)]]);

        try {
            $packages->install($validated['slug']);
        } catch (\Throwable $e) {
            Log::error('Native package installation failed', [
                'slug' => $validated['slug'],
                'exception' => $e,
            ]);

            return back()->withErrors(['slug' => 'No se pudo instalar el paquete: '.$e->getMessage()]);
        }

        return back()->with('success', 'Paquete nativo instalado correctamente. Ahora puedes habilitarlo para clientes.');
    }

    public function toggle(Request $request, NativePackageManager $packages)
    {
        $catalog = collect($packages->catalog());
        $validated = $request->validate([
            'slug' => ['required', Rule::in($catalog->pluck('slug')->all())],
        ]);
        $definition = $catalog->firstWhere('slug', $validated['slug']);

        if (! $definition || ! $definition['installed'] || ! $definition['service_active']) {
            return back()->withErrors(['slug' => 'El paquete debe estar instalado y activo antes de ofrecerlo.']);
        }

        $node = ServerNode::query()->where('is_active', true)->first();
        if (! $node) {
            return back()->withErrors(['slug' => 'No hay un servidor activo.']);
        }

        $package = SoftwarePackage::firstOrNew([
            'server_node_id' => $node->id,
            'slug' => $validated['slug'],
        ]);
        $currentlyEnabled = $package->exists
            ? $package->enabled_for_clients
            : (bool) $definition['enabled_for_clients'];
        $package->category = $definition['category'];
        $package->enabled_for_clients = ! $currentlyEnabled;
        $package->save();

        return back()->with('success', 'Disponibilidad para clientes actualizada.');
    }
}
