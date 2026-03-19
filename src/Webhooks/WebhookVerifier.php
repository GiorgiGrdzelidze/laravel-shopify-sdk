<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Webhooks;

use LaravelShopifySdk\Auth\HmacValidator;
use LaravelShopifySdk\Exceptions\WebhookException;

/**
 * Webhook Verifier
 *
 * Verifies Shopify webhook signatures using HMAC-SHA256.
 * Ensures webhook authenticity before processing.
 *
 * @package LaravelShopifySdk\Webhooks
 */
class WebhookVerifier
{
    public function __construct(
        protected ?string $secret,
        protected HmacValidator $hmacValidator
    ) {}

    /**
     * Verify webhook signature.
     *
     * @param string $data Raw request body
     * @param string $hmacHeader HMAC from X-Shopify-Hmac-SHA256 header
     * @return bool
     * @throws WebhookException
     */
    public function verify(string $data, string $hmacHeader): bool
    {
        if (!$this->secret) {
            throw new WebhookException('Webhook secret not configured');
        }

        return $this->hmacValidator->validateWebhook($data, $hmacHeader, $this->secret);
    }

    /**
     * Verify webhook request.
     *
     * @param \Illuminate\Http\Request $request
     * @return bool
     * @throws WebhookException
     */
    public function verifyRequest($request): bool
    {
        $hmac = $request->header('X-Shopify-Hmac-SHA256');

        if (!$hmac) {
            throw new WebhookException('Missing HMAC header');
        }

        $data = $request->getContent();

        return $this->verify($data, $hmac);
    }
}
