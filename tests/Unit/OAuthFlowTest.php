<?php

namespace LaravelShopifySdk\Tests\Unit;

use LaravelShopifySdk\Auth\OAuthManager;
use LaravelShopifySdk\Exceptions\OAuthException;
use LaravelShopifySdk\Tests\TestCase;

class OAuthFlowTest extends TestCase
{
    protected OAuthManager $oauthManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->oauthManager = app(OAuthManager::class);
    }

    public function test_authorization_url_includes_state_param(): void
    {
        $url = $this->oauthManager->getAuthorizationUrl('test-shop.myshopify.com', 'random-state-123');

        $this->assertStringContainsString('state=random-state-123', $url);
        $this->assertStringContainsString('test-shop.myshopify.com', $url);
    }

    public function test_authorization_url_without_state(): void
    {
        $url = $this->oauthManager->getAuthorizationUrl('test-shop.myshopify.com');

        $this->assertStringNotContainsString('state=', $url);
        $this->assertStringContainsString('/admin/oauth/authorize', $url);
    }

    public function test_authorization_url_includes_required_params(): void
    {
        config()->set('shopify.oauth.client_id', 'my-client-id');
        config()->set('shopify.oauth.scopes', 'read_products,write_products');

        $url = $this->oauthManager->getAuthorizationUrl('test-shop.myshopify.com');

        $this->assertStringContainsString('client_id=my-client-id', $url);
        $this->assertStringContainsString('scope=read_products', $url);
        $this->assertStringContainsString('redirect_uri=', $url);
    }

    public function test_sanitize_strips_protocol(): void
    {
        $url = $this->oauthManager->getAuthorizationUrl('https://test-shop.myshopify.com');

        $this->assertStringContainsString('test-shop.myshopify.com', $url);
        $this->assertStringNotContainsString('https://', parse_url($url, PHP_URL_PATH));
    }

    public function test_sanitize_appends_myshopify_domain(): void
    {
        $url = $this->oauthManager->getAuthorizationUrl('test-shop');

        $this->assertStringContainsString('test-shop.myshopify.com', $url);
    }

    public function test_sanitize_rejects_invalid_domain(): void
    {
        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('Invalid shop domain');

        $this->oauthManager->getAuthorizationUrl('../../admin');
    }

    public function test_sanitize_rejects_domain_with_special_chars(): void
    {
        $this->expectException(OAuthException::class);

        $this->oauthManager->getAuthorizationUrl('shop<script>alert(1)</script>');
    }

    public function test_sanitize_accepts_valid_domain_with_hyphens(): void
    {
        $url = $this->oauthManager->getAuthorizationUrl('my-test-shop');

        $this->assertStringContainsString('my-test-shop.myshopify.com', $url);
    }

    public function test_sanitize_converts_to_lowercase(): void
    {
        $url = $this->oauthManager->getAuthorizationUrl('MY-SHOP.myshopify.com');

        $this->assertStringContainsString('my-shop.myshopify.com', $url);
    }

    public function test_callback_validation_rejects_missing_secret(): void
    {
        config()->set('shopify.oauth.client_secret', null);

        // Re-resolve to pick up new config
        $manager = app(OAuthManager::class);

        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('Client secret not configured');

        $manager->validateCallback(['shop' => 'test.myshopify.com', 'hmac' => 'fake']);
    }

    public function test_callback_validation_rejects_invalid_hmac(): void
    {
        $result = $this->oauthManager->validateCallback([
            'shop' => 'test.myshopify.com',
            'code' => 'abc123',
            'timestamp' => '1234567890',
            'hmac' => 'invalid-hmac-value',
        ]);

        $this->assertFalse($result);
    }

    public function test_oauth_install_endpoint_generates_state(): void
    {
        $response = $this->get('/shopify/install?shop=test-shop.myshopify.com');

        $response->assertRedirect();
        $this->assertTrue(session()->has('shopify_oauth_state'));
    }

    public function test_oauth_callback_rejects_missing_state(): void
    {
        // No state in session
        $response = $this->get('/shopify/callback?shop=test-shop.myshopify.com&code=abc&hmac=xyz&timestamp=123');

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_oauth_callback_rejects_mismatched_state(): void
    {
        session(['shopify_oauth_state' => 'expected-state']);

        $response = $this->get('/shopify/callback?shop=test-shop.myshopify.com&code=abc&hmac=xyz&timestamp=123&state=wrong-state');

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_oauth_install_rejects_missing_shop(): void
    {
        $response = $this->get('/shopify/install');

        $response->assertStatus(400);
    }
}
