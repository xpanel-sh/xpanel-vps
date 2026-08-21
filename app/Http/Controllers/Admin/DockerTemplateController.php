<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DockerAppTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DockerTemplateController extends Controller
{
    public function index()
    {
        $templates = DockerAppTemplate::withCount('instances')
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return view('admin.docker.index', compact('templates'));
    }

    public function create()
    {
        return view('admin.docker.create', ['template' => new DockerAppTemplate]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['parameters'] = $this->parseParameters($request->input('parameters'));

        DockerAppTemplate::create($data);

        return redirect()->route('admin.docker.index')
            ->with('success', 'Template de app creado correctamente.');
    }

    public function edit(DockerAppTemplate $dockerTemplate)
    {
        return view('admin.docker.edit', ['template' => $dockerTemplate]);
    }

    public function update(Request $request, DockerAppTemplate $dockerTemplate)
    {
        $data = $this->validated($request, $dockerTemplate);
        $data['parameters'] = $this->parseParameters($request->input('parameters'));

        $dockerTemplate->update($data);

        return redirect()->route('admin.docker.index')
            ->with('success', 'Template actualizado correctamente.');
    }

    public function destroy(DockerAppTemplate $dockerTemplate)
    {
        $dockerTemplate->delete();

        return redirect()->route('admin.docker.index')
            ->with('success', 'Template eliminado.');
    }

    // ── private ───────────────────────────────────────────────────────────────

    private function validated(Request $request, ?DockerAppTemplate $template = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('docker_app_templates', 'slug')->ignore($template)],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:60'],
            'compose_template' => ['required', 'string'],
            'is_public' => ['nullable', 'boolean'],
        ]) + ['is_public' => $request->boolean('is_public')];
    }

    private function parseParameters(?string $raw): ?array
    {
        if (! $raw) {
            return null;
        }
        $params = json_decode($raw, true);
        if (! is_array($params)) {
            return null;
        }

        return array_values(array_filter($params, fn ($p) => ! empty($p['key']) && ! empty($p['label'])));
    }
}
