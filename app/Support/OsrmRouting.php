<?php

namespace App\Support;

use App\Services\GeocodingService;
use App\Services\OsrmService;

/**
 * Compatibilidade: geocoding + rota OSRM (delega aos serviços).
 */
final class OsrmRouting
{
    /**
     * @return array{lat: float, lon: float, display_name?: string|null}|null
     */
    public static function geocodeEndereco(string $query): ?array
    {
        return app(GeocodingService::class)->geocodeByQuery($query);
    }

    /**
     * @param  ?string  $origemFallback  Ex.: "76800-000, Brasil" quando o endereço completo da loja não geocodifica.
     */
    public static function distanciaKmRodoviaria(string $origem, string $destino, ?string $origemFallback = null): ?float
    {
        $geo = app(GeocodingService::class);
        $osrm = app(OsrmService::class);

        $origem = trim($origem);
        $destino = trim($destino);
        if ($origem === '' || $destino === '') {
            return null;
        }

        $fb = $origemFallback !== null ? trim($origemFallback) : '';

        $o = $geo->geocodeByQuery($origem);
        if ($o === null && $fb !== '') {
            $o = $geo->geocodeByQuery($fb);
        }
        $d = $geo->geocodeByQuery($destino);
        if ($o === null || $d === null) {
            \Illuminate\Support\Facades\Log::debug('osrm.geocode_falhou', [
                'origem_coords_ok' => $o !== null,
                'destino_coords_ok' => $d !== null,
                'origem_preview' => substr($origem, 0, 120),
                'destino_preview' => substr($destino, 0, 120),
                'fallback_cep_loja_disponivel' => $fb !== '',
            ]);

            return null;
        }

        $route = $osrm->routeDriving($o['lat'], $o['lon'], $d['lat'], $d['lon']);

        return $route === null ? null : $route['distance_km'];
    }
}
