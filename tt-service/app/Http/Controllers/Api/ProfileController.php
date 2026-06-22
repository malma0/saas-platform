<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * GET /api/profile
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return ApiResponse::success([
            'id'         => $user->public_id,
            'name'       => $user->name,
            'email'      => $user->email,
            'club_id'    => $user->club_id,
            'roles'      => $user->getRoleNames(),
            'created_at' => $user->created_at?->toIso8601String(),
        ]);
    }

    /**
     * PUT /api/profile
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return ApiResponse::success([
            'id'    => $user->public_id,
            'name'  => $user->name,
            'email' => $user->email,
        ], 'Профиль обновлён.');
    }
}
