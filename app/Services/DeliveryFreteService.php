<?php

namespace App\Services;

use App\Models\Empresa;
use App\Support\Cep;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Cálculo de frete por rota OSRM: geocode cliente, origem em coordenadas, taxa base + km extra.
 */
final class DeliveryFreteService
{
    public function __construct(
        private GeocodingService $geocoding,
        private OsrmService $osrm,
    ) {}

    /**
     * @param  array{cep?:string, rua?:string, numero?:string, bairro?:string, cidade?:string, estado?:string}  $cliente
     * @return array{
     *   success: bool,
     *   message?: string,
     *   distancia_km?: float,
     *   tempo_minutos?: int,
     *   taxa_entrega?: float,
     *   endereco_formatado?: string,
     *   lat_cliente?: float,
     *   lng_cliente?: float,
     *   rotulo?: string,
     *   entrega_bloqueada?: bool
     * }
     */
    public function calcular(Empresa $empresa, array $cliente, ?float $subtotalPedido = null): array
    {
        $padrao = round($empresa->lojaTaxaEntregaPadraoEfetiva(), 2);

        $ua = trim((string) config('services.osm_routing.http_user_agent', ''));
        if ($ua === '') {
            Log::warning('delivery_frete.user_agent_osm_ausente');

            return $this->erro($padrao, 'Servidor sem OSM_HTTP_USER_AGENT (Nominatim/OSRM).', false);
        }

        $origem = $this->resolverCoordenadasOrigem($empresa);
        if ($origem === null) {
            Log::info('delivery_frete.origem_nao_resolvida', ['empresa_id' => $empresa->id]);

            return $this->erro($padrao, 'Coordenadas de origem da loja não configuradas ou endereço não localizado. Ajuste em Configurações.', false);
        }

        $cepDigits = preg_replace('/\D+/', '', (string) ($cliente['cep'] ?? ''));
        if (strlen($cepDigits) !== 8) {
            return $this->erro($padrao, 'Informe um CEP válido com 8 dígitos.', false);
        }

        $clienteNorm = array_merge([
            'cep' => substr($cepDigits, 0, 5).'-'.substr($cepDigits, 5),
            'rua' => trim((string) ($cliente['rua'] ?? '')),
            'numero' => trim((string) ($cliente['numero'] ?? '')),
            'bairro' => trim((string) ($cliente['bairro'] ?? '')),
            'cidade' => trim((string) ($cliente['cidade'] ?? '')),
            'estado' => trim((string) ($cliente['estado'] ?? '')),
        ], []);

        $geoCliente = $this->geocoding->geocodeClienteEndereco($clienteNorm);
        if ($geoCliente === null) {
            Log::info('delivery_frete.cliente_geocode_falhou', [
                'empresa_id' => $empresa->id,
                'query' => $this->geocoding->montarQueryCliente($clienteNorm),
            ]);

            return [
                'success' => false,
                'message' => 'Não foi possível localizar o endereço informado.',
                'taxa_entrega' => $padrao,
                'rotulo' => 'Frete R$ '.number_format($padrao, 2, ',', '.').' (taxa base) — endereço não localizado no mapa.',
                'entrega_bloqueada' => false,
            ];
        }

        $rota = $this->osrm->routeDriving(
            $origem['lat'],
            $origem['lon'],
            $geoCliente['lat'],
            $geoCliente['lon']
        );

        if ($rota === null) {
            Log::warning('delivery_frete.osrm_rota_falhou', ['empresa_id' => $empresa->id]);

            return $this->erro($padrao, 'Não foi possível calcular a rota até o endereço. Tente novamente em instantes.', false);
        }

        $distKm = $rota['distance_km'];
        $kmMax = $empresa->lojaFreteGoogleKmMax();
        if ($kmMax !== null && $distKm > $kmMax) {
            return [
                'success' => true,
                'distancia_km' => $distKm,
                'tempo_minutos' => (int) round($rota['duration_seconds'] / 60),
                'taxa_entrega' => 0.0,
                'endereco_formatado' => (string) ($geoCliente['display_name'] ?? $this->geocoding->montarQueryCliente($clienteNorm)),
                'lat_cliente' => $geoCliente['lat'],
                'lng_cliente' => $geoCliente['lon'],
                'rotulo' => 'Fora da área de entrega (máx. '.number_format((float) $kmMax, 1, ',', '.').' km)',
                'entrega_bloqueada' => true,
            ];
        }

        $taxa = $this->precificar($empresa, $distKm, $subtotalPedido);
        $minutos = max(1, (int) round($rota['duration_seconds'] / 60));
        $endFmt = (string) ($geoCliente['display_name'] ?? $this->geocoding->montarQueryCliente($clienteNorm));

        $kmInc = $empresa->lojaEntregaKmInclusoEfetivo();
        $vExtra = $empresa->lojaEntregaValorKmExtraEfetivo();
        $extraKm = max(0.0, $distKm - $kmInc);
        $unidades = (int) ceil($extraKm);
        $gratisAcima = $empresa->lojaEntregaGratisAcimaPedido();
        $ehGratis = $taxa <= 0.0001 && $gratisAcima !== null && $subtotalPedido !== null && $subtotalPedido >= $gratisAcima;

        $rotulo = 'Entrega ~'.number_format($distKm, 1, ',', '.').' km, ~'.$minutos.' min';
        if ($ehGratis) {
            $rotulo .= ' — entrega grátis (pedido ≥ R$ '.number_format($gratisAcima, 2, ',', '.').')';
        } else {
            $rotulo .= ' — base R$ '.number_format($empresa->lojaTaxaEntregaPadraoEfetiva(), 2, ',', '.');
            $rotulo .= ' + '.$unidades.' × R$ '.number_format($vExtra, 2, ',', '.').' (km acima de '.number_format($kmInc, 1, ',', '.').' km)';
        }

        return [
            'success' => true,
            'distancia_km' => $distKm,
            'tempo_minutos' => $minutos,
            'taxa_entrega' => round($taxa, 2),
            'endereco_formatado' => $endFmt,
            'lat_cliente' => $geoCliente['lat'],
            'lng_cliente' => $geoCliente['lon'],
            'rotulo' => $rotulo,
            'entrega_bloqueada' => false,
        ];
    }

