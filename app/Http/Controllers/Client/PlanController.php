<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\HostingPlan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $request->attributes->get('tenant');
        $plans = HostingPlan::query()->where('is_active', true)->orderBy('monthly_price')->get();

        return view('client.plans.index', compact('tenant', 'plans'));
    }
}
