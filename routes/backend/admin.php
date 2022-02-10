<?php

use App\Domains\Card\Http\Controllers\Backend\CardController;
use App\Domains\Marketplace\Http\Controllers\Backend\MarketplaceController;
use App\Http\Controllers\Backend\DashboardController;
use Tabuna\Breadcrumbs\Trail;

// All route names are prefixed with 'admin.'.
Route::redirect('/', '/admin/dashboard', 301);
Route::get('dashboard', [DashboardController::class, 'index'])
    ->name('dashboard')
    ->breadcrumbs(function (Trail $trail) {
        $trail->push(__('Home'), route('admin.dashboard'));
    });

Route::group([
    'prefix' => 'marketplace',
    'as' => 'marketplace.'
], function () {
    Route::get('/', [MarketplaceController::class, 'index'])->name('index');

    Route::get('{marketplace}', [MarketplaceController::class, 'show'])->name('show');
    Route::get('/offer/{offerTrades}', [MarketplaceController::class, 'tradeShow'])->name('offer');
    Route::post('/offer/withdraw', [MarketplaceController::class, 'tradeWithdraw'])->name('withdraw');
    Route::post('/offer/status', [MarketplaceController::class, 'offerStatus'])->name('offerstatus');
    Route::post('/bid/status', [MarketplaceController::class, 'bidStatus'])->name('bidstatus');
    Route::post('{marketplace}/reverse', [MarketplaceController::class, 'reverse'])->name('reverse');
    Route::post('dispute', [MarketplaceController::class, 'dispute'])->name('dispute');
});

Route::group([
    'prefix' => 'category',
    'as' => 'category.'
], function () {
    Route::get('/', [CardController::class, 'index'])->name('index');
    Route::post('create', [CardController::class, 'create'])->name('create');
    Route::get('show', [CardController::class, 'showCategory'])->name('showcategory');
    Route::post('edit/{category}', [CardController::class, 'editCategory'])->name('editcategory');
    Route::post('update', [CardController::class, 'updateCategory'])->name('updateCategory');
});

Route::group([
    'prefix' => 'brand',
    'as' => 'brand.'
], function () {
    Route::get('/', [CardController::class, 'addbrand'])->name('addbrand');
    Route::post('create', [CardController::class, 'createbrand'])->name('createbrand');
    Route::get('showbrand', [CardController::class, 'showbrand'])->name('showbrand');
    Route::any('edit/{brand}', [CardController::class, 'editBrand'])->name('editbrand');
    Route::post('update', [CardController::class, 'updateBrand'])->name('updatebrand');
    Route::any('delete/{brand}', [CardController::class, 'delete'])->name('delete');
}); 
