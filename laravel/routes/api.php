<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ReaderController;
use App\Http\Controllers\LoanController;


// JWT
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');

Route::middleware('auth:api')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    //Client
    Route::middleware('role:client,manager,admin')->group(function () {
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/{product}', [ProductController::class, 'show']);

        Route::get('/authors', [AuthorController::class, 'index']);
        Route::get('/authors/{author}', [AuthorController::class, 'show']);
        Route::get('/authors/{author}/books', [AuthorController::class, 'books']);

        Route::get('/books', [BookController::class, 'index']);
        Route::get('/books/{book}', [BookController::class, 'show']);
        Route::get('/books/{book}/loans', [BookController::class, 'loans']);

        Route::get('/categories', [CategoryController::class, 'index']);
        Route::get('/categories/{category}', [CategoryController::class, 'show']);
        Route::get('/categories/{category}/books', [CategoryController::class, 'books']);

        Route::get('/readers', [ReaderController::class, 'index']);
        Route::get('/readers/{reader}', [ReaderController::class, 'show']);
        Route::get('/readers/{reader}/loans', [ReaderController::class, 'loans']);
        Route::get('/readers/{reader}/active-loans', [ReaderController::class, 'activeLoans']);
        Route::get('/readers/{reader}/overdue-loans', [ReaderController::class, 'overdueLoans']);
        Route::get('/readers/search', [ReaderController::class, 'search']);

        Route::get('/loans', [LoanController::class, 'index']);
        Route::get('/loans/{loan}', [LoanController::class, 'show']);
        Route::get('/loans/reader/{id}', [LoanController::class, 'loansByReader']);
        Route::get('/loans/book/{id}', [LoanController::class, 'loansByBook']);
        Route::get('/loans/overdue', [LoanController::class, 'overdueLoans']);
    });

    // Manager
    Route::middleware('role:manager,admin')->group(function () {
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);

        Route::post('/authors/create', [AuthorController::class, 'create']);
        Route::put('/authors/{author}/update', [AuthorController::class, 'update']);
        Route::delete('/authors/{author}/delete', [AuthorController::class, 'delete']);

        Route::post('/books/create', [BookController::class, 'create']);
        Route::put('/books/{book}/update', [BookController::class, 'update']);
        Route::delete('/books/{book}/delete', [BookController::class, 'delete']);

        Route::post('/categories/create', [CategoryController::class, 'create']);
        Route::put('/categories/{category}/update', [CategoryController::class, 'update']);
        Route::delete('/categories/{category}/delete', [CategoryController::class, 'delete']);

        Route::post('/readers/create', [ReaderController::class, 'create']);
        Route::put('/readers/{reader}/update', [ReaderController::class, 'update']);
        Route::delete('/readers/{reader}/delete', [ReaderController::class, 'delete']);

        Route::post('/loans/create', [LoanController::class, 'create']);
        Route::put('/loans/{loan}/update', [LoanController::class, 'update']);
        Route::delete('/loans/{loan}/delete', [LoanController::class, 'delete']);
    });

    // Admin
    Route::middleware('role:admin')->group(function () {

    });
});
