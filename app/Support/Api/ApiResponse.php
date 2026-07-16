<?php

namespace App\Support\Api;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $meta
     */
    public static function success(array $data = [], int $status = 200, array $meta = []): JsonResponse
    {
        $payload = array_merge([
            'success' => true,
        ], $data);

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status)
            ->header('X-Api-Version', (string) config('api.version', '1.0'));
    }

    /**
     * @param  array<string, mixed>|null  $errors
     * @param  array<string, mixed>  $meta
     */
    public static function error(
        string $message,
        int $status = 400,
        ?string $code = null,
        ?array $errors = null,
        array $meta = [],
    ): JsonResponse {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($code !== null) {
            $payload['code'] = $code;
        }

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status)
            ->header('X-Api-Version', (string) config('api.version', '1.0'));
    }
}
