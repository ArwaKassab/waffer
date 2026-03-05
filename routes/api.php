<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\AuthResetController;
use App\Http\Controllers\AuthResetSafrjalController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\CustomerAuthSafrjalController;
use App\Http\Controllers\CustomerFirebaseAuthController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\LinkController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OfferDiscountController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductRequestsController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\SubAdmin\DiscountController;
use App\Http\Controllers\SubAdmin\ProductRequestController;
use App\Http\Controllers\SupAdminAuthController;
use App\Http\Controllers\WalletController;
use App\Services\FcmV1Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdController;


use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\StoreAuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SubAdmin\CustomerController as SubAdminCustomerController;
use App\Http\Controllers\SubAdmin\OrderController as SubAdminOrderController;
use App\Http\Controllers\SubAdmin\StoreController as SubAdminStoreController;
use App\Http\Controllers\SubAdmin\CategoryController as SubAdminCategoryController;
use App\Http\Controllers\SubAdmin\ComplaintController as SubAdminComplaintController;
use App\Http\Controllers\SubAdmin\OrderStatisticsController as SubAdminOrderStatisticsController;
use App\Http\Controllers\SuperAdmin\SubAdminController as SuperAdminSubAdminController;
use App\Http\Controllers\SuperAdmin\AreaController as SubAdminAreaController;
use App\Http\Controllers\SubAdmin\AreaAdController as SubAdminAdController;
use App\Http\Controllers\SubAdmin\ProductController as SubAdminProductController;

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

        Route::post('send-reset-password-code', [AuthResetController::class, 'sendResetPasswordCode']);
        Route::post('verify-reset-password-code', [AuthResetController::class, 'verifyResetPasswordCode']);
        Route::post('reset-password', [AuthResetController::class, 'resetPassword'])
            ->middleware('verify.temp.token');
    });

    // ✅ SubAdmin Auth
    Route::prefix('sub_admin')->group(function () {
        Route::post('/login', [SupAdminAuthController::class, 'login']);
    //    Route::middleware(['auth:sanctum', 'check.role:customer'])->get('/profile', [CustomerController::class, 'profile']);
        Route::middleware('auth:sanctum')->post('/logout', [SupAdminAuthController::class, 'logout']);

        // استعادة كلمة المرور
        Route::post('send-reset-password-code', [AuthResetController::class, 'sendResetPasswordCode']);
        Route::post('verify-reset-password-code', [AuthResetController::class, 'verifyResetPasswordCode']);
        Route::post('reset-password', [AuthResetController::class, 'resetPassword'])
            ->middleware('verify.temp.token');

    });

    // ✅ Store Auth
    Route::prefix('store')->group(function () {
//        Route::post('/register', [StoreAuthController::class, 'register']);
        Route::post('/login', [StoreAuthController::class, 'login']);
    //    Route::middleware(['auth:sanctum', 'check.role:store'])->get('/dashboard', [StoreController::class, 'dashboard']);
        Route::middleware('auth:sanctum')->post('/logout', [StoreAuthController::class, 'logout']);

        // استعادة كلمة المرور
        Route::post('send-reset-password-code', [AuthResetController::class, 'sendResetPasswordCode']);
        Route::post('verify-reset-password-code', [AuthResetController::class, 'verifyResetPasswordCode']);
        Route::post('reset-password', [AuthResetController::class, 'resetPassword'])
            ->middleware('verify.temp.token');
    });

    //otp sms.chef
    // ✅ Customer Auth + Reset Password
    Route::prefix('customer')->group(function () {
//        Route::post('/register', [CustomerAuthController::class, 'startRegister']);
//        Route::post('/register/verify', [CustomerAuthController::class, 'verifyRegister']);
//        Route::post('register/resend-otp', [CustomerAuthController::class, 'resendRegisterOtp']);

        Route::post('/login', [CustomerAuthController::class, 'login']);
    //    Route::middleware(['auth:sanctum', 'check.role:customer'])->get('/profile', [CustomerController::class, 'profile']);
        Route::middleware('auth:sanctum')->post('/logout', [CustomerAuthController::class, 'logout']);

        // استعادة كلمة المرور
        Route::post('send-reset-password-code', [AuthResetController::class, 'sendResetPasswordCode']);
        Route::post('verify-reset-password-code', [AuthResetController::class, 'verifyResetPasswordCode']);
        Route::post('reset-password', [AuthResetController::class, 'resetPassword'])
            ->middleware('verify.temp.token');
        Route::post('resend-reset-password-otp', [AuthResetController::class, 'resendResetPasswordCode']);

    });
