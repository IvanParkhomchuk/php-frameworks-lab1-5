<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    /**
     * Display a listing of products with filtering and pagination.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::query();
        
         
        if ($request->has('id')) {
            $query->where('id', $request->id);
        }
        
         
        if ($request->has('name')) {
            $query->where('name', 'like', "%{$request->name}%");
        }
        
         
        if ($request->has('description')) {
            $query->where('description', 'like', "%{$request->description}%");
        }
        
         
        if ($request->has('price')) {
            $query->where('price', $request->price);
        }
        
         
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }
        
         
        if ($request->has('created_from')) {
            $query->whereDate('created_at', '>=', $request->created_from);
        }
        
        if ($request->has('created_to')) {
            $query->whereDate('created_at', '<=', $request->created_to);
        }
        
         
        $sortField = $request->input('sort_by', 'id');
        $sortDirection = $request->input('sort_direction', 'asc');
        $allowedSortFields = ['id', 'name', 'price', 'created_at', 'updated_at'];
        
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection === 'desc' ? 'desc' : 'asc');
        }
        
         
        $perPage = (int)$request->input('per_page', 15);
         
        $perPage = max(1, min($perPage, 100));
        
         
        $products = $query->paginate($perPage);
        
         
        $products->appends($request->except('page'));
        
        return response()->json($products, Response::HTTP_OK);
    }

    /**
     * Store a newly created product.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
        ]);

        $product = Product::create($request->all());

        return response()->json(['data' => $product], Response::HTTP_CREATED);
    }

    /**
     * Display the specified product.
     *
     * @param  string  $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $product], Response::HTTP_OK);
    }

    /**
     * Update the specified product.
     *
     * @param  Request  $request
     * @param  string  $id
     * @return JsonResponse
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $product->update($request->all());

        return response()->json(['data' => $product], Response::HTTP_OK);
    }

    /**
     * Remove the specified product.
     *
     * @param  string  $id
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully'], Response::HTTP_OK);
    }
}
