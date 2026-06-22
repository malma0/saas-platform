<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Booking\Actions\CreateHold;
use App\Domain\Booking\Exceptions\SlotNotAvailableException;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Hold-механика для клиентского приложения:
 * слот блокируется на время оформления, токен передаётся в POST /bookings.
 */
class HoldController extends Controller
{
    public function __construct(
        private readonly CreateHold $createHold,
    ) {}

    /**
     * POST /api/v1/holds
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id'      => 'required|integer|exists:branches,id',
            'resource_ids'   => 'required|array|min:1',
            'resource_ids.*' => 'integer|exists:resources,id',
            'start_at'       => 'required|date|after:now',
            'end_at'         => 'required|date|after:start_at',
        ]);

        try {
            $hold = $this->createHold->handle(
                clubId:      $request->user()->club_id,
                branchId:    $request->integer('branch_id'),
                resourceIds: $request->input('resource_ids'),
                startAt:     Carbon::parse($request->input('start_at'))->utc(),
                endAt:       Carbon::parse($request->input('end_at'))->utc(),
            );
        } catch (SlotNotAvailableException $e) {
            return ApiResponse::error('Слот недоступен. ' . $e->getMessage(), 422);
        }

        return ApiResponse::success([
            'hold_token' => $hold->session_token,
            'expires_at' => $hold->expires_at->toIso8601String(),
        ], 'Слот временно заблокирован.', 201);
    }
}
