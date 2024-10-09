<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\TransactionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/transactions', [TransactionController::class, 'getMonthlyTransactions']);
Route::get('/transactions/customer/{customerId}', [TransactionController::class, 'getMonthlyTransactionsByCustomer']);
Route::get('/transactions/sales/{salesId}', [TransactionController::class, 'getMonthlyTransactionsBySales']);
Route::get('/monthly-target-transactions', [TransactionController::class, 'getMonthlyTargetAndTransactions']);
Route::get('/transactions/monthly-targets', [TransactionController::class, 'monthlyTargetsAndTransactions']);
Route::get('/transactions/month-targets/', [TransactionController::class, 'getAllMonthlyTargetsAndTransactions']);
Route::post('/customers', [CustomerController::class, 'store']);
Route::put('/customers/{id}', [CustomerController::class, 'update']);
Route::post('/sales-orders', [TransactionController::class, 'createSalesOrder']);
Route::get('/sales-orders/{id}', [TransactionController::class, 'getById']);
