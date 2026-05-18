<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Http\Resources\StockMovementResource;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService) {}

    /**
     * @OA\Get(
     *     path="/api/dashboard",
     *     summary="Get dashboard stats, low-stock products, and last movements",
     *     tags={"Dashboard"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Dashboard data")
     * )
     */
    public function index(): JsonResponse
    {
        return $this->success([
            'stats'          => $this->dashboardService->stats(),
            'low_stock'      => ProductResource::collection($this->dashboardService->lowStockProducts()),
            'last_movements' => StockMovementResource::collection($this->dashboardService->lastMovements()),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/health",
     *     summary="Health check",
     *     tags={"System"},
     *     @OA\Response(response=200, description="Service is healthy"),
     *     @OA\Response(response=500, description="Service unavailable")
     * )
     */
    public function health(): JsonResponse
    {
        try {
            DB::select('SELECT 1');

            return $this->success(['database' => 'connected'], 'Service healthy.');
        } catch (\Throwable $e) {
            return $this->error('Service unavailable.', 500);
        }
    }
}
