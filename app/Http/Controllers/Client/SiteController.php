<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;

class SiteController extends Controller
{
    public function index()
    {
        // Mostrar sitios del cliente
        return view('client.sites.index');
    }
}
