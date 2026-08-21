<?php

namespace App\Http\Controllers;

use App\Support\PublicPageRegistry;

class PublicPageController extends Controller
{
    public function show(string $page)
    {
        abort_unless(array_key_exists($page, PublicPageRegistry::PAGES) && $page !== 'home', 404);

        return view('home.page', [
            'page' => $page,
            'content' => PublicPageRegistry::content($page),
            'home' => PublicPageRegistry::content('home'),
        ]);
    }
}
