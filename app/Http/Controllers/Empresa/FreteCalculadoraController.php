<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\EmpresaEntregaFaixaCep;
use App\Services\DeliveryFreteService;
use App\Support\Cep;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Calculadora rápida de frete: atendente consulta o valor para responder ao cliente
 * no WhatsApp/telefone sem precisar montar pedido nem entrar na vitrine pública.
 */
class FreteCalculadoraController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()
                ->route('empresa.dashboard')
                ->with('warning', 'Vincule sua empresa para usar a calculadora de frete.');
        }

        return view('empresa.frete-calculadora.index', [
            'empresa' => $empresa,
            'modoFrete' => $empresa->lojaFreteModoEfetivo(),
            'modoRotulo' => Empresa::lojaFreteModosRotulos()[$empresa->lojaFreteModoEfetivo()] ?? '—',
            'taxaPadrao' => round($empresa->lojaTaxaEntregaPadraoEfetiva(), 2),
        ]);
    }

    public function calcular(Request $request, DeliveryFreteService $delivery): JsonResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return response()->json(['ok' => false, 'message' => 'Empresa não vinculada.'], 422);
        }

        $request->validate([
            'cep' => ['required', 'string', 'max:16'],
            'rua' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:32'],
            'bairro' => ['nullable', 'string', 'max:120'],
            'cidade' => ['nullable', 'string', 'max:120'],
            'estado' => ['nullable', 'string', 'max:2'],
            'valor_pedido' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ]);

        $cep8 = Cep::normalizar8((string) $request->input('cep'));
        if ($cep8 === null) {
            return response()->json([
                'ok' => false,
                'message' => 'CEP inválido. Digite 8 dígitos.',
            ]);
        }

        $sub = $request->input('valor_pedido');
        $subF = ($sub !== null && $sub !== '') ? (float) $sub : null;

        $cliente = [
            'cep' => $cep8,
            'rua' => trim((string) $request->input('rua', '')),
            'numero' => trim((string) $request->input('numero', '')),
            'bairro' => trim((string) $request->input('bairro', '')),
            'cidade' => trim((string) $request->input('cidade', '')),
            'estado' => strtoupper(trim((string) $request->input('estado', ''))),
        ];

        $modo = $empresa->lojaFreteModoEfetivo();
        $padrao = round($empresa->lojaTaxaEntregaPadraoEfetiva(), 2);

        $resumo = [
            'taxa' => $padrao,
            'rotulo' => 'Taxa padrão da loja',
            'entrega_bloqueada' => false,
        ];

        if ($modo === Empresa::LOJA_FRETE_PADRAO_UNICO) {
            $resumo = [
                'taxa' => $padrao,
                'rotulo' => 'Taxa fixa da loja (valor único)',
                'entrega_bloqueada' => false,
            ];
        } elseif ($modo === Empresa::LOJA_FRETE_OSRM_DISTANCIA) {
            $r = $delivery->calcular($empresa, $cliente, $subF);
            $resumo = [
                'taxa' => round((float) ($r['taxa_entrega'] ?? $padrao), 2),
                'rotulo' => (string) ($r['rotulo'] ?? 'Frete por rota OSRM'),
                'distancia_km' => $r['distancia_km'] ?? null,
                'tempo_minutos' => $r['tempo_minutos'] ?? null,
                'endereco_formatado' => $r['endereco_formatado'] ?? null,
                'entrega_bloqueada' => (bool) ($r['entrega_bloqueada'] ?? false),
            ];
        } elseif ($modo === Empresa::LOJA_FRETE_GOOGLE_DISTANCIA) {
            $resumo = [
                'taxa' => $padrao,
                'rotulo' => 'Modo Google: use a vitrine pública para o cálculo automático.',
                'entrega_bloqueada' => false,
            ];
        } else {
            if (Schema::hasTable('empresa_entrega_faixas_cep')) {
                $porFaixa = EmpresaEntregaFaixaCep::taxaParaCep((int) $empresa->id, $cep8);
                if ($porFaixa !== null) {
                    $resumo = [
                        'taxa' => round($porFaixa, 2),
                        'rotulo' => 'Faixa de CEP cadastrada',
                        'entrega_bloqueada' => false,
                    ];
                } else {
                    $resumo = [
                        'taxa' => $padrao,
                        'rotulo' => 'Taxa padrão (CEP fora das faixas cadastradas)',
                        'entrega_bloqueada' => false,
                    ];
                }
            }
        }

        $resumo = $empresa->aplicarAcrescimoChuvaNoResumoFrete($resumo);

        return response()->json([
            'ok' => true,
            'taxa' => round((float) $resumo['taxa'], 2),
            'rotulo' => (string) ($resumo['rotulo'] ?? ''),
            'distancia_km' => $resumo['distancia_km'] ?? null,
            'tempo_minutos' => $resumo['tempo_minutos'] ?? null,
            'endereco_formatado' => $resumo['endereco_formatado'] ?? null,
            'entrega_bloqueada' => (bool) ($resumo['entrega_bloqueada'] ?? false),
            'cep_formatado' => substr($cep8, 0, 5).'-'.substr($cep8, 5),
        ]);
    }
}