//otp firebase
Route::prefix('customer')->group(function () {
//    Route::post('/register', [CustomerFirebaseAuthController::class, 'register']);
//    Route::post('/reset-password', [CustomerFirebaseAuthController::class, 'resetPassword']);

//    Route::post('/register/firebase', [CustomerFirebaseAuthController::class, 'register']);
//    Route::post('/reset-password/firebase', [CustomerFirebaseAuthController::class, 'resetPassword']);
});

//otp safarjal
Route::prefix('customer')->group(function () {
    Route::post('/register', [CustomerAuthSafrjalController::class, 'startRegister']);
    Route::post('/register/verify', [CustomerAuthSafrjalController::class, 'verifyRegister']);
    Route::post('register/resend-otp', [CustomerAuthSafrjalController::class, 'resendRegisterOtp']);

    Route::post('/login', [CustomerAuthController::class, 'login']);
    //    Route::middleware(['auth:sanctum', 'check.role:customer'])->get('/profile', [CustomerController::class, 'profile']);
    Route::middleware('auth:sanctum')->post('/logout', [CustomerAuthController::class, 'logout']);

    // استعادة كلمة المرور
    Route::post('send-reset-password-code', [AuthResetSafrjalController::class, 'sendResetPasswordCode']);
    Route::post('verify-reset-password-code', [AuthResetSafrjalController::class, 'verifyResetPasswordCode']);
    Route::post('reset-password', [AuthResetSafrjalController::class, 'resetPassword'])
        ->middleware('verify.temp.token');
    Route::post('resend-reset-password-otp', [AuthResetSafrjalController::class, 'resendResetPasswordCode']);

});


/*
|--------------------------------------------------------------------------
| 🌍 Public (Visitor) Routes - مناطق وتعيينها
|--------------------------------------------------------------------------
*/

    Route::prefix('customer')->group(function () {

        Route::get('/areas', [AreaController::class, 'index']);
        Route::post('/set-area', [AreaController::class, 'setArea']);
        Route::get('/showFee/{area}', [AreaController::class, 'showFee']);

    });


/*
|--------------------------------------------------------------------------
| 🛍️ Customer & Visitore(after area selection) Routes
|--------------------------------------------------------------------------
*/

    Route::prefix('customer')->middleware(['ensure.visitor', 'detect.area','refresh.visitor'])->group(function () {

        //التصنيفات
        Route::get('categories', [CategoryController::class, 'index']);

        //المتاجر
        Route::get('/area-stores', [StoreController::class, 'indexByArea']);
        Route::get('/category-stores/{categoryId}', [StoreController::class, 'index']);
        Route::get('/stores/{id}', [StoreController::class, 'show']);


        //المنتجات
        Route::get('products/{id}', [ProductController::class, 'productDetails']);

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
//        Route::get('store/search', [StoreController::class, 'searchGroupedInArea']);
//        Route::get('store/categories-stores/search/{categoryId}', [StoreController::class, 'searchByCategoryGrouped']);
        Route::get('store/products/search/{store}', [ProductController::class, 'searchProductsInStore']);
        Route::get('store/search', [StoreController::class, 'searchUnified']);


    });

