<?php

namespace LaravelShopifySdk\Tests;

use LaravelShopifySdk\ShopifyServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        return [
            ShopifyServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Set encryption key for testing (required for encrypted fields)
        // Generate a proper random 32-byte key at runtime for AES-256-CBC
        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        config()->set('app.cipher', 'AES-256-CBC');

        // Database configuration
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Shopify package configuration
        config()->set('shopify.oauth.client_id', 'test-client-id');
        config()->set('shopify.oauth.client_secret', 'test-client-secret');
        config()->set('shopify.webhooks.secret', 'test-webhook-secret');

        // Minimize retry delays for tests
        config()->set('shopify.client.retry_delay', 1);
        config()->set('shopify.client.max_backoff', 1);
        config()->set('shopify.client.timeout', 5);
    }

    protected function defineRoutes($router): void
    {
        $router->get('/home', fn () => 'Home')->name('home');
    }
}
