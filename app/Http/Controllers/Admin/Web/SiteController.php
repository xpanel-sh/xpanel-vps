<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class SiteController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('admin.domains.index');
    }
}
