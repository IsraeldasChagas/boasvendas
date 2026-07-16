<?php

namespace App\Support\Api;

use App\Models\Empresa;
use App\Models\EmpresaApiToken;
use Illuminate\Http\Request;

class ApiTenant
{
    public const ATTR_EMPRESA = 'api.empresa';

    public const ATTR_TOKEN = 'api.token';

    public const ATTR_IDEMPOTENCY = 'api.idempotency_key';

    public const ATTR_STARTED_AT = 'api.started_at';

    public static function set(Request $request, Empresa $empresa, EmpresaApiToken $token): void
    {
        $request->attributes->set(self::ATTR_EMPRESA, $empresa);
        $request->attributes->set(self::ATTR_TOKEN, $token);
    }

    public static function empresa(Request $request): ?Empresa
    {
        $empresa = $request->attributes->get(self::ATTR_EMPRESA);

        return $empresa instanceof Empresa ? $empresa : null;
    }

    public static function token(Request $request): ?EmpresaApiToken
    {
        $token = $request->attributes->get(self::ATTR_TOKEN);

        return $token instanceof EmpresaApiToken ? $token : null;
    }

    public static function requireEmpresa(Request $request): Empresa
    {
        $empresa = self::empresa($request);
        if ($empresa === null) {
            abort(401, 'Empresa da API não identificada.');
        }

        return $empresa;
    }

    public static function requireToken(Request $request): EmpresaApiToken
    {
        $token = self::token($request);
        if ($token === null) {
            abort(401, 'Token da API não identificado.');
        }

        return $token;
    }
}
