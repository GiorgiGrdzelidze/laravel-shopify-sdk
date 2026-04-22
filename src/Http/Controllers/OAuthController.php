<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Http\Controllers;

use LaravelShopifySdk\Auth\OAuthManager;
use LaravelShopifySdk\Exceptions\OAuthException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OAuthController extends Controller
{
    public function __construct(
        protected OAuthManager $oauthManager
    ) {}

    /**
     * Handle OAuth installation request.
     */
    public function install(Request $request)
    {
        $shop = $request->input('shop');

        if (!$shop) {
            return response()->json(['error' => 'Missing shop parameter'], 400);
        }

        try {
            $state = Str::random(40);
            session(['shopify_oauth_state' => $state]);

            $authUrl = $this->oauthManager->getAuthorizationUrl($shop, $state);

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
     */
    public function callback(Request $request)
    {
        try {
            // Validate state parameter to prevent CSRF
            $expectedState = session()->pull('shopify_oauth_state');
            $receivedState = $request->input('state');

            if (!$expectedState || !$receivedState || !hash_equals($expectedState, $receivedState)) {
                throw new OAuthException('Invalid OAuth state parameter — possible CSRF attack');
            }

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
