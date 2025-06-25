<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OfferDiscountController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\SupAdminAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\StoreAuthController;
use App\Http\Controllers\CustomerAuthController;
//use App\Http\Controllers\AdminController;
//use App\Http\Controllers\StoreController;
//use App\Http\Controllers\CustomerController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();

});


Route::prefix('admin')->group(function () {
    Route::post('/register', [AdminAuthController::class, 'register']);
    Route::post('/login', [AdminAuthController::class, 'login']);
//    Route::middleware(['auth:sanctum', 'check.role:admin'])->get('/dashboard', [AdminController::class, 'dashboard']);
    Route::middleware('auth:sanctum')->post('/logout', [AdminAuthController::class, 'logout']);
});
Route::prefix('sup_admin')->group(function () {
    Route::post('/register', [SupAdminAuthController::class, 'register']);
    Route::post('/login', [SupAdminAuthController::class, 'login']);
//    Route::middleware(['auth:sanctum', 'check.role:customer'])->get('/profile', [CustomerController::class, 'profile']);
    Route::middleware('auth:sanctum')->post('/logout', [SupAdminAuthController::class, 'logout']);
});

Route::prefix('store')->group(function () {
    Route::post('/register', [StoreAuthController::class, 'register']);
    Route::post('/login', [StoreAuthController::class, 'login']);
//    Route::middleware(['auth:sanctum', 'check.role:store'])->get('/dashboard', [StoreController::class, 'dashboard']);
    Route::middleware('auth:sanctum')->post('/logout', [StoreAuthController::class, 'logout']);
});

Route::prefix('customer')->group(function () {
    Route::post('/register', [CustomerAuthController::class, 'register']);
    Route::post('/login', [CustomerAuthController::class, 'login']);
//    Route::middleware(['auth:sanctum', 'check.role:customer'])->get('/profile', [CustomerController::class, 'profile']);
    Route::middleware('auth:sanctum')->post('/logout', [CustomerAuthController::class, 'logout']);
    Route::post('send-reset-password-code', [CustomerAuthController::class, 'sendResetPasswordCode']);
    Route::post('verify-reset-password-code', [CustomerAuthController::class, 'verifyResetPasswordCode']);
    Route::post('reset-password', [CustomerAuthController::class, 'resetPassword'])->middleware('verify.temp.token');;

});

Route::prefix('customer')->group(function () {

    Route::get('/areas', [AreaController::class, 'index']);
    Route::post('/set-area', [AreaController::class, 'setArea']);

});
Route::prefix('customer')->middleware(['ensure.visitor', 'detect.area'])->group(function () {


    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('/stores', [StoreController::class, 'index']);
    Route::get('/stores/{id}', [StoreController::class, 'show']);
    Route::get('products', [ProductController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::get('available-offers-discounts', [OfferDiscountController::class, 'available']);
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/add', [CartController::class, 'add']);
    Route::patch('/cart/update/{product_id}', [CartController::class, 'update']);
    Route::delete('/cart/remove/{product_id}', [CartController::class, 'remove']);



    // تأكيد الرمز
    Route::post('verify', [VerificationController::class, 'verifyCode']);
    Route::post('resend-code', [VerificationController::class, 'resendCode']);

    // تسجيل الخروج
//    Route::middleware('auth:sanctum')->post('logout', [AuthController::class, 'logout']);

    # ✅ مسارات خاصة بالمستخدم المسجل (محمية)
    Route::middleware('auth:sanctum')->prefix('user')->group(function () {

        Route::post('orders', [OrderController::class, 'store']);
        Route::get('orders', [OrderController::class, 'index']);
        Route::get('orders/{id}', [OrderController::class, 'show']);

        // إدارة العناوين
        Route::get('addresses', [AddressController::class, 'index']);
        Route::post('addresses', [AddressController::class, 'store']);
        Route::put('addresses/{id}', [AddressController::class, 'update']);
        Route::delete('addresses/{id}', [AddressController::class, 'destroy']);


        // السلة
        Route::get('/cart', [CartController::class, 'index']);
        Route::post('/cart/add', [CartController::class, 'add']);
        Route::post('/cart/update', [CartController::class, 'update']);
        Route::delete('/cart/remove', [CartController::class, 'remove']);

        // الحساب الشخصي
        Route::get('profile', [ProfileController::class, 'show']);
        Route::put('profile', [ProfileController::class, 'update']);

        // المحفظة
        Route::get('wallet', [WalletController::class, 'show']);

        // الإشعارات
        Route::get('notifications', [NotificationController::class, 'index']);

        // الشكاوى
        Route::post('complaints', [ComplaintController::class, 'store']);
    });
});
