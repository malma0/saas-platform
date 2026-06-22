<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Crm\Models\Client;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * POST /api/auth/register
     * Регистрация нового клиента клуба.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'club_id'  => $request->club_id,
            ]);

            $user->assignRole('client');

            // CRM-карточка клиента (ТЗ Блок 8): брони из приложения
            // попадают в историю клиента через client_id.
            [$firstName, $lastName] = array_pad(explode(' ', trim($request->name), 2), 2, null);

            Client::create([
                'club_id'    => $request->club_id,
                'user_id'    => $user->id,
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'phone'      => $request->phone,
                'email'      => $request->email,
                'source'     => 'app',
            ]);

            return $user;
        });

        $token = $user->createToken(
            $request->input('device_name', 'mobile')
        )->plainTextToken;

        return ApiResponse::success([
            'user'  => $this->userResource($user),
            'token' => $token,
        ], 'Регистрация выполнена.', 201);
    }

    /**
     * POST /api/auth/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        // Ищем без глобального scope tenancy (email уникален глобально)
        $user = User::withoutGlobalScopes()->where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Неверный email или пароль.'],
            ]);
        }

        // Удаляем старые токены для устройства (опционально — один токен на устройство)
        $deviceName = $request->input('device_name', 'mobile');
        $user->tokens()->where('name', $deviceName)->delete();

        $token = $user->createToken($deviceName)->plainTextToken;

        return ApiResponse::success([
            'user'  => $this->userResource($user),
            'token' => $token,
        ], 'Вход выполнен.');
    }

    /**
     * POST /api/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(null, 'Выход выполнен.');
    }

    // -------------------------------------------------------------------------

    private function userResource(User $user): array
    {
        return [
            'id'       => $user->public_id,
            'name'     => $user->name,
            'email'    => $user->email,
            'club_id'  => $user->club_id,
            'roles'    => $user->getRoleNames(),
        ];
    }
}
