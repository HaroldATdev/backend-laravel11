<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Requests\Stock\StoreStockMovementRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StockMovementResource;
use App\Models\Product;
use App\Services\ProductService;
use App\Services\StockMovementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly StockMovementService $stockMovementService,
    ) {}

    /**
     * @OA\Get(
     *     path="/api/products",
     *     summary="List products (paginated, filterable)",
     *     tags={"Products"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="name",        in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="category_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status",      in="query", @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="price_min",   in="query", @OA\Schema(type="number")),
     *     @OA\Parameter(name="price_max",   in="query", @OA\Schema(type="number")),
     *     @OA\Parameter(name="stock_min",   in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="stock_max",   in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="sort",        in="query", @OA\Schema(type="string", enum={"name","price","stock","created_at"})),
     *     @OA\Parameter(name="direction",   in="query", @OA\Schema(type="string", enum={"asc","desc"})),
     *     @OA\Parameter(name="per_page",    in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Paginated products")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->productService->paginate(
            $request->only(['name', 'category_id', 'status', 'price_min', 'price_max', 'stock_min', 'stock_max']),
            $request->input('sort'),
            $request->input('direction', 'asc'),
            (int) $request->input('per_page', 15)
        );

        return $this->success(ProductResource::collection($paginator)->response()->getData(true));
    }

    /**
     * @OA\Post(
     *     path="/api/products",
     *     summary="Create a product",
     *     tags={"Products"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(required={"name","price","stock"},
     *             @OA\Property(property="name",        type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="price",       type="number"),
     *             @OA\Property(property="stock",       type="integer"),
     *             @OA\Property(property="category_id", type="integer"),
     *             @OA\Property(property="status",      type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->create($request->validated(), $request);

        return $this->success(new ProductResource($product), 'Product created.', 201);
    }

    /**
     * @OA\Get(
     *     path="/api/products/{id}",
     *     summary="Get a product",
     *     tags={"Products"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Product"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(Product $product): JsonResponse
    {
        return $this->success(new ProductResource($product->load('category')));
    }

    /**
     * @OA\Put(
     *     path="/api/products/{id}",
     *     summary="Update a product",
     *     tags={"Products"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Updated"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $updated = $this->productService->update($product, $request->validated(), $request);

        return $this->success(new ProductResource($updated), 'Product updated.');
    }

    /**
     * @OA\Delete(
     *     path="/api/products/{id}",
     *     summary="Delete a product",
     *     tags={"Products"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Deleted"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(Product $product, Request $request): JsonResponse
    {
        $this->productService->delete($product, $request);

        return $this->success(null, 'Product deleted.');
    }

    /**
     * @OA\Get(
     *     path="/api/products/{id}/stock-movements",
     *     summary="List stock movements for a product",
     *     tags={"Stock"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id",       in="path",  required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Paginated movements"),
     *     @OA\Response(response=404, description="Product not found")
     * )
     */
    public function stockMovements(Product $product, Request $request): JsonResponse
    {
        $paginator = $this->stockMovementService->paginate(
            $product,
            (int) $request->input('per_page', 15)
        );

        return $this->success(StockMovementResource::collection($paginator)->response()->getData(true));
    }

    /**
     * @OA\Post(
     *     path="/api/products/{id}/stock-movements",
     *     summary="Register a stock movement",
     *     tags={"Stock"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(required={"type","quantity"},
     *             @OA\Property(property="type",     type="string", enum={"entrada","salida"}),
     *             @OA\Property(property="quantity", type="integer", minimum=1),
     *             @OA\Property(property="reason",   type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Movement created"),
     *     @OA\Response(response=422, description="Insufficient stock or validation error")
     * )
     */
    public function storeStockMovement(StoreStockMovementRequest $request, Product $product): JsonResponse
    {
        $movement = $this->stockMovementService->create($product, $request->validated(), $request);

        return $this->success(new StockMovementResource($movement), 'Stock movement registered.', 201);
    }
}
