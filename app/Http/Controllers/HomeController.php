<?php

namespace App\Http\Controllers;

use App\Models\HostingPlan;
use App\Support\PublicPageRegistry;

class HomeController extends Controller
{
    public function index()
    {
        if (! config('xpanel.home_enabled', false)) {
            return response()->view('home.disabled', [], 200);
        }

        return view('home.index', [
            'plans' => HostingPlan::query()->where('is_active', true)->orderBy('monthly_price')->get(),
            'home' => PublicPageRegistry::content('home'),
        ]);
    }
}
