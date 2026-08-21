<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $appName = SystemSetting::get('app_name', 'XPanel');

        return view('admin.settings.index', compact('appName'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'app_name' => 'required|string|max:80',
        ]);

        SystemSetting::set('app_name', trim($request->input('app_name')));

        return back()->with('status', 'Configuración guardada.');
    }
}