/*
|--------------------------------------------------------------------------
| 🏪 Customer Dashboard Routes
|--------------------------------------------------------------------------
*/

    Route::prefix('customer-auth')->middleware(['auth:sanctum','attach.user.area'])->group(function () {

        // الحساب الشخصي
        Route::patch('/profile/update-profile', [UserController::class, 'updateProfile']);
        Route::post('/profile/change-area', [UserController::class, 'changeArea']);
        Route::get('/profile', [UserController::class, 'profile']);
        Route::delete('/deleteMyAccount', [UserController::class, 'deleteMyAccount']);

        //الطلبات
        Route::post('/orders/confirmOrder', [OrderController::class, 'confirm']);
        Route::post('/orders/reorder/{orderId}', [OrderController::class, 'reorder']);
        Route::post('/orders/changePaymentMethod/{order_id}', [OrderController::class, 'changePaymentMethod']);
        Route::get('/orders/my', [OrderController::class, 'myOrders']);
        Route::get('/orders/{orderId}', [OrderController::class, 'show']);
        Route::get('/orders/{orderId}/status', [OrderController::class, 'orderStatus']);


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
        Route::get('/complaints/types', [ComplaintController::class, 'types']);
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
        Route::post('orders/{order}/setAsReady', [OrderController::class, 'setAsReady']);
        Route::post('orders/{order}/reject', [OrderController::class, 'rejectOrder']);
//التقاير
        Route::get('orders/done-report', [OrderController::class, 'doneOrdersBetweenDates']);
        Route::get('orders/reject-report', [OrderController::class, 'rejectordersBetweenDates']);

        //المنتجات
        Route::get('/products/units', [ProductController::class, 'Units']);

        Route::get('/products', [ProductController::class, 'myStoreProducts']);
        Route::get('/products/product-details/{productId}', [ProductController::class, 'productDetails']);

        //الخصومات
        Route::post('products/discounts/{productId}', [ProductController::class, 'addDiscount']);
        Route::delete('products/discounts/delete/{productId}', [ProductController::class, 'deleteDiscount']);


        // البائع: إنشاء طلب تعديل
        Route::post('/products/update-request/{product}', [ProductRequestsController::class, 'updateRequest']);
        Route::post('/products/create-request', [ProductRequestsController::class, 'createRequest']);
        Route::post('/products/delete-request/{product}', [ProductRequestsController::class, 'deleteRequest']);
        Route::post('/requests/update-pending-request/{requestId}', [ProductRequestsController::class, 'updatePending']);
        Route::get('/requests/pending', [ProductRequestsController::class, 'getPendingRequests']);
        Route::delete('requests/delete-pending-request/{id}',[ProductRequestsController::class, 'deleteCreateRequest']);
        Route::patch('/products/update/{productId}', [ProductRequestsController::class, 'directUpdateProduct']);
        Route::delete('/products/delete/{productId}', [ProductRequestsController::class, 'directDeleteProduct']);


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
        Route::get('customers/count', [SubAdminCustomerController::class, 'customersCount']);

        //عناوبن المستخدمين
        Route::get('/customers/addresses/{user}', [SubAdminCustomerController::class, 'addresses']);

        //المستخدمين المحظورين
        Route::get('/customers/banned', [SubAdminCustomerController::class, 'banned']);
        Route::put('/customers/ban/{user}', [SubAdminCustomerController::class, 'setOrToggleBan']);
        //الطلبات
        Route::post('/orders/{orderId}/accept', [SubAdminOrderController::class, 'accept']);
        Route::patch('/orders/{orderId}/change', [SubAdminOrderController::class, 'changeStatus']);
        Route::post('/orders/{id}/on-the-way', [SubAdminOrderController::class, 'markOnTheWay']);
        Route::post('/orders/{id}/delivered', [SubAdminOrderController::class, 'markDelivered']);
        Route::post('/orders/{id}/reject', [SubAdminOrderController::class, 'reject']);


        Route::get('/orders/today/pending/count', [SubAdminOrderController::class, 'countPending']);
        Route::get('/orders/today/pending/ids',       [SubAdminOrderController::class, 'listPending']);

        Route::get('/orders/today/preparing/count', [SubAdminOrderController::class, 'countPreparing']);
        Route::get('/orders/today/preparing/ids',       [SubAdminOrderController::class, 'listPreparing']);
        Route::get('/orders/today/OnWay/count', [SubAdminOrderController::class, 'countOnWay']);
        Route::get('/orders/today/OnWay/ids',       [SubAdminOrderController::class, 'listOnWay']);
        Route::get('/orders/today/Done/count', [SubAdminOrderController::class, 'countDone']);
        Route::get('/orders/today/Done/ids',       [SubAdminOrderController::class, 'listDone']);
        Route::get('/orders/orderDetails',[SubAdminOrderController::class, 'getOrderDetailsForSubAdmin']);
        Route::get('orders/delivered-today-count', [SubAdminOrderController::class, 'deliveredTodayCount']);
//        طلبات المتاجر
        Route::post('/products/approve/{req}', [ProductRequestsController::class, 'approve']);
        Route::post('/products/reject/{req}', [ProductRequestsController::class, 'reject']);



        //المتاجر
        Route::get('/stores/all-area', [SubAdminStoreController::class, 'allArea']);
        Route::post('/stores/add', [SubAdminStoreController::class, 'addStore']);
        Route::get('stores/{storeId}', [SubAdminStoreController::class, 'show']);
        Route::PATCH('/stores/update/{storeId}', [SubAdminStoreController::class, 'update']);
        Route::delete('stores/destroy/{storeId}', [SubAdminStoreController::class, 'destroy']);
        Route::get('stores/count/{areaId}', [SubAdminStoreController::class, 'storesCount']);
        //المنتجات
        Route::PATCH('products/status/{product}', [SubAdminStoreController::class, 'updateStatus']);
        Route::post('products/add', [SubAdminProductController::class, 'store']);
        Route::post('products/update/{id}', [SubAdminProductController::class, 'update']);
        Route::delete('products/delete/{id}', [SubAdminProductController::class, 'destroy']);

        Route::get('product-requests/create/{id}', [ProductRequestController::class, 'showCreateRequest']);
        Route::get('product-requests/create', [ProductRequestController::class, 'indexCreateRequests']);

//التصنيفات

        Route::get('categories/add', [SubAdminCategoryController::class, 'store_super_admin']);
        Route::get('categories/all', [SubAdminCategoryController::class, 'index']);
        Route::get('categories/allbyArea', [SubAdminCategoryController::class, 'byArea']);
        Route::get('categories/unassigned-toarea', [SubAdminCategoryController::class, 'unassigned']);
        Route::post('categories/assign-toarea/{category}', [SubAdminCategoryController::class, 'assign']);
        Route::post('categories/detach/{category}', [SubAdminCategoryController::class, 'detach']);


//        Route::post('categories/add', [SubAdminCategoryController::class, 'store']);
//        Route::post('categories/assignToArea', [SubAdminCategoryController::class, 'assignToArea']);

//        Route::put('categories/{id}', [CategoryController::class, 'update']);
        Route::PATCH('categories/update/{id}', [SubAdminCategoryController::class, 'update']);
        Route::delete('categories/delete/{id}', [SubAdminCategoryController::class, 'destroy']);

//الشكاوي
        Route::get('complaints/all', [SubAdminComplaintController::class, 'index']);
        Route::get('complaints/details/{id}', [SubAdminComplaintController::class, 'show'])
            ->name('subadmin.complaints.show');
//        التقاير

        Route::get('/orders/statistics', [SubAdminOrderStatisticsController::class, 'index']);
        //otp-health
        Route::get('otp-provider/health', [SubAdminCustomerController::class, 'health']);
        Route::get('otp-failures/{tempId}', [SubAdminCustomerController::class, 'show_error_reasone']);

//Ads
        Route::get('Ads/all', [SubAdminAdController::class, 'index']);
        Route::post('Ads/add', [SubAdminAdController::class, 'store']);
        Route::delete('Ads/delete/{adId}', [SubAdminAdController::class, 'destroy']);

        // العروض
        Route::get('discounts/listByArea', [DiscountController::class, 'listByArea']);
        Route::get('discounts/listByStore/{id}', [DiscountController::class, 'listByStore']);
        Route::get('discounts/{productId}', [DiscountController::class, 'index']);
        Route::post('discounts/add/{id}', [DiscountController::class, 'store']);
        Route::delete('discounts/destroy/{id}', [DiscountController::class, 'destroy']);


    });


