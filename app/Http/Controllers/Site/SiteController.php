<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function home(): View
    {
        return view('site.home');
    }

    public function planos(): View
    {
        return view('site.planos');
    }

    public function sobre(): View
    {
        return view('site.sobre');
    }

    public function contato(): View
    {
        return view('site.contato');
    }
}
