<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HostAccessController extends Controller
{
    public function show(Request $request)
    {
        $tenant = $request->attributes->get('tenant');
        $tenant->load('hostInstance');

        return view('client.host.show', compact('tenant'));
    }
}
