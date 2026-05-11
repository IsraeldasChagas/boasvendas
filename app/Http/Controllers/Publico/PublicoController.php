<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Models\Adicional;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\EmpresaEntregaFaixaCep;
use App\Models\EmpresaSlug;
use App\Models\FidelidadeCartao;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Produto;
use App\Models\ProdutoIngrediente;
use App\Services\DeliveryFreteService;
use App\Support\Cep;
use App\Support\GoogleMapsDistanceMatrix;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicoController extends Controller
{
    private function empresaLojaAtiva(string $slug): Empresa
    {
        $empresa = Empresa::query()
            ->where('slug', $slug)
            ->where('status', '!=', 'suspensa')
            ->first();

        if (! $empresa) {
            $slugRow = EmpresaSlug::query()
                ->where('slug', $slug)
                ->with('empresa')
                ->first();
            $empresa = $slugRow?->empresa;
            if ($empresa && $empresa->status === 'suspensa') {
                $empresa = null;
            }
        }

        if (! $empresa) {
            abort(404, 'Não encontramos esta loja. Verifique o link ou se o estabelecimento ainda está ativo.');
        }

        return $empresa;
    }

    private function carrinhoKey(string $slug): string
    {
        return 'loja_carrinho.'.$slug;
    }

    /**
     * @return list<array{produto_id: int, quantidade: int, adicional_qtd: array<int, int>, retirar_qtd: array<int, int>, observacao: string, nota_produto: int}>
     */
    private function getCarrinhoLines(string $slug): array
    {
        $raw = session($this->carrinhoKey($slug), []);
        if (! is_array($raw) || $raw === []) {
            return [];
        }

        if (isset($raw[0]) && is_array($raw[0]) && array_key_exists('produto_id', $raw[0])) {
            $out = [];
            foreach ($raw as $line) {
                if (! is_array($line) || ! isset($line['produto_id'])) {
                    continue;
                }
                $out[] = [
                    'produto_id' => (int) $line['produto_id'],
                    'quantidade' => max(0, (int) ($line['quantidade'] ?? 0)),
                    'adicional_qtd' => $this->linhaParaMapaAdicionalQtd($line),
                    'retirar_qtd' => $this->linhaParaMapaRetirarQtd($line),
                    'observacao' => $this->normalizarObservacao($line['observacao'] ?? null),
                    'nota_produto' => $this->normalizarNotaProduto($line['nota_produto'] ?? null),
                ];
            }

            return array_values(array_filter($out, fn ($l) => $l['quantidade'] > 0));
        }

        $lines = [];
        foreach ($raw as $pid => $qty) {
            if (! is_numeric($pid)) {
                continue;
            }
            $q = (int) $qty;
            if ($q < 1) {
                continue;
            }
            $lines[] = [
                'produto_id' => (int) $pid,
                'quantidade' => $q,
                'adicional_qtd' => [],
                'retirar_qtd' => [],
                'observacao' => '',
                'nota_produto' => 0,
            ];
        }

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $line
     * @return array<int, int>
     */
    private function linhaParaMapaAdicionalQtd(array $line): array
    {
        $raw = $line['adicional_qtd'] ?? null;
        if (is_array($raw) && $raw !== []) {
            $map = [];
            foreach ($raw as $kid => $qv) {
                $id = (int) $kid;
                $q = max(0, (int) $qv);
                if ($id > 0 && $q > 0) {
                    $map[$id] = ($map[$id] ?? 0) + $q;
                }
            }
            if ($map !== []) {
                ksort($map);

                return $map;
            }
        }

        $ids = $this->normalizarIdsAdicionais($line['adicional_ids'] ?? []);
        $map = [];
        foreach ($ids as $id) {
            $map[$id] = 1;
        }
        ksort($map);

        return $map;
    }

    /**
     * Quantidades por ingrediente (escolha / retirada na vitrine).
     *
     * @param  array<string, mixed>  $line
     * @return array<int, int>
     */
    private function linhaParaMapaRetirarQtd(array $line): array
    {
        $raw = $line['retirar_qtd'] ?? null;
        if (is_array($raw) && $raw !== []) {
            $map = [];
            foreach ($raw as $kid => $qv) {
                $id = (int) $kid;
                $q = max(0, (int) $qv);
                if ($id > 0 && $q > 0) {
                    $map[$id] = ($map[$id] ?? 0) + $q;
                }
            }
            if ($map !== []) {
                ksort($map);

                return $map;
            }
        }

        $ids = $this->normalizarIdsAdicionais($line['retirar_ingrediente_ids'] ?? []);
        $map = [];
        foreach ($ids as $id) {
            $map[$id] = ($map[$id] ?? 0) + 1;
        }
        ksort($map);

        return $map;
    }

    private function produtoTemLimiteEscolhasAcrescimo(Produto $p): bool
    {
        if (! $p->permite_adicionais) {
            return false;
        }
        if (! Schema::hasColumn('produtos', 'acrescimo_escolhas_min')) {
            return false;
        }

        return $p->acrescimo_escolhas_min !== null || $p->acrescimo_escolhas_max !== null;
    }

    /**
     * @param  array<int, int>  $map
     * @return array<int, int>
     */
    private function reduzirMapaAteSomaMax(array $map, int $maxSum): array
    {
        $sum = (int) array_sum($map);
        if ($sum <= $maxSum) {
            return array_filter($map, fn (int $q) => $q > 0);
        }
        krsort($map);
        while ($sum > $maxSum) {
            $reduced = false;
            foreach ($map as $id => $q) {
                if ($q > 0) {
                    $map[$id] = $q - 1;
                    $sum--;
                    $reduced = true;
                    if ($sum <= $maxSum) {
                        break;
                    }
                }
            }
            if (! $reduced) {
                break;
            }
        }

        return array_filter($map, fn (int $q) => $q > 0);
    }

    /**
     * @return list<int>
     */
    private function normalizarIdsAdicionais(mixed $ids): array
    {
        if (! is_array($ids)) {
            return [];
        }

        return collect($ids)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int>  $adicionalQtdMap
     * @param  array<int, int>  $retirarQtdMap
     */
    private function fingerprintLinha(int $produtoId, array $adicionalQtdMap, array $retirarQtdMap, string $observacaoNormalizada, int $notaProduto = 0): string
    {
        ksort($adicionalQtdMap);
        ksort($retirarQtdMap);
        $partsA = [];
        foreach ($adicionalQtdMap as $id => $q) {
            $id = (int) $id;
            $q = (int) $q;
            if ($id > 0 && $q > 0) {
                $partsA[] = $id.'x'.$q;
            }
        }
        $partsR = [];
        foreach ($retirarQtdMap as $id => $q) {
            $id = (int) $id;
            $q = (int) $q;
            if ($id > 0 && $q > 0) {
                $partsR[] = $id.'x'.$q;
            }
        }

        $notaProduto = max(0, min(5, $notaProduto));

        return $produtoId.'|a:'.implode(',', $partsA).'|r:'.implode(',', $partsR).'|'.sha1($observacaoNormalizada).'|n:'.$notaProduto;
    }

    private function normalizarObservacao(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        $t = trim(strip_tags($text));
        if (function_exists('mb_strlen') && mb_strlen($t) > 500) {
            return mb_substr($t, 0, 500);
        }
        if (strlen($t) > 500) {
            return substr($t, 0, 500);
        }

        return $t;
    }

    private function normalizarNotaProduto(mixed $v): int
    {
        if ($v === null || $v === '' || $v === false) {
            return 0;
        }
        $n = (int) $v;
        if ($n < 1 || $n > 5) {
            return 0;
        }

        return $n;
    }

    private function setCarrinhoLines(string $slug, array $lines): void
    {
        session([$this->carrinhoKey($slug) => array_values($lines)]);
    }

    /**
     * @return list<array{
     *   line_index: int,
     *   produto: Produto,
     *   quantidade: int,
     *   adicional_qtd: array<int, int>,
     *   opcoes: list<array{id:int,nome:string,tipo:string,preco:float,quantidade?:int}>,
     *   preco_unitario: float,
     *   subtotal: float,
     *   observacao: string,
     *   nota_produto: int
     * }>
     */
    private function linhasCarrinho(Empresa $empresa, string $slug): array
    {
        $linesRaw = $this->getCarrinhoLines($slug);
        if ($linesRaw === []) {
            $this->setCarrinhoLines($slug, []);

            return [];
        }

        $pids = collect($linesRaw)->pluck('produto_id')->unique()->filter()->all();
        $produtos = Produto::query()
            ->where('empresa_id', $empresa->id)
            ->whereIn('id', $pids)
            ->where('ativo', true)
            ->where('visivel_loja', true)
            ->with([
                'adicionais' => fn ($q) => $q->where('adicionais.ativo', true),
                'ingredientes' => fn ($q) => $q->orderBy('ordem')->orderBy('nome'),
            ])
            ->get()
            ->keyBy('id');

        $novasLinhasSessao = [];
        $linhas = [];
        $idx = 0;

        foreach ($linesRaw as $line) {
            $pid = (int) $line['produto_id'];
            $qty = max(0, (int) $line['quantidade']);
            $mapReq = $this->linhaParaMapaAdicionalQtd($line);
            $retReqMap = $this->linhaParaMapaRetirarQtd($line);
            if ($qty < 1) {
                continue;
            }

            $p = $produtos->get($pid);
            if (! $p) {
                continue;
            }

            $idsPermAcre = $p->permite_adicionais
                ? $p->adicionais->where('tipo', Adicional::TIPO_ACRESCENTAR)->pluck('id')->map(fn ($id) => (int) $id)->all()
                : [];

            $temLimite = $this->produtoTemLimiteEscolhasAcrescimo($p) && $idsPermAcre !== [];

            $mapFiltrado = [];
            foreach ($mapReq as $aid => $q) {
                $aid = (int) $aid;
                $q = max(0, min(999, (int) $q));
                if ($aid > 0 && $q > 0 && in_array($aid, $idsPermAcre, true)) {
                    $mapFiltrado[$aid] = ($mapFiltrado[$aid] ?? 0) + $q;
                }
            }
            ksort($mapFiltrado);

            $mapOk = $mapFiltrado;
            if ($temLimite) {
                $maxEsc = Schema::hasColumn('produtos', 'acrescimo_escolhas_min') ? $p->acrescimo_escolhas_max : null;
                if ($maxEsc !== null) {
                    $mapOk = $this->reduzirMapaAteSomaMax($mapOk, (int) $maxEsc);
                }
            }

            $idsPermIng = $p->ingredientes->pluck('id')->map(fn ($id) => (int) $id)->all();
            $retFiltrado = [];
            foreach ($retReqMap as $rid => $rq) {
                $rid = (int) $rid;
                $rq = max(0, min(999, (int) $rq));
                if ($rid < 1 || $rq < 1) {
                    continue;
                }
                if (! in_array($rid, $idsPermIng, true)) {
                    continue;
                }
                $retFiltrado[$rid] = ($retFiltrado[$rid] ?? 0) + $rq;
            }
            ksort($retFiltrado);
            $maxR = $p->limiteRetiradaIngredientesNaLoja();
            if ($p->ingredientes->isEmpty() || $maxR === 0) {
                $retOk = [];
            } else {
                $sumR = (int) array_sum($retFiltrado);
                if ($sumR > $maxR) {
                    $retFiltrado = $this->reduzirMapaAteSomaMax($retFiltrado, $maxR);
                }
                $retOk = array_filter($retFiltrado, fn (int $q) => $q > 0);
            }

            $obsLinha = $this->normalizarObservacao($line['observacao'] ?? null);
            $notaLinha = $this->normalizarNotaProduto($line['nota_produto'] ?? null);

            $opcoes = [];
            $extraUnit = 0.0;
            foreach ($mapOk as $aid => $qtdAd) {
                $ad = $p->adicionais->firstWhere('id', $aid);
                if (! $ad || $ad->tipo !== Adicional::TIPO_ACRESCENTAR) {
                    continue;
                }
                $qtdAd = (int) $qtdAd;
                if ($qtdAd < 1) {
                    continue;
                }
                $precoAd = (float) $ad->preco;
                $extraUnit += $precoAd * $qtdAd;
                $op = [
                    'id' => (int) $ad->id,
                    'nome' => $ad->nome,
                    'tipo' => $ad->tipo,
                    'preco' => round($precoAd, 2),
                ];
                if ($qtdAd > 1) {
                    $op['quantidade'] = $qtdAd;
                }
                $opcoes[] = $op;
            }
            foreach ($retOk as $iid => $qtdIng) {
                $iid = (int) $iid;
                $qtdIng = (int) $qtdIng;
                if ($qtdIng < 1) {
                    continue;
                }
                $ing = $p->ingredientes->firstWhere('id', $iid);
                if (! $ing) {
                    continue;
                }
                $op = [
                    'id' => $iid,
                    'nome' => $ing->nome,
                    'tipo' => 'retirar_ingrediente',
                    'preco' => 0.0,
                ];
                if ($qtdIng > 1) {
                    $op['quantidade'] = $qtdIng;
                }
                $opcoes[] = $op;
            }

            $base = (float) $p->preco;
            $precoUnit = round($base + $extraUnit, 2);
            $subtotal = round($precoUnit * $qty, 2);

            $novasLinhasSessao[] = [
                'produto_id' => $pid,
                'quantidade' => $qty,
                'adicional_qtd' => $mapOk,
                'retirar_qtd' => $retOk,
                'observacao' => $obsLinha,
                'nota_produto' => $notaLinha,
            ];

            $linhas[] = [
                'line_index' => $idx,
                'produto' => $p,
                'quantidade' => $qty,
                'adicional_qtd' => $mapOk,
                'opcoes' => $opcoes,
                'preco_unitario' => $precoUnit,
                'subtotal' => $subtotal,
                'observacao' => $obsLinha,
                'nota_produto' => $notaLinha,
            ];
            $idx++;
        }

        $this->setCarrinhoLines($slug, $novasLinhasSessao);

        return $linhas;
    }

    private function subtotalCarrinho(array $linhas): float
    {
        $t = 0.0;
        foreach ($linhas as $l) {
            $t += $l['subtotal'];
        }

        return round($t, 2);
    }

    private function entregaPrefsKey(string $slug): string
    {
        return 'loja_entrega_prefs.'.$slug;
    }

    private function lojaPermiteRetiradaBalcao(Empresa $empresa): bool
    {
        if (! Schema::hasColumn('empresas', 'loja_permite_retirada_balcao')) {
            return true;
        }

        return (bool) $empresa->loja_permite_retirada_balcao;
    }

    /**
     * @return array{modo: string, cep: string}
     */
    private function getEntregaPrefs(string $slug, Empresa $empresa): array
    {
        $default = [
            'modo' => Pedido::TIPO_ENTREGA_ENTREGA,
            'cep' => '',
        ];
        $raw = session($this->entregaPrefsKey($slug), []);
        if (! is_array($raw)) {
            return $default;
        }
        $modoRaw = $raw['modo'] ?? Pedido::TIPO_ENTREGA_ENTREGA;
        $modo = $modoRaw === Pedido::TIPO_ENTREGA_BALCAO && $this->lojaPermiteRetiradaBalcao($empresa)
            ? Pedido::TIPO_ENTREGA_BALCAO
            : Pedido::TIPO_ENTREGA_ENTREGA;
        $cep = isset($raw['cep']) && is_string($raw['cep']) ? preg_replace('/\D+/', '', $raw['cep']) : '';

        return ['modo' => $modo, 'cep' => $cep];
    }

    /**
     * Junta logradouro + número + bairro + cidade/UF (como no checkout) para gravar no pedido e exibir ao cliente/entregador.
     *
     * @param  array<string, mixed>  $data  Request validado (entrega_numero, entrega_bairro, etc.)
     */
    private function montarEnderecoEntregaCheckout(string $logradouro, array $data): string
    {
        $parts = [];
        $log = trim($logradouro);
        if ($log !== '') {
            $parts[] = $log;
        }
        $n = trim((string) ($data['entrega_numero'] ?? ''));
        if ($n !== '') {
            $parts[] = 'nº '.$n;
        }
        $b = trim((string) ($data['entrega_bairro'] ?? ''));
        if ($b !== '') {
            $parts[] = $b;
        }
        $cidade = trim((string) ($data['entrega_cidade'] ?? ''));
        $uf = strtoupper(trim((string) ($data['entrega_estado'] ?? '')));
        if ($cidade !== '' && $uf !== '') {
            $parts[] = $cidade.' — '.$uf;
        } elseif ($cidade !== '') {
            $parts[] = $cidade;
        } elseif ($uf !== '') {
            $parts[] = $uf;
        }

        return implode(', ', $parts);
    }

    /** Código público do pedido (BV-…) para busca na URL. */
    private function normalizarCodigoPublicoPedido(string $codigo): string
    {
        $c = strtoupper(trim($codigo));
        $c = ltrim($c, '#');
        if (! str_starts_with($c, 'BV-')) {
            $c = 'BV-'.$c;
        }

        return $c;
    }

    /**
     * @return array{taxa: float, rotulo: string, entrega_bloqueada?: bool}
     */
    private function calcularTaxaResumo(Empresa $empresa, string $modo, ?string $cepSoDigitos, ?float $subtotalPedido = null): array
    {
        $res = null;

        if ($modo === Pedido::TIPO_ENTREGA_BALCAO && $this->lojaPermiteRetiradaBalcao($empresa)) {
            $res = ['taxa' => 0.0, 'rotulo' => 'Retirada no balcão'];
        } elseif ($empresa->lojaFreteModoEfetivo() === Empresa::LOJA_FRETE_PADRAO_UNICO) {
            $res = [
                'taxa' => round($empresa->lojaTaxaEntregaPadraoEfetiva(), 2),
                'rotulo' => 'Taxa fixa da loja (modo sem faixas)',
            ];
        } elseif ($empresa->lojaFreteModoEfetivo() === Empresa::LOJA_FRETE_GOOGLE_DISTANCIA) {
            $cep8 = Cep::normalizar8($cepSoDigitos);
            $destino = ($cep8 !== null && $cepSoDigitos !== null && $cepSoDigitos !== '')
                ? $this->formatoDestinoFreteGoogleCep($cep8)
                : '';

            $res = $this->taxaFreteGoogleDistancia($empresa, $destino);
        } elseif ($empresa->lojaFreteModoEfetivo() === Empresa::LOJA_FRETE_OSRM_DISTANCIA) {
            $cep8 = Cep::normalizar8($cepSoDigitos);
            if ($cep8 === null || $cepSoDigitos === null || $cepSoDigitos === '') {
                $res = [
                    'taxa' => round($empresa->lojaTaxaEntregaPadraoEfetiva(), 2),
                    'rotulo' => 'Informe o CEP para calcular o frete (OpenStreetMap / OSRM)',
                    'entrega_bloqueada' => false,
                ];
            } else {
                $res = $this->taxaFreteOsrmPorCliente($empresa, ['cep' => $cep8], $subtotalPedido);
            }
        } else {
            $cep8 = Cep::normalizar8($cepSoDigitos);
            if ($cep8 === null || $cepSoDigitos === null || $cepSoDigitos === '') {
                $res = [
                    'taxa' => round($empresa->lojaTaxaEntregaPadraoEfetiva(), 2),
                    'rotulo' => 'Taxa padrão (informe o CEP para usar faixa)',
                ];
            } elseif (Schema::hasTable('empresa_entrega_faixas_cep')) {
                $porFaixa = EmpresaEntregaFaixaCep::taxaParaCep((int) $empresa->id, $cep8);
                if ($porFaixa !== null) {
                    $res = ['taxa' => round($porFaixa, 2), 'rotulo' => 'Faixa de CEP'];
                } else {
                    $res = [
                        'taxa' => round($empresa->lojaTaxaEntregaPadraoEfetiva(), 2),
                        'rotulo' => 'Taxa padrão da loja',
                    ];
                }
            } else {
                $res = [
                    'taxa' => round($empresa->lojaTaxaEntregaPadraoEfetiva(), 2),
                    'rotulo' => 'Taxa padrão da loja',
                ];
            }
        }

        return $empresa->aplicarAcrescimoChuvaNoResumoFrete($res);
    }

    private function formatoDestinoFreteGoogleCep(string $cep8): string
    {
        return substr($cep8, 0, 5).'-'.substr($cep8, 5).', Brasil';
    }

    private function formatoDestinoFreteGoogleEnderecoCompleto(string $logradouro, string $cep8): string
    {
        $cepFmt = substr($cep8, 0, 5).'-'.substr($cep8, 5);

        return $logradouro.', '.$cepFmt.', Brasil';
    }

    /** Explicação ao cliente quando não há rota por km: o valor é o de «Taxa de entrega» nas configurações. */
    private function rotuloFreteTaxaBaseLoja(float $valor, string $motivo): string
    {
        return 'Frete R$ '.number_format($valor, 2, ',', '.').' (taxa da loja em Configurações) — '.$motivo;
    }

    /**
     * @return array{taxa: float, rotulo: string, entrega_bloqueada: bool}
     */
    private function taxaFreteGoogleDistancia(Empresa $empresa, string $destino): array
    {
        $padrao = round($empresa->lojaTaxaEntregaPadraoEfetiva(), 2);
        $apiKey = config('services.google_maps.api_key');
        $origem = $empresa->lojaFreteOrigemEnderecoEfetiva();
        $rsKm = $empresa->lojaFreteGoogleRsPorKm();

        if (trim($destino) === '') {
            return [
                'taxa' => $padrao,
                'rotulo' => 'Informe o CEP para calcular o frete (Google Maps)',
                'entrega_bloqueada' => false,
            ];
        }

        if (! filled($apiKey)) {
            return [
                'taxa' => $padrao,
                'rotulo' => 'Taxa padrão (Google Maps: configure GOOGLE_MAPS_API_KEY no servidor)',
                'entrega_bloqueada' => false,
            ];
        }

        if ($origem === null) {
            return [
                'taxa' => $padrao,
                'rotulo' => 'Taxa padrão (informe o endereço de origem nas configurações da loja)',
                'entrega_bloqueada' => false,
            ];
        }

        if ($rsKm === null) {
            return [
                'taxa' => $padrao,
                'rotulo' => 'Taxa padrão (defina R$ por km nas configurações)',
                'entrega_bloqueada' => false,
            ];
        }

        $km = GoogleMapsDistanceMatrix::distanciaKmRodoviaria($origem, $destino, is_string($apiKey) ? $apiKey : null);
        if ($km === null) {
            return [
                'taxa' => $padrao,
                'rotulo' => 'Taxa padrão (rota indisponível — verifique CEP/endereço ou tente depois)',
                'entrega_bloqueada' => false,
            ];
        }

        return $this->precificarFreteKmRodoviario(
            $empresa,
            $km,
            'Google Maps (~'.number_format($km, 1, ',', '.').' km × R$ '.number_format($rsKm, 2, ',', '.').'/km)'
        );
    }

    /**
     * Frete OSRM: geocode + rota + taxa base + km incluso + valor km extra.
     *
     * @param  array{cep?:string, rua?:string, numero?:string, bairro?:string, cidade?:string, estado?:string}  $cliente
     * @return array{taxa: float, rotulo: string, entrega_bloqueada: bool}
     */
    private function taxaFreteOsrmPorCliente(Empresa $empresa, array $cliente, ?float $subtotalPedido = null): array
    {
        $padrao = round($empresa->lojaTaxaEntregaPadraoEfetiva(), 2);
        $cepDigits = preg_replace('/\D+/', '', (string) ($cliente['cep'] ?? ''));
        if (strlen($cepDigits) !== 8) {
            return [
                'taxa' => $padrao,
                'rotulo' => 'Informe o CEP para calcular o frete (OpenStreetMap / OSRM)',
                'entrega_bloqueada' => false,
            ];
        }

        $r = app(DeliveryFreteService::class)->calcular($empresa, array_merge($cliente, ['cep' => $cepDigits]), $subtotalPedido);

        return [
            'taxa' => round((float) ($r['taxa_entrega'] ?? $padrao), 2),
            'rotulo' => (string) ($r['rotulo'] ?? 'Frete por rota'),
            'entrega_bloqueada' => (bool) ($r['entrega_bloqueada'] ?? false),
        ];
    }

    /**
     * @return array{taxa: float, rotulo: string, entrega_bloqueada: bool}
     */
    private function precificarFreteKmRodoviario(Empresa $empresa, float $km, string $rotuloDetalhe): array
    {
        $padrao = round($empresa->lojaTaxaEntregaPadraoEfetiva(), 2);
        $rsKm = $empresa->lojaFreteGoogleRsPorKm();
        if ($rsKm === null) {
            return [
                'taxa' => $padrao,
                'rotulo' => 'Taxa padrão (defina R$ por km nas configurações)',
                'entrega_bloqueada' => false,
            ];
        }

        $kmMax = $empresa->lojaFreteGoogleKmMax();
        if ($kmMax !== null && $km > $kmMax) {
            return [
                'taxa' => 0.0,
                'rotulo' => 'Fora da área de entrega (máx. '.number_format($kmMax, 1, ',', '.').' km)',
                'entrega_bloqueada' => true,
            ];
        }

        $bruto = $km * $rsKm;
        $min = $empresa->lojaFreteGoogleTaxaMinima();
        if ($min !== null && $bruto < $min) {
            $bruto = $min;
        }

        return [
            'taxa' => round($bruto, 2),
            'rotulo' => $rotuloDetalhe,
            'entrega_bloqueada' => false,
        ];
    }

    private function gerarCodigoPublico(): string
    {
        do {
            $codigo = 'BV-'.strtoupper(Str::random(6));
        } while (Pedido::query()->where('codigo_publico', $codigo)->exists());

        return $codigo;
    }

    public function loja(Request $request, string $slug): View
    {
        $empresa = $this->empresaLojaAtiva($slug);
        $empresa->loadMissing('fidelidadePrograma');

        $query = $empresa->produtos()
            ->where('ativo', true)
            ->where('visivel_loja', true)
            ->with('categoria')
            ->withCount([
                'adicionais as adicionais_acrescimo_count' => function ($q) {
                    $q->where('adicionais.ativo', true)->where('adicionais.tipo', Adicional::TIPO_ACRESCENTAR);
                },
            ])
            ->withCount('ingredientes');

        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->integer('categoria_id'));
        }

        $produtos = $query->orderBy('nome')->paginate(24)->withQueryString();

        $categorias = Categoria::query()
            ->where('empresa_id', $empresa->id)
            ->where('ativo', true)
            ->whereHas('produtos', function ($q) {
                $q->where('ativo', true)->where('visivel_loja', true);
            })
            ->orderBy('ordem')
            ->orderBy('nome')
            ->get();

        $bannerCategoria = null;
        /** @var Collection<int, array{url: string, nome: string, produto_id: int}> $bannerSlides */
        $bannerSlides = collect();
        if (Empresa::schemaTemColunaLojaBannerCategoria()) {
            $empresa->loadMissing('lojaBannerCategoria');
            $bc = $empresa->lojaBannerCategoria;
            if ($bc !== null && (int) $bc->empresa_id === (int) $empresa->id && $bc->ativo) {
                $bannerCategoria = $bc;
                $capas = Produto::query()
                    ->where('empresa_id', $empresa->id)
                    ->where('categoria_id', $bc->id)
                    ->where('ativo', true)
                    ->where('visivel_loja', true)
                    ->whereNotNull('foto')
                    ->where('foto', '!=', '')
                    ->orderBy('nome')
                    ->limit(24)
                    ->get();
                foreach ($capas as $prodBanner) {
                    $u = $prodBanner->urlFoto();
                    if ($u !== null && $u !== '') {
                        $bannerSlides->push([
                            'url' => $u,
                            'nome' => $prodBanner->nome,
                            'produto_id' => (int) $prodBanner->id,
                        ]);
                    }
                }
            }
        }

        return view('publico.loja', compact(
            'slug',
            'empresa',
            'produtos',
            'categorias',
            'bannerCategoria',
            'bannerSlides'
        ));
    }

    public function produto(string $slug, string $produto_id): Response
    {
        $empresa = $this->empresaLojaAtiva($slug);

        $produtoModel = Produto::query()
            ->where('empresa_id', $empresa->id)
            ->where('id', $produto_id)
            ->where('ativo', true)
            ->where('visivel_loja', true)
            ->with([
                'categoria',
                'adicionais' => fn ($q) => $q->where('adicionais.ativo', true)
                    ->orderBy('adicionais.ordem')
                    ->orderBy('adicionais.nome'),
                'ingredientes' => fn ($q) => $q->orderBy('ordem')->orderBy('nome'),
            ])
            ->first();

        if (! $produtoModel) {
            abort(404, 'Este produto não está disponível na vitrine ou foi removido.');
        }

        return response()
            ->view('publico.produto', [
                'slug' => $slug,
                'empresa' => $empresa,
                'produto' => $produtoModel,
            ])
            ->header('Cache-Control', 'private, no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache');
    }

    public function carrinhoAdicionar(Request $request, string $slug): RedirectResponse
    {
        $empresa = $this->empresaLojaAtiva($slug);

        $adicionalQtdRaw = $request->input('adicional_qtd', []);
        if (is_array($adicionalQtdRaw)) {
            foreach ($adicionalQtdRaw as $kid => $qv) {
                if ($qv === '' || $qv === null) {
                    $adicionalQtdRaw[$kid] = 0;
                }
            }
            $request->merge(['adicional_qtd' => $adicionalQtdRaw]);
        }

        $retirarQtdRaw = $request->input('retirar_qtd', []);
        if (is_array($retirarQtdRaw)) {
            foreach ($retirarQtdRaw as $kid => $qv) {
                if ($qv === '' || $qv === null) {
                    $retirarQtdRaw[$kid] = 0;
                }
            }
            $request->merge(['retirar_qtd' => $retirarQtdRaw]);
        }

        $data = $request->validate([
            'produto_id' => ['required', 'integer'],
            'quantidade' => ['nullable', 'integer', 'min:1', 'max:99'],
            'adicional_ids' => ['nullable', 'array'],
            'adicional_ids.*' => ['integer'],
            'adicional_qtd' => ['nullable', 'array'],
            'adicional_qtd.*' => ['nullable', 'integer', 'min:0', 'max:999'],
            'retirar_ingrediente_ids' => ['nullable', 'array'],
            'retirar_ingrediente_ids.*' => ['integer'],
            'retirar_qtd' => ['nullable', 'array'],
            'retirar_qtd.*' => ['nullable', 'integer', 'min:0', 'max:999'],
            'observacao' => ['nullable', 'string', 'max:500'],
            'nota_produto' => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);

        $qty = $data['quantidade'] ?? 1;
        $obsNorm = $this->normalizarObservacao($data['observacao'] ?? null);
        $notaNorm = $this->normalizarNotaProduto($data['nota_produto'] ?? null);

        $p = Produto::query()
            ->where('empresa_id', $empresa->id)
            ->where('id', $data['produto_id'])
            ->where('ativo', true)
            ->where('visivel_loja', true)
            ->with([
                'adicionais' => fn ($q) => $q->where('adicionais.ativo', true),
                'ingredientes' => fn ($q) => $q->orderBy('ordem')->orderBy('nome'),
            ])
            ->first();

        if (! $p) {
            abort(404, 'Produto não encontrado ou indisponível na loja.');
        }

        $idsPermAcre = $p->permite_adicionais
            ? $p->adicionais->where('tipo', Adicional::TIPO_ACRESCENTAR)->pluck('id')->map(fn ($id) => (int) $id)->all()
            : [];

        $mapReq = [];
        $rawQtd = $data['adicional_qtd'] ?? [];
        if (is_array($rawQtd)) {
            foreach ($rawQtd as $kid => $qv) {
                $id = (int) $kid;
                $q = max(0, (int) $qv);
                if ($id > 0 && $q > 0) {
                    $mapReq[$id] = ($mapReq[$id] ?? 0) + $q;
                }
            }
        }
        if ($mapReq === []) {
            foreach ($this->normalizarIdsAdicionais($data['adicional_ids'] ?? []) as $id) {
                $mapReq[$id] = ($mapReq[$id] ?? 0) + 1;
            }
        }
        ksort($mapReq);

        $temLimite = $this->produtoTemLimiteEscolhasAcrescimo($p) && $idsPermAcre !== [];

        if (! $p->permite_adicionais && $mapReq !== []) {
            return back()->withInput()->with('warning', 'Este produto não permite acréscimos opcionais.');
        }

        foreach ($mapReq as $aid => $_) {
            if (! in_array((int) $aid, $idsPermAcre, true)) {
                return back()->withInput()->with('warning', 'Uma das opções escolhidas não é válida para este produto.');
            }
        }

        $mapFiltrado = [];
        foreach ($mapReq as $aid => $q) {
            $aid = (int) $aid;
            if (in_array($aid, $idsPermAcre, true)) {
                $mapFiltrado[$aid] = max(0, min(999, (int) $q));
            }
        }
        ksort($mapFiltrado);

        if ($p->modoAcrescimosNaLoja() === Produto::ACRESCIMO_LOJA_UI_CHECKBOX) {
            foreach ($mapFiltrado as $aid => $q) {
                $mapFiltrado[$aid] = ((int) $q > 0) ? 1 : 0;
            }
        }

        $mapOk = array_filter($mapFiltrado, fn (int $q) => $q > 0);

        if ($temLimite) {
            $sum = (int) array_sum($mapOk);
            $minE = Schema::hasColumn('produtos', 'acrescimo_escolhas_min') ? $p->acrescimo_escolhas_min : null;
            $maxE = Schema::hasColumn('produtos', 'acrescimo_escolhas_min') ? $p->acrescimo_escolhas_max : null;
            $minOk = $minE !== null ? (int) $minE : 0;
            $maxOk = $maxE !== null ? (int) $maxE : 99999;
            if ($sum < $minOk || $sum > $maxOk) {
                return back()->withInput()->with('warning', 'Para este produto, escolha entre '.$minOk.' e '.$maxOk.' opções de acréscimo (total somando as quantidades).');
            }
        }

        $idsPermIng = $p->ingredientes->pluck('id')->map(fn ($id) => (int) $id)->all();

        $retMapSolicitado = [];
        if (isset($data['retirar_qtd']) && is_array($data['retirar_qtd'])) {
            foreach ($data['retirar_qtd'] as $kid => $qv) {
                $id = (int) $kid;
                $q = max(0, min(999, (int) $qv));
                if ($id > 0 && $q > 0) {
                    $retMapSolicitado[$id] = ($retMapSolicitado[$id] ?? 0) + $q;
                }
            }
            ksort($retMapSolicitado);
        } else {
            foreach ($this->normalizarIdsAdicionais($data['retirar_ingrediente_ids'] ?? []) as $rid) {
                $rid = (int) $rid;
                if ($rid > 0) {
                    $retMapSolicitado[$rid] = ($retMapSolicitado[$rid] ?? 0) + 1;
                }
            }
        }

        $retOk = [];
        foreach ($retMapSolicitado as $id => $q) {
            $id = (int) $id;
            $q = (int) $q;
            if ($id < 1 || $q < 1) {
                continue;
            }
            if (! in_array($id, $idsPermIng, true)) {
                return back()->withInput()->with('warning', 'Uma das opções de ingrediente não é válida para este produto.');
            }
            $retOk[$id] = $q;
        }
        ksort($retOk);

        $maxR = $p->limiteRetiradaIngredientesNaLoja();
        if ($p->ingredientes->isEmpty() || $maxR === 0) {
            $retOk = [];
        } else {
            $sumR = (int) array_sum($retOk);
            if ($sumR > $maxR) {
                return back()->withInput()->with('warning', 'Você pode escolher no máximo '.$maxR.' (somando as quantidades entre os ingredientes).');
            }
        }

        if ($p->estoque !== null && $p->estoque < $qty) {
            return back()->withInput()->with('warning', 'Quantidade indisponível em estoque para este produto.');
        }

        $lines = $this->getCarrinhoLines($slug);
        $fp = $this->fingerprintLinha((int) $p->id, $mapOk, $retOk, $obsNorm, $notaNorm);
        $found = false;
        foreach ($lines as $i => $line) {
            $lineObs = $this->normalizarObservacao($line['observacao'] ?? null);
            $lineNota = $this->normalizarNotaProduto($line['nota_produto'] ?? null);
            $lineFp = $this->fingerprintLinha(
                (int) $line['produto_id'],
                $this->linhaParaMapaAdicionalQtd($line),
                $this->linhaParaMapaRetirarQtd($line),
                $lineObs,
                $lineNota
            );
            if ($lineFp === $fp) {
                $lines[$i]['quantidade'] = (int) $lines[$i]['quantidade'] + $qty;
                $found = true;
                break;
            }
        }

        if (! $found) {
            $lines[] = [
                'produto_id' => (int) $p->id,
                'quantidade' => $qty,
                'adicional_qtd' => $mapOk,
                'retirar_qtd' => $retOk,
                'observacao' => $obsNorm,
                'nota_produto' => $notaNorm,
            ];
        }

        $totalMesmoProduto = collect($lines)->where('produto_id', (int) $p->id)->sum('quantidade');
        if ($p->estoque !== null && $totalMesmoProduto > $p->estoque) {
            return back()->withInput()->with('warning', 'Não há estoque suficiente para a quantidade desejada.');
        }

        $this->setCarrinhoLines($slug, array_values($lines));

        return redirect()
            ->route('publico.carrinho', ['slug' => $slug])
            ->with('status', 'Item adicionado ao carrinho.');
    }

    public function carrinho(string $slug): View
    {
        $empresa = $this->empresaLojaAtiva($slug);
        $linhas = $this->linhasCarrinho($empresa, $slug);
        $subtotal = $this->subtotalCarrinho($linhas);
        $prefs = $this->getEntregaPrefs($slug, $empresa);
        $resumo = $this->calcularTaxaResumo(
            $empresa,
            $prefs['modo'],
            $prefs['cep'] !== '' ? $prefs['cep'] : null,
            $subtotal
        );
        $taxa = $resumo['taxa'];
        $taxaRotulo = $resumo['rotulo'];
        $freteEntregaBloqueada = (bool) ($resumo['entrega_bloqueada'] ?? false);
        $total = $freteEntregaBloqueada ? round($subtotal, 2) : round($subtotal + $taxa, 2);
        $permiteBalcao = $this->lojaPermiteRetiradaBalcao($empresa);

        return view('publico.carrinho', compact(
            'slug',
            'empresa',
            'linhas',
            'subtotal',
            'taxa',
            'taxaRotulo',
            'total',
            'prefs',
            'permiteBalcao',
            'freteEntregaBloqueada'
        ));
    }

    public function carrinhoEntregaPrefs(Request $request, string $slug): RedirectResponse
    {
        $empresa = $this->empresaLojaAtiva($slug);

        $data = $request->validate([
            'modo' => ['required', Rule::in([Pedido::TIPO_ENTREGA_ENTREGA, Pedido::TIPO_ENTREGA_BALCAO])],
            'cep' => ['nullable', 'string', 'max:16'],
        ]);

        if ($data['modo'] === Pedido::TIPO_ENTREGA_BALCAO && ! $this->lojaPermiteRetiradaBalcao($empresa)) {
            return back()->with('warning', 'Retirada no balcão não está disponível nesta loja.');
        }

        session([$this->entregaPrefsKey($slug) => [
            'modo' => $data['modo'],
            'cep' => preg_replace('/\D+/', '', (string) ($data['cep'] ?? '')),
        ]]);

        return redirect()
            ->route('publico.carrinho', ['slug' => $slug])
            ->with('status', 'Frete atualizado.');
    }

    public function carrinhoAtualizar(Request $request, string $slug): RedirectResponse
    {
        $this->empresaLojaAtiva($slug);

        $data = $request->validate([
            'quantidade' => ['required', 'array'],
            'quantidade.*' => ['integer', 'min:0', 'max:99'],
        ]);

        $lines = $this->getCarrinhoLines($slug);
        foreach ($data['quantidade'] as $idx => $q) {
            $idx = (int) $idx;
            if (! isset($lines[$idx])) {
                continue;
            }
            $lines[$idx]['quantidade'] = (int) $q;
        }
        $lines = array_values(array_filter($lines, fn ($l) => $l['quantidade'] > 0));
        $this->setCarrinhoLines($slug, $lines);

        return redirect()
            ->route('publico.carrinho', ['slug' => $slug])
            ->with('status', 'Carrinho atualizado.');
    }

    public function carrinhoRemover(Request $request, string $slug): RedirectResponse
    {
        $this->empresaLojaAtiva($slug);

        $data = $request->validate([
            'line_index' => ['required', 'integer', 'min:0'],
        ]);

        $lines = $this->getCarrinhoLines($slug);
        $i = (int) $data['line_index'];
        if (isset($lines[$i])) {
            array_splice($lines, $i, 1);
        }
        $this->setCarrinhoLines($slug, array_values($lines));

        return redirect()
            ->route('publico.carrinho', ['slug' => $slug])
            ->with('status', 'Item removido.');
    }

    public function freteResumoJson(Request $request, string $slug): JsonResponse
    {
        $empresa = $this->empresaLojaAtiva($slug);
        $request->validate([
            'cep' => ['nullable', 'string', 'max:16'],
            'subtotal' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ]);
        $digits = preg_replace('/\D+/', '', (string) $request->input('cep', ''));
        if (strlen($digits) > 0 && strlen($digits) < 8) {
            return response()->json([
                'ok' => true,
                'incomplete' => true,
            ]);
        }
        $cepParam = strlen($digits) === 8 ? $digits : null;
        $sub = $request->input('subtotal');
        $subF = $sub !== null && $sub !== '' ? (float) $sub : null;
        $resumo = $this->calcularTaxaResumo($empresa, Pedido::TIPO_ENTREGA_ENTREGA, $cepParam, $subF);

        return response()->json([
            'ok' => true,
            'incomplete' => false,
            'taxa' => $resumo['taxa'],
            'rotulo' => $resumo['rotulo'],
            'entrega_bloqueada' => (bool) ($resumo['entrega_bloqueada'] ?? false),
        ]);
    }

    public function checkout(string $slug): View|RedirectResponse
    {
        $empresa = $this->empresaLojaAtiva($slug);
        $linhas = $this->linhasCarrinho($empresa, $slug);
        if ($linhas === []) {
            return redirect()
                ->route('publico.carrinho', ['slug' => $slug])
                ->with('warning', 'Seu carrinho está vazio.');
        }

        $subtotal = $this->subtotalCarrinho($linhas);
        $prefs = $this->getEntregaPrefs($slug, $empresa);
        $tipoCheckout = old('tipo_entrega', $prefs['modo']);
        if (! in_array($tipoCheckout, [Pedido::TIPO_ENTREGA_ENTREGA, Pedido::TIPO_ENTREGA_BALCAO], true)) {
            $tipoCheckout = Pedido::TIPO_ENTREGA_ENTREGA;
        }
        if ($tipoCheckout === Pedido::TIPO_ENTREGA_BALCAO && ! $this->lojaPermiteRetiradaBalcao($empresa)) {
            $tipoCheckout = Pedido::TIPO_ENTREGA_ENTREGA;
        }
        $cepDigits = old('cep_entrega') !== null
            ? preg_replace('/\D+/', '', (string) old('cep_entrega'))
            : $prefs['cep'];
        $resumo = $this->calcularTaxaResumo(
            $empresa,
            $tipoCheckout,
            $tipoCheckout === Pedido::TIPO_ENTREGA_ENTREGA && $cepDigits !== '' ? $cepDigits : null,
            $subtotal
        );
        $taxa = $resumo['taxa'];
        $taxaRotulo = $resumo['rotulo'];
        $freteEntregaBloqueada = (bool) ($resumo['entrega_bloqueada'] ?? false);
        $total = $freteEntregaBloqueada ? round($subtotal, 2) : round($subtotal + $taxa, 2);
        $permiteBalcao = $this->lojaPermiteRetiradaBalcao($empresa);

        $resumoEntregaCep = $this->calcularTaxaResumo(
            $empresa,
            Pedido::TIPO_ENTREGA_ENTREGA,
            $cepDigits !== '' ? $cepDigits : null,
            $subtotal
        );
        $taxaSeEntrega = $resumoEntregaCep['taxa'];
        $rotuloSeEntrega = $resumoEntregaCep['rotulo'];
        $freteEntregaBloqueadaSeEntrega = (bool) ($resumoEntregaCep['entrega_bloqueada'] ?? false);

        $checkoutOsrm = $empresa->lojaFreteModoEfetivo() === Empresa::LOJA_FRETE_OSRM_DISTANCIA;
        $calcularEntregaApiUrl = route('api.calcular-entrega');

        $empresa->loadMissing('fidelidadePrograma');

        return view('publico.checkout', compact(
            'slug',
            'empresa',
            'linhas',
            'subtotal',
            'taxa',
            'taxaRotulo',
            'total',
            'tipoCheckout',
            'cepDigits',
            'permiteBalcao',
            'taxaSeEntrega',
            'rotuloSeEntrega',
            'freteEntregaBloqueada',
            'freteEntregaBloqueadaSeEntrega',
            'checkoutOsrm',
            'calcularEntregaApiUrl'
        ));
    }

    public function checkoutFinalizar(Request $request, string $slug): RedirectResponse
    {
        $empresa = $this->empresaLojaAtiva($slug);
        $linhas = $this->linhasCarrinho($empresa, $slug);
        if ($linhas === []) {
            return redirect()
                ->route('publico.carrinho', ['slug' => $slug])
                ->with('warning', 'Seu carrinho está vazio.');
        }

        $subtotalVal = $this->subtotalCarrinho($linhas);

        $formasCheckout = array_keys($empresa->formasPagamentoLojaPublica());

        $tiposEntrega = [Pedido::TIPO_ENTREGA_ENTREGA, Pedido::TIPO_ENTREGA_BALCAO];
        if (! $this->lojaPermiteRetiradaBalcao($empresa)) {
            $tiposEntrega = [Pedido::TIPO_ENTREGA_ENTREGA];
        }

        $empresa->loadMissing('fidelidadePrograma');
        $programaFid = $empresa->fidelidadePrograma;

        $rules = [
            'tipo_entrega' => ['required', Rule::in($tiposEntrega)],
            'cep_entrega' => ['nullable', 'string', 'max:16'],
            'cliente_nome' => ['required', 'string', 'max:120'],
            'cliente_telefone' => ['required', 'string', 'max:32'],
            'cliente_email' => ['nullable', 'email', 'max:255'],
            'endereco' => ['nullable', 'string', 'max:255'],
            'entrega_numero' => ['nullable', 'string', 'max:32'],
            'entrega_bairro' => ['nullable', 'string', 'max:120'],
            'entrega_cidade' => ['nullable', 'string', 'max:120'],
            'entrega_estado' => ['nullable', 'string', 'max:2'],
            'complemento' => ['nullable', 'string', 'max:120'],
            'forma_pagamento' => ['required', 'string', Rule::in($formasCheckout)],
            'pagamento_dinheiro_modo' => ['nullable', 'string', Rule::in(['exato', 'com_troco'])],
            'pagamento_troco_para' => ['nullable', 'numeric', 'min:0'],
            'observacoes' => ['nullable', 'string', 'max:220'],
        ];
        if ($programaFid && $programaFid->ativo) {
            $rules['fidelidade_quero'] = ['nullable', 'in:0,1'];
            $rules['fidelidade_telefone'] = ['nullable', 'string', 'max:32'];
            $rules['fidelidade_cpf'] = ['nullable', 'string', 'max:18'];
        }

        $data = $request->validate($rules);

        $fidelidadeQuero = $programaFid && $programaFid->ativo && (($data['fidelidade_quero'] ?? '0') === '1');
        $telFidNorm = null;
        $cpfFidNorm = null;
        if ($fidelidadeQuero) {
            $telFidNorm = FidelidadeCartao::normalizarTelefone($data['fidelidade_telefone'] ?? '');
            if (strlen($telFidNorm) < 8) {
                return back()
                    ->withInput()
                    ->withErrors(['fidelidade_telefone' => 'Informe o telefone do cartão fidelidade.']);
            }
            $telPedido = FidelidadeCartao::normalizarTelefone($data['cliente_telefone']);
            if ($telFidNorm !== $telPedido) {
                return back()
                    ->withInput()
                    ->withErrors(['fidelidade_telefone' => 'O telefone do cartão deve ser o mesmo do pedido.']);
            }
            $cpfFidNorm = FidelidadeCartao::normalizarCpf($data['fidelidade_cpf'] ?? '');
            if ($cpfFidNorm === null || ! FidelidadeCartao::cpfValido($cpfFidNorm)) {
                return back()
                    ->withInput()
                    ->withErrors(['fidelidade_cpf' => 'Informe um CPF válido.']);
            }
            $emailPedido = strtolower(trim((string) ($data['cliente_email'] ?? '')));
            $conflitoFid = FidelidadeCartao::conflitoCadastroFidelidade(
                $empresa->id,
                $telFidNorm,
                $cpfFidNorm,
                $emailPedido,
                true
            );
            if ($conflitoFid !== null) {
                return back()
                    ->withInput()
                    ->withErrors([$conflitoFid['field'] => $conflitoFid['message']]);
            }
        }

        $tipoEntrega = $data['tipo_entrega'];
        $cepNorm = null;
        $enderecoPedido = '';
        $complementoPedido = $data['complemento'] ?: null;

        if ($tipoEntrega === Pedido::TIPO_ENTREGA_BALCAO) {
            $taxaVal = 0.0;
            $enderecoPedido = 'Retirada no balcão';
        } else {
            $cepNorm = Cep::normalizar8($data['cep_entrega'] ?? '');
            if ($cepNorm === null) {
                return back()
                    ->withInput()
                    ->withErrors(['cep_entrega' => 'Informe um CEP válido (8 dígitos).']);
            }
            $enderecoTrim = trim((string) ($data['endereco'] ?? ''));
            if ($enderecoTrim === '') {
                return back()
                    ->withInput()
                    ->withErrors(['endereco' => 'Informe o endereço de entrega.']);
            }
            $enderecoPedido = $this->montarEnderecoEntregaCheckout($enderecoTrim, $data);
            if (mb_strlen($enderecoPedido) > 255) {
                $enderecoPedido = mb_substr($enderecoPedido, 0, 255, 'UTF-8');
            }
            if ($empresa->lojaFreteModoEfetivo() === Empresa::LOJA_FRETE_PADRAO_UNICO) {
                $taxaVal = $empresa->lojaTaxaEntregaPadraoEfetiva();
            } elseif (Empresa::lojaFreteModoUsaKmRodoviario($empresa->lojaFreteModoEfetivo())) {
                if ($empresa->lojaFreteModoEfetivo() === Empresa::LOJA_FRETE_GOOGLE_DISTANCIA) {
                    $destino = $this->formatoDestinoFreteGoogleEnderecoCompleto($enderecoPedido, $cepNorm);
                    $rKm = $this->taxaFreteGoogleDistancia($empresa, $destino);
                } else {
                    $rKm = $this->taxaFreteOsrmPorCliente($empresa, [
                        'cep' => $cepNorm,
                        'rua' => $enderecoTrim,
                        'numero' => trim((string) ($data['entrega_numero'] ?? '')),
                        'bairro' => trim((string) ($data['entrega_bairro'] ?? '')),
                        'cidade' => trim((string) ($data['entrega_cidade'] ?? '')),
                        'estado' => strtoupper(trim((string) ($data['entrega_estado'] ?? ''))),
                    ], $subtotalVal);
                }
                if ($rKm['entrega_bloqueada']) {
                    return back()
                        ->withInput()
                        ->withErrors([
                            'cep_entrega' => 'Este CEP/endereço está fora da área de entrega da loja.',
                        ]);
                }
                $taxaVal = $rKm['taxa'];
            } elseif (Schema::hasTable('empresa_entrega_faixas_cep')) {
                $porFaixa = EmpresaEntregaFaixaCep::taxaParaCep((int) $empresa->id, $cepNorm);
                $taxaVal = $porFaixa !== null ? (float) $porFaixa : $empresa->lojaTaxaEntregaPadraoEfetiva();
            } else {
                $taxaVal = $empresa->lojaTaxaEntregaPadraoEfetiva();
            }
        }

        if ($tipoEntrega !== Pedido::TIPO_ENTREGA_BALCAO) {
            $taxaVal = (float) ($empresa->aplicarAcrescimoChuvaNoResumoFrete([
                'taxa' => (float) $taxaVal,
                'rotulo' => '',
                'entrega_bloqueada' => false,
            ])['taxa']);
        }

        $taxaVal = round((float) $taxaVal, 2);
        $totalPedido = round($subtotalVal + $taxaVal, 2);

        $pagamentoTrocoPara = null;
        if ($data['forma_pagamento'] === Pedido::PAGAMENTO_DINHEIRO) {
            $modo = $data['pagamento_dinheiro_modo'] ?? 'exato';
            if ($modo === 'com_troco') {
                $trocoPara = $data['pagamento_troco_para'] ?? null;
                if ($trocoPara === null || $trocoPara === '') {
                    return back()
                        ->withInput()
                        ->withErrors([
                            'pagamento_troco_para' => 'Informe com quanto vai pagar em dinheiro (valor igual ou maior ao total) para levarmos o troco.',
                        ]);
                }
                $v = (float) $trocoPara;
                if ($v < $totalPedido) {
                    return back()
                        ->withInput()
                        ->withErrors([
                            'pagamento_troco_para' => 'O valor deve ser igual ou maior ao total do pedido (R$ '.number_format($totalPedido, 2, ',', '.').').',
                        ]);
                }
                $pagamentoTrocoPara = round($v, 2);
            }
        }

        if ($data['forma_pagamento'] === Pedido::PAGAMENTO_PIX && ! $empresa->lojaPixConfiguradaParaCheckout()) {
            return back()
                ->withInput()
                ->withErrors(['forma_pagamento' => 'A loja ainda não configurou o PIX. Escolha outra forma de pagamento.']);
        }

        foreach ($linhas as $l) {
            $p = $l['produto'];
            $q = $l['quantidade'];
            if ($p->estoque !== null && $p->estoque < $q) {
                return back()->withInput()->with('warning', 'O produto "'.$p->nome.'" não tem estoque suficiente. Ajuste o carrinho.');
            }
        }

        $subtotal = $subtotalVal;
        $taxa = $taxaVal;
        $total = $totalPedido;

        $statusInicial = Pedido::STATUS_RECEBIDO;
        if (Schema::hasColumn('empresas', 'loja_confirmar_pedidos')
            && (bool) ($empresa->loja_confirmar_pedidos ?? false)) {
            $statusInicial = Pedido::STATUS_PENDENTE_LOJA;
        }

        $pedido = DB::transaction(function () use ($empresa, $linhas, $data, $subtotal, $taxa, $total, $pagamentoTrocoPara, $tipoEntrega, $cepNorm, $enderecoPedido, $complementoPedido, $statusInicial) {
            $pedido = Pedido::query()->create([
                'empresa_id' => $empresa->id,
                'codigo_publico' => $this->gerarCodigoPublico(),
                'canal' => Pedido::CANAL_LOJA,
                'tipo_entrega' => $tipoEntrega,
                'cliente_nome' => $data['cliente_nome'],
                'cliente_telefone' => $data['cliente_telefone'],
                'cliente_email' => $data['cliente_email'] ?: null,
                'endereco' => $enderecoPedido,
                'complemento' => $complementoPedido,
                'cep_entrega' => $cepNorm,
                'forma_pagamento' => $data['forma_pagamento'],
                'pagamento_troco_para' => $pagamentoTrocoPara,
                'observacoes' => $data['observacoes'] ?: null,
                'status' => $statusInicial,
                'subtotal' => $subtotal,
                'taxa_entrega' => $taxa,
                'total' => $total,
            ]);

            foreach ($linhas as $l) {
                $p = $l['produto'];
                $opLinha = [];
                if ($l['opcoes'] !== []) {
                    $opLinha['adicionais'] = $l['opcoes'];
                }
                if (($l['observacao'] ?? '') !== '') {
                    $opLinha['observacao'] = $l['observacao'];
                }
                $notaItem = (int) ($l['nota_produto'] ?? 0);
                if ($notaItem >= 1 && $notaItem <= 5) {
                    $opLinha['nota_produto'] = $notaItem;
                }

                PedidoItem::query()->create([
                    'pedido_id' => $pedido->id,
                    'produto_id' => $p->id,
                    'nome_produto' => $p->nome,
                    'preco_unitario' => $l['preco_unitario'],
                    'quantidade' => $l['quantidade'],
                    'subtotal' => $l['subtotal'],
                    'opcoes_linha' => $opLinha === [] ? null : $opLinha,
                ]);

                if ($p->estoque !== null) {
                    $p->decrement('estoque', $l['quantidade']);
                }
            }

            return $pedido;
        });

        $this->setCarrinhoLines($slug, []);
        session([$this->entregaPrefsKey($slug) => [
            'modo' => $tipoEntrega,
            'cep' => $cepNorm !== null ? $cepNorm : '',
        ]]);

        if ($fidelidadeQuero && $telFidNorm !== null && $cpfFidNorm !== null) {
            $this->registrarCartaoFidelidadePosPedido($empresa, $telFidNorm, $cpfFidNorm);
        }

        return redirect()
            ->route('publico.pedido.show', ['slug' => $slug, 'codigo' => $pedido->codigo_publico])
            ->with('status', 'Pedido registrado! Guarde o código para acompanhar.');
    }

    public function pedidoPublico(string $slug, string $codigo): View
    {
        $empresa = $this->empresaLojaAtiva($slug);

        $codigoNorm = $this->normalizarCodigoPublicoPedido($codigo);

        $pedido = Pedido::query()
            ->where('empresa_id', $empresa->id)
            ->where('codigo_publico', $codigoNorm)
            ->with('itens')
            ->first();

        if (! $pedido) {
            abort(404, 'Pedido não encontrado nesta loja. Confira o código (ex.: BV-XXXXXX).');
        }

        return view('publico.pedido-show', compact('slug', 'empresa', 'pedido'));
    }

    public function acompanhar(string $slug): View
    {
        $empresa = $this->empresaLojaAtiva($slug);

        return view('publico.acompanhar-pedido', compact('slug', 'empresa'));
    }

    public function acompanharBuscar(Request $request, string $slug): View|RedirectResponse
    {
        $empresa = $this->empresaLojaAtiva($slug);

        $data = $request->validate([
            'codigo' => ['required', 'string', 'max:32'],
        ]);

        $codigo = strtoupper(trim($data['codigo']));
        $codigo = ltrim($codigo, '#');
        if (! str_starts_with($codigo, 'BV-')) {
            $codigo = 'BV-'.$codigo;
        }

        $pedido = Pedido::query()
            ->where('empresa_id', $empresa->id)
            ->where('codigo_publico', $codigo)
            ->with('itens')
            ->first();

        if (! $pedido) {
            return back()
                ->withInput()
                ->with('warning', 'Pedido não encontrado. Confira o código (ex.: BV-XXXXXX).');
        }

        return view('publico.pedido-show', [
            'slug' => $slug,
            'empresa' => $empresa,
            'pedido' => $pedido,
        ]);
    }

    /**
     * Serve foto do produto (public/uploads ou storage/app/public legado).
     */
    public function produtoFoto(Produto $produto): BinaryFileResponse
    {
        $full = $produto->resolveFotoAbsolutePath();
        if ($full === null || ! is_file($full)) {
            abort(404);
        }

        $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
        if (in_array($ext, ['webp', 'avif'], true)) {
            try {
                $img = null;
                if ($ext === 'webp' && function_exists('imagecreatefromwebp')) {
                    $img = @imagecreatefromwebp($full);
                } elseif ($ext === 'avif' && function_exists('imagecreatefromavif')) {
                    $img = @imagecreatefromavif($full);
                } else {
                    $raw = @file_get_contents($full);
                    if (is_string($raw) && $raw !== '') {
                        $img = @imagecreatefromstring($raw);
                    }
                }

                if ($img !== null && $img !== false) {
                    $w = imagesx($img);
                    $h = imagesy($img);

                    $max = 1400;
                    $scale = ($w > 0 && $h > 0) ? min(1, $max / max($w, $h)) : 1;
                    $nw = max(1, (int) round($w * $scale));
                    $nh = max(1, (int) round($h * $scale));

                    $dst = imagecreatetruecolor($nw, $nh);
                    $white = imagecolorallocate($dst, 255, 255, 255);
                    imagefilledrectangle($dst, 0, 0, $nw, $nh, $white);
                    imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);

                    ob_start();
                    imagejpeg($dst, null, 85);
                    $jpeg = ob_get_clean();

                    imagedestroy($dst);
                    imagedestroy($img);

                    if (is_string($jpeg) && $jpeg !== '') {
                        return response($jpeg, 200, [
                            'Content-Type' => 'image/jpeg',
                            'Cache-Control' => 'public, max-age=604800',
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                // fallback abaixo
            }
        }

        return response()->file($full, [
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }

    /**
     * Serve foto opcional de ingrediente do prato (miniatura).
     */
    public function produtoIngredienteFoto(ProdutoIngrediente $produtoIngrediente): BinaryFileResponse|Response
    {
        $full = $produtoIngrediente->resolveFotoAbsolutePath();
        if ($full === null || ! is_file($full)) {
            abort(404);
        }

        $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
        if (in_array($ext, ['webp', 'avif'], true)) {
            try {
                $img = null;
                if ($ext === 'webp' && function_exists('imagecreatefromwebp')) {
                    $img = @imagecreatefromwebp($full);
                } elseif ($ext === 'avif' && function_exists('imagecreatefromavif')) {
                    $img = @imagecreatefromavif($full);
                } else {
                    $raw = @file_get_contents($full);
                    if (is_string($raw) && $raw !== '') {
                        $img = @imagecreatefromstring($raw);
                    }
                }

                if ($img !== null && $img !== false) {
                    $w = imagesx($img);
                    $h = imagesy($img);

                    $max = 1400;
                    $scale = ($w > 0 && $h > 0) ? min(1, $max / max($w, $h)) : 1;
                    $nw = max(1, (int) round($w * $scale));
                    $nh = max(1, (int) round($h * $scale));

                    $dst = imagecreatetruecolor($nw, $nh);
                    $white = imagecolorallocate($dst, 255, 255, 255);
                    imagefilledrectangle($dst, 0, 0, $nw, $nh, $white);
                    imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);

                    ob_start();
                    imagejpeg($dst, null, 85);
                    $jpeg = ob_get_clean();

                    imagedestroy($dst);
                    imagedestroy($img);

                    if (is_string($jpeg) && $jpeg !== '') {
                        return response($jpeg, 200, [
                            'Content-Type' => 'image/jpeg',
                            'Cache-Control' => 'public, max-age=604800',
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                // fallback abaixo
            }
        }

        return response()->file($full, [
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }

    /**
     * Serve logo da empresa (public/uploads ou storage/app/public legado).
     */
    public function empresaLogo(Empresa $empresa): BinaryFileResponse
    {
        $full = $empresa->resolveLogoAbsolutePath();
        if ($full === null || ! is_file($full)) {
            abort(404);
        }

        return response()->file($full, [
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }

    public function legadoCarrinho(): RedirectResponse
    {
        return redirect()
            ->route('site.home')
            ->with('warning', 'Abra a loja pelo link do seu restaurante e use o carrinho no menu superior.');
    }

    private function registrarCartaoFidelidadePosPedido(Empresa $empresa, string $telefoneNormalizado, string $cpf11): void
    {
        $programa = $empresa->fidelidadePrograma;
        if (! $programa || ! $programa->ativo) {
            return;
        }

        $clienteId = null;
        $clientes = Cliente::query()
            ->where('empresa_id', $empresa->id)
            ->whereNotNull('telefone')
            ->get(['id', 'telefone']);
        foreach ($clientes as $c) {
            if (FidelidadeCartao::normalizarTelefone($c->telefone) === $telefoneNormalizado) {
                $clienteId = (int) $c->id;
                break;
            }
        }

        $attrs = ['cliente_id' => $clienteId];
        if (Schema::hasColumn('fidelidade_cartoes', 'cpf_normalizado')) {
            $attrs['cpf_normalizado'] = $cpf11;
        }

        $cartao = FidelidadeCartao::query()->firstOrCreate(
            [
                'empresa_id' => $empresa->id,
                'telefone_normalizado' => $telefoneNormalizado,
            ],
            $attrs
        );

        if (Schema::hasColumn('fidelidade_cartoes', 'cpf_normalizado')) {
            if (! $cartao->cpf_normalizado) {
                $cartao->forceFill(['cpf_normalizado' => $cpf11])->save();
            }
        }
        if ($clienteId && ! $cartao->cliente_id) {
            $cartao->update(['cliente_id' => $clienteId]);
        }

        $cartao->increment('selos');
    }

    public function legadoCheckout(): RedirectResponse
    {
        return redirect()
            ->route('site.home')
            ->with('warning', 'O checkout fica dentro da página da loja, após adicionar itens ao carrinho.');
    }

    public function legadoProduto(): RedirectResponse
    {
        return redirect()
            ->route('site.home')
            ->with('warning', 'Acesse o produto pelo cardápio da loja (link com o nome do estabelecimento).');
    }

    public function legadoAcompanhar(): RedirectResponse
    {
        return redirect()
            ->route('site.home')
            ->with('warning', 'Para acompanhar um pedido, abra a loja onde você comprou e use “Pedido” no menu.');
    }
}
