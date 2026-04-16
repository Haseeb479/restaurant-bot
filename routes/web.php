<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\RestaurantController;
use Illuminate\Support\Facades\Route;

// ── Home redirect ──────────────────────────────────────────
Route::get('/', function () {
    return redirect('/admin/login');
});

// ── Restaurant self-service onboarding ───────────────────────
Route::get('restaurant/register', [RestaurantController::class, 'showRegistrationForm'])
    ->name('restaurant.register');
Route::post('restaurant/register', [RestaurantController::class, 'register']);

// ── Restaurant owner dashboard ─────────────────────────────
Route::prefix('dashboard/{id}')->group(function () {
    Route::get('login',                        [DashboardController::class, 'loginForm'])->name('dashboard.login');
    Route::post('login',                       [DashboardController::class, 'login']);
    Route::post('logout',                      [DashboardController::class, 'logout'])->name('dashboard.logout');
    Route::get('orders',                       [DashboardController::class, 'orders'])->name('dashboard.orders');
    Route::post('orders/{order}/status',       [DashboardController::class, 'updateStatus'])->name('dashboard.update-status');
    Route::get('menu',                         [DashboardController::class, 'menu'])->name('dashboard.menu');
    Route::post('menu/category',               [DashboardController::class, 'storeCategory'])->name('dashboard.store-category');
    Route::post('menu/item',                   [DashboardController::class, 'storeItem'])->name('dashboard.store-item');
    Route::post('menu/item/{item}/toggle',     [DashboardController::class, 'toggleItem'])->name('dashboard.toggle-item');
    Route::delete('menu/item/{item}',          [DashboardController::class, 'deleteItem'])->name('dashboard.delete-item');
    Route::get('settings',                     [DashboardController::class, 'settings'])->name('dashboard.settings');
    Route::post('settings',                    [DashboardController::class, 'updateSettings'])->name('dashboard.update-settings');
});

// ── Super admin panel ──────────────────────────────────────
Route::prefix('admin')->group(function () {
    Route::get('login',                        [AdminController::class, 'loginForm'])->name('admin.login');
    Route::post('login',                       [AdminController::class, 'login']);
    Route::post('logout',                      [AdminController::class, 'logout'])->name('admin.logout');
    Route::get('/',                            [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('restaurant/create',            [AdminController::class, 'createRestaurant'])->name('admin.create-restaurant');
    Route::post('restaurant',                  [AdminController::class, 'storeRestaurant'])->name('admin.store-restaurant');
    Route::post('restaurant/{r}/toggle',       [AdminController::class, 'toggleRestaurant'])->name('admin.toggle-restaurant');
    Route::post('restaurant/{r}/plan',         [AdminController::class, 'extendPlan'])->name('admin.extend-plan');
    Route::get('orders',                       [AdminController::class, 'allOrders'])->name('admin.orders');
});