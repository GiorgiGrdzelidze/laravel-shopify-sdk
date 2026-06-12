<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Concerns;

use Illuminate\Support\Str;

/**
 * Pulls every Filament resource label (nav, singular, plural) from the SDK's
 * translation files (`resources/lang/{locale}/shopify.php`) so consumer apps
 * can ship the resources in any locale without forking the SDK.
 *
 * Each resource declares `protected static ?string $resourceKey = 'order'`
 * which maps to the `shopify.resources.order.{singular|plural|nav}` keys.
 * If the key is unset, the trait falls back to a kebab-cased class name.
 */
trait HasShopifyLabels
{
    public static function getNavigationLabel(): string
    {
        return static::shopifyLabel('nav') ?? static::fallbackLabel(plural: true);
    }

    public static function getModelLabel(): string
    {
        return static::shopifyLabel('singular') ?? static::fallbackLabel(plural: false);
    }

    public static function getPluralModelLabel(): string
    {
        return static::shopifyLabel('plural') ?? static::fallbackLabel(plural: true);
    }

    public static function getTitleCaseModelLabel(): string
    {
        return static::getModelLabel();
    }

    public static function getTitleCasePluralModelLabel(): string
    {
        return static::getPluralModelLabel();
    }

    protected static function shopifyLabel(string $variant): ?string
    {
        $key = static::$resourceKey ?? null;
        if ($key === null) {
            return null;
        }

        $translation = trans("shopify::shopify.resources.{$key}.{$variant}");

        // trans() returns the key itself when missing — treat that as null.
        return $translation === "shopify::shopify.resources.{$key}.{$variant}"
            ? null
            : $translation;
    }

    protected static function fallbackLabel(bool $plural): string
    {
        $base = Str::headline(class_basename(static::getModel()));

        return $plural ? Str::plural($base) : $base;
    }
}
