<?php

use App\Http\Controllers\TestController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', [TestController::class, 'test']);
// Route::get('/products', [ProductController::class, 'getProducts']);
// Route::get('/products/{id}', [ProductController::class, 'getProductItem']);
// Route::post('/products', [ProductController::class, 'createProduct']) -> withoutMiddleware([VerifyCsrfToken::class]);
// Route::put('/products/{id}', [ProductController::class, 'updateProduct'])-> withoutMiddleware([VerifyCsrfToken::class]);
// Route::delete('/products/{id}', [ProductController::class, 'deleteProduct'])-> withoutMiddleware([VerifyCsrfToken::class]);