Route::prefix('Admin-auth')->middleware(['auth:sanctum','attach.user.area'])->group(function () {

    Route::post('categories/add', [SubAdminCategoryController::class, 'store_super_admin']);
    Route::get('categories/all', [SubAdminCategoryController::class, 'index']);

//        Route::post('categories/add', [SubAdminCategoryController::class, 'store']);
//        Route::post('categories/assignToArea', [SubAdminCategoryController::class, 'assignToArea']);

//        Route::put('categories/{id}', [CategoryController::class, 'update']);
    Route::PATCH('categories/update/{id}', [SubAdminCategoryController::class, 'update']);
    Route::delete('categories/delete/{id}', [SubAdminCategoryController::class, 'destroy']);

    Route::post('sub-admin/add', [SuperAdminSubAdminController::class, 'addSubAdmin']);
    //اضافة منطقة
    Route::post('area/add', [SubAdminAreaController::class, 'store']);
    //  كل المناطق
    Route::get('area/all', [SubAdminAreaController::class, 'index']);
    // استرجاع منطقة واحدة
    Route::get('{id}', [SubAdminAreaController::class, 'show']);
    // حذف منطقة
    Route::delete('area/delete/{id}', [SubAdminAreaController::class, 'destroy']);
    Route::patch('areas/update/{id}', [SubAdminAreaController::class, 'update']);

});

