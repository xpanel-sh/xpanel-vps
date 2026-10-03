<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function show(Request $request)
    {
        $tenant = $request->attributes->get('tenant');

        $hostingAccounts = $tenant->hostingAccounts()->with(['plan', 'hostInstance'])->latest()->get();
        $usage = [
            'hostings' => $hostingAccounts->count(),
            'active_hostings' => $hostingAccounts->where('status', 'active')->count(),
            'pending_invoices' => $tenant->planOrders()->where('payment_status', 'pending')->count(),
        ];

        return view('client.account.show', compact('tenant', 'hostingAccounts', 'usage'));
    }
}
