<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\DockerAppTemplate;
use App\Models\DockerInstance;
use App\Models\Domain;
use App\Services\DaemonClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DockerController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $request->attributes->get('tenant');

        $instances = DockerInstance::where('tenant_id', $tenant->id)
            ->with('template')
            ->latest()
            ->get();

        $templates = DockerAppTemplate::where('is_public', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return view('client.docker.index', compact('instances', 'templates', 'tenant'));
    }

    public function create(Request $request)
    {
        $tenant = $request->attributes->get('tenant');

        $templates = DockerAppTemplate::where('is_public', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        // Datos contextuales para campos especiales en los parámetros del template
        $tenantDomains = Domain::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('domain')
            ->pluck('domain')
            ->values()
            ->all();

        $planStorageMb = $tenant->plan?->storage_mb ?? 10240;

        // Pre-selección vía ?app=id desde la tienda
        $preselectedId = (int) request('app');

        return view('client.docker.create', compact('templates', 'tenant', 'tenantDomains', 'planStorageMb', 'preselectedId'));
    }

    public function store(Request $request)
    {
        $tenant = $request->attributes->get('tenant');

        $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'slug' => ['required', 'string', 'regex:/^[a-z0-9][a-z0-9-]{0,48}[a-z0-9]$/'],
            'domain' => ['nullable', 'string', 'max:253'],
            'template_id' => ['required', 'exists:docker_app_templates,id'],
            'params' => ['nullable', 'array'],
        ]);

        if (DockerInstance::where('tenant_id', $tenant->id)->where('slug', $request->slug)->exists()) {
            return back()->withErrors(['slug' => 'Ya existe una app con ese identificador.'])->withInput();
        }

        // YAML siempre viene del template en la DB
        $template = DockerAppTemplate::findOrFail($request->template_id);
        $composeYaml = $template->compose_template;
        $envValues = [];

        foreach ($template->parameters ?? [] as $param) {
            $key = $param['key'];
            $value = $request->input("params.{$key}", $param['default'] ?? '');
            $envValues[$key] = $value;
            $composeYaml = str_replace("{{{$key}}}", $value, $composeYaml);
        }

        $systemVars = [
            'TENANT_CODE' => $tenant->code,
            'SLUG' => $request->slug,
            'CONTAINER_NAME' => 'xpanel-docker-'.strtolower($tenant->code).'-'.$request->slug,
            'DOMAIN' => $request->domain ?? '',
        ];
        foreach ($systemVars as $k => $v) {
            $composeYaml = str_replace("{{{$k}}}", $v, $composeYaml);
        }

        $instance = DockerInstance::create([
            'tenant_id' => $tenant->id,
            'docker_app_template_id' => $request->template_id,
            'name' => $request->name,
            'slug' => $request->slug,
            'compose_yaml' => $composeYaml,
            'env_values' => $envValues ?: null,
            'domain' => $request->domain ?: null,
            'status' => 'provisioning',
        ]);

        try {
            $daemon = app(DaemonClient::class);
            $daemon->dockerAppCreate($tenant->code, $instance->slug, $instance->compose_yaml);
            $instance->update(['status' => 'running']);
        } catch (\RuntimeException $e) {
            $instance->update(['status' => 'error']);
            Log::error('Docker app create failed', ['instance' => $instance->id, 'error' => $e->getMessage()]);

            return redirect()->route('client.docker.show', $instance)
                ->with('error', 'La app se guardó pero falló al arrancar: '.$e->getMessage());
        }

        return redirect()->route('client.docker.show', $instance)
            ->with('success', 'App Docker creada y arrancada.');
    }

    public function show(Request $request, DockerInstance $dockerInstance)
    {
        $this->authorizeInstance($request, $dockerInstance);

        $dockerInstance->loadMissing('template');

        // Obtener estado real del daemon
        try {
            $daemon = app(DaemonClient::class);
            $tenant = $request->attributes->get('tenant');
            $statusData = $daemon->dockerAppStatus($tenant->code, $dockerInstance->slug);
            $dockerInstance->update(['status' => $statusData['status'] ?? $dockerInstance->status]);
            $dockerInstance->status = $statusData['status'] ?? $dockerInstance->status;
            $services = $statusData['services'] ?? [];
        } catch (\RuntimeException $e) {
            $services = [];
        }

        return view('client.docker.show', compact('dockerInstance', 'services'));
    }

    public function update(Request $request, DockerInstance $dockerInstance)
    {
        $tenant = $this->authorizeInstance($request, $dockerInstance);

        $request->validate([
            'compose_yaml' => ['required', 'string'],
        ]);

        $dockerInstance->update(['compose_yaml' => $request->compose_yaml, 'status' => 'provisioning']);

        try {
            $daemon = app(DaemonClient::class);
            $daemon->dockerAppUpdate($tenant->code, $dockerInstance->slug, $request->compose_yaml);
            $dockerInstance->update(['status' => 'running']);
        } catch (\RuntimeException $e) {
            $dockerInstance->update(['status' => 'error']);

            return back()->with('error', 'Error al aplicar cambios: '.$e->getMessage());
        }

        return back()->with('success', 'Compose actualizado y contenedor reiniciado.');
    }

    public function start(Request $request, DockerInstance $dockerInstance)
    {
        $tenant = $this->authorizeInstance($request, $dockerInstance);

        try {
            app(DaemonClient::class)->dockerAppStart($tenant->code, $dockerInstance->slug);
            $dockerInstance->update(['status' => 'running']);
        } catch (\RuntimeException $e) {
            return back()->with('error', 'Error al arrancar: '.$e->getMessage());
        }

        return back()->with('success', 'App iniciada.');
    }

    public function stop(Request $request, DockerInstance $dockerInstance)
    {
        $tenant = $this->authorizeInstance($request, $dockerInstance);

        try {
            app(DaemonClient::class)->dockerAppStop($tenant->code, $dockerInstance->slug);
            $dockerInstance->update(['status' => 'stopped']);
        } catch (\RuntimeException $e) {
            return back()->with('error', 'Error al detener: '.$e->getMessage());
        }

        return back()->with('success', 'App detenida.');
    }

    public function restart(Request $request, DockerInstance $dockerInstance)
    {
        $tenant = $this->authorizeInstance($request, $dockerInstance);

        try {
            app(DaemonClient::class)->dockerAppRestart($tenant->code, $dockerInstance->slug);
            $dockerInstance->update(['status' => 'running']);
        } catch (\RuntimeException $e) {
            return back()->with('error', 'Error al reiniciar: '.$e->getMessage());
        }

        return back()->with('success', 'App reiniciada.');
    }

    public function destroy(Request $request, DockerInstance $dockerInstance)
    {
        $tenant = $this->authorizeInstance($request, $dockerInstance);

        try {
            app(DaemonClient::class)->dockerAppDelete($tenant->code, $dockerInstance->slug);
        } catch (\RuntimeException $e) {
            Log::warning('Docker app delete daemon error', ['id' => $dockerInstance->id, 'error' => $e->getMessage()]);
        }

        $dockerInstance->delete();

        return redirect()->route('client.docker.index')
            ->with('success', 'App Docker eliminada.');
    }

    public function logs(Request $request, DockerInstance $dockerInstance)
    {
        $tenant = $this->authorizeInstance($request, $dockerInstance);

        $logs = '';
        try {
            $logs = app(DaemonClient::class)->dockerAppLogs($tenant->code, $dockerInstance->slug, 200);
        } catch (\RuntimeException $e) {
            $logs = 'Error obteniendo logs: '.$e->getMessage();
        }

        return response()->json(['logs' => $logs]);
    }

    // ── private ───────────────────────────────────────────────────────────────

    private function authorizeInstance(Request $request, DockerInstance $dockerInstance)
    {
        $tenant = $request->attributes->get('tenant');
        if ($dockerInstance->tenant_id !== $tenant->id) {
            abort(403);
        }

        return $tenant;
    }
}
