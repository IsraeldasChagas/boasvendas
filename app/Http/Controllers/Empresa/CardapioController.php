<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Cardápio somente leitura no painel — mesmos produtos cadastrados (para atendente / consulta rápida).
 * Não substitui a vitrine pública da loja; é visão operacional interna.
 */
class CardapioController extends Controller
{
    public function index(Request $request): View
    {
        $empresaId = (int) $request->user()->empresa_id;
        $categoriaId = $request->query('categoria');
        $categoriaId = is_numeric($categoriaId) ? (int) $categoriaId : null;

        $categorias = Categoria::query()
            ->where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->orderBy('ordem')
            ->orderBy('nome')
            ->get();

        $produtosQuery = Produto::query()
            ->where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->with('categoria')
            ->orderBy('nome');

        if ($categoriaId !== null) {
            $produtosQuery->where('categoria_id', $categoriaId);
        }

        $produtos = $produtosQuery->get();

        return view('empresa.cardapio.index', [
            'categorias' => $categorias,
            'produtos' => $produtos,
            'categoriaFiltro' => $categoriaId,
        ]);
    }
}
