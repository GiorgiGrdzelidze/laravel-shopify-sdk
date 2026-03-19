<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Helpers;

class CurrencyHelper
{
    /**
     * Format amount with currency symbol in correct position.
     * Some currencies like GEL (₾) go after the amount, others like USD ($) go before.
     */
    public static function format(float|int|string $amount, string $currency = 'USD'): string
    {
        $amount = (float) $amount;
        $symbol = self::getSymbol($currency);
        $formatted = number_format($amount, 2);
        
        // Currencies where symbol goes after the amount
        $suffixCurrencies = ['GEL', 'PLN', 'CZK', 'SEK', 'NOK', 'DKK', 'HUF', 'RON', 'BGN', 'HRK', 'RSD', 'UAH', 'BYN'];
        
        if (in_array($currency, $suffixCurrencies)) {
            return $formatted . ' ' . $symbol;
        }
        
        return $symbol . $formatted;
    }

    /**
     * Get currency symbol for a currency code.
     */
    public static function getSymbol(string $currency): string
    {
        return match ($currency) {
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'GEL' => '₾',
            'JPY' => '¥',
            'CNY' => '¥',
            'RUB' => '₽',
            'INR' => '₹',
            'KRW' => '₩',
            'TRY' => '₺',
            'BRL' => 'R$',
            'CAD' => 'C$',
            'AUD' => 'A$',
            'PLN' => 'zł',
            'CZK' => 'Kč',
            'SEK' => 'kr',
            'NOK' => 'kr',
            'DKK' => 'kr',
            'HUF' => 'Ft',
            'CHF' => 'CHF',
            'UAH' => '₴',
            default => $currency,
        };
    }

    /**
     * Check if currency symbol should be placed after the amount.
     */
    public static function isSymbolSuffix(string $currency): bool
    {
        $suffixCurrencies = ['GEL', 'PLN', 'CZK', 'SEK', 'NOK', 'DKK', 'HUF', 'RON', 'BGN', 'HRK', 'RSD', 'UAH', 'BYN'];
        return in_array($currency, $suffixCurrencies);
    }
}
