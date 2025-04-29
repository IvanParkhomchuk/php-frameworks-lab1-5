<?php

namespace App\Http\Controllers;

use App\Models\Reader;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class ReaderController extends Controller
{
    /**
     * Display a listing of the readers with filtering and pagination.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Reader::query();
        
         
        if ($request->has('id')) {
            $query->where('id', $request->id);
        }
        
         
        if ($request->has('first_name')) {
            $query->where('first_name', 'like', "%{$request->first_name}%");
        }
        
         
        if ($request->has('last_name')) {
            $query->where('last_name', 'like', "%{$request->last_name}%");
        }
        
         
        if ($request->has('email')) {
            $query->where('email', 'like', "%{$request->email}%");
        }
        
         
        if ($request->has('phone')) {
            $query->where('phone', 'like', "%{$request->phone}%");
        }
        
         
        if ($request->has('address')) {
            $query->where('address', 'like', "%{$request->address}%");
        }
        
         
        if ($request->has('registration_date')) {
            $query->whereDate('registration_date', $request->registration_date);
        }
        
         
        if ($request->has('registered_from')) {
            $query->whereDate('registration_date', '>=', $request->registered_from);
        }
        
        if ($request->has('registered_to')) {
            $query->whereDate('registration_date', '<=', $request->registered_to);
        }
        
         
        $sortField = $request->input('sort_by', 'id');
        $sortDirection = $request->input('sort_direction', 'asc');
        $allowedSortFields = [
            'id', 'first_name', 'last_name', 'email', 
            'registration_date', 'created_at', 'updated_at'
        ];
        
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection === 'desc' ? 'desc' : 'asc');
        }
        
         
        $perPage = (int)$request->input('per_page', 15);
         
        $perPage = max(1, min($perPage, 100));
        
         
        $readers = $query->paginate($perPage);
        
         
        $readers->appends($request->except('page'));
        
        return response()->json($readers);
    }

    /**
     * Display the specified reader.
     *
     * @param  Reader  $reader
     * @return JsonResponse
     */
    public function show(Reader $reader): JsonResponse
    {
        return response()->json($reader);
    }

    /**
     * Create a new reader.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|email|unique:readers',
            'phone' => 'nullable|string|max:15',
            'address' => 'nullable|string',
            'registration_date' => 'nullable|date'
        ]);

        if (!isset($validated['registration_date'])) {
            $validated['registration_date'] = Carbon::now();
        }

        $reader = Reader::create($validated);

        return response()->json($reader, 201);
    }

    /**
     * Update the specified reader.
     *
     * @param  Request  $request
     * @param  Reader  $reader
     * @return JsonResponse
     */
    public function update(Request $request, Reader $reader): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:50',
            'last_name' => 'sometimes|string|max:50',
            'email' => 'sometimes|email|unique:readers,email,' . $reader->id,
            'phone' => 'nullable|string|max:15',
            'address' => 'nullable|string',
            'registration_date' => 'nullable|date'
        ]);

        $reader->update($validated);

        return response()->json($reader);
    }

    /**
     * Remove the specified reader.
     *
     * @param  Reader  $reader
     * @return JsonResponse
     */
    public function delete(Reader $reader): JsonResponse
    {
        $reader->delete();

        return response()->json(['message' => 'Reader deleted successfully']);
    }

    /**
     * Get all loans for the specified reader.
     *
     * @param  Reader  $reader
     * @return JsonResponse
     */
    public function loans(Reader $reader): JsonResponse
    {
        $loans = $reader->loans;
        return response()->json($loans);
    }

    /**
     * Get active loans for the specified reader.
     *
     * @param  Reader  $reader
     * @return JsonResponse
     */
    public function activeLoans(Reader $reader): JsonResponse
    {
        $activeLoans = $reader->loans()->where('status', 'borrowed')->get();
        return response()->json($activeLoans);
    }

    /**
     * Get overdue loans for the specified reader.
     *
     * @param  Reader  $reader
     * @return JsonResponse
     */
    public function overdueLoans(Reader $reader): JsonResponse
    {
        $today = Carbon::now();
        $overdueLoans = $reader->loans()
            ->where('status', 'borrowed')
            ->where('due_date', '<', $today)
            ->get();

        return response()->json($overdueLoans);
    }

    /**
     * Search for readers.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $term = $request->query('term');
        
        if (!$term) {
            return response()->json(['error' => 'Search term is required'], 400);
        }
        
        $readers = Reader::where('first_name', 'like', "%{$term}%")
            ->orWhere('last_name', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%")
            ->get();
            
        return response()->json($readers);
    }
}