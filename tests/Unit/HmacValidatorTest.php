<?php

namespace LaravelShopifySdk\Tests\Unit;

use LaravelShopifySdk\Auth\HmacValidator;
use LaravelShopifySdk\Tests\TestCase;

class HmacValidatorTest extends TestCase
{
    protected HmacValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new HmacValidator();
    }

    public function test_validates_correct_oauth_hmac(): void
    {
        $secret = 'test-secret';
        $params = [
            'code' => 'test-code',
            'shop' => 'test-shop.myshopify.com',
            'timestamp' => '1234567890',
            'hmac' => hash_hmac('sha256', 'code=test-code&shop=test-shop.myshopify.com&timestamp=1234567890', $secret),
        ];

        $result = $this->validator->validateOAuthCallback($params, $secret);

        $this->assertTrue($result);
    }

    public function test_rejects_invalid_oauth_hmac(): void
    {
        $secret = 'test-secret';
        $params = [
            'code' => 'test-code',
            'shop' => 'test-shop.myshopify.com',
            'timestamp' => '1234567890',
            'hmac' => 'invalid-hmac',
        ];

        $result = $this->validator->validateOAuthCallback($params, $secret);

        $this->assertFalse($result);
    }

    public function test_validates_correct_webhook_hmac(): void
    {
        $secret = 'test-secret';
        $data = '{"id":123,"name":"test"}';
        $hmac = base64_encode(hash_hmac('sha256', $data, $secret, true));

        $result = $this->validator->validateWebhook($data, $hmac, $secret);

        $this->assertTrue($result);
    }

    public function test_rejects_invalid_webhook_hmac(): void
    {
        $secret = 'test-secret';
        $data = '{"id":123,"name":"test"}';
        $hmac = 'invalid-hmac';

        $result = $this->validator->validateWebhook($data, $hmac, $secret);

        $this->assertFalse($result);
    }

    public function test_rejects_tampered_webhook_data(): void
    {
        $secret = 'test-secret';
        $originalData = '{"id":123,"name":"test"}';
        $tamperedData = '{"id":456,"name":"hacked"}';
        $hmac = base64_encode(hash_hmac('sha256', $originalData, $secret, true));

        $result = $this->validator->validateWebhook($tamperedData, $hmac, $secret);

        $this->assertFalse($result);
    }
}
