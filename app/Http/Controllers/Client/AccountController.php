<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\EmailAccount;
use App\Models\ManagedDatabase;
use App\Models\Site;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function show(Request $request)
    {
        $tenant = $request->attributes->get('tenant');

        $usage = [
            'sites' => Site::where('tenant_id', $tenant->id)->count(),
            'databases' => ManagedDatabase::where('tenant_id', $tenant->id)->count(),
            'domains' => Domain::where('tenant_id', $tenant->id)->count(),
            'emails' => EmailAccount::where('tenant_id', $tenant->id)->count(),
        ];

        return view('client.account.show', compact('tenant', 'usage'));
    }
}
