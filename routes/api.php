<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\LinkController;
use App\Http\Controllers\OfferDiscountController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductRequestsController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\SupAdminAuthController;
use App\Http\Controllers\WalletController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdController;


use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\StoreAuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SubAdmin\CustomerController as SubAdminCustomerController;
//use App\Http\Controllers\AdminController;
//use App\Http\Controllers\StoreController;
//use App\Http\Controllers\CustomerController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| هذه هي نقطة تسجيل جميع الراوتات الخاصة بواجهة برمجة التطبيقات
| ويتم تحميلها عبر RouteServiceProvider
|
*/

    //   معلومات المستخدم عند التوثيق
    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return $request->user();
    });

/*
|--------------------------------------------------------------------------
| 🔐 Auth Routes (Admin - SubAdmin - Store - Customer)
|--------------------------------------------------------------------------
*/

    // ✅ Admin Auth
    Route::prefix('admin')->group(function () {
        Route::post('/register', [AdminAuthController::class, 'register']);
        Route::post('/login', [AdminAuthController::class, 'login']);
    //    Route::middleware(['auth:sanctum', 'check.role:admin'])->get('/dashboard', [AdminController::class, 'dashboard']);
        Route::middleware('auth:sanctum')->post('/logout', [AdminAuthController::class, 'logout']);

        // استعادة كلمة المرور
        Route::post('send-reset-password-code', [UserController::class, 'sendResetPasswordCode']);
        Route::post('verify-reset-password-code', [UserController::class, 'verifyResetPasswordCode']);
        Route::post('reset-password', [UserController::class, 'resetPassword'])->middleware('verify.temp.token');;

    });

    // ✅ SubAdmin Auth
    Route::prefix('sub_admin')->group(function () {
        Route::post('/login', [SupAdminAuthController::class, 'login']);
    //    Route::middleware(['auth:sanctum', 'check.role:customer'])->get('/profile', [CustomerController::class, 'profile']);
        Route::middleware('auth:sanctum')->post('/logout', [SupAdminAuthController::class, 'logout']);

        // استعادة كلمة المرور
        Route::post('send-reset-password-code', [UserController::class, 'sendResetPasswordCode']);
        Route::post('verify-reset-password-code', [UserController::class, 'verifyResetPasswordCode']);
        Route::post('reset-password', [UserController::class, 'resetPassword'])->middleware('verify.temp.token');;


    });

    // ✅ Store Auth
    Route::prefix('store')->group(function () {
        Route::post('/register', [StoreAuthController::class, 'register']);
        Route::post('/login', [StoreAuthController::class, 'login']);
    //    Route::middleware(['auth:sanctum', 'check.role:store'])->get('/dashboard', [StoreController::class, 'dashboard']);
        Route::middleware('auth:sanctum')->post('/logout', [StoreAuthController::class, 'logout']);

        // استعادة كلمة المرور
        Route::post('send-reset-password-code', [UserController::class, 'sendResetPasswordCode']);
        Route::post('verify-reset-password-code', [UserController::class, 'verifyResetPasswordCode']);
        Route::post('reset-password', [UserController::class, 'resetPassword'])->middleware('verify.temp.token');;

    });

    // ✅ Customer Auth + Reset Password
    Route::prefix('customer')->group(function () {
        Route::post('/register', [CustomerAuthController::class, 'startRegister']);
        Route::post('/register/verify', [CustomerAuthController::class, 'verifyRegister']);
        Route::post('register/resend-otp', [CustomerAuthController::class, 'resendRegisterOtp']);

        Route::post('/login', [CustomerAuthController::class, 'login']);
    //    Route::middleware(['auth:sanctum', 'check.role:customer'])->get('/profile', [CustomerController::class, 'profile']);
        Route::middleware('auth:sanctum')->post('/logout', [CustomerAuthController::class, 'logout']);

        // استعادة كلمة المرور
        Route::post('send-reset-password-code', [UserController::class, 'sendResetPasswordCode']);
        Route::post('verify-reset-password-code', [UserController::class, 'verifyResetPasswordCode']);
        Route::post('reset-password', [UserController::class, 'resetPassword'])->middleware('verify.temp.token');;

    });


/*
|--------------------------------------------------------------------------
| 🌍 Public (Visitor) Routes - مناطق وتعيينها
|--------------------------------------------------------------------------
*/

    Route::prefix('customer')->group(function () {

        Route::get('/areas', [AreaController::class, 'index']);
        Route::post('/set-area', [AreaController::class, 'setArea']);

    });


/*
|--------------------------------------------------------------------------
| 🛍️ Customer & Visitore(after area selection) Routes
|--------------------------------------------------------------------------
*/

    Route::prefix('customer')->middleware(['ensure.visitor', 'detect.area'])->group(function () {

        //التصنيفات
        Route::get('categories', [CategoryController::class, 'index']);

        //المتاجر
        Route::get('/category-stores/{categoryId}', [StoreController::class, 'index']);
        Route::get('/stores/{id}', [StoreController::class, 'show']);

        //المنتجات
        Route::get('products', [ProductController::class, 'index']);
        Route::get('byId/{id}', [ProductController::class, 'productDetails']);

        //التخفيضات
        Route::get('available-offers-discounts', [OfferDiscountController::class, 'available']);

        //السلة
        Route::get('/cart', [CartController::class, 'index']);
        Route::post('/cart/add', [CartController::class, 'add']);
        Route::patch('/cart/update/{product_id}', [CartController::class, 'update']);
        Route::delete('/cart/remove/{product_id}', [CartController::class, 'remove']);

        //الاعلانات
        Route::post('/ads', [AdController::class, 'store']);
        Route::get('/ads/latest', [AdController::class, 'latestAds']);


        //search
        Route::get('store/search', [StoreController::class, 'search']);
        Route::get('store/categories-stores/search/{categoryId}', [StoreController::class, 'searchByCategory']);
        Route::get('store/products/search/{store}', [ProductController::class, 'searchProductsInStore']);



    });

