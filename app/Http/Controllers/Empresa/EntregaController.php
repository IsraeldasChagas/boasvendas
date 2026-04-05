<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class EntregaController extends Controller
{
    public function index(): View
    {
        return view('empresa.entregas.index');
    }
}
