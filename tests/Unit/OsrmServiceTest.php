<?php

namespace Tests\Unit;

use App\Services\OsrmService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OsrmServiceTest extends TestCase
{
    public function test_route_driving_por_coordenadas(): void
    {
        Config::set('services.osm_routing.osrm_base_url', 'https://router.project-osrm.org');
        Config::set('services.osm_routing.http_user_agent', 'VendAffacilTest/1.0');

        Http::fake([
            '*router.project-osrm.org*' => Http::response([
                'code' => 'Ok',
                'routes' => [
                    [
                        'distance' => 5400,
                        'duration' => 840,
                    ],
                ],
            ], 200),
        ]);

        $osrm = new OsrmService;
        $r = $osrm->routeDriving(-8.76, -63.90, -8.75, -63.91);

        $this->assertNotNull($r);
        $this->assertSame(5.4, $r['distance_km']);
        $this->assertSame(840.0, $r['duration_seconds']);
    }
}
