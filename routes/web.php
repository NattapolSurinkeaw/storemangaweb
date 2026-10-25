<?php
/**
 * routes/web.php
 * ไฟล์กำหนด Route ของเว็บไซต์ Manga Stock System
 * 
 * ใช้สำหรับ:
 *  - กำหนด path URL ของเว็บไซต์
 *  - กำหนด Controller และ Method/Action ที่จะถูกเรียกใช้
 *  - กำหนด HTTP Methods ที่อนุญาต (GET, POST, PUT, DELETE, MATCH)
 *  - กำหนด Parameter ใน URL เช่น {id}
 * 
 * รูปแบบการใช้งาน:
 *   Router::get('/path', 'ControllerName@actionName');
 *   Router::post('/path', 'ControllerName@actionName');
 *   Router::match(['GET', 'POST'], '/path', 'ControllerName@actionName');
 *   Router::get('/path/{id}', 'ControllerName@actionName');
 *   Router::get('/path', [ControllerName::class, 'actionName']);
 *   Router::get('/path', function() { ... });
 */

// =============================================================
// 1. หน้าแรก & Dashboard
// =============================================================
Router::get('/', 'DashboardController@index');
Router::get('/dashboard', 'DashboardController@index');
Router::get('/dashboard/data', 'DashboardController@data');

// =============================================================
// 2. การเข้าสู่ระบบ & ออกจากระบบ (Authentication)
// =============================================================
Router::get('/login', 'LoginController@index', ['public' => true]);
Router::post('/login', 'LoginController@index', ['public' => true]);
Router::get('/logout', 'LoginController@logout');
Router::get('/login/logout', 'LoginController@logout');

// =============================================================
// 3. ข้อมูลส่วนตัว (Profile & Password)
// =============================================================
Router::get('/profile', 'ProfileController@index');
Router::post('/profile/updateProfile', 'ProfileController@updateProfile');
Router::post('/profile/update-profile', 'ProfileController@updateProfile');
Router::post('/profile/updatePassword', 'ProfileController@updatePassword');
Router::post('/profile/update-password', 'ProfileController@updatePassword');

// =============================================================
// 4. สต็อกมังงะ & การจัดชุดขาย (Stock & Bundles)
// =============================================================
Router::get('/stock', 'StockController@index');
Router::get('/stock/data', 'StockController@data');
Router::get('/stock/bundle', 'StockController@bundle');
Router::get('/stock/bundleData', 'StockController@bundleData');
Router::post('/stock/bundle', 'StockController@bundle');

// API & Action สำหรับสต็อกและชุดมังงะ
Router::get('/stock/dissolveBundle/{id}', 'StockController@dissolveBundle');
Router::post('/stock/dissolveBundle/{id}', 'StockController@dissolveBundle');
Router::get('/stock/dissolveBundleGroup/{id}', 'StockController@dissolveBundleGroup');
Router::post('/stock/dissolveBundleGroup/{id}', 'StockController@dissolveBundleGroup');

Router::get('/stock/getAvailableVolumes', 'StockController@getAvailableVolumes');
Router::get('/stock/previewBundle', 'StockController@previewBundle');
Router::get('/stock/bundleDetail/{id}', 'StockController@bundleDetail');
Router::get('/stock/bundleGroupDetail/{id}', 'StockController@bundleGroupDetail');
Router::get('/stock/titleDetail', 'StockController@titleDetail');

// =============================================================
// 5. บันทึกบิลซื้อเข้า (Purchases)
// =============================================================
Router::get('/purchase', 'PurchaseController@index');
Router::get('/purchase/create', 'PurchaseController@create');
Router::get('/purchase/list', 'PurchaseController@listData');
Router::post('/purchase/create', 'PurchaseController@create');
Router::post('/purchase/update/{id}', 'PurchaseController@update');
Router::get('/purchase/detail/{id}', 'PurchaseController@detail');
Router::post('/purchase/delete/{id}', 'PurchaseController@delete');

// =============================================================
// 6. บันทึกรายการขาย (Sales)
// =============================================================
Router::get('/sales', 'SalesController@index');
Router::get('/sales/create', 'SalesController@create');
Router::get('/sales/data', 'SalesController@data');
Router::post('/sales/create', 'SalesController@create');
Router::post('/sales/update/{id}', 'SalesController@update');
Router::post('/sales/delete/{id}', 'SalesController@delete');
Router::match(['GET', 'POST'], '/sales/calculate', 'SalesController@calculate');

// =============================================================
// 7. รายงานยอดซื้อ-ขายและส่งออกข้อมูล (Reports & Export)
// =============================================================
Router::get('/report', 'ReportController@index');
Router::get('/report/data', 'ReportController@data');
Router::get('/report/export', 'ReportController@export');
