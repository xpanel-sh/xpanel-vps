<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Support\PublicPageRegistry;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function index()
    {
        return view('admin.pages.index', ['pages' => PublicPageRegistry::PAGES]);
    }

    public function edit(string $page)
    {
        $this->assertPage($page);

        return view('admin.pages.edit', [
            'page' => $page,
            'label' => PublicPageRegistry::PAGES[$page],
            'content' => PublicPageRegistry::content($page),
        ]);
    }

    public function update(Request $request, string $page)
    {
        $this->assertPage($page);
        $rules = $page === 'home' ? [
            'company_name' => ['required', 'string', 'max:100'],
            'company_tagline' => ['required', 'string', 'max:180'],
            'hero_title' => ['required', 'string', 'max:180'],
            'hero_description' => ['required', 'string', 'max:500'],
            'company_description' => ['required', 'string', 'max:1000'],
            'support_email' => ['required', 'email', 'max:180'],
            'sales_email' => ['nullable', 'email', 'max:180'],
            'company_phone' => ['nullable', 'string', 'max:80'],
            'company_address' => ['nullable', 'string', 'max:300'],
            'currency_symbol' => ['required', 'string', 'max:8'],
            'cta_title' => ['required', 'string', 'max:180'],
            'cta_description' => ['required', 'string', 'max:500'],
        ] : [
            'title' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:30000'],
        ];
        $validated = $request->validate($rules);

        foreach ($validated as $field => $value) {
            SystemSetting::set("page_{$page}_{$field}", trim((string) ($value ?? '')));
        }

        return back()->with('status', 'Pagina actualizada correctamente.');
    }

    private function assertPage(string $page): void
    {
        abort_unless(array_key_exists($page, PublicPageRegistry::PAGES), 404);
    }
}
