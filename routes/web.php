<?php

use LaravelShopifySdk\Http\Controllers\OAuthController;
use LaravelShopifySdk\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

$prefix = config('shopify.routes.prefix', 'shopify');
$middleware = config('shopify.routes.middleware', ['web']);

Route::prefix($prefix)
    ->middleware($middleware)
    ->group(function () {
        Route::get(config('shopify.routes.install_path', '/install'), [OAuthController::class, 'install'])
            ->name('shopify.install');

        Route::get(config('shopify.routes.callback_path', '/callback'), [OAuthController::class, 'callback'])
            ->name('shopify.callback');
    });

Route::post($prefix . config('shopify.routes.webhook_path', '/webhooks'), [WebhookController::class, 'handle'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('shopify.webhooks');
