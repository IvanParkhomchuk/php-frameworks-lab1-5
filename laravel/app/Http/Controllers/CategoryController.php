<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * Display a listing of the categories with filtering and pagination.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Category::query();
        
         
        if ($request->has('id')) {
            $query->where('id', $request->id);
        }
        
         
        if ($request->has('name')) {
            $query->where('name', 'like', "%{$request->name}%");
        }
        
         
        if ($request->has('description')) {
            $query->where('description', 'like', "%{$request->description}%");
        }
        
         
        if ($request->has('created_from')) {
            $query->whereDate('created_at', '>=', $request->created_from);
        }
        
        if ($request->has('created_to')) {
            $query->whereDate('created_at', '<=', $request->created_to);
        }
        
         
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }
        
         
        if ($request->has('with_book_count') && $request->with_book_count) {
            $query->withCount('books');
        }
        
         
        $sortField = $request->input('sort_by', 'id');
        $sortDirection = $request->input('sort_direction', 'asc');
        $allowedSortFields = ['id', 'name', 'created_at', 'updated_at', 'books_count'];
        
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection === 'desc' ? 'desc' : 'asc');
        }
        
         
        $perPage = (int)$request->input('per_page', 15);
         
        $perPage = max(1, min($perPage, 100));
        
         
        $categories = $query->paginate($perPage);
        
         
        $categories->appends($request->except('page'));
        
        return response()->json([
            'data' => $categories->items(),
            'pagination' => [
                'total' => $categories->total(),
                'per_page' => $categories->perPage(),
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'from' => $categories->firstItem(),
                'to' => $categories->lastItem(),
            ],
            'links' => [
                'first' => $categories->url(1),
                'last' => $categories->url($categories->lastPage()),
                'prev' => $categories->previousPageUrl(),
                'next' => $categories->nextPageUrl(),
            ]
        ]);
    }

    /**
     * Display the specified category.
     *
     * @param  Category  $category
     * @param  Request  $request
     * @return JsonResponse
     */
    public function show(Category $category, Request $request): JsonResponse
    {
         
        if ($request->has('with_book_count') && $request->with_book_count) {
            $category->loadCount('books');
        }
        
        return response()->json($category);
    }

    /**
     * Create a new category.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'description' => 'nullable|string'
        ]);

        $category = Category::create($validated);

         
        if ($request->has('with_book_count') && $request->with_book_count) {
            $category->loadCount('books');
        }

        return response()->json($category, 201);
    }

    /**
     * Update the specified category.
     *
     * @param  Request  $request
     * @param  Category  $category
     * @return JsonResponse
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:50',
            'description' => 'nullable|string'
        ]);

        $category->update($validated);

         
        if ($request->has('with_book_count') && $request->with_book_count) {
            $category->loadCount('books');
        }

        return response()->json($category);
    }

    /**
     * Remove the specified category.
     *
     * @param  Category  $category
     * @return JsonResponse
     */
    public function delete(Category $category): JsonResponse
    {
        $category->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }

    /**
     * Get books in the specified category with filtering and pagination.
     *
     * @param  Category  $category
     * @param  Request  $request
     * @return JsonResponse
     */
    public function books(Category $category, Request $request): JsonResponse
    {
        $query = $category->books();
        
         
        if ($request->has('title')) {
            $query->where('title', 'like', "%{$request->title}%");
        }
        
         
        if ($request->has('isbn')) {
            $query->where('isbn', 'like', "%{$request->isbn}%");
        }
        
         
        if ($request->has('publication_year')) {
            $query->where('publication_year', $request->publication_year);
        }
        
         
        if ($request->has('publication_year_from')) {
            $query->where('publication_year', '>=', $request->publication_year_from);
        }
        
        if ($request->has('publication_year_to')) {
            $query->where('publication_year', '<=', $request->publication_year_to);
        }
        
         
        if ($request->has('available_copies')) {
            $query->where('available_copies', $request->available_copies);
        }
        
         
        if ($request->has('min_copies')) {
            $query->where('available_copies', '>=', $request->min_copies);
        }
        
         
        if ($request->has('author_id')) {
            $query->where('author_id', $request->author_id);
        }
        
         
        if ($request->has('with')) {
            $relations = explode(',', $request->with);
            $allowedRelations = ['author', 'loans'];
            $validRelations = array_intersect($allowedRelations, $relations);
            if (!empty($validRelations)) {
                $query->with($validRelations);
            }
        }
        
         
        $sortField = $request->input('sort_by', 'id');
        $sortDirection = $request->input('sort_direction', 'asc');
        $allowedSortFields = [
            'id', 'title', 'isbn', 'publication_year', 
            'available_copies', 'created_at', 'updated_at'
        ];
        
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection === 'desc' ? 'desc' : 'asc');
        }
        
         
        $perPage = (int)$request->input('per_page', 15);
        $perPage = max(1, min($perPage, 100));
        
         
        $books = $query->paginate($perPage);
        
         
        $books->appends($request->except('page'));
        
        return response()->json([
            'data' => $books->items(),
            'pagination' => [
                'total' => $books->total(),
                'per_page' => $books->perPage(),
                'current_page' => $books->currentPage(),
                'last_page' => $books->lastPage(),
                'from' => $books->firstItem(),
                'to' => $books->lastItem(),
            ],
            'links' => [
                'first' => $books->url(1),
                'last' => $books->url($books->lastPage()),
                'prev' => $books->previousPageUrl(),
                'next' => $books->nextPageUrl(),
            ]
        ]);
    }
}