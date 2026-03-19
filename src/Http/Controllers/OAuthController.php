<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Http\Controllers;

use LaravelShopifySdk\Auth\OAuthManager;
use LaravelShopifySdk\Exceptions\OAuthException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

/**
 * OAuth Controller
 *
 * Handles OAuth installation and callback for Shopify apps.
 * Manages the Authorization Code Grant flow for multi-store installations.
 *
 * @package LaravelShopifySdk\Http\Controllers
 */
class OAuthController extends Controller
{
    public function __construct(
        protected OAuthManager $oauthManager
    ) {}

    /**
     * Handle OAuth installation request.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function install(Request $request)
    {
        $shop = $request->input('shop');

        if (!$shop) {
            return response()->json(['error' => 'Missing shop parameter'], 400);
        }

        try {
            $authUrl = $this->oauthManager->getAuthorizationUrl($shop);
            return redirect($authUrl);
        } catch (\Exception $e) {
            Log::error('OAuth install failed', [
                'shop' => $shop,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Installation failed'], 500);
        }
    }

    /**
     * Handle OAuth callback.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function callback(Request $request)
    {
        try {
            $store = $this->oauthManager->handleCallback($request->all());

            Log::info('OAuth callback successful', [
                'shop_domain' => $store->shop_domain,
                'store_id' => $store->id,
            ]);

            return redirect()->route('home')->with('success', 'Store connected successfully!');

        } catch (OAuthException $e) {
            Log::error('OAuth callback failed', [
                'shop' => $request->input('shop'),
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('home')->with('error', 'Authentication failed. Please try again.');
        }
    }
}
