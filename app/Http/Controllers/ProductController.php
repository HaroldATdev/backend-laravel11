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


class ProductController extends Controller
{
    public function index(Request $request)
    {
        // Legacy issue: no pagination, raw SQL, string concatenation and N+1 category loading.
        $sql = "SELECT * FROM products WHERE 1=1";

        if ($request->get('q')) {
            $sql .= " AND name LIKE '%" . $request->get('q') . "%'";
        }

        if ($request->get('category_id')) {
            $sql .= " AND category_id = " . $request->get('category_id');
        }

        if ($request->get('status') !== null) {
            $sql .= " AND status = " . $request->get('status');
        }

        $sql .= " ORDER BY created_at DESC";

        $products = DB::select($sql);

        foreach ($products as $product) {
            $product->category = DB::table('categories')->where('id', $product->category_id)->first();
            $product->total_movements = DB::table('stock_movements')->where('product_id', $product->id)->count();
        }

        return response()->json($products);
    }

    public function store(Request $request)
    {
        // Legacy issue: validation is incomplete and mixed with persistence logic.
        if (!$request->name) {
            return response()->json(['message' => 'Name is required'], 422);
        }

        $product = new Product();
        $product->name = $request->name;
        $product->description = $request->description;
        $product->price = $request->price;
        $product->stock = $request->stock;
        $product->category_id = $request->category_id;
        $product->status = $request->status ?? 1;
        $product->save();

        Log::info('Product created', ['product_id' => $product->id, 'payload' => $request->all()]);

        return response()->json(['ok' => true, 'product' => $product], 201);
    }

    public function show($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $product->category_name = DB::table('categories')->where('id', $product->category_id)->value('name');
        return response()->json(['data' => $product]);
    }

    public function update(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['msg' => 'No existe'], 404);
        }

        // Legacy issue: mass assignment without specific validation or normalization.
        $product->fill($request->all());
        $product->save();

        Log::info('Product updated', ['product_id' => $product->id, 'payload' => $request->all()]);

        return response()->json(['success' => true, 'data' => $product]);
    }

    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        $product->delete();
        Log::info('Product deleted', ['product_id' => $id]);

        return response()->json(['deleted' => true]);
    }

    public function stockMovements($id)
    {
        // Legacy issue: no pagination and no product validation.
        $movements = StockMovement::where('product_id', $id)->orderBy('id', 'desc')->get();
        return response()->json($movements);
    }

    public function storeStockMovement(Request $request, $id)
    {
        // Legacy issue: no transaction, weak validation and race-condition risk.
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        if ($request->type == 'salida') {
            $product->stock = $product->stock - $request->quantity;
        } else {
            $product->stock = $product->stock + $request->quantity;
        }

        $product->save();

        $movement = StockMovement::create([
            'product_id' => $product->id,
            'type' => $request->type,
            'quantity' => $request->quantity,
            'reason' => $request->reason,
            'user_id' => $request->auth_user_id,
        ]);

        Log::info('Stock movement registered', ['movement_id' => $movement->id]);

        return response()->json([
            'message' => 'Stock updated',
            'product' => $product,
            'movement' => $movement,
        ]);
    }
}
