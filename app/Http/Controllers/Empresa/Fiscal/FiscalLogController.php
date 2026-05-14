<?php

namespace App\Http\Controllers\Empresa\Fiscal;

use App\Enums\Fiscal\FiscalLogTipo;
use App\Http\Controllers\Controller;
use App\Models\FiscalLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FiscalLogController extends Controller
{
    public function index(Request $request): View
    {
        $empresa = $request->user()->empresa;
        abort_unless($empresa, 403);

        $tipo = $request->string('tipo')->trim()->value();
        $tipoEnum = FiscalLogTipo::tryFrom($tipo);

        $logs = FiscalLog::query()
            ->with(['nota.pedido'])
            ->where('empresa_id', $empresa->id)
            ->when($tipoEnum !== null, fn ($q) => $q->where('tipo', $tipoEnum))
            ->orderByDesc('id')
            ->paginate(40)
            ->withQueryString();

        return view('empresa.fiscal.logs.index', compact('empresa', 'logs', 'tipo'));
    }
}
