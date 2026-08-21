<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Domain;

class DomainController extends Controller
{
    public function index()
    {
        $domains = Domain::query()
            ->with(['tenant', 'site'])
            ->latest()
            ->paginate(20);

        return view('admin.domains.index', compact('domains'));
    }
}
