<?php

namespace LaravelShopifySdk\Tests\Unit;

use LaravelShopifySdk\Exceptions\ShopifyApiException;
use LaravelShopifySdk\Tests\TestCase;

class ShopifyApiExceptionTest extends TestCase
{
    public function test_rate_limit_exception_has_correct_properties(): void
    {
        $exception = ShopifyApiException::rateLimitExceeded('test-store.myshopify.com', 30);

        $this->assertEquals('test-store.myshopify.com', $exception->getShopDomain());
        $this->assertEquals(ShopifyApiException::ERROR_RATE_LIMIT, $exception->getErrorType());
        $this->assertEquals(429, $exception->getHttpStatus());
        $this->assertEquals(429, $exception->getCode());
        $this->assertArrayHasKey('retry_after', $exception->getContext());
        $this->assertEquals(30, $exception->getContext()['retry_after']);
    }

    public function test_authentication_exception_has_correct_properties(): void
    {
        $exception = ShopifyApiException::authenticationFailed('test-store.myshopify.com', 'Invalid token');

        $this->assertEquals('test-store.myshopify.com', $exception->getShopDomain());
        $this->assertEquals(ShopifyApiException::ERROR_AUTHENTICATION, $exception->getErrorType());
        $this->assertEquals(401, $exception->getHttpStatus());
        $this->assertStringContainsString('Invalid token', $exception->getMessage());
    }

    public function test_graphql_exception_has_correct_properties(): void
    {
        $errors = [
            ['message' => 'Field not found'],
            ['message' => 'Invalid query'],
        ];

        $exception = ShopifyApiException::graphqlError('test-store.myshopify.com', $errors);

        $this->assertEquals('test-store.myshopify.com', $exception->getShopDomain());
        $this->assertEquals(ShopifyApiException::ERROR_GRAPHQL, $exception->getErrorType());
        $this->assertArrayHasKey('graphql_errors', $exception->getContext());
        $this->assertStringContainsString('Field not found', $exception->getMessage());
        $this->assertStringContainsString('Invalid query', $exception->getMessage());
    }

    public function test_server_error_exception_has_correct_properties(): void
    {
        $exception = ShopifyApiException::serverError('test-store.myshopify.com', 500, 'Internal Server Error');

        $this->assertEquals('test-store.myshopify.com', $exception->getShopDomain());
        $this->assertEquals(ShopifyApiException::ERROR_SERVER, $exception->getErrorType());
        $this->assertEquals(500, $exception->getHttpStatus());
        $this->assertArrayHasKey('response_body', $exception->getContext());
    }

    public function test_to_log_context_returns_structured_data(): void
    {
        $exception = ShopifyApiException::rateLimitExceeded('test-store.myshopify.com', 30);

        $context = $exception->toLogContext();

        $this->assertArrayHasKey('shop_domain', $context);
        $this->assertArrayHasKey('error_type', $context);
        $this->assertArrayHasKey('http_status', $context);
        $this->assertArrayHasKey('message', $context);
        $this->assertArrayHasKey('context', $context);
    }
}
