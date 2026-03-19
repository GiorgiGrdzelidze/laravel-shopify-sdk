<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Auth;

/**
 * HMAC Validator
 *
 * Provides timing-safe HMAC validation for Shopify OAuth callbacks and webhooks.
 * Uses hash_equals() for constant-time comparison to prevent timing attacks.
 *
 * @package LaravelShopifySdk\Auth
 */
class HmacValidator
{
    /**
     * Validate HMAC signature for OAuth callback.
     *
     * @param array<string, mixed> $params Query parameters from callback
     * @param string $secret Client secret
     * @return bool
     */
    public function validateOAuthCallback(array $params, string $secret): bool
    {
        if (!isset($params['hmac'])) {
            return false;
        }

        $hmac = $params['hmac'];
        unset($params['hmac']);

        $queryString = $this->buildQueryString($params);
        $calculatedHmac = hash_hmac('sha256', $queryString, $secret);

        return hash_equals($calculatedHmac, $hmac);
    }

    /**
     * Validate HMAC signature for webhook.
     *
     * @param string $data Raw request body
     * @param string $hmacHeader HMAC from X-Shopify-Hmac-SHA256 header
     * @param string $secret Webhook secret
     * @return bool
     */
    public function validateWebhook(string $data, string $hmacHeader, string $secret): bool
    {
        $calculatedHmac = base64_encode(hash_hmac('sha256', $data, $secret, true));

        return hash_equals($calculatedHmac, $hmacHeader);
    }

    /**
     * Build query string from parameters for HMAC calculation.
     *
     * @param array<string, mixed> $params
     * @return string
     */
    protected function buildQueryString(array $params): string
    {
        ksort($params);

        $pairs = [];
        foreach ($params as $key => $value) {
            $pairs[] = $key . '=' . $value;
        }

        return implode('&', $pairs);
    }
}
