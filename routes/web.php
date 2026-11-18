<?php

use App\Http\Controllers\Api\HotspotController;
use App\Http\Controllers\Api\TourViewController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\TourViewerController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

// Landing page
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Guest & Auth Routes
Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Demo for guests (FR-20)
Route::get('/demo', [DemoController::class, 'index'])->name('demo');

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Profile & Workspaces (FR-11, FR-16)
    Route::get('/profile', [WorkspaceController::class, 'profile'])->name('profile');
    Route::post('/workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');
    Route::get('/workspaces/{workspace}/editor', [WorkspaceController::class, 'editor'])->name('workspaces.editor');
    Route::put('/workspaces/{workspace}', [WorkspaceController::class, 'update'])->name('workspaces.update');
    Route::delete('/workspaces/{workspace}', [WorkspaceController::class, 'destroy'])->name('workspaces.destroy');

    // API Routes for Editor
    Route::post('/api/workspaces/{workspace}/views', [TourViewController::class, 'store'])->name('api.views.store');
    Route::put('/api/views/{view}', [TourViewController::class, 'update'])->name('api.views.update');
    Route::post('/api/views/{view}/starting-point', [TourViewController::class, 'setStartingPoint'])->name('api.views.starting-point');
    Route::delete('/api/views/{view}', [TourViewController::class, 'destroy'])->name('api.views.destroy');

    Route::post('/api/views/{view}/hotspots', [HotspotController::class, 'store'])->name('api.hotspots.store');
    Route::delete('/api/hotspots/{hotspot}', [HotspotController::class, 'destroy'])->name('api.hotspots.destroy');
});

// Public Tour Sharing (FR-13 & Viewer FR-01)
// Format: /{username}/{workspace_slug}
Route::get('/{username}/{workspace_slug}', [TourViewerController::class, 'show'])->name('tour.show');