Route::middleware('auth:sanctum')->group(function () {
    // Flutter يسجّل توكن الجهاز
    Route::post('devices/register-tokens', [DeviceController::class, 'store']);

    // عرض الإشعارات
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);

    // تعليم كمقروء
    Route::post('/notifications/{id}/mark-read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead']);
});
//
//Route::middleware('auth:sanctum')->group(function () {
//    Route::post('devices/register-token',   [DeviceController::class,'register'])->middleware('throttle:20,1');
//    Route::delete('devices/unregister-token',[DeviceController::class,'unregister'])->middleware('throttle:20,1');
//
//    Route::get('notifications',               [NotificationController::class,'index']);
//    Route::patch('notifications/{id}/read',   [NotificationController::class,'markRead']);
//    Route::patch('notifications/read-all',    [NotificationController::class,'markAllRead']);
//
//    Route::middleware('auth:sanctum')->get('/feed', function () {
//        $uid = auth()->id();
//        return DB::table('app_user_notifications')
//            ->where('user_id', $uid)
//            ->orderByDesc('id')
//            ->paginate(15);
//    });
//
//    Route::middleware('auth:sanctum')->post('/feed/{id}/read', function ($id) {
//        $uid = auth()->id();
//
//        // حدّث Projection
//        DB::table('app_user_notifications')
//            ->where('id', $id)
//            ->where('user_id', $uid)
//            ->update(['read_at' => now(), 'updated_at' => now()]);
//
//        // (اختياري) حدّث القياسي أيضًا إن استطعتِ مطابقة السطر؛
//        // الأسهل: وفرّي أيضاً API مستقل يستخدم notifications() القياسي:
//        // auth()->user()->unreadNotifications()->find($nid)?->markAsRead();
//
//        return response()->json(['ok' => true]);
//    });
//});
//
//Route::middleware('auth:sanctum')->
//post('test/push', function (Request $r, FcmV1Client $fcm) {
//    $data = $r->validate([
//        'token' => 'required|string',
//        'title' => 'required|string',
//        'body'  => 'nullable|string',
//        'data'  => 'array'
//    ]);
//
//    $fcm->sendToToken($data['token'], $data['title'], $data['body'] ?? '', $data['data'] ?? []);
//    return response()->json(['ok' => true]);
//});
