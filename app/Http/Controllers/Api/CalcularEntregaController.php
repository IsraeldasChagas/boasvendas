<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\EmpresaSlug;
use App\Services\DeliveryFreteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CalcularEntregaController extends Controller
{
    public function __invoke(Request $request, DeliveryFreteService $delivery): JsonResponse
    {
        $data = $request->validate([
            'slug' => ['required', 'string', 'max:64'],
            'cep' => ['required', 'string', 'max:16'],
            'rua' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:32'],
            'bairro' => ['nullable', 'string', 'max:120'],
            'cidade' => ['nullable', 'string', 'max:120'],
            'estado' => ['nullable', 'string', 'max:2'],
            'subtotal_pedido' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ]);

        $slug = strtolower(trim($data['slug']));
        $empresa = Empresa::query()
            ->where('slug', $slug)
            ->where('status', '!=', 'suspensa')
            ->first();

        if (! $empresa) {
            $slugRow = EmpresaSlug::query()->where('slug', $slug)->with('empresa')->first();
            $empresa = $slugRow?->empresa;
            if ($empresa && $empresa->status === 'suspensa') {
                $empresa = null;
            }
        }

        if (! $empresa) {
            throw ValidationException::withMessages(['slug' => 'Loja não encontrada.']);
        }

        if ($empresa->lojaFreteModoEfetivo() !== \App\Models\Empresa::LOJA_FRETE_OSRM_DISTANCIA) {
            return response()->json([
                'success' => false,
                'message' => 'Esta loja não usa frete por rota (OSRM).',
            ], 422);
        }

        $sub = isset($data['subtotal_pedido']) ? (float) $data['subtotal_pedido'] : null;
        $r = $delivery->calcular($empresa, [
            'cep' => $data['cep'],
            'rua' => $data['rua'] ?? '',
            'numero' => $data['numero'] ?? '',
            'bairro' => $data['bairro'] ?? '',
            'cidade' => $data['cidade'] ?? '',
            'estado' => $data['estado'] ?? '',
        ], $sub);
        $r = $empresa->aplicarAcrescimoChuvaNoResumoFrete($r);

        if (! ($r['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $r['message'] ?? 'Não foi possível calcular a entrega.',
            ]);
        }

        return response()->json([
            'success' => true,
            'distancia_km' => $r['distancia_km'],
            'tempo_minutos' => $r['tempo_minutos'],
            'taxa_entrega' => $r['taxa_entrega'],
            'endereco_formatado' => $r['endereco_formatado'] ?? '',
            'lat_cliente' => $r['lat_cliente'],
            'lng_cliente' => $r['lng_cliente'],
            'entrega_bloqueada' => (bool) ($r['entrega_bloqueada'] ?? false),
        ]);
    }
}
