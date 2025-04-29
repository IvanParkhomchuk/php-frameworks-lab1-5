<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;

class AuthorController extends Controller
{
    /**
     * Display a listing of the authors with filtering and pagination.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
      
        $query = Author::query();
        
     
        if ($request->has('id')) {
            $query->where('id', $request->id);
        }
        
        if ($request->has('first_name')) {
            $query->where('first_name', 'like', "%{$request->first_name}%");
        }
        
        if ($request->has('last_name')) {
            $query->where('last_name', 'like', "%{$request->last_name}%");
        }
        
        if ($request->has('biography')) {
            $query->where('biography', 'like', "%{$request->biography}%");
        }
        
        if ($request->has('birth_date')) {
            $query->whereDate('birth_date', $request->birth_date);
        }
        

        if ($request->has('birth_date_from')) {
            $query->whereDate('birth_date', '>=', $request->birth_date_from);
        }
        
        if ($request->has('birth_date_to')) {
            $query->whereDate('birth_date', '<=', $request->birth_date_to);
        }

        $sortField = $request->input('sort_by', 'id');
        $sortDirection = $request->input('sort_direction', 'asc');
        $allowedSortFields = ['id', 'first_name', 'last_name', 'birth_date', 'created_at', 'updated_at'];
        
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection === 'desc' ? 'desc' : 'asc');
        }
        

        $perPage = (int)$request->input('per_page', 15);
    
        $perPage = max(1, min($perPage, 100));
        
      
        $authors = $query->paginate($perPage);
        
        $authors->appends($request->except('page'));
        
        return response()->json($authors);
    }

    /**
     * Display the specified author.
     *
     * @param  Author  $author
     * @return JsonResponse
     */
    public function show(Author $author): JsonResponse
    {
        return response()->json($author);
    }

    /**
     * Create a new author.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'biography' => 'nullable|string',
            'birth_date' => 'nullable|date'
        ]);

        $author = Author::create($validated);

        return response()->json($author, 201);
    }

    /**
     * Update the specified author.
     *
     * @param  Request  $request
     * @param  Author  $author
     * @return JsonResponse
     */
    public function update(Request $request, Author $author): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:50',
            'last_name' => 'sometimes|string|max:50',
            'biography' => 'nullable|string',
            'birth_date' => 'nullable|date'
        ]);

        // Handle date format validation
        if ($request->has('birth_date')) {
            if ($request->birth_date === null || $request->birth_date === '') {
                $author->birth_date = null;
            } else {
                $datePattern = '/^\d{4}-\d{2}-\d{2}$/';
                if (!preg_match($datePattern, $request->birth_date)) {
                    return response()->json([
                        'error' => 'Invalid date format. Please use YYYY-MM-DD format.'
                    ], 400);
                }
            }
        }

        $author->update($validated);

        return response()->json($author);
    }

    /**
     * Remove the specified author.
     *
     * @param  Author  $author
     * @return JsonResponse
     */
    public function delete(Author $author): JsonResponse
    {
        $author->delete();

        return response()->json(['message' => 'Author deleted successfully']);
    }

    /**
     * Get books by the specified author with filtering and pagination.
     *
     * @param  Author  $author
     * @param  Request  $request
     * @return JsonResponse
     */
    public function books(Author $author, Request $request): JsonResponse
    {
        $query = $author->books();
        
        // Apply filters
        if ($request->has('title')) {
            $query->where('title', 'like', "%{$request->title}%");
        }
        
        if ($request->has('isbn')) {
            $query->where('isbn', 'like', "%{$request->isbn}%");
        }
        
        if ($request->has('publication_year')) {
            $query->where('publication_year', $request->publication_year);
        }
        
        if ($request->has('available_copies')) {
            $query->where('available_copies', $request->available_copies);
        }
        
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        
        // Apply sorting
        $sortField = $request->input('sort_by', 'id');
        $sortDirection = $request->input('sort_direction', 'asc');
        $allowedSortFields = ['id', 'title', 'isbn', 'publication_year', 'available_copies', 'created_at'];
        
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection === 'desc' ? 'desc' : 'asc');
        }
        
        // Determine items per page
        $perPage = (int)$request->input('per_page', 15);
        $perPage = max(1, min($perPage, 100));
        
        $books = $query->paginate($perPage);
        $books->appends($request->except('page'));
        
        return response()->json($books);
    }
}