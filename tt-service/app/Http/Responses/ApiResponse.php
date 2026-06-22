<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

/**
 * Единый формат ответа Client API (Фаза 13).
 *
 * Все ответы оборачиваются в конверт:
 *   { "success": bool, "data": mixed|null, "message": string|null }
 *
 * Ошибки валидации дополнительно содержат "errors".
 */
final class ApiResponse
{
    /**
     * Успешный ответ.
     */
    public static function success(mixed $data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $data,
            'message' => $message,
        ], $status);
    }

    /**
     * Ответ с ошибкой.
     *
     * @param array<string, array<string>>|null $errors
     */
    public static function error(string $message, int $status = 400, ?array $errors = null): JsonResponse
    {
        $payload = [
            'success' => false,
            'data'    => null,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
