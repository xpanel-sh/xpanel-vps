<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use App\Models\Site;

class SiteController extends Controller
{
    public function index()
    {
        // Admin sees ALL sites
        $sites = Site::with('tenant')->latest()->paginate(20);

        return view('admin.web.index', compact('sites'));
    }
}
