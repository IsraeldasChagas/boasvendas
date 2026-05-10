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

        $cacheKey = 'nominatim_geo_v1:'.md5($query);
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $base = (string) config('services.osm_routing.nominatim_base_url', '');
        $ua = trim((string) config('services.osm_routing.http_user_agent', ''));
        if ($base === '' || $ua === '') {
            return null;
        }

        $response = Http::timeout(12)
            ->withHeaders([
                'User-Agent' => $ua,
                'Accept' => 'application/json',
            ])
            ->get(rtrim($base, '/').'/search', [
                'q' => $query,
                'format' => 'json',
                'limit' => 1,
            ]);

        if (! $response->successful()) {
            return null;
        }

        $json = $response->json();
        if (! is_array($json) || $json === []) {
            return null;
        }

        $first = $json[0];
        if (! is_array($first)) {
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

    /** Distância em km pela rota (modo driving OSRM), ou null se indisponível. */
    public static function distanciaKmRodoviaria(string $origem, string $destino): ?float
    {
        $origem = trim($origem);
        $destino = trim($destino);
        if ($origem === '' || $destino === '') {
            return null;
        }

        $cacheKey = 'osrm_route_v1:'.md5($origem.'|'.$destino);
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
