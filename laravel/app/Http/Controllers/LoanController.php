<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Book;
use App\Models\Reader;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LoanController extends Controller
{
    /**
     * Display a listing of the loans with filtering and pagination.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Loan::query();
        
         
        if ($request->has('id')) {
            $query->where('id', $request->id);
        }
        
         
        if ($request->has('book_id')) {
            $query->where('book_id', $request->book_id);
        }
        
         
        if ($request->has('reader_id')) {
            $query->where('reader_id', $request->reader_id);
        }
        
         
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
         
        if ($request->has('loan_date')) {
            $query->whereDate('loan_date', $request->loan_date);
        }
        
         
        if ($request->has('due_date')) {
            $query->whereDate('due_date', $request->due_date);
        }
        
         
        if ($request->has('return_date')) {
            if ($request->return_date === 'null') {
                $query->whereNull('return_date');
            } else {
                $query->whereDate('return_date', $request->return_date);
            }
        }
        
         
        if ($request->has('not_returned') && $request->not_returned) {
            $query->whereNull('return_date');
        }
        
         
        if ($request->has('loan_date_from')) {
            $query->whereDate('loan_date', '>=', $request->loan_date_from);
        }
        
        if ($request->has('loan_date_to')) {
            $query->whereDate('loan_date', '<=', $request->loan_date_to);
        }
        
        if ($request->has('due_date_from')) {
            $query->whereDate('due_date', '>=', $request->due_date_from);
        }
        
        if ($request->has('due_date_to')) {
            $query->whereDate('due_date', '<=', $request->due_date_to);
        }
        
        if ($request->has('return_date_from')) {
            $query->whereDate('return_date', '>=', $request->return_date_from);
        }
        
        if ($request->has('return_date_to')) {
            $query->whereDate('return_date', '<=', $request->return_date_to);
        }
         
        $sortField = $request->input('sort_by', 'id');
        $sortDirection = $request->input('sort_direction', 'asc');
        $allowedSortFields = [
            'id', 'book_id', 'reader_id', 'loan_date', 
            'due_date', 'return_date', 'status', 
            'created_at', 'updated_at'
        ];
        
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection === 'desc' ? 'desc' : 'asc');
        }
        
         
        $perPage = (int)$request->input('per_page', 15);
         
        $perPage = max(1, min($perPage, 100));
        
         
        $loans = $query->paginate($perPage);
        
        return response()->json($loans);
    }

    /**
     * Display the specified loan.
     *
     * @param  Loan  $loan
     * @return JsonResponse
     */
    public function show(Loan $loan): JsonResponse
    {
        return response()->json($loan);
    }

    /**
     * Create a new loan.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'book_id' => 'required|exists:books,id',
            'reader_id' => 'required|exists:readers,id',
            'loan_date' => 'nullable|date',
            'due_date' => 'required|date|after_or_equal:loan_date',
            'return_date' => 'nullable|date',
            'status' => 'nullable|string'
        ]);

        $book = Book::findOrFail($request->book_id);
        $reader = Reader::findOrFail($request->reader_id);

         
        if ($book->available_copies <= 0) {
            return response()->json(['error' => 'No available copies of this book'], 400);
        }

        if (!isset($validated['loan_date'])) {
            $validated['loan_date'] = Carbon::now();
        }

        if (!isset($validated['status'])) {
            $validated['status'] = 'borrowed';
        }

        DB::beginTransaction();
        try {
            $loan = Loan::create($validated);
            
             
            $book->available_copies -= 1;
            $book->save();
            
            DB::commit();
            return response()->json($loan, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create loan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified loan.
     *
     * @param  Request  $request
     * @param  Loan  $loan
     * @return JsonResponse
     */
    public function update(Request $request, Loan $loan): JsonResponse
    {
        $validated = $request->validate([
            'book_id' => 'sometimes|exists:books,id',
            'reader_id' => 'sometimes|exists:readers,id',
            'loan_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'return_date' => 'nullable|date',
            'status' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
             
            if ($request->has('book_id') && $loan->book_id != $request->book_id) {
                $oldBook = Book::findOrFail($loan->book_id);
                $newBook = Book::findOrFail($request->book_id);
                
                 
                $oldBook->available_copies += 1;
                $oldBook->save();
                
                 
                if ($newBook->available_copies <= 0) {
                    DB::rollBack();
                    return response()->json(['error' => 'No available copies of the new book'], 400);
                }
                $newBook->available_copies -= 1;
                $newBook->save();
            }

             
            if ($request->has('status')) {
                $oldStatus = $loan->status;
                $newStatus = $request->status;
                
                 
                if ($oldStatus === 'borrowed' && $newStatus === 'returned') {
                    $book = Book::findOrFail($loan->book_id);
                    $book->available_copies += 1;
                    $book->save();
                    
                     
                    if (!$loan->return_date) {
                        $loan->return_date = Carbon::now();
                    }
                }
            }

            $loan->update($validated);
            
            DB::commit();
            return response()->json($loan);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update loan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified loan.
     *
     * @param  Loan  $loan
     * @return JsonResponse
     */
    public function delete(Loan $loan): JsonResponse
    {
        DB::beginTransaction();
        try {
             
            if ($loan->status === 'borrowed') {
                $book = Book::findOrFail($loan->book_id);
                $book->available_copies += 1;
                $book->save();
            }

            $loan->delete();
            
            DB::commit();
            return response()->json(['message' => 'Loan deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to delete loan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get loans for the specified reader.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function loansByReader($id): JsonResponse
    {
        $loans = Loan::where('reader_id', $id)->get();
        return response()->json($loans);
    }

    /**
     * Get loans for the specified book.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function loansByBook($id): JsonResponse
    {
        $loans = Loan::where('book_id', $id)->get();
        return response()->json($loans);
    }

    /**
     * Get overdue loans.
     *
     * @return JsonResponse
     */
    public function overdueLoans(): JsonResponse
    {
        $today = Carbon::now();
        $overdueLoans = Loan::where('due_date', '<', $today)
            ->where('status', 'borrowed')
            ->get();

        return response()->json($overdueLoans);
    }
}