/*
|--------------------------------------------------------------------------
| 🏪 Customer Dashboard Routes
|--------------------------------------------------------------------------
*/

    Route::prefix('customer-auth')->middleware(['auth:sanctum','attach.user.area'])->group(function () {

        // الحساب الشخصي
        Route::put('/profile/update-profile', [UserController::class, 'updateProfile']);
        Route::post('/profile/change-area', [UserController::class, 'changeArea']);
        Route::get('/profile', [UserController::class, 'profile']);

        //الطلبات
        Route::post('/orders/confirmOrder', [OrderController::class, 'confirm']);
        Route::post('/orders/changePaymentMethod/{order_id}', [OrderController::class, 'changePaymentMethod']);
        Route::get('/orders/my', [OrderController::class, 'myOrders']);
        Route::get('/orders/{orderId}', [OrderController::class, 'show']);

        // إدارة العناوين
        Route::get('addresses', [AddressController::class, 'index']);
        Route::post('addresses/new', [AddressController::class, 'add']);
        Route::get('addresses/{id}', [AddressController::class, 'show']);
        Route::put('addresses/update/{id}', [AddressController::class, 'update']);
        Route::delete('addresses/{id}', [AddressController::class, 'destroy']);

        // المحفظة
        Route::get('/wallet/balance', [WalletController::class, 'balance']);

        //فريق الدعم
        Route::get('/support-links', [LinkController::class, 'index']);
        // الشكاوى
        Route::post('complaints', [ComplaintController::class, 'store']);


        // الإشعارات
        Route::get('notifications', [NotificationController::class, 'index']);


    });


/*
|--------------------------------------------------------------------------
| 🏪 Store Dashboard Routes
|--------------------------------------------------------------------------
*/


    Route::prefix('store-auth')->middleware(['auth:sanctum','attach.user.area','store.only'])->group(function () {

        // الحساب الشخصي
        Route::put('/profile/update-profile', [UserController::class, 'updateProfile']);
        Route::post('/profile/change-area', [UserController::class, 'changeArea']);
        Route::get('/profile', [UserController::class, 'profile']);


        //الطلبات
        Route::get('/orders/pending-orders', [OrderController::class, 'pendingOrders']);
        Route::get('/orders/preparing-orders', [OrderController::class, 'preparingOrders']);
        Route::get('/orders/done-orders', [OrderController::class, 'doneOrders']);
        Route::get('/orders/rejected-orders', [OrderController::class, 'rejectedOrders']);
        Route::get('/orders/showStoreOrderDetails/{order}', [OrderController::class, 'showStoreOrderDetails']);
        Route::post('orders/{order}/accept', [OrderController::class, 'acceptOrder']);
        Route::post('orders/{order}/reject', [OrderController::class, 'rejectOrder']);

        //المنتجات
        Route::get('/products', [ProductController::class, 'myStoreProducts']);
        Route::get('/products/product-details/{productId}', [ProductController::class, 'productDetails']);

        //الخصومات
        Route::post('products/discounts/{productId}', [ProductController::class, 'addDiscount']);


        // البائع: إنشاء طلب تعديل
        Route::post('/products/update-request/{product}', [ProductRequestsController::class, 'updateRequest']);
        Route::post('/products/create-request', [ProductRequestsController::class, 'createRequest']);
        Route::post('/products/delete-request/{product}', [ProductRequestsController::class, 'deleteRequest']);
        Route::post('/products/approve/{req}', [ProductRequestsController::class, 'approve']);
        Route::post('/requests/update-pending-request/{requestId}', [ProductRequestsController::class, 'updatePending']);
        Route::get('/requests/pending', [ProductRequestsController::class, 'getPendingRequests']);
        Route::delete('requests/delete-pending-request/{id}',[ProductRequestsController::class, 'deleteCreateRequest']);

        // إدارة العناوين
        Route::get('addresses', [AddressController::class, 'index']);
        Route::post('addresses/new', [AddressController::class, 'add']);
        Route::get('addresses/{id}', [AddressController::class, 'show']);
        Route::put('addresses/update/{id}', [AddressController::class, 'update']);
        Route::delete('addresses/{id}', [AddressController::class, 'destroy']);

        // المحفظة
        Route::get('/wallet/balance', [WalletController::class, 'balance']);

        //فريق الدعم
        Route::get('/support-links', [LinkController::class, 'index']);
        // الشكاوى
        Route::post('complaints', [ComplaintController::class, 'store']);


        // الإشعارات
        Route::get('notifications', [NotificationController::class, 'index']);



// الأدمن: موافقة/رفض
        Route::post('/admin/product-change-requests/{id}/approve', [ProductRequestsController::class, 'approve'])
            ->middleware(['auth:sanctum']);

        Route::post('/admin/product-change-requests/{id}/reject', [ProductRequestsController::class, 'reject'])
            ->middleware(['auth:sanctum']);


    });

/*
|--------------------------------------------------------------------------
| 🏪 SubAdmin Dashboard Routes
|--------------------------------------------------------------------------
*/

    Route::prefix('subAdmin-auth')->middleware(['auth:sanctum','attach.user.area'])->group(function () {
        //الزبائن
        Route::get('customers/AllCustomers', [SubAdminCustomerController::class, 'index']);
        Route::get('customers/search-name',   [SubAdminCustomerController::class, 'searchByName']);
        Route::get('customers/search-phone',  [SubAdminCustomerController::class, 'searchByPhone']);

    });
