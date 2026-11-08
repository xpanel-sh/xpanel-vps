<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DatabaseEngineManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DatabaseEngineController extends Controller
{
    public function index(DatabaseEngineManager $manager): View
    {
        return view('admin.settings.database-engines', ['engines' => $manager->statuses()]);
    }

    public function install(string $engine, DatabaseEngineManager $manager): RedirectResponse
    {
        try { $manager->install($engine); }
        catch (\Throwable $exception) { return back()->withErrors(['server' => $exception->getMessage()]); }
        return back()->with('status', DatabaseEngineManager::ENGINES[$engine]['label'].' quedó disponible para las instancias Host.');
    }
}
