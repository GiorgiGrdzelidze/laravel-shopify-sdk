<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Concerns;

use Filament\Panel;
use Illuminate\Support\Str;

/**
 * Prefixes every Shopify SDK resource slug with the configured value so the
 * SDK doesn't hijack common consumer routes like /admin/orders, /admin/products,
 * /admin/customers, etc. Default prefix is 'shopify' — set
 * `shopify.filament.slug_prefix` to null to fall back to bare slugs.
 *
 * Consumers using the SDK as their primary admin (no conflicting resources)
 * can set the prefix to null in config to get the shorter URLs back.
 */
trait HasShopifySlug
{
    public static function getSlug(?Panel $panel = null): string
    {
        $base = static::$slug ?? Str::kebab(Str::pluralStudly(class_basename(static::getModel())));

        $prefix = config('shopify.filament.slug_prefix');

        return $prefix ? "{$prefix}/{$base}" : $base;
    }
}
