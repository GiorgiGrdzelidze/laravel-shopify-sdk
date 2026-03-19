<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Exceptions;

use Exception;
use Throwable;

/**
 * Shopify API Exception
 *
 * Thrown when Shopify API requests fail (GraphQL or REST).
 * Includes rate limit errors, network failures, and API errors.
 *
 * @package LaravelShopifySdk\Exceptions
 */
class ShopifyApiException extends Exception
{
    public const ERROR_RATE_LIMIT = 'rate_limit';
    public const ERROR_AUTHENTICATION = 'authentication';
    public const ERROR_NOT_FOUND = 'not_found';
    public const ERROR_VALIDATION = 'validation';
    public const ERROR_SERVER = 'server_error';
    public const ERROR_NETWORK = 'network';
    public const ERROR_GRAPHQL = 'graphql';

    protected ?string $shopDomain = null;
    protected ?string $errorType = null;
    protected ?int $httpStatus = null;
    protected array $context = [];

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ?string $shopDomain = null,
        ?string $errorType = null,
        ?int $httpStatus = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->shopDomain = $shopDomain;
        $this->errorType = $errorType;
        $this->httpStatus = $httpStatus;
        $this->context = $context;
    }

    /**
     * Create a rate limit exception.
     */
    public static function rateLimitExceeded(string $shopDomain, int $retryAfter = 0): self
    {
        return new self(
            "Rate limit exceeded for {$shopDomain}. Retry after {$retryAfter} seconds.",
            429,
            null,
            $shopDomain,
            self::ERROR_RATE_LIMIT,
            429,
            ['retry_after' => $retryAfter]
        );
    }

    /**
     * Create an authentication exception.
     */
    public static function authenticationFailed(string $shopDomain, string $reason = ''): self
    {
        $message = "Authentication failed for {$shopDomain}";
        if ($reason) {
            $message .= ": {$reason}";
        }

        return new self(
            $message,
            401,
            null,
            $shopDomain,
            self::ERROR_AUTHENTICATION,
            401
        );
    }

    /**
     * Create a GraphQL error exception.
     */
    public static function graphqlError(string $shopDomain, array $errors): self
    {
        $errorMessages = collect($errors)->pluck('message')->implode('; ');

        return new self(
            "GraphQL errors for {$shopDomain}: {$errorMessages}",
            0,
            null,
            $shopDomain,
            self::ERROR_GRAPHQL,
            null,
            ['graphql_errors' => $errors]
        );
    }

    /**
     * Create a server error exception.
     */
    public static function serverError(string $shopDomain, int $httpStatus, string $body = ''): self
    {
        return new self(
            "Server error ({$httpStatus}) for {$shopDomain}",
            $httpStatus,
            null,
            $shopDomain,
            self::ERROR_SERVER,
            $httpStatus,
            ['response_body' => $body]
        );
    }

    /**
     * Get the shop domain associated with this error.
     */
    public function getShopDomain(): ?string
    {
        return $this->shopDomain;
    }

    /**
     * Get the error type.
     */
    public function getErrorType(): ?string
    {
        return $this->errorType;
    }

    /**
     * Get the HTTP status code.
     */
    public function getHttpStatus(): ?int
    {
        return $this->httpStatus;
    }

    /**
     * Get additional context.
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get structured log context for this exception.
     */
    public function toLogContext(): array
    {
        return array_filter([
            'shop_domain' => $this->shopDomain,
            'error_type' => $this->errorType,
            'http_status' => $this->httpStatus,
            'message' => $this->getMessage(),
            'context' => $this->context,
        ]);
    }
}
