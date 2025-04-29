<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Author;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BookController extends Controller
{
    /**
     * Display a listing of books with filtering and pagination.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Book::query();
        
        
        if ($request->has('id')) {
            $query->where('id', $request->id);
        }
        
      
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
        
         
        if ($request->has('max_copies')) {
            $query->where('available_copies', '<=', $request->max_copies);
        }
        
         
        if ($request->has('author_id')) {
            $query->where('author_id', $request->author_id);
        }
        
         
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        
         
        if ($request->has('created_from')) {
            $query->whereDate('created_at', '>=', $request->created_from);
        }
        
        if ($request->has('created_to')) {
            $query->whereDate('created_at', '<=', $request->created_to);
        }
        
         
        if ($request->has('with')) {
            $relations = explode(',', $request->with);
            $allowedRelations = ['author', 'category', 'loans'];
            $validRelations = array_intersect($allowedRelations, $relations);
            if (!empty($validRelations)) {
                $query->with($validRelations);
            }
        }
        
         
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                  ->orWhere('isbn', 'like', "%{$searchTerm}%");
            });
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
        ]);
    }

    /**
     * Display the specified book.
     *
     * @param  Book  $book
     * @return JsonResponse
     */
    public function show(Book $book): JsonResponse
    {
        return response()->json($book);
    }

    /**
     * Create a new book.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'isbn' => 'required|string|max:13|unique:books',
            'publication_year' => 'required|integer',
            'available_copies' => 'required|integer|min:0',
            'author_id' => 'required|exists:authors,id',
            'category_id' => 'required|exists:categories,id',
        ]);

        $author = Author::find($request->author_id);
        $category = Category::find($request->category_id);

        if (!$author || !$category) {
            return response()->json(['error' => 'Invalid author or category ID'], 400);
        }

        $book = Book::create($validated);

        return response()->json($book, 201);
    }

    /**
     * Update the specified book.
     *
     * @param  Request  $request
     * @param  Book  $book
     * @return JsonResponse
     */
    public function update(Request $request, Book $book): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'isbn' => 'sometimes|string|max:13|unique:books,isbn,' . $book->id,
            'publication_year' => 'sometimes|integer',
            'available_copies' => 'sometimes|integer|min:0',
            'author_id' => 'sometimes|exists:authors,id',
            'category_id' => 'sometimes|exists:categories,id',
        ]);

        if ($request->has('author_id')) {
            $author = Author::find($request->author_id);
            if (!$author) {
                return response()->json(['error' => 'Invalid author ID'], 400);
            }
        }

        if ($request->has('category_id')) {
            $category = Category::find($request->category_id);
            if (!$category) {
                return response()->json(['error' => 'Invalid category ID'], 400);
            }
        }

        $book->update($validated);

        return response()->json($book);
    }

    /**
     * Remove the specified book.
     *
     * @param  Book  $book
     * @return JsonResponse
     */
    public function delete(Book $book): JsonResponse
    {
        $book->delete();

        return response()->json(['message' => 'Book deleted successfully']);
    }

    /**
     * Get loans for the specified book.
     *
     * @param  Book  $book
     * @return JsonResponse
     */
    public function loans(Book $book): JsonResponse
    {
        $loans = $book->loans;
        return response()->json($loans);
    }
}