    /**
     * @param  array{cep?:string, rua?:string, numero?:string, bairro?:string, cidade?:string, estado?:string}  $cliente
     */
    public function calcularParaCepApenas(Empresa $empresa, string $cep8Digits, ?float $subtotalPedido = null): array
    {
        $n = Cep::normalizar8($cep8Digits);
        if ($n === null) {
            return [
                'success' => false,
                'message' => 'CEP inválido.',
                'taxa_entrega' => round($empresa->lojaTaxaEntregaPadraoEfetiva(), 2),
                'rotulo' => 'Informe o CEP para calcular o frete.',
                'entrega_bloqueada' => false,
            ];
        }

        return $this->calcular($empresa, ['cep' => $n], $subtotalPedido);
    }

    private function precificar(Empresa $empresa, float $distKm, ?float $subtotalPedido): float
    {
        $gratisAcima = $empresa->lojaEntregaGratisAcimaPedido();
        if ($gratisAcima !== null && $gratisAcima > 0 && $subtotalPedido !== null && $subtotalPedido >= $gratisAcima) {
            return 0.0;
        }

        $taxaBase = (float) $empresa->lojaTaxaEntregaPadraoEfetiva();
        $kmIncluso = $empresa->lojaEntregaKmInclusoEfetivo();
        $valorKmExtra = $empresa->lojaEntregaValorKmExtraEfetivo();

        $extraKm = max(0.0, $distKm - $kmIncluso);
        $unidades = (int) ceil($extraKm);

        return $taxaBase + ($unidades * $valorKmExtra);
    }

    /**
     * @return array{lat: float, lon: float}|null
     */
    private function resolverCoordenadasOrigem(Empresa $empresa): ?array
    {
        if (Schema::hasColumn('empresas', 'loja_entrega_lat_origem')
            && Schema::hasColumn('empresas', 'loja_entrega_lng_origem')) {
            $lat = $empresa->loja_entrega_lat_origem;
            $lng = $empresa->loja_entrega_lng_origem;
            if ($lat !== null && $lng !== null && (float) $lat != 0.0 && (float) $lng != 0.0) {
                return ['lat' => (float) $lat, 'lon' => (float) $lng];
            }
        }

        $texto = $empresa->lojaFreteOrigemEnderecoEfetiva();
        if ($texto === null || trim($texto) === '') {
            return null;
        }

        $g = $this->geocoding->geocodeByQuery($texto);

        return $g === null ? null : ['lat' => $g['lat'], 'lon' => $g['lon']];
    }

    /**
     * @return array{success: false, message: string, taxa_entrega: float, rotulo: string, entrega_bloqueada: bool}
     */
    private function erro(float $padrao, string $message, bool $bloqueada): array
    {
        return [
            'success' => false,
            'message' => $message,
            'taxa_entrega' => $padrao,
            'rotulo' => 'Frete R$ '.number_format($padrao, 2, ',', '.').' (taxa base) — '.$message,
            'entrega_bloqueada' => $bloqueada,
        ];
    }
}
