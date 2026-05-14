<?php

namespace App\Http\Controllers\Empresa\Fiscal;

use App\Http\Controllers\Controller;
use App\Models\FiscalEmitente;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FiscalCertificadoController extends Controller
{
    public function index(Request $request): View
    {
        $empresa = $request->user()->empresa;
        abort_unless($empresa, 403);

        $emitentes = FiscalEmitente::query()
            ->where('empresa_id', $empresa->id)
            ->orderByDesc('ativo')
            ->orderBy('razao_social')
            ->get();

        return view('empresa.fiscal.certificados.index', compact('empresa', 'emitentes'));
    }
}
