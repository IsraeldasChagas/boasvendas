<?php

namespace Tests\Unit;

use App\Support\OsrmRouting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OsrmRoutingTest extends TestCase
{
    public function test_distancia_km_rodo_osrm_apos_geocode(): void
    {
        Config::set('services.osm_routing.osrm_base_url', 'https://router.project-osrm.org');
        Config::set('services.osm_routing.nominatim_base_url', 'https://nominatim.openstreetmap.org');
        Config::set('services.osm_routing.http_user_agent', 'VendAffacilTest/1.0');

        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            if (str_contains($request->url(), 'nominatim')) {
                return Http::response([
                    ['lat' => '-23.5', 'lon' => '-46.6'],
                ], 200);
            }
            if (str_contains($request->url(), 'router.project-osrm.org')) {
                return Http::response([
                    'code' => 'Ok',
                    'routes' => [
                        ['distance' => 7500],
                    ],
                ], 200);
            }

            return Http::response([], 404);
        });

        $km = OsrmRouting::distanciaKmRodoviaria('Origem teste SP', 'Destino teste SP');

        $this->assertSame(7.5, $km);
    }
}
