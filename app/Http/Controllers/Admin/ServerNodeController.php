<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServerNode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServerNodeController extends Controller
{
    public function index()
    {
        $nodes = ServerNode::latest()->paginate(15);

        return view('admin.servers.index', compact('nodes'));
    }

    public function create()
    {
        return view('admin.servers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ip_address' => ['required', 'ip'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'auth_token' => ['nullable', 'string', 'max:255'],
        ]);

        if (empty($validated['auth_token'])) {
            $validated['auth_token'] = Str::random(40);
        }

        $validated['is_active'] = true;
        ServerNode::create($validated);

        return redirect()->route('admin.servers.index')->with('success', 'Servidor conectado registrado correctamente.');
    }

    public function toggle(ServerNode $server)
    {
        $server->update(['is_active' => ! $server->is_active]);

        return redirect()->route('admin.servers.index')
            ->with('success', "Estado del servidor {$server->name} actualizado.");
    }
}
