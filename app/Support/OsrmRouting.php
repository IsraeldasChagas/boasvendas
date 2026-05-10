<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Geocodificação via Nominatim (OpenStreetMap) e rota rodoviária via OSRM.
 * Respeite a política de uso do Nominatim (User-Agent identificável, cache, volume moderado).
 */
final class OsrmRouting
{
    /**
     * @return array{lat: float, lon: float}|null
     */
    public static function geocodeEndereco(string $query): ?array
    {
        $query = trim($query);
        if ($query === '') {
            return null;
        }

        $cacheKey = 'nominatim_geo_v3:'.md5($query);
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $base = (string) config('services.osm_routing.nominatim_base_url', '');
        $ua = trim((string) config('services.osm_routing.http_user_agent', ''));
        if ($base === '' || $ua === '') {
            return null;
        }

        $headers = [
            'User-Agent' => $ua,
            'Accept' => 'application/json',
        ];

        $first = self::primeiroResultadoNominatim($base, $headers, [
            'q' => $query,
            'format' => 'json',
            'limit' => 1,
        ]);

        if ($first === null) {
            $cep8Extraido = self::extrairCepBrasil8DaString($query);
            if ($cep8Extraido !== null) {
                $first = self::geocodeCepBrasilFallback($base, $headers, $cep8Extraido);
            }
        }

        if ($first === null) {
            return null;
        }

        $lat = isset($first['lat']) ? (float) $first['lat'] : null;
        $lon = isset($first['lon']) ? (float) $first['lon'] : null;
        if ($lat === null || $lon === null) {
            return null;
        }

        $out = ['lat' => $lat, 'lon' => $lon];
        Cache::put($cacheKey, $out, now()->addDays(7));

        return $out;
    }

    /**
     * @param  array<string, scalar|null>  $params
     * @return array<string, mixed>|null
     */
    private static function primeiroResultadoNominatim(string $base, array $headers, array $params): ?array
    {
        $response = Http::timeout(12)
            ->withHeaders($headers)
            ->get(rtrim($base, '/').'/search', $params);

        if (! $response->successful()) {
            return null;
        }

        $json = $response->json();
        if (! is_array($json) || $json === []) {
            return null;
        }

        $first = $json[0];

        return is_array($first) ? $first : null;
    }

    /**
     * Evita usar os 8 primeiros dígitos de textos longos (ex.: número + CEP misturados).
     */
    private static function extrairCepBrasil8DaString(string $query): ?string
    {
        if (preg_match('/\b(\d{5})-?(\d{3})\b/', $query, $m)) {
            return Cep::normalizar8($m[1].$m[2]);
        }

        $digits = preg_replace('/\D+/', '', $query);

        return strlen($digits) === 8 ? Cep::normalizar8($digits) : null;
    }

    /** Texto livre no Nominatim costuma falhar só com "xxxx-xxxx, Brasil"; tenta CEP estruturado e ViaCEP. */
    private static function geocodeCepBrasilFallback(string $base, array $headers, string $cep8): ?array
    {
        $cepFmt = substr($cep8, 0, 5).'-'.substr($cep8, 5);

        $try = self::primeiroResultadoNominatim($base, $headers, [
            'postalcode' => $cepFmt,
            'countrycodes' => 'br',
            'format' => 'json',
            'limit' => 1,
        ]);
        if ($try !== null) {
            return $try;
        }

        $try = self::primeiroResultadoNominatim($base, $headers, [
            'postalcode' => $cep8,
            'countrycodes' => 'br',
            'format' => 'json',
            'limit' => 1,
        ]);
        if ($try !== null) {
            return $try;
        }

        $response = Http::timeout(10)
            ->withHeaders([
                'User-Agent' => $headers['User-Agent'] ?? 'VendAffacil',
                'Accept' => 'application/json',
            ])
            ->get('https://viacep.com.br/ws/'.$cep8.'/json/');
        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();
        if (! is_array($data)) {
            return null;
        }

        $erroVia = $data['erro'] ?? false;
        if ($erroVia === true || $erroVia === 'true') {
            return null;
        }

        $cidade = trim((string) ($data['localidade'] ?? ''));
        $uf = trim((string) ($data['uf'] ?? ''));
        if ($cidade === '' || $uf === '') {
            return null;
        }

        $partes = [];
        $log = trim((string) ($data['logradouro'] ?? ''));
        $bai = trim((string) ($data['bairro'] ?? ''));
        if ($log !== '') {
            $partes[] = $log;
        }
        if ($bai !== '') {
            $partes[] = $bai;
        }
        $partes[] = $cidade;
        $partes[] = $uf;
        $partes[] = 'Brasil';
        $q = implode(', ', $partes);

        return self::primeiroResultadoNominatim($base, $headers, [
            'q' => $q,
            'format' => 'json',
            'limit' => 1,
        ]);
    }

    /** Distância em km pela rota (modo driving OSRM), ou null se indisponível. */
    public static function distanciaKmRodoviaria(string $origem, string $destino): ?float
    {
        $origem = trim($origem);
        $destino = trim($destino);
        if ($origem === '' || $destino === '') {
            return null;
        }

        $cacheKey = 'osrm_route_v2:'.md5($origem.'|'.$destino);
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $o = self::geocodeEndereco($origem);
        $d = self::geocodeEndereco($destino);
        if ($o === null || $d === null) {
            return null;
        }

        $base = (string) config('services.osm_routing.osrm_base_url', '');
        if ($base === '') {
            return null;
        }

        $coords = $o['lon'].','.$o['lat'].';'.$d['lon'].','.$d['lat'];
        $url = rtrim($base, '/').'/route/v1/driving/'.$coords;
        $ua = trim((string) config('services.osm_routing.http_user_agent', ''));

        $response = Http::timeout(18)
            ->withHeaders([
                'User-Agent' => $ua !== '' ? $ua : 'VendAffacil',
                'Accept' => 'application/json',
            ])
            ->get($url, [
                'overview' => 'false',
            ]);

        if (! $response->successful()) {
            return null;
        }

        $json = $response->json();
        if (! is_array($json) || ($json['code'] ?? '') !== 'Ok') {
            return null;
        }

        $routes = $json['routes'] ?? null;
        $route0 = is_array($routes) && isset($routes[0]) ? $routes[0] : null;
        if (! is_array($route0)) {
            return null;
        }

        $meters = (float) ($route0['distance'] ?? 0);
        if ($meters <= 0) {
            return null;
        }

        $km = round($meters / 1000, 3);
        Cache::put($cacheKey, $km, now()->addDay());

        return $km;
    }
}
