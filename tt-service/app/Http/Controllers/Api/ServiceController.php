<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Services\Models\ServiceOffering;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * GET /api/services
     * Список активных услуг клуба.
     */
    public function index(Request $request): JsonResponse
    {
        $branchId = $request->integer('branch_id') ?: null;

        $query = ServiceOffering::query()
            ->where('is_active', true)
            ->orderBy('name');

        if ($branchId) {
            // Фильтр по филиалу через requirements
            $query->whereHas('resourceRequirements.resource', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        $services = $query->get()->map(fn($s) => [
            'id'               => $s->public_id,
            'name'             => $s->name,
            'description'      => $s->description,
            'duration_minutes' => $s->duration_minutes,
            'capacity'         => $s->capacity,
            'price_minor'      => null, // цена через PricingRule — отдельный эндпоинт
        ]);

        return ApiResponse::success($services);
    }
}
