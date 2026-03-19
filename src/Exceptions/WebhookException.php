<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Exceptions;

use Exception;

/**
 * Webhook Exception
 *
 * Thrown when webhook verification or processing fails.
 * Includes HMAC validation failures and payload processing errors.
 *
 * @package LaravelShopifySdk\Exceptions
 */
class WebhookException extends Exception
{
}
