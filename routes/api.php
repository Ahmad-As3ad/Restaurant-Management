<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\MealController;
use App\Http\Controllers\Api\TableController;
use App\Http\Controllers\Api\IngredientController;
use App\Http\Controllers\Api\RatingController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

// ============================================================
// PUBLIC ROUTES - لا تحتاج توكن
// ============================================================

/*
|--------------------------------------------------------------------------
| POST /api/register
|--------------------------------------------------------------------------
| تسجيل مستخدم جديد
| Required: first_name, last_name, phone, address, email, password, password_confirmation
*/
Route::post('/register', [AuthController::class, 'register']);

/*
|--------------------------------------------------------------------------
| POST /api/login
|--------------------------------------------------------------------------
| تسجيل دخول
| Required: email, password
*/
Route::post('/login', [AuthController::class, 'login']);

// ============================================================
// PROTECTED ROUTES - تتطلب توكن (Bearer Token)
// ============================================================

Route::middleware('auth:sanctum')->group(function () {

    // ===================== المصادقة =====================

    /*
    |--------------------------------------------------------------------------
    | POST /api/logout
    |--------------------------------------------------------------------------
    | تسجيل خروج
    | Requires: Bearer Token
    */
    Route::post('/logout', [AuthController::class, 'logout']);

    /*
    |--------------------------------------------------------------------------
    | GET /api/me
    |--------------------------------------------------------------------------
    | الحصول على بيانات المستخدم الحالي
    | Requires: Bearer Token
    */
    Route::get('/me', [AuthController::class, 'me']);

    // ===================== الملف الشخصي =====================

    /*
    |--------------------------------------------------------------------------
    | GET /api/profile
    |--------------------------------------------------------------------------
    | عرض الملف الشخصي مع المحفظة والطلبات والحجوزات
    | Requires: Bearer Token
    */
    Route::get('/profile', [UserController::class, 'profile']);

    /*
    |--------------------------------------------------------------------------
    | PUT /api/profile
    |--------------------------------------------------------------------------
    | تحديث الملف الشخصي
    | Optional: first_name, last_name, phone, address, password, password_confirmation
    | Requires: Bearer Token
    */
    Route::put('/profile', [UserController::class, 'updateProfile']);

    // ===================== المحفظة =====================

    /*
    |--------------------------------------------------------------------------
    | GET /api/wallet/balance
    |--------------------------------------------------------------------------
    | عرض رصيد المحفظة
    | Requires: Bearer Token
    */
    Route::get('/wallet/balance', [WalletController::class, 'balance']);

    /*
    |--------------------------------------------------------------------------
    | POST /api/wallet/deposit
    |--------------------------------------------------------------------------
    | شحن المحفظة
    | Required: amount
    | Optional: description
    | Requires: Bearer Token
    */
    Route::post('/wallet/deposit', [WalletController::class, 'deposit']);

    /*
    |--------------------------------------------------------------------------
    | GET /api/wallet/transactions
    |--------------------------------------------------------------------------
    | عرض سجل المعاملات
    | Optional: per_page
    | Requires: Bearer Token
    */
    Route::get('/wallet/transactions', [WalletController::class, 'transactions']);

    /*
    |--------------------------------------------------------------------------
    | GET /api/wallet/transactions/{type}
    |--------------------------------------------------------------------------
    | عرض سجل المعاملات حسب النوع
    | Type: deposit, payment, refund, cancellation_fee
    | Requires: Bearer Token
    */
    Route::get('/wallet/transactions/{type}', [WalletController::class, 'transactionsByType']);

    // ===================== الطلبات =====================

    /*
    |--------------------------------------------------------------------------
    | POST /api/orders/create
    |--------------------------------------------------------------------------
    | إنشاء طلب جديد
    | Required: items (array), items.*.meal_id, items.*.quantity, pickup_time
    | Optional: items.*.customizations (added, removed), coupon_code, notes
    | Requires: Bearer Token
    */
    Route::post('/orders/create', [OrderController::class, 'create']);

    /*
    |--------------------------------------------------------------------------
    | POST /api/orders/cancel
    |--------------------------------------------------------------------------
    | إلغاء طلب (يسمح فقط في مرحلتي pending و preparing)
    | Required: order_id
    | سياسة الإلغاء: استرداد 50% للمحفظة، 50% رسوم إلغاء
    | Requires: Bearer Token
    */
    Route::post('/orders/cancel', [OrderController::class, 'cancel']);

    /*
    |--------------------------------------------------------------------------
    | GET /api/orders/my-orders
    |--------------------------------------------------------------------------
    | عرض طلباتي السابقة
    | Optional: per_page
    | Requires: Bearer Token
    */
    Route::get('/orders/my-orders', [OrderController::class, 'userOrders']);

    /*
    |--------------------------------------------------------------------------
    | GET /api/orders/{id}
    |--------------------------------------------------------------------------
    | عرض تفاصيل طلب
    | Requires: Bearer Token
    */
    Route::get('/orders/{id}', [OrderController::class, 'show']);

    // ===================== الحجوزات =====================

    /*
    |--------------------------------------------------------------------------
    | POST /api/bookings/create
    |--------------------------------------------------------------------------
    | إنشاء حجز جديد
    | Required: table_id, booking_date, booking_time, number_of_people
    | Optional: notes
    | نظام الدفع: خصم فوري من المحفظة
    | Requires: Bearer Token
    */
    Route::post('/bookings/create', [BookingController::class, 'create']);

    /*
    |--------------------------------------------------------------------------
    | POST /api/bookings/cancel
    |--------------------------------------------------------------------------
    | إلغاء حجز (يسمح قبل موعد الحجز بساعة على الأقل)
    | Required: booking_id
    | سياسة الإلغاء: استرداد 50% للمحفظة، 50% رسوم إلغاء
    | Requires: Bearer Token
    */
    Route::post('/bookings/cancel', [BookingController::class, 'cancel']);

    /*
    |--------------------------------------------------------------------------
    | GET /api/bookings/my-bookings
    |--------------------------------------------------------------------------
    | عرض حجوزاتي
    | Optional: per_page
    | Requires: Bearer Token
    */
    Route::get('/bookings/my-bookings', [BookingController::class, 'userBookings']);

    /*
    |--------------------------------------------------------------------------
    | GET /api/bookings/upcoming
    |--------------------------------------------------------------------------
    | عرض الحجوزات القادمة
    | Requires: Bearer Token
    */
    Route::get('/bookings/upcoming', [BookingController::class, 'upcomingBookings']);

    /*
    |--------------------------------------------------------------------------
    | GET /api/bookings/{id}
    |--------------------------------------------------------------------------
    | عرض تفاصيل حجز
    | Requires: Bearer Token
    */
    Route::get('/bookings/{id}', [BookingController::class, 'show']);

    // ===================== الوجبات =====================

    /*
    |--------------------------------------------------------------------------
    | GET /api/meals
    |--------------------------------------------------------------------------
    | عرض جميع الوجبات
    | Optional: category, sort (price_asc, price_desc, rating, popular), per_page
    | Requires: Bearer Token
    */
    Route::get('/meals', [MealController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | GET /api/meals/{id}
    |--------------------------------------------------------------------------
    | عرض تفاصيل وجبة
    | Requires: Bearer Token
    */
    Route::get('/meals/{id}', [MealController::class, 'show']);

    /*
    |--------------------------------------------------------------------------
    | GET /api/meals/{id}/ingredients
    |--------------------------------------------------------------------------
    | عرض مكونات وجبة (افتراضية واختيارية)
    | Requires: Bearer Token
    */
    Route::get('/meals/{id}/ingredients', [MealController::class, 'ingredients']);

    /*
    |--------------------------------------------------------------------------
    | GET /api/meals/categories/{category}
    |--------------------------------------------------------------------------
    | عرض وجبات حسب التصنيف
    | Category: appetizer, main, drink, dessert
    | Requires: Bearer Token
    */
    Route::get('/meals/categories/{category}', [MealController::class, 'byCategory']);

    /*
    |--------------------------------------------------------------------------
    | GET /api/meals/search/{query}
    |--------------------------------------------------------------------------
    | بحث عن وجبات
    | Requires: Bearer Token
    */
    Route::get('/meals/search/{query}', [MealController::class, 'search']);

    // ===================== المكونات =====================

    /*
    |--------------------------------------------------------------------------
    | GET /api/ingredients
    |--------------------------------------------------------------------------
    | عرض جميع المكونات
    | Optional: search, per_page
    | Requires: Bearer Token
    */
    Route::get('/ingredients', [IngredientController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | GET /api/ingredients/{id}
    |--------------------------------------------------------------------------
    | عرض تفاصيل مكون
    | Requires: Bearer Token
    */
    Route::get('/ingredients/{id}', [IngredientController::class, 'show']);

    // ===================== الطاولات =====================

    /*
    |--------------------------------------------------------------------------
    | GET /api/tables
    |--------------------------------------------------------------------------
    | عرض جميع الطاولات
    | Optional: status, capacity, per_page
    | Requires: Bearer Token
    */
    Route::get('/tables', [TableController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | GET /api/tables/available
    |--------------------------------------------------------------------------
    | عرض الطاولات المتاحة
    | Optional: date, time
    | Requires: Bearer Token
    */
    Route::get('/tables/available', [TableController::class, 'available']);

    /*
    |--------------------------------------------------------------------------
    | GET /api/tables/{id}
    |--------------------------------------------------------------------------
    | عرض تفاصيل طاولة
    | Requires: Bearer Token
    */
    Route::get('/tables/{id}', [TableController::class, 'show']);

    /*
    |--------------------------------------------------------------------------
    | GET /api/tables/{id}/availability
    |--------------------------------------------------------------------------
    | التحقق من توفر طاولة في وقت محدد
    | Required: date, time (query params)
    | Requires: Bearer Token
    */
    Route::get('/tables/{id}/availability', [TableController::class, 'checkAvailability']);

    // ===================== التقييمات =====================

    /*
    |--------------------------------------------------------------------------
    | POST /api/ratings/create
    |--------------------------------------------------------------------------
    | إنشاء تقييم جديد (يسمح فقط بعد استلام الطلب)
    | Required: meal_id, order_id, rating (1-5)
    | Optional: comment
    | Requires: Bearer Token
    */
    Route::post('/ratings/create', [RatingController::class, 'create']);

    /*
    |--------------------------------------------------------------------------
    | GET /api/ratings/my-ratings
    |--------------------------------------------------------------------------
    | عرض تقييماتي
    | Optional: per_page
    | Requires: Bearer Token
    */
    Route::get('/ratings/my-ratings', [RatingController::class, 'myRatings']);

    /*
    |--------------------------------------------------------------------------
    | GET /api/ratings/meal/{mealId}
    |--------------------------------------------------------------------------
    | عرض تقييمات وجبة معينة
    | Requires: Bearer Token
    */
    Route::get('/ratings/meal/{mealId}', [RatingController::class, 'mealRatings']);

    /*
    |--------------------------------------------------------------------------
    | PUT /api/ratings/{id}
    |--------------------------------------------------------------------------
    | تحديث تقييم
    | Required: rating (1-5)
    | Optional: comment
    | Requires: Bearer Token
    */
    Route::put('/ratings/{id}', [RatingController::class, 'update']);

    /*
    |--------------------------------------------------------------------------
    | DELETE /api/ratings/{id}
    |--------------------------------------------------------------------------
    | حذف تقييم
    | Requires: Bearer Token
    */
    Route::delete('/ratings/{id}', [RatingController::class, 'destroy']);

    // ===================== الكوبونات =====================

    /*
    |--------------------------------------------------------------------------
    | POST /api/coupons/validate
    |--------------------------------------------------------------------------
    | التحقق من صحة كوبون
    | Required: code, order_amount
    | Requires: Bearer Token
    */
    Route::post('/coupons/validate', [CouponController::class, 'validateCoupon']);

    /*
    |--------------------------------------------------------------------------
    | GET /api/coupons/my-coupons
    |--------------------------------------------------------------------------
    | عرض الكوبونات المستخدمة
    | Optional: per_page
    | Requires: Bearer Token
    */
    Route::get('/coupons/my-coupons', [CouponController::class, 'myCoupons']);

    /*
    |--------------------------------------------------------------------------
    | GET /api/coupons/available
    |--------------------------------------------------------------------------
    | عرض الكوبونات المتاحة للمستخدم
    | Optional: order_amount
    | Requires: Bearer Token
    */
    Route::get('/coupons/available', [CouponController::class, 'availableCoupons']);

    // ===================== الإشعارات =====================

    /*
    |--------------------------------------------------------------------------
    | GET /api/notifications
    |--------------------------------------------------------------------------
    | عرض جميع الإشعارات
    | Optional: per_page
    | Requires: Bearer Token
    */
    Route::get('/notifications', [NotificationController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | GET /api/notifications/unread
    |--------------------------------------------------------------------------
    | عرض الإشعارات غير المقروءة
    | Requires: Bearer Token
    */
    Route::get('/notifications/unread', [NotificationController::class, 'unread']);

    /*
    |--------------------------------------------------------------------------
    | POST /api/notifications/{id}/read
    |--------------------------------------------------------------------------
    | تحديد إشعار كمقروء
    | Requires: Bearer Token
    */
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);

    /*
    |--------------------------------------------------------------------------
    | POST /api/notifications/read-all
    |--------------------------------------------------------------------------
    | تحديد جميع الإشعارات كمقروءة
    | Requires: Bearer Token
    */
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    /*
    |--------------------------------------------------------------------------
    | DELETE /api/notifications/{id}
    |--------------------------------------------------------------------------
    | حذف إشعار
    | Requires: Bearer Token
    */
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | DELETE /api/notifications
    |--------------------------------------------------------------------------
    | حذف جميع الإشعارات
    | Requires: Bearer Token
    */
    Route::delete('/notifications', [NotificationController::class, 'destroyAll']);

    // ===================== التقارير =====================

    /*
    |--------------------------------------------------------------------------
    | GET /api/reports/daily
    |--------------------------------------------------------------------------
    | التقرير اليومي
    | Roles: Admin, Accountant
    | Optional: date
    | Requires: Bearer Token
    */
    Route::get('/reports/daily', [ReportController::class, 'daily']);

    /*
    |--------------------------------------------------------------------------
    | GET /api/reports/weekly
    |--------------------------------------------------------------------------
    | التقرير الأسبوعي
    | Roles: Admin, Accountant
    | Optional: start_date, end_date
    | Requires: Bearer Token
    */
    Route::get('/reports/weekly', [ReportController::class, 'weekly']);

    /*
    |--------------------------------------------------------------------------
    | GET /api/reports/monthly
    |--------------------------------------------------------------------------
    | التقرير الشهري
    | Roles: Admin, Accountant
    | Optional: month, year
    | Requires: Bearer Token
    */
    Route::get('/reports/monthly', [ReportController::class, 'monthly']);

    // ============================================================
    // ADMIN ROUTES - تتطلب توكن + دور admin
    // ============================================================

    Route::middleware('admin')->group(function () {

        // ===================== إدارة المستخدمين =====================

        /*
        |--------------------------------------------------------------------------
        | GET /api/users
        |--------------------------------------------------------------------------
        | عرض جميع المستخدمين
        | Optional: role, status, search, per_page
        | Requires: Bearer Token + Admin
        */
        Route::get('/users', [UserController::class, 'index']);

        /*
        |--------------------------------------------------------------------------
        | GET /api/users/statistics
        |--------------------------------------------------------------------------
        | عرض إحصاءات المستخدمين
        | Requires: Bearer Token + Admin
        */
        Route::get('/users/statistics', [UserController::class, 'statistics']);

        /*
        |--------------------------------------------------------------------------
        | POST /api/users
        |--------------------------------------------------------------------------
        | إنشاء مستخدم جديد (موظف)
        | Required: first_name, last_name, phone, address, email, password, password_confirmation, role
        | Optional: status
        | Roles: customer, admin, chef, booking_staff, accountant
        | Requires: Bearer Token + Admin
        */
        Route::post('/users', [UserController::class, 'store']);

        /*
        |--------------------------------------------------------------------------
        | GET /api/users/{id}
        |--------------------------------------------------------------------------
        | عرض تفاصيل مستخدم
        | Requires: Bearer Token + Admin
        */
        Route::get('/users/{id}', [UserController::class, 'show']);

        /*
        |--------------------------------------------------------------------------
        | PUT /api/users/{id}
        |--------------------------------------------------------------------------
        | تحديث مستخدم
        | Optional: first_name, last_name, phone, address, email, password, password_confirmation
        | Requires: Bearer Token + Admin
        */
        Route::put('/users/{id}', [UserController::class, 'update']);

        /*
        |--------------------------------------------------------------------------
        | DELETE /api/users/{id}
        |--------------------------------------------------------------------------
        | حذف مستخدم (لا يمكن حذف الأدمن الوحيد أو المستخدم نفسه)
        | Requires: Bearer Token + Admin
        */
        Route::delete('/users/{id}', [UserController::class, 'destroy']);

        /*
        |--------------------------------------------------------------------------
        | POST /api/users/{id}/role
        |--------------------------------------------------------------------------
        | تحديث دور مستخدم
        | Required: role (customer, admin, chef, booking_staff, accountant)
        | Requires: Bearer Token + Admin
        */
        Route::post('/users/{id}/role', [UserController::class, 'updateRole']);

        /*
        |--------------------------------------------------------------------------
        | POST /api/users/{id}/status
        |--------------------------------------------------------------------------
        | تحديث حالة مستخدم
        | Required: status (active, inactive, banned)
        | Requires: Bearer Token + Admin
        */
        Route::post('/users/{id}/status', [UserController::class, 'updateStatus']);

        /*
        |--------------------------------------------------------------------------
        | POST /api/users/{id}/reset-password
        |--------------------------------------------------------------------------
        | إعادة تعيين كلمة المرور
        | Required: password, password_confirmation
        | Requires: Bearer Token + Admin
        */
        Route::post('/users/{id}/reset-password', [UserController::class, 'resetPassword']);

        // ===================== إدارة الوجبات =====================

        /*
        |--------------------------------------------------------------------------
        | POST /api/admin/meals
        |--------------------------------------------------------------------------
        | إضافة وجبة جديدة
        | Required: name, price, category (appetizer, main, drink, dessert)
        | Optional: description, image, ingredients (array)
        | Requires: Bearer Token + Admin
        */
        Route::post('/admin/meals', [MealController::class, 'store']);

        /*
        |--------------------------------------------------------------------------
        | PUT /api/admin/meals/{id}
        |--------------------------------------------------------------------------
        | تعديل وجبة
        | Optional: name, description, price, category, image, is_active, ingredients
        | Requires: Bearer Token + Admin
        */
        Route::put('/admin/meals/{id}', [MealController::class, 'update']);

        /*
        |--------------------------------------------------------------------------
        | DELETE /api/admin/meals/{id}
        |--------------------------------------------------------------------------
        | حذف وجبة
        | Requires: Bearer Token + Admin
        */
        Route::delete('/admin/meals/{id}', [MealController::class, 'destroy']);

        /*
        |--------------------------------------------------------------------------
        | POST /api/admin/meals/{id}/toggle
        |--------------------------------------------------------------------------
        | تفعيل/تعطيل وجبة
        | Requires: Bearer Token + Admin
        */
        Route::post('/admin/meals/{id}/toggle', [MealController::class, 'toggle']);

        /*
        |--------------------------------------------------------------------------
        | POST /api/admin/meals/{id}/ingredients
        |--------------------------------------------------------------------------
        | إضافة مكون لوجبة
        | Required: ingredient_id, quantity
        | Optional: is_default
        | Requires: Bearer Token + Admin
        */
        Route::post('/admin/meals/{id}/ingredients', [MealController::class, 'addIngredient']);

        /*
        |--------------------------------------------------------------------------
        | DELETE /api/admin/meals/{mealId}/ingredients/{ingredientId}
        |--------------------------------------------------------------------------
        | حذف مكون من وجبة
        | Requires: Bearer Token + Admin
        */
        Route::delete('/admin/meals/{mealId}/ingredients/{ingredientId}', [MealController::class, 'removeIngredient']);

        // ===================== إدارة المكونات =====================

        /*
        |--------------------------------------------------------------------------
        | POST /api/admin/ingredients
        |--------------------------------------------------------------------------
        | إضافة مكون جديد
        | Required: name, unit (piece, gram, kg, ml, liter)
        | Optional: initial_quantity, min_quantity
        | Requires: Bearer Token + Admin
        */
        Route::post('/admin/ingredients', [IngredientController::class, 'store']);

        /*
        |--------------------------------------------------------------------------
        | PUT /api/admin/ingredients/{id}
        |--------------------------------------------------------------------------
        | تعديل مكون
        | Optional: name, unit, initial_quantity, min_quantity
        | Requires: Bearer Token + Admin
        */
        Route::put('/admin/ingredients/{id}', [IngredientController::class, 'update']);

        /*
        |--------------------------------------------------------------------------
        | DELETE /api/admin/ingredients/{id}
        |--------------------------------------------------------------------------
        | حذف مكون (لا يمكن حذف مكون مستخدم في وجبات)
        | Requires: Bearer Token + Admin
        */
        Route::delete('/admin/ingredients/{id}', [IngredientController::class, 'destroy']);

        // ===================== إدارة الطاولات =====================

        /*
        |--------------------------------------------------------------------------
        | POST /api/admin/tables
        |--------------------------------------------------------------------------
        | إضافة طاولة جديدة
        | Required: table_number, capacity, price_per_person
        | Optional: location
        | Requires: Bearer Token + Admin
        */
        Route::post('/admin/tables', [TableController::class, 'store']);

        /*
        |--------------------------------------------------------------------------
        | PUT /api/admin/tables/{id}
        |--------------------------------------------------------------------------
        | تعديل طاولة
        | Optional: table_number, capacity, location, price_per_person, status
        | Requires: Bearer Token + Admin
        */
        Route::put('/admin/tables/{id}', [TableController::class, 'update']);

        /*
        |--------------------------------------------------------------------------
        | DELETE /api/admin/tables/{id}
        |--------------------------------------------------------------------------
        | حذف طاولة (لا يمكن حذف طاولة عليها حجوزات نشطة)
        | Requires: Bearer Token + Admin
        */
        Route::delete('/admin/tables/{id}', [TableController::class, 'destroy']);

        /*
        |--------------------------------------------------------------------------
        | POST /api/admin/tables/{id}/status
        |--------------------------------------------------------------------------
        | تحديث حالة طاولة
        | Required: status (available, occupied, reserved)
        | Requires: Bearer Token + Admin
        */
        Route::post('/admin/tables/{id}/status', [TableController::class, 'updateStatus']);

        // ===================== إدارة الكوبونات =====================

        /*
        |--------------------------------------------------------------------------
        | POST /api/admin/coupons
        |--------------------------------------------------------------------------
        | إنشاء كوبون جديد
        | Required: code, name, type (percentage, fixed), value, start_date, end_date
        | Optional: min_order_amount, max_discount, usage_limit, usage_per_user
        | Requires: Bearer Token + Admin
        */
        Route::post('/admin/coupons', [CouponController::class, 'store']);

        /*
        |--------------------------------------------------------------------------
        | PUT /api/admin/coupons/{id}
        |--------------------------------------------------------------------------
        | تعديل كوبون
        | Optional: code, name, type, value, min_order_amount, max_discount, start_date, end_date, usage_limit, usage_per_user, is_active
        | Requires: Bearer Token + Admin
        */
        Route::put('/admin/coupons/{id}', [CouponController::class, 'update']);

        /*
        |--------------------------------------------------------------------------
        | DELETE /api/admin/coupons/{id}
        |--------------------------------------------------------------------------
        | حذف كوبون
        | Requires: Bearer Token + Admin
        */
        Route::delete('/admin/coupons/{id}', [CouponController::class, 'destroy']);

        /*
        |--------------------------------------------------------------------------
        | POST /api/admin/coupons/{id}/toggle
        |--------------------------------------------------------------------------
        | تفعيل/تعطيل كوبون
        | Requires: Bearer Token + Admin
        */
        Route::post('/admin/coupons/{id}/toggle', [CouponController::class, 'toggle']);

        // ===================== إدارة الطلبات =====================

        /*
        |--------------------------------------------------------------------------
        | GET /api/admin/orders
        |--------------------------------------------------------------------------
        | عرض جميع الطلبات
        | Roles: Admin, Chef (يرى فقط pending و preparing)
        | Optional: status, date, per_page
        | Requires: Bearer Token + Admin/Chef
        */
        Route::get('/admin/orders', [OrderController::class, 'adminOrders']);

        /*
        |--------------------------------------------------------------------------
        | POST /api/admin/orders/{id}/status
        |--------------------------------------------------------------------------
        | تحديث حالة طلب
        | Roles: Admin (جميع الحالات), Chef (فقط إلى ready)
        | Required: status (pending, preparing, ready, collected, cancelled)
        | Requires: Bearer Token + Admin/Chef
        */
        Route::post('/admin/orders/{id}/status', [OrderController::class, 'updateStatus']);

        /*
        |--------------------------------------------------------------------------
        | GET /api/admin/orders/{id}
        |--------------------------------------------------------------------------
        | عرض تفاصيل طلب (للأدمن)
        | Requires: Bearer Token + Admin
        */
        Route::get('/admin/orders/{id}', [OrderController::class, 'adminShow']);

        // ===================== إدارة الحجوزات =====================

        /*
        |--------------------------------------------------------------------------
        | GET /api/admin/bookings
        |--------------------------------------------------------------------------
        | عرض جميع الحجوزات
        | Roles: Admin, Booking Staff
        | Optional: status, date, per_page
        | Requires: Bearer Token + Admin/BookingStaff
        */
        Route::get('/admin/bookings', [BookingController::class, 'adminBookings']);

        /*
        |--------------------------------------------------------------------------
        | POST /api/admin/bookings/{id}/confirm
        |--------------------------------------------------------------------------
        | تأكيد حجز
        | Roles: Admin, Booking Staff
        | Requires: Bearer Token + Admin/BookingStaff
        */
        Route::post('/admin/bookings/{id}/confirm', [BookingController::class, 'confirm']);

        /*
        |--------------------------------------------------------------------------
        | POST /api/admin/bookings/{id}/cancel
        |--------------------------------------------------------------------------
        | إلغاء حجز (من قبل الأدمن أو موظف الحجوزات)
        | Roles: Admin, Booking Staff
        | Requires: Bearer Token + Admin/BookingStaff
        */
        Route::post('/admin/bookings/{id}/cancel', [BookingController::class, 'adminCancel']);
    });
});
