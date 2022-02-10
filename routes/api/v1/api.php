<?php

use App\Domains\Auth\Http\Controllers\API\AuthController;
use App\Domains\Auth\Http\Controllers\API\UserController;
use App\Domains\Card\Http\Controllers\API\CardsController;
use App\Domains\Marketplace\Http\Controllers\API\MarketplaceController;
use App\Domains\Marketplace\Http\Controllers\API\PaymentController;
use App\Domains\Activity\Http\Controllers\API\ActivityController;
use App\Domains\Auth\Models\User;

Route::post('register', [AuthController::class, 'register']);
Route::post('final-register', [AuthController::class, 'finalRegister']);
Route::post('login', [AuthController::class, 'login']);
Route::post('otp', [AuthController::class, 'otp']);
Route::post('verify-otp', [AuthController::class, 'verifyOTP']);

// Forgot password
Route::post('forgot-password/send-otp', [AuthController::class, 'forgotPasswordSendOtp']);
Route::post('forgot-password/verify-otp', [AuthController::class, 'forgotPasswordVerifyOtp']);
Route::post('forgot-password/update-password', [AuthController::class, 'forgotPasswordUpdate']);

// Forgot email


Route::group(['prefix' => 'categories'], function () {
    Route::get('', [CardsController::class, 'getCategories']);
    Route::post('brands', [CardsController::class, 'getmarketplaceBrandByCategory']);
});
Route::group(['middleware' => 'auth:api'], function () {
    Route::get('dashboard', [UserController::class, 'getMyDashBoard']);
    Route::get('profile', [UserController::class, 'getProfile']);
    Route::post('profile', [UserController::class, 'updateProfile']);
    Route::post('change-number', [UserController::class, 'changeNumber']);
    Route::post('password', [UserController::class, 'passwordVerify']);
    Route::post('password/verifyotp', [UserController::class, 'verifyOTp']);
    Route::post('change/password', [UserController::class, 'changePassword']);
    Route::post('resend-mobile-otp', [UserController::class, 'resendMobileOtp']);
    Route::post('resend-email-otp', [UserController::class, 'resendEmailOtp']);
});

Route::group(['prefix' => 'cards'], function () {
    Route::post('brands', [CardsController::class, 'getAllBrands']);

    Route::group(['middleware' => 'auth:api'], function () {
        Route::post('digitize', [CardsController::class, 'digitize']);
        Route::post('digitize/delete', [CardsController::class, 'deleteDigitize']);
        Route::get('get-digitize/{brand_id?}/{marketplace_id?}', [CardsController::class, 'getMyDigitizedCards']);
        // Route::get('get-digitize/{brand_id?}', [CardsController::class, 'getMyDigitizedCards']);
        // Route::get('get-digitize/{}', [CardsController::class, 'getMyDigitizedCards']);
        Route::post('mybrands', [CardsController::class, 'getMyDigitizedBrands']);
        Route::get('cardbyid/{card_id}', [CardsController::class, 'getDigitizedCardByCardID']);
        Route::post('gift', [MarketplaceController::class, 'giftCard']);
    });
});

Route::group(['prefix' => 'marketplace'], function () {
    Route::post('', [MarketplaceController::class, 'getMarketplaceByBrand']);
    Route::get('/detail/{marketplace}', [MarketplaceController::class, 'getMarketplaceCardById']);
    Route::get('/list/bids/{marketplace}', [MarketplaceController::class, 'getBids']);

    Route::group(['middleware' => 'auth:api'], function () {
        Route::post('sell/add', [MarketplaceController::class, 'CardAddInSellMarketplace']);
        Route::post('auction/add', [MarketplaceController::class, 'cardAddInAuctionMarketplace']);
        Route::post('trade/add', [MarketplaceController::class, 'cardAddInTradeMarketplace']);

        // update

        Route::post('sell/update', [MarketplaceController::class, 'CardUpdateInSellMarketplace']);
        Route::post('auction/update', [MarketplaceController::class, 'cardUpdateInAuctionMarketplace']);
        Route::post('trade/update', [MarketplaceController::class, 'cardUpdateInTradeMarketplace']);
        // get

        Route::post('purchase', [MarketplaceController::class, 'purchase']);
        Route::post('purchase-confirm', [MarketplaceController::class, 'purchaseConfirm']);
        Route::post('bid', [MarketplaceController::class, 'placeBid']);
        Route::post('bid/edit', [MarketplaceController::class, 'editPlacedBid']);
        Route::post('withdraw', [MarketplaceController::class, 'withdrawRequest']);
        Route::post('stripeWebhook', [MarketplaceController::class, 'stripeWebhook']);


        Route::post('winner', [MarketplaceController::class, 'selectBidWinner']);
        Route::post('tradestatus', [MarketplaceController::class, 'changeTradeOfferStatus']);
        Route::post('tradecard/accept', [MarketplaceController::class, 'acceptTradeCard']);
        Route::post('cancel', [MarketplaceController::class, 'removeCardFromMarktplace']);
        Route::post('status', [MarketplaceController::class, 'getMarketplaceStatus']);
    });
});



// Payment
Route::group(['middleware' => 'auth:api', 'prefix' => 'payment'], function () {
    Route::post('status', [PaymentController::class, 'acceptPayment']);
    Route::post('cash-withdraw/send-otp', [AuthController::class, 'withdrawCashSendOTP']);
    Route::post('cash-withdraw/verify-otp', [AuthController::class, 'withdrawVerifyOTP']);
    Route::post('link-token-plaid', [PaymentController::class, 'token_plaid']);
});

Route::group(['middleware' => 'auth:api', 'prefix' => 'activity'], function () {
    Route::get('{type?}', [ActivityController::class, 'myActivity']);
});

Route::post('stripe-webhook', [PaymentController::class, 'stripeWebhook']);

Route::post('transfer', [PaymentController::class, 'transfer